<?php

namespace App\Http\Controllers;

use App\Models\Mwc;
use App\Models\Ranting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Illuminate\Support\Facades\Auth;

class RantingController extends Controller
{
    /**
     * Menampilkan daftar Master Data Ranting NU
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Ranting::with('mwc')
            ->withCount('relawans')
            ->withSum(['transactions as total_collected' => function ($q) {
                $q->where('status', 'confirmed');
            }], 'amount');

        // Batasi Hak Akses Hirarki Wilayah
        if ($user && $user->isMwc()) {
            // MWC HANYA bisa melihat ranting di wilayah MWC-nya saja
            $query->where('mwc_id', $user->mwc_id);
            $mwcs = Mwc::where('id', $user->mwc_id)->get();
        } elseif ($user && $user->isRanting()) {
            // Ranting HANYA melihat ranting miliknya sendiri
            $query->where('id', $user->ranting_id);
            $mwcs = Mwc::where('id', $user->ranting?->mwc_id ?? 0)->get();
        } else {
            // PC Lazisnu / Guest: Dapat melihat seluruh ranting se-Cabang
            if ($request->filled('mwc_id')) {
                $query->where('mwc_id', (int) $request->input('mwc_id'));
            }
            $mwcs = Mwc::orderBy('code')->get();
        }

        // Pencarian Nama, Kode (M01R01), atau Desa
        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('desa', 'like', "%{$search}%");
            });
        }

        // Filter Status Program KOIN NU (Aktif vs Nonaktif)
        if ($request->filled('status')) {
            if ($request->input('status') === 'active') {
                $query->where('is_active', true);
            } elseif ($request->input('status') === 'inactive') {
                $query->where('is_active', false);
            }
        }

        // Hitung metrik ringkasan status program
        $baseStatQuery = clone $query;
        $totalRanting = (clone $baseStatQuery)->count();
        $activeRanting = (clone $baseStatQuery)->where('is_active', true)->count();
        $inactiveRanting = (clone $baseStatQuery)->where('is_active', false)->count();

        $rantings = $query->orderBy('code')->paginate(15)->withQueryString();

        return view('rantings.index', compact('rantings', 'mwcs', 'totalRanting', 'activeRanting', 'inactiveRanting'));
    }

    /**
     * Toggle status keikutsertaan ranting dalam program KOIN NU (Khusus Admin PC)
     */
    public function toggleStatus(Ranting $ranting): RedirectResponse
    {
        $user = Auth::user();
        if ($user && !$user->isPc()) {
            abort(403, 'Hanya Administrator PC Lazisnu yang berhak mengaktifkan atau menonaktifkan status program Ranting.');
        }

        $ranting->is_active = !$ranting->is_active;
        $ranting->save();

        $statusText = $ranting->is_active ? 'DIAKTIFKAN' : 'DINONAKTIFKAN';
        return back()->with('success', "Ranting {$ranting->code} ({$ranting->name}) berhasil {$statusText} dari keikutsertaan program KOIN NU.");
    }

    /**
     * Form pendaftaran Ranting NU baru
     */
    public function create(Request $request): View
    {
        $user = Auth::user();

        // Admin Ranting tidak boleh menambah master data Ranting
        if ($user && $user->isRanting()) {
            abort(403, 'Akses Ditolak: Admin Ranting tidak memiliki izin menambah data Ranting.');
        }

        if ($user && $user->isMwc()) {
            $mwcs = Mwc::where('id', $user->mwc_id)->get();
            $selectedMwcId = $user->mwc_id;
        } else {
            $mwcs = Mwc::orderBy('code')->get();
            $selectedMwcId = $request->input('mwc_id', $mwcs->first()?->id);
        }
        
        $previewCode = $selectedMwcId ? Ranting::generateCode($selectedMwcId) : '';

        return view('rantings.create', compact('mwcs', 'selectedMwcId', 'previewCode'));
    }

    /**
     * Endpoint AJAX untuk mendapatkan preview kode Ranting secara dinamis
     * Rumus: {KODE_MWC} + 'R' + {No_2_digit}
     * Contoh: M01 + 1 -> M01R01
     */
    public function getNextCode(Request $request): JsonResponse
    {
        $mwcId = $request->input('mwc_id');
        $number = $request->filled('number') ? (int) $request->input('number') : null;

        if (!$mwcId) {
            return response()->json(['code' => '']);
        }

        $code = Ranting::generateCode((int) $mwcId, $number);

        return response()->json([
            'code'   => $code,
            'status' => 'success',
        ]);
    }

    /**
     * Simpan Master Data Ranting baru ke database
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Admin Ranting tidak berwenang menambah Ranting baru
        if ($user && $user->isRanting()) {
            abort(403, 'Akses Ditolak: Admin Ranting tidak memiliki izin menambah data Ranting.');
        }

        $request->validate([
            'mwc_id'             => ['required', 'exists:mwcs,id'],
            'name'               => ['required', 'string', 'max:100'],
            'desa'               => ['nullable', 'string', 'max:100'],
            'pj_name'            => ['nullable', 'string', 'max:150'],
            'address'            => ['nullable', 'string', 'max:255'],
            'nomor_urut'         => ['nullable', 'integer', 'min:1'],
            // Susunan Pengurus Ranting & WhatsApp
            'nama_ketua_ranting' => ['nullable', 'string', 'max:150'],
            'wa_ketua_ranting'   => ['nullable', 'string', 'max:30'],
            'nama_ketua_upzis'   => ['nullable', 'string', 'max:150'],
            'wa_ketua_upzis'     => ['nullable', 'string', 'max:30'],
            'nama_bendahara'     => ['nullable', 'string', 'max:150'],
            'wa_bendahara'       => ['nullable', 'string', 'max:30'],
        ], [
            'mwc_id.required' => 'Pilih MWC Induk terlebih dahulu.',
            'name.required'   => 'Nama Ranting NU wajib diisi.',
        ]);

        if ($user && $user->isMwc()) {
            $mwcId = (int) $user->mwc_id; // Kunci ke wilayah MWC miliknya
        } else {
            $mwcId = (int) $request->input('mwc_id');
        }

        $nomorUrut = $request->filled('nomor_urut') ? (int) $request->input('nomor_urut') : null;

        // Susun kode Ranting sesuai aturan: {KODE_MWC}R{No 2 digit} (contoh: M01R01)
        if ($request->filled('code')) {
            $code = strtoupper(trim($request->input('code')));
        } else {
            $code = Ranting::generateCode($mwcId, $nomorUrut);
        }

        // Cek duplikasi kode
        if (Ranting::where('code', $code)->exists()) {
            return back()
                ->withInput()
                ->withErrors(['code' => "Kode Ranting {$code} sudah digunakan. Gunakan nomor urut lain."]);
        }

        $ranting = Ranting::create([
            'mwc_id'             => $mwcId,
            'code'               => $code,
            'name'               => $request->input('name'),
            'desa'               => $request->input('desa') ?? $request->input('name'),
            'pj_name'            => $request->input('pj_name'),
            'address'            => $request->input('address'),
            'nama_ketua_ranting' => $request->input('nama_ketua_ranting'),
            'wa_ketua_ranting'   => $request->input('wa_ketua_ranting'),
            'nama_ketua_upzis'   => $request->input('nama_ketua_upzis'),
            'wa_ketua_upzis'     => $request->input('wa_ketua_upzis'),
            'nama_bendahara'     => $request->input('nama_bendahara'),
            'wa_bendahara'       => $request->input('wa_bendahara'),
        ]);

        return redirect()
            ->route('rantings.index')
            ->with('success', "Ranting NU {$ranting->name} dengan Kode [{$ranting->code}] berhasil ditambahkan.");
    }

    /**
     * Detail Ranting dan daftar Relawannya
     */
    public function show(Ranting $ranting): View
    {
        $user = Auth::user();

        // MWC hanya boleh melihat detail Ranting di wilayah MWC-nya
        if ($user && $user->isMwc() && (int) $ranting->mwc_id !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Ranting ini di luar wilayah MWC Anda.');
        }

        // Ranting hanya boleh melihat detail Ranting miliknya
        if ($user && $user->isRanting() && (int) $ranting->id !== (int) $user->ranting_id) {
            abort(403, 'Akses Ditolak: Anda hanya berwenang melihat data Ranting Anda sendiri.');
        }

        $ranting->load(['mwc', 'relawans.transactions']);
        $totalCollected = $ranting->transactions()->where('status', 'confirmed')->sum('amount');

        return view('rantings.show', compact('ranting', 'totalCollected'));
    }

    /**
     * Form edit Ranting
     */
    public function edit(Ranting $ranting): View
    {
        $user = Auth::user();

        if ($user && $user->isRanting()) {
            abort(403, 'Akses Ditolak: Admin Ranting tidak berwenang mengedit master data Ranting.');
        }

        if ($user && $user->isMwc() && (int) $ranting->mwc_id !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengedit Ranting di luar wilayah MWC Anda.');
        }

        if ($user && $user->isMwc()) {
            $mwcs = Mwc::where('id', $user->mwc_id)->get();
        } else {
            $mwcs = Mwc::orderBy('code')->get();
        }

        return view('rantings.edit', compact('ranting', 'mwcs'));
    }

    /**
     * Perbarui data Ranting
     */
    public function update(Request $request, Ranting $ranting): RedirectResponse
    {
        $user = Auth::user();

        if ($user && $user->isRanting()) {
            abort(403, 'Akses Ditolak: Admin Ranting tidak berwenang mengedit master data Ranting.');
        }

        if ($user && $user->isMwc() && (int) $ranting->mwc_id !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Anda tidak dapat mengedit Ranting di luar wilayah MWC Anda.');
        }

        $validated = $request->validate([
            'mwc_id'             => ['required', 'exists:mwcs,id'],
            'code'               => ['required', 'string', 'max:20', 'unique:rantings,code,' . $ranting->id],
            'name'               => ['required', 'string', 'max:100'],
            'desa'               => ['nullable', 'string', 'max:100'],
            'pj_name'            => ['nullable', 'string', 'max:150'],
            'address'            => ['nullable', 'string', 'max:255'],
            // Susunan Pengurus Ranting & WhatsApp
            'nama_ketua_ranting' => ['nullable', 'string', 'max:150'],
            'wa_ketua_ranting'   => ['nullable', 'string', 'max:30'],
            'nama_ketua_upzis'   => ['nullable', 'string', 'max:150'],
            'wa_ketua_upzis'     => ['nullable', 'string', 'max:30'],
            'nama_bendahara'     => ['nullable', 'string', 'max:150'],
            'wa_bendahara'       => ['nullable', 'string', 'max:30'],
        ]);

        if ($user && $user->isMwc()) {
            $validated['mwc_id'] = $user->mwc_id;
        }

        $ranting->update($validated);

        return redirect()
            ->route('rantings.index')
            ->with('success', "Data Ranting {$ranting->name} ({$ranting->code}) berhasil diperbarui.");
    }

    /**
     * Hapus data Ranting
     */
    public function destroy(Ranting $ranting): RedirectResponse
    {
        $user = Auth::user();

        if ($user && $user->isRanting()) {
            abort(403, 'Akses Ditolak.');
        }

        if ($user && $user->isMwc() && (int) $ranting->mwc_id !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Ranting ini di luar wilayah MWC Anda.');
        }

        if ($ranting->relawans()->count() > 0) {
            return redirect()
                ->route('rantings.index')
                ->with('error', "Gagal menghapus: Ranting {$ranting->name} masih memiliki data Relawan aktif.");
        }

        $name = $ranting->name;
        $code = $ranting->code;
        $ranting->delete();

        return redirect()
            ->route('rantings.index')
            ->with('success', "Ranting {$name} [{$code}] berhasil dihapus.");
    }
}
