<?php

namespace App\Http\Controllers;

use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Pentasyarufan;
use App\Models\Penthong;
use App\Models\PilarProgram;
use App\Models\ProgramKerja;
use App\Models\Ranting;
use App\Services\PentasyarufanBalanceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class PentasyarufanController extends Controller
{
    protected PentasyarufanBalanceService $balanceService;

    public function __construct(PentasyarufanBalanceService $balanceService)
    {
        $this->balanceService = $balanceService;
    }

    /**
     * Tampilkan riwayat realisasi pentasyarufan (pengeluaran dana)
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Pentasyarufan::with(['penthong', 'programKerja', 'pilarProgram', 'mwc', 'ranting', 'user', 'spjVerifier'])
            ->latest('tanggal_penyaluran');

        // Batasan data sesuai hierarki
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

        // Filter Periode Penthong
        if ($request->filled('penthong_id')) {
            $query->where('penthong_id', (int) $request->input('penthong_id'));
        }

        // Filter Pilar
        if ($request->filled('pilar_id')) {
            $query->where('pilar_program_id', (int) $request->input('pilar_id'));
        }

        // Filter Tingkat
        if ($request->filled('tingkat')) {
            $query->where('tingkat', $request->input('tingkat'));
        }

        // Filter SPJ Status
        if ($request->filled('spj_status')) {
            $query->where('spj_status', $request->input('spj_status'));
        }

        // Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal_penyaluran', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal_penyaluran', '<=', $request->input('end_date'));
        }

        // Pencarian
        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('nomor_transaksi', 'like', "%{$search}%")
                  ->orWhere('penerima_manfaat', 'like', "%{$search}%")
                  ->orWhereHas('programKerja', fn($pk) => $pk->where('nama_kegiatan', 'like', "%{$search}%"));
            });
        }

        $pentasyarufans = $query->paginate(15)->withQueryString();
        $pilars = PilarProgram::where('is_active', true)->orderBy('sort_order')->get();
        $penthongs = Penthong::orderByDesc('tahun')->orderByDesc('bulan')->get();

        // Ringkasan statistik
        $totalNominalDisalurkan = (clone $query)->sum('nominal_pencairan');
        $totalPenerimaManfaat = (clone $query)->sum('jumlah_penerima');
        $countBelumSpj = (clone $query)->where('spj_status', 'belum_spj')->count();

        return view('pentasyarufan.realisasi.index', compact(
            'pentasyarufans',
            'pilars',
            'penthongs',
            'totalNominalDisalurkan',
            'totalPenerimaManfaat',
            'countBelumSpj'
        ));
    }

    /**
     * Endpoint API JSON untuk mengambil saldo real-time per pilar dari periode tertentu
     * dengan dukungan scoping per tingkatan (PC, MWC, Ranting)
     */
    public function getPeriodBalance(Request $request, Penthong $penthong): JsonResponse
    {
        $user = Auth::user();
        $prokerId  = $request->input('proker_id');
        $tingkat   = $request->input('tingkat');
        $mwcId     = $request->input('mwc_id') ? (int) $request->input('mwc_id') : null;
        $rantingId = $request->input('ranting_id') ? (int) $request->input('ranting_id') : null;

        if ($prokerId) {
            $proker = ProgramKerja::find($prokerId);
            if ($proker) {
                $tingkat   = $proker->tingkat;
                $mwcId     = $proker->mwc_id;
                $rantingId = $proker->ranting_id;
            }
        } elseif ($user && $user->isRanting()) {
            $tingkat   = 'ranting';
            $rantingId = $user->ranting_id;
            $mwcId     = $user->ranting?->mwc_id;
        } elseif ($user && $user->isMwc()) {
            if ($tingkat !== 'ranting') {
                $tingkat = 'mwc';
                $mwcId   = $user->mwc_id;
            } else {
                $mwcId = $user->mwc_id;
            }
        } else {
            $tingkat = $tingkat ?: 'pc';
        }

        $budget = $this->balanceService->calculatePeriodBudget($penthong, null, $rantingId, $mwcId, $tingkat);

        return response()->json([
            'success' => true,
            'data'    => $budget,
        ]);
    }

    /**
     * Form input realisasi pentasyarufan baru dengan perhitungan saldo spesifik per tingkatan
     */
    public function create(Request $request): View
    {
        $user = Auth::user();

        // 1. Ambil seluruh periode penthong (Urut terbaru)
        $penthongs = Penthong::orderByDesc('tahun')->orderByDesc('bulan')->get();

        // Default periode: yang dipilih di request, atau yang berstatus open, atau record pertama
        $selectedPenthongId = (int) ($request->input('penthong_id') 
            ?: ($penthongs->firstWhere('status', 'open')?->id ?? $penthongs->first()?->id));

        // Tentukan Scoping Wilayah Awal berdasarkan Proker / User Role
        $selectedProkerId = $request->input('proker_id');
        $tingkat   = $request->input('tingkat');
        $mwcId     = $request->input('mwc_id') ? (int) $request->input('mwc_id') : null;
        $rantingId = $request->input('ranting_id') ? (int) $request->input('ranting_id') : null;

        if ($selectedProkerId) {
            $proker = ProgramKerja::find($selectedProkerId);
            if ($proker) {
                $tingkat   = $proker->tingkat;
                $mwcId     = $proker->mwc_id;
                $rantingId = $proker->ranting_id;
            }
        } elseif ($user && $user->isRanting()) {
            $tingkat   = 'ranting';
            $rantingId = $user->ranting_id;
            $mwcId     = $user->ranting?->mwc_id;
        } elseif ($user && $user->isMwc()) {
            if ($tingkat !== 'ranting') {
                $tingkat = 'mwc';
                $mwcId   = $user->mwc_id;
            } else {
                $mwcId = $user->mwc_id;
            }
        } else {
            $tingkat = $tingkat ?: 'pc';
        }

        // 2. Ambil kalkulasi saldo & pagu 5 pilar untuk periode & tingkatan terpilih
        $periodBudget = $selectedPenthongId 
            ? $this->balanceService->calculatePeriodBudget($selectedPenthongId, null, $rantingId, $mwcId, $tingkat) 
            : null;

        // 3. Ambil Program Kerja yang berstatus 'disetujui' dan masih memiliki sisa anggaran
        $prokerQuery = ProgramKerja::with(['pilarProgram', 'mwc', 'ranting'])
            ->where('status', 'disetujui');

        if ($user && $user->isRanting()) {
            $prokerQuery->where('ranting_id', $user->ranting_id);
        } elseif ($user && $user->isMwc()) {
            $prokerQuery->where(function ($q) use ($user) {
                $q->where('mwc_id', $user->mwc_id)
                  ->orWhere(function ($sub) use ($user) {
                      $sub->where('tingkat', 'ranting')
                          ->whereHas('ranting', fn($r) => $r->where('mwc_id', $user->mwc_id));
                  });
            });
        }

        $approvedProkers = $prokerQuery->get()->filter(function ($proker) {
            return $proker->sisa_anggaran > 0;
        });

        $pilars = PilarProgram::where('is_active', true)->orderBy('sort_order')->get();

        // Data Master Wilayah untuk Pilihan Tingkatan
        $mwcs = ($user && $user->isPc()) ? Mwc::orderBy('name')->get() : collect();
        if ($user && $user->isMwc()) {
            $mwcs = Mwc::where('id', $user->mwc_id)->get();
        }

        $allRantings = Ranting::orderBy('name')->get(['id', 'name', 'code', 'mwc_id']);

        $rantings = collect();
        if ($user && $user->isRanting()) {
            $rantings = Ranting::where('id', $user->ranting_id)->get();
        } elseif ($user && $user->isMwc()) {
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        } elseif ($mwcId) {
            $rantings = Ranting::where('mwc_id', $mwcId)->orderBy('name')->get();
        } elseif ($user && $user->isPc()) {
            $rantings = $allRantings;
        }

        return view('pentasyarufan.realisasi.create', compact(
            'approvedProkers',
            'pilars',
            'penthongs',
            'selectedPenthongId',
            'periodBudget',
            'selectedProkerId',
            'tingkat',
            'mwcId',
            'rantingId',
            'mwcs',
            'rantings',
            'allRantings'
        ));
    }

    /**
     * Simpan Realisasi Pentasyarufan dengan Validasi Pagu Anggaran & Sisa RAB Proker
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'penthong_id'        => 'required|exists:penthongs,id',
            'program_kerja_id'   => 'required|exists:program_kerjas,id',
            'nominal_pencairan'  => 'required|numeric|min:1000',
            'tanggal_penyaluran' => 'required|date',
            'penerima_manfaat'   => 'required|string|max:255',
            'jumlah_penerima'    => 'required|integer|min:1',
            'keterangan'         => 'nullable|string',
            'bukti_penyaluran'   => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
        ], [
            'penthong_id.required'       => 'Pilih Sumber Dana (Periode Penthong) terlebih dahulu.',
            'penthong_id.exists'         => 'Periode Penthong yang dipilih tidak valid dalam sistem.',
            'program_kerja_id.required'  => 'Pilih Program Kerja yang telah disetujui.',
            'nominal_pencairan.required' => 'Nominal pencairan dana wajib diisi.',
            'penerima_manfaat.required'  => 'Nama atau kelompok penerima manfaat wajib diisi.',
        ]);

        $penthongId = (int) $request->input('penthong_id');
        $prokerId = (int) $request->input('program_kerja_id');
        $nominal = (float) $request->input('nominal_pencairan');

        // Proteksi Grandfathering: Tolak pencairan pada Periode Pentong yang sudah ditutup
        $selectedPenthong = Penthong::findOrFail($penthongId);
        if ($selectedPenthong->isClosed()) {
            throw ValidationException::withMessages([
                'penthong_id' => "Periode Pentong [{$selectedPenthong->nama_periode}] telah ditutup ('closed'). Rekalkulasi atau pencairan baru tidak diizinkan pada periode yang sudah terkunci demi menjaga integritas buku kas.",
            ]);
        }

        // =========================================================================
        // EKSEKUSI BUSINESS LOGIC & ACCOUNTING VALIDATION VIA SERVICE PATTERN:
        // 1. Status Proker Disetujui
        // 2. Nominal <= Sisa RAB Proker
        // 3. Isolasi Periode: Nominal <= Sisa Saldo Pilar pada Periode Penthong X
        //    (Total Bruto - Potongan Operasional = Dana Bersih Netto * Pct Pilar - Terpakai)
        // =========================================================================
        $validation = $this->balanceService->validateDisbursement($penthongId, $prokerId, $nominal);

        if (!$validation['valid']) {
            throw ValidationException::withMessages([
                'nominal_pencairan' => $validation['error'],
            ]);
        }

        $proker = $validation['data']['proker'];
        $periodData = $validation['data']['period_data'];

        // Upload bukti penyaluran jika dilampirkan
        $buktiPath = null;
        if ($request->hasFile('bukti_penyaluran')) {
            $file = $request->file('bukti_penyaluran');
            $fileName = 'bukti_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $buktiPath = $file->storeAs('pentasyarufan/bukti', $fileName, 'public');
        }

        // Generate Nomor Transaksi Unik: TSF-YYYYMM-XXXX
        $prefix = 'TSF-' . date('Ym') . '-';
        $latest = Pentasyarufan::where('nomor_transaksi', 'like', "{$prefix}%")
            ->orderBy('id', 'desc')
            ->first();
        $nextNum = 1;
        if ($latest && preg_match('/TSF-\d{6}-(\d+)/', $latest->nomor_transaksi, $matches)) {
            $nextNum = (int) $matches[1] + 1;
        }
        $nomorTransaksi = $prefix . str_pad($nextNum, 4, '0', STR_PAD_LEFT);

        DB::transaction(function () use ($nomorTransaksi, $penthongId, $periodData, $proker, $user, $nominal, $request, $buktiPath) {
            $pentasyarufan = Pentasyarufan::create([
                'nomor_transaksi'          => $nomorTransaksi,
                'penthong_id'              => $penthongId,
                'periode_pentong_id'       => $penthongId,
                'program_kerja_id'         => $proker->id,
                'pilar_program_id'         => $proker->pilar_program_id,
                'user_id'                  => $user->id,
                'tingkat'                  => $proker->tingkat,
                'mwc_id'                   => $proker->mwc_id,
                'ranting_id'               => $proker->ranting_id,
                'nominal_pencairan'        => $nominal,
                'potongan_operasional_pct' => $periodData['operasional_pct'],
                'tanggal_penyaluran'       => $request->input('tanggal_penyaluran'),
                'penerima_manfaat'         => $request->input('penerima_manfaat'),
                'jumlah_penerima'          => (int) $request->input('jumlah_penerima', 1),
                'mustahiq_id'              => $request->filled('mustahiq_id') ? (int) $request->input('mustahiq_id') : null,
                'keterangan'               => $request->input('keterangan'),
                'bukti_penyaluran'         => $buktiPath,
                'spj_status'               => 'belum_spj',
            ]);

            // Hubungkan relasi ke pivot pentasyarufan_mustahiq jika dipilih mustahiq spesifik
            if ($request->filled('mustahiq_id')) {
                $mustahiqId = (int) $request->input('mustahiq_id');
                $pentasyarufan->mustahiqs()->syncWithoutDetaching([
                    $mustahiqId => [
                        'nominal_bantuan' => $nominal,
                        'keterangan' => $request->input('keterangan') ?: 'Penyaluran Pentasyarufan',
                    ]
                ]);
            }

            // Jika seluruh anggaran proker telah terserap (sisa = 0), tandai selesai
            $freshProker = ProgramKerja::find($proker->id);
            if ($freshProker->sisa_anggaran <= 0) {
                $freshProker->update(['status' => 'selesai']);
            }
        });

        return redirect()->route('pentasyarufan.index')
            ->with('success', "Pencairan dana sebesar Rp " . number_format($nominal, 0, ',', '.') . 
                " dari [{$periodData['nama_periode']}] untuk kegiatan [{$proker->nama_kegiatan}] berhasil dicatat dengan No. Transaksi {$nomorTransaksi}. Silakan lengkapi dokumen SPJ.");
    }

    /**
     * Tampilkan detail transaksi pentasyarufan & berkas SPJ
     */
    public function show(Pentasyarufan $pentasyarufan): View
    {
        $pentasyarufan->load(['programKerja', 'pilarProgram', 'mwc', 'ranting', 'user', 'spjVerifier']);
        return view('pentasyarufan.realisasi.show', compact('pentasyarufan'));
    }

    /**
     * Upload dokumen / berkas SPJ
     */
    public function uploadSpj(Request $request, Pentasyarufan $pentasyarufan): RedirectResponse
    {
        $request->validate([
            'berkas_spj' => 'required|file|mimes:pdf,jpeg,png,jpg,zip|max:10240',
        ], [
            'berkas_spj.required' => 'Pilih file dokumen SPJ (PDF/Gambar/ZIP).',
            'berkas_spj.max'      => 'Ukuran file SPJ maksimal 10 MB.',
        ]);

        if ($request->hasFile('berkas_spj')) {
            // Hapus file lama jika ada
            if ($pentasyarufan->berkas_spj && Storage::disk('public')->exists($pentasyarufan->berkas_spj)) {
                Storage::disk('public')->delete($pentasyarufan->berkas_spj);
            }

            $file = $request->file('berkas_spj');
            $fileName = 'spj_' . $pentasyarufan->nomor_transaksi . '_' . time() . '.' . $file->getClientOriginalExtension();
            $path = $file->storeAs('pentasyarufan/spj', $fileName, 'public');

            $pentasyarufan->update([
                'berkas_spj' => $path,
                'spj_status' => 'menunggu_verifikasi',
            ]);
        }

        return redirect()->route('pentasyarufan.show', $pentasyarufan->id)
            ->with('success', 'Dokumen SPJ berhasil diunggah! Saat ini berstatus "Menunggu Verifikasi" oleh Bendahara/Admin PC.');
    }

    /**
     * Verifikasi Pertanggungjawaban (SPJ) oleh Bendahara / Super Admin
     */
    public function verifySpj(Request $request, Pentasyarufan $pentasyarufan): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isBendahara() && !$user->isKetua())) {
            abort(403, 'Akses Ditolak: Hanya Bendahara PC, Ketua, atau Super Admin yang berwenang memverifikasi SPJ.');
        }

        $request->validate([
            'status'    => 'required|in:diterima,ditolak',
            'spj_notes' => 'nullable|string',
        ]);

        $status = $request->input('status');
        $pentasyarufan->update([
            'spj_status'      => $status,
            'spj_verified_by' => $user->id,
            'spj_verified_at' => now(),
            'spj_notes'       => $request->input('spj_notes'),
        ]);

        $msg = $status === 'diterima'
            ? 'Dokumen SPJ telah diverifikasi dan DITERIMA (Sah).'
            : 'Dokumen SPJ DITOLAK / Membutuhkan Perbaikan revisi.';

        return redirect()->route('pentasyarufan.show', $pentasyarufan->id)->with('success', $msg);
    }

    /**
     * Halaman Laporan Pentasyarufan Resmi & Format Cetak (PDF/Excel)
     */
    public function laporan(Request $request): View
    {
        $query = Pentasyarufan::with(['penthong', 'programKerja', 'pilarProgram', 'mwc', 'ranting', 'user'])
            ->latest('tanggal_penyaluran');

        // Filter Periode Penthong
        if ($request->filled('penthong_id')) {
            $query->where('penthong_id', (int) $request->input('penthong_id'));
        }

        // Filter Tingkat
        if ($request->filled('tingkat')) {
            $query->where('tingkat', $request->input('tingkat'));
        }

        // Filter MWC
        if ($request->filled('mwc_id')) {
            $query->where('mwc_id', (int) $request->input('mwc_id'));
        }

        // Filter Pilar
        if ($request->filled('pilar_id')) {
            $query->where('pilar_program_id', (int) $request->input('pilar_id'));
        }

        // Filter Tanggal
        if ($request->filled('start_date')) {
            $query->whereDate('tanggal_penyaluran', '>=', $request->input('start_date'));
        }
        if ($request->filled('end_date')) {
            $query->whereDate('tanggal_penyaluran', '<=', $request->input('end_date'));
        }

        $isPrint = $request->boolean('print');
        $pentasyarufans = $isPrint ? $query->get() : $query->paginate(20)->withQueryString();

        $pilars = PilarProgram::where('is_active', true)->orderBy('sort_order')->get();
        $mwcs = Mwc::orderBy('code')->get();
        $penthongs = Penthong::orderByDesc('tahun')->orderByDesc('bulan')->get();

        // Rekapitulasi per pilar
        $totalNominal = (clone $query)->sum('nominal_pencairan');
        $totalPenerima = (clone $query)->sum('jumlah_penerima');

        $rekapPilar = [];
        foreach ($pilars as $p) {
            $rekapPilar[$p->id] = (clone $query)->where('pilar_program_id', $p->id)->sum('nominal_pencairan');
        }

        if ($isPrint) {
            return view('pentasyarufan.laporan_cetak', compact('pentasyarufans', 'pilars', 'penthongs', 'totalNominal', 'totalPenerima', 'rekapPilar'));
        }

        return view('pentasyarufan.laporan', compact(
            'pentasyarufans',
            'pilars',
            'penthongs',
            'mwcs',
            'totalNominal',
            'totalPenerima',
            'rekapPilar'
        ));
    }
}
