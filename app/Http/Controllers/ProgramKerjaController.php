<?php

namespace App\Http\Controllers;

use App\Models\Mwc;
use App\Models\PilarProgram;
use App\Models\ProgramKerja;
use App\Models\Ranting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ProgramKerjaController extends Controller
{
    /**
     * Tampilkan daftar Program Kerja (RAB) berjenjang (Ranting, MWC, PC)
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = ProgramKerja::with(['pilarProgram', 'mwc', 'ranting', 'user', 'approver'])
            ->latest('id');

        // Batasan data sesuai wilayah naungan
        if ($user && $user->isRanting()) {
            $query->where('ranting_id', $user->ranting_id);
        } elseif ($user && $user->isMwc()) {
            $query->where(function ($q) use ($user) {
                $q->where('mwc_id', $user->mwc_id)
                  ->orWhere(function ($sub) use ($user) {
                      $sub->where('tingkat', 'ranting')
                          ->whereHas('ranting', fn($r) => $r->where('mwc_id', $user->mwc_id));
                  });
            });
        }

        // Filter Tingkat
        if ($request->filled('tingkat')) {
            $query->where('tingkat', $request->input('tingkat'));
        }

        // Filter Pilar Program
        if ($request->filled('pilar_id')) {
            $query->where('pilar_program_id', (int) $request->input('pilar_id'));
        }

        // Filter Status
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter Tahun
        if ($request->filled('tahun')) {
            $query->where('tahun_anggaran', (int) $request->input('tahun'));
        }

        // Search
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nama_kegiatan', 'like', "%{$search}%")
                  ->orWhere('nomor_proker', 'like', "%{$search}%")
                  ->orWhere('target_penerima_manfaat', 'like', "%{$search}%");
            });
        }

        $prokers = $query->paginate(15)->withQueryString();
        $pilars = PilarProgram::where('is_active', true)->orderBy('sort_order')->get();

        // Kartu statistik ringkasan
        $countDraft = (clone $query)->where('status', 'draft')->count();
        $countMenunggu = (clone $query)->where('status', 'menunggu_persetujuan')->count();
        $countDisetujui = (clone $query)->where('status', 'disetujui')->count();
        $totalRencanaAnggaran = (clone $query)->sum('rencana_anggaran');

        return view('pentasyarufan.proker.index', compact(
            'prokers',
            'pilars',
            'countDraft',
            'countMenunggu',
            'countDisetujui',
            'totalRencanaAnggaran'
        ));
    }

    /**
     * Form input usulan Program Kerja baru
     */
    public function create(): View
    {
        $user = Auth::user();
        $pilars = PilarProgram::where('is_active', true)->orderBy('sort_order')->get();
        $mwcs = Mwc::orderBy('code')->get();

        // Otomatis tentukan tingkat berdasarkan user
        $defaultTingkat = 'pc';
        if ($user && $user->isRanting()) {
            $defaultTingkat = 'ranting';
        } elseif ($user && $user->isMwc()) {
            $defaultTingkat = 'mwc';
        }

        return view('pentasyarufan.proker.create', compact('pilars', 'mwcs', 'defaultTingkat'));
    }

    /**
     * Simpan Program Kerja baru ke database
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $validated = $request->validate([
            'nama_kegiatan'           => 'required|string|max:255',
            'pilar_program_id'        => 'required|exists:pilar_programs,id',
            'rencana_anggaran'        => 'required|numeric|min:10000',
            'tahun_anggaran'          => 'required|integer|min:2020|max:2035',
            'target_penerima_manfaat' => 'nullable|string|max:255',
            'tanggal_mulai'           => 'nullable|date',
            'tanggal_selesai'         => 'nullable|date|after_or_equal:tanggal_mulai',
            'deskripsi_kegiatan'      => 'nullable|string',
            'tingkat'                 => 'required|in:pc,mwc,ranting',
            'mwc_id'                  => 'nullable|exists:mwcs,id',
            'ranting_id'              => 'nullable|exists:rantings,id',
        ], [
            'nama_kegiatan.required'    => 'Nama program kerja / kegiatan wajib diisi.',
            'pilar_program_id.required' => 'Pilih salah satu dari 5 Pilar Program NU Care.',
            'rencana_anggaran.min'      => 'Rencana anggaran minimal Rp 10.000.',
        ]);

        // Tetapkan wilayah sesuai user jika bukan super admin
        $tingkat = $validated['tingkat'];
        $mwcId = $validated['mwc_id'] ?? null;
        $rantingId = $validated['ranting_id'] ?? null;

        if ($user && $user->isRanting()) {
            $tingkat = 'ranting';
            $rantingId = $user->ranting_id;
            $mwcId = $user->ranting?->mwc_id;
        } elseif ($user && $user->isMwc()) {
            $tingkat = 'mwc';
            $mwcId = $user->mwc_id;
            $rantingId = null;
        }

        // Generate nomor proker unik: PRK-YYYYMM-XXXX
        $prefix = 'PRK-' . date('Ym') . '-';
        $latest = ProgramKerja::where('nomor_proker', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();
        $nextNumber = 1;
        if ($latest && preg_match('/PRK-\d{6}-(\d+)/', $latest->nomor_proker, $matches)) {
            $nextNumber = (int) $matches[1] + 1;
        }
        $nomorProker = $prefix . str_pad($nextNumber, 4, '0', STR_PAD_LEFT);

        $proker = ProgramKerja::create([
            'nomor_proker'            => $nomorProker,
            'nama_kegiatan'           => $validated['nama_kegiatan'],
            'pilar_program_id'        => (int) $validated['pilar_program_id'],
            'tingkat'                 => $tingkat,
            'mwc_id'                  => $mwcId,
            'ranting_id'              => $rantingId,
            'user_id'                 => $user->id,
            'tahun_anggaran'          => (int) $validated['tahun_anggaran'],
            'rencana_anggaran'        => (float) $validated['rencana_anggaran'],
            'deskripsi_kegiatan'      => $validated['deskripsi_kegiatan'] ?? null,
            'target_penerima_manfaat' => $validated['target_penerima_manfaat'] ?? null,
            'tanggal_mulai'           => $validated['tanggal_mulai'] ?? null,
            'tanggal_selesai'         => $validated['tanggal_selesai'] ?? null,
            'status'                  => 'draft',
        ]);

        return redirect()->route('proker.show', $proker->id)
            ->with('success', "Program Kerja [{$proker->nama_kegiatan}] berhasil dibuat dalam status Draft! Anda dapat memeriksanya sebelum mengajukan persetujuan.");
    }

    /**
     * Tampilkan detail Program Kerja beserta riwayat pencairan dana
     */
    public function show(ProgramKerja $proker): View
    {
        $proker->load(['pilarProgram', 'mwc', 'ranting', 'user', 'approver', 'pentasyarufans.user', 'pentasyarufans.spjVerifier']);
        return view('pentasyarufan.proker.show', compact('proker'));
    }

    /**
     * Form edit Program Kerja (hanya draft atau ditolak)
     */
    public function edit(ProgramKerja $proker): View|RedirectResponse
    {
        if (!in_array($proker->status, ['draft', 'ditolak'])) {
            return redirect()->route('proker.show', $proker->id)
                ->with('error', 'Program Kerja yang sedang menunggu persetujuan atau telah disetujui tidak dapat diubah.');
        }

        $pilars = PilarProgram::where('is_active', true)->orderBy('sort_order')->get();
        return view('pentasyarufan.proker.edit', compact('proker', 'pilars'));
    }

    /**
     * Simpan perubahan Program Kerja
     */
    public function update(Request $request, ProgramKerja $proker): RedirectResponse
    {
        if (!in_array($proker->status, ['draft', 'ditolak'])) {
            return redirect()->route('proker.show', $proker->id)
                ->with('error', 'Program Kerja ini tidak dalam status yang dapat diedit.');
        }

        $validated = $request->validate([
            'nama_kegiatan'           => 'required|string|max:255',
            'pilar_program_id'        => 'required|exists:pilar_programs,id',
            'rencana_anggaran'        => 'required|numeric|min:10000',
            'tahun_anggaran'          => 'required|integer|min:2020|max:2035',
            'target_penerima_manfaat' => 'nullable|string|max:255',
            'tanggal_mulai'           => 'nullable|date',
            'tanggal_selesai'         => 'nullable|date|after_or_equal:tanggal_mulai',
            'deskripsi_kegiatan'      => 'nullable|string',
        ]);

        $proker->update($validated);

        return redirect()->route('proker.show', $proker->id)
            ->with('success', 'Data Program Kerja berhasil diperbarui!');
    }

    /**
     * Ajukan Program Kerja untuk mendapatkan persetujuan (Draft -> Menunggu Persetujuan)
     */
    public function submit(ProgramKerja $proker): RedirectResponse
    {
        if (!in_array($proker->status, ['draft', 'ditolak'])) {
            return back()->with('error', 'Status Program Kerja saat ini tidak valid untuk diajukan.');
        }

        $proker->update([
            'status' => 'menunggu_persetujuan',
        ]);

        return redirect()->route('proker.show', $proker->id)
            ->with('success', "Program Kerja [{$proker->nama_kegiatan}] berhasil diajukan untuk mendapatkan persetujuan pejabat berwenang.");
    }

    /**
     * Approval Flow: Setujui Program Kerja
     * Wewenang:
     * - Proker Ranting: disetujui oleh Admin MWC atau Ketua/Super Admin PC
     * - Proker MWC & PC: disetujui oleh Ketua LAZISNU atau Super Admin PC
     */
    public function approve(Request $request, ProgramKerja $proker): RedirectResponse
    {
        $user = Auth::user();

        // Validasi Wewenang Approval
        $canApprove = false;
        if ($user && ($user->isSuperAdmin() || $user->isKetua())) {
            $canApprove = true;
        } elseif ($user && $user->isMwc() && $proker->tingkat === 'ranting') {
            // MWC menyetujui Proker Ranting di wilayahnya
            if ((int) $proker->ranting?->mwc_id === (int) $user->mwc_id) {
                $canApprove = true;
            }
        }

        if (!$canApprove) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menyetujui Program Kerja ini.');
        }

        $proker->update([
            'status'           => 'disetujui',
            'approved_by'      => $user->id,
            'approved_at'      => now(),
            'catatan_approval' => $request->input('catatan_approval', 'Program Kerja disetujui sesuai rencana anggaran.'),
        ]);

        return redirect()->route('proker.show', $proker->id)
            ->with('success', "Program Kerja [{$proker->nomor_proker}] resmi DISETUJUI! Realisasi dana pentasyarufan kini dapat dicairkan.");
    }

    /**
     * Approval Flow: Tolak Program Kerja (dengan catatan revisi/alasan)
     */
    public function reject(Request $request, ProgramKerja $proker): RedirectResponse
    {
        $user = Auth::user();

        $canReject = false;
        if ($user && ($user->isSuperAdmin() || $user->isKetua())) {
            $canReject = true;
        } elseif ($user && $user->isMwc() && $proker->tingkat === 'ranting') {
            if ((int) $proker->ranting?->mwc_id === (int) $user->mwc_id) {
                $canReject = true;
            }
        }

        if (!$canReject) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menolak Program Kerja ini.');
        }

        $request->validate([
            'catatan_approval' => 'required|string|min:5',
        ], [
            'catatan_approval.required' => 'Wajib mencantumkan alasan penolakan atau catatan revisi.',
        ]);

        $proker->update([
            'status'           => 'ditolak',
            'approved_by'      => $user->id,
            'approved_at'      => now(),
            'catatan_approval' => $request->input('catatan_approval'),
        ]);

        return redirect()->route('proker.show', $proker->id)
            ->with('warning', "Program Kerja [{$proker->nomor_proker}] telah DITOLAK dengan catatan revisi.");
    }

    /**
     * Hapus Program Kerja (hanya bila belum ada riwayat pentasyarufan)
     */
    public function destroy(ProgramKerja $proker): RedirectResponse
    {
        if ($proker->pentasyarufans()->exists()) {
            return back()->with('error', 'Program Kerja tidak dapat dihapus karena telah memiliki riwayat pencairan dana pentasyarufan.');
        }

        $proker->delete();

        return redirect()->route('proker.index')
            ->with('success', 'Program Kerja berhasil dihapus.');
    }
}
