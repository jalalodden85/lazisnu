<?php

namespace App\Http\Controllers;

use App\Models\DistributionSetting;
use App\Models\KasMutasi;
use App\Models\KoinTransaction;
use App\Models\KontribusiStruktur;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class KontribusiStrukturController extends Controller
{
    /**
     * Riwayat setoran kontribusi bagi hasil ranting ke struktur atas
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = KontribusiStruktur::with(['penthong', 'ranting', 'mwc', 'user', 'verifier'])
            ->latest('tanggal_setor')
            ->latest('id');

        // Data Scoping
        if ($user && $user->isRanting()) {
            $query->where('ranting_id', $user->ranting_id);
        } elseif ($user && $user->isMwc()) {
            $query->where('mwc_id', $user->mwc_id);
        }

        // Filter Periode Pentong (Default ke Aktif jika ada)
        if ($request->filled('penthong_id')) {
            $query->forPeriode((int) $request->input('penthong_id'));
        }

        $kontribusis = $query->paginate(15)->withQueryString();
        $penthongs = Penthong::orderByDesc('tahun')->orderByDesc('bulan')->get();

        return view('pentasyarufan.kontribusi.index', compact('kontribusis', 'penthongs'));
    }

    /**
     * Form Input Pengeluaran Kontribusi ke tingkat MWC/PC/PW/PB
     */
    public function create(Request $request): View
    {
        $user = Auth::user();

        // 1. Ambil seluruh periode penthong (Urut terbaru)
        $penthongs = Penthong::orderByDesc('tahun')->orderByDesc('bulan')->get();

        // 2. Default Periode Pentong: yang dipilih via URL atau yang berstatus AKTIF (OPEN)
        $selectedPenthongId = (int) ($request->input('penthong_id') 
            ?: ($penthongs->firstWhere('status', 'aktif')?->id ?? $penthongs->first()?->id));

        $selectedPenthong = Penthong::find($selectedPenthongId);

        // 3. Resolusi Ranting
        $rantingId = null;
        $ranting = null;
        if ($user && $user->isRanting()) {
            $rantingId = $user->ranting_id;
            $ranting = $user->ranting;
        } elseif ($request->filled('ranting_id')) {
            $rantingId = (int) $request->input('ranting_id');
            $ranting = Ranting::find($rantingId);
        }

        // 4. Hitung Estimasi Akumulasi Koin Bruto Ranting pada Periode Pentong Terpilih
        $estimasiKoinBruto = 0;
        if ($selectedPenthongId && $rantingId) {
            $estimasiKoinBruto = (float) KoinTransaction::where('status', KoinTransaction::STATUS_CONFIRMED)
                ->where('ranting_id', $rantingId)
                ->where(function ($q) use ($selectedPenthongId, $selectedPenthong) {
                    $q->where('penthong_id', $selectedPenthongId);
                    if ($selectedPenthong && $selectedPenthong->start_date && $selectedPenthong->end_date) {
                        $q->orWhere(function ($sub) use ($selectedPenthong) {
                            $sub->whereNull('penthong_id')
                                ->whereBetween('collection_date', [
                                    $selectedPenthong->start_date->toDateString(),
                                    $selectedPenthong->end_date->toDateString()
                                ]);
                        });
                    }
                })
                ->sum('amount');
        }

        // 5. Pengaturan Persentase Distribusi Bagi Hasil (Grandfathering support)
        $setting = $selectedPenthong ? $selectedPenthong->getDistributionSetting() : DistributionSetting::getActive();
        $persentase = [
            'operasional' => (float) ($setting->operasional_pct ?? 20.00),
            'ranting'     => (float) ($setting->ranting_pct ?? 80.00),
            'mwc'         => (float) ($setting->mwc_pct ?? 13.00),
            'pc'          => (float) ($setting->pc_pct ?? 5.00),
            'pw'          => (float) ($setting->pw_pct ?? 1.50),
            'pb'          => (float) ($setting->pb_pct ?? 0.50),
        ];

        // Daftar Ranting jika diakses oleh admin MWC atau PC
        $allRantings = collect();
        if ($user && $user->isMwc()) {
            $allRantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        } elseif ($user && $user->isPc()) {
            $allRantings = Ranting::with('mwc')->orderBy('name')->get();
        }

        return view('pentasyarufan.kontribusi.create', compact(
            'penthongs',
            'selectedPenthongId',
            'selectedPenthong',
            'ranting',
            'rantingId',
            'allRantings',
            'estimasiKoinBruto',
            'persentase'
        ));
    }

    /**
     * Simpan transaksi kontribusi struktur dan mutasi kas BKU
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        $request->validate([
            'periode_pentong_id' => 'required|exists:penthongs,id',
            'ranting_id'         => 'required|exists:rantings,id',
            'tanggal_setor'      => 'required|date',
            'nominal_bruto'      => 'required|numeric|min:1000',
            'metode_penyaluran'  => 'required|in:tunai,transfer,qris',
            'nama_bank'          => 'nullable|string|max:100',
            'nomor_rekening'     => 'nullable|string|max:100',
            'bukti_transfer'     => 'nullable|file|mimes:jpeg,png,jpg,pdf|max:5120',
            'catatan'            => 'nullable|string|max:1000',
        ]);

        // Validasi Isolasi Ranting (Admin Ranting tidak boleh memanipulasi ranting_id)
        $rantingId = (int) $request->input('ranting_id');
        if ($user && $user->isRanting() && $user->ranting_id !== $rantingId) {
            abort(403, 'Akses Ditolak: Anda hanya berhak menginput kontribusi untuk ranting Anda sendiri.');
        }

        $ranting = Ranting::findOrFail($rantingId);
        $mwcId = $ranting->mwc_id;

        $penthongId = (int) $request->input('periode_pentong_id');
        $nominalBruto = (float) $request->input('nominal_bruto');

        // Proteksi Grandfathering: Cegah input setoran pada Periode Pentong yang sudah ditutup
        $penthong = Penthong::findOrFail($penthongId);
        if ($penthong->isClosed()) {
            return back()->withInput()->with('error', "Periode Pentong [{$penthong->nama_periode}] sudah ditutup ('closed'). Tidak dapat menginput setoran kontribusi pada periode yang telah terkunci.");
        }

        // Ambil persentase acuan sesuai periode penthong
        $setting = $penthong->getDistributionSetting();
        $opsPct = (float) ($setting->operasional_pct ?? 20.00);
        $mwcPct = (float) ($setting->mwc_pct ?? 13.00);
        $pcPct  = (float) ($setting->pc_pct ?? 5.00);
        $pwPct  = (float) ($setting->pw_pct ?? 1.50);
        $pbPct  = (float) ($setting->pb_pct ?? 0.50);

        // Kalkulasi Keuangan
        $nominalOperasional = round($nominalBruto * ($opsPct / 100), 2);
        $nominalNetto = max(0, $nominalBruto - $nominalOperasional);

        // Alokasi porsi struktur atas dari dana netto
        $nominalMwc = round($nominalNetto * ($mwcPct / 100), 2);
        $nominalPc  = round($nominalNetto * ($pcPct / 100), 2);
        $nominalPw  = round($nominalNetto * ($pwPct / 100), 2);
        $nominalPb  = round($nominalNetto * ($pbPct / 100), 2);

        // Total yang disetor ke struktur atas
        $totalDisetor = $nominalMwc + $nominalPc + $nominalPw + $nominalPb;

        // Upload bukti transfer
        $buktiPath = null;
        if ($request->hasFile('bukti_transfer')) {
            $buktiPath = $request->file('bukti_transfer')->store('kontribusi_bukti', 'public');
        }

        DB::beginTransaction();
        try {
            $nomorTransaksi = KontribusiStruktur::generateNomorTransaksi($rantingId);

            // 1. Simpan Transaksi Kontribusi Struktur
            $kontribusi = KontribusiStruktur::create([
                'nomor_transaksi'        => $nomorTransaksi,
                'periode_pentong_id'     => $penthongId,
                'penthong_id'            => $penthongId,
                'ranting_id'             => $rantingId,
                'mwc_id'                 => $mwcId,
                'user_id'                => $user ? $user->id : 1,
                'tanggal_setor'          => $request->input('tanggal_setor'),
                'nominal_bruto'          => $nominalBruto,
                'persentase_operasional' => $opsPct,
                'nominal_operasional'    => $nominalOperasional,
                'nominal_netto'          => $nominalNetto,
                'nominal_mwc'            => $nominalMwc,
                'nominal_pc'             => $nominalPc,
                'nominal_pw'             => $nominalPw,
                'nominal_pb'             => $nominalPb,
                'total_disetor'          => $totalDisetor,
                'persentase_setting_json'=> [
                    'operasional' => $opsPct,
                    'mwc'         => $mwcPct,
                    'pc'          => $pcPct,
                    'pw'          => $pwPct,
                    'pb'          => $pbPct,
                ],
                'tujuan_struktur'        => 'semua',
                'metode_penyaluran'      => $request->input('metode_penyaluran'),
                'nama_bank'              => $request->input('nama_bank'),
                'nomor_rekening'         => $request->input('nomor_rekening'),
                'bukti_transfer'         => $buktiPath,
                'catatan'                => $request->input('catatan'),
                'status'                 => 'disetor',
            ]);

            // 2. Catat Mutasi Kas BKU (Kredit / Pengeluaran Ranting)
            KasMutasi::create([
                'nomor_bukti'        => $nomorTransaksi,
                'periode_pentong_id' => $penthongId,
                'penthong_id'        => $penthongId,
                'jenis'              => 'kredit',
                'kode_transaksi'     => 'NU-KONTRIBUSI',
                'uraian'             => "Setoran Kontribusi Koin ke MWC/PC/PW/PB - {$nomorTransaksi}",
                'nominal'            => $totalDisetor,
                'tanggal_transaksi'  => $request->input('tanggal_setor'),
                'tingkat'            => 'ranting',
                'ranting_id'         => $rantingId,
                'mwc_id'             => $mwcId,
                'user_id'            => $user ? $user->id : 1,
                'source_type'        => KontribusiStruktur::class,
                'source_id'          => $kontribusi->id,
                'keterangan'         => "Porsi: MWC Rp" . number_format($nominalMwc, 0, ',', '.') . " | PC Rp" . number_format($nominalPc, 0, ',', '.') . " | PW Rp" . number_format($nominalPw, 0, ',', '.') . " | PB Rp" . number_format($nominalPb, 0, ',', '.'),
            ]);

            DB::commit();

            return redirect()->route('bku.index', [
                'penthong_id' => $penthongId,
                'ranting_id'  => $rantingId,
            ])->with('success', "Setoran Kontribusi Struktur sebesar Rp " . number_format($totalDisetor, 0, ',', '.') . " berhasil dicatat dan dibukukan ke kolom PENGELUARAN BKU Ranting ({$nomorTransaksi}).");

        } catch (\Throwable $e) {
            DB::rollBack();
            if ($buktiPath && Storage::disk('public')->exists($buktiPath)) {
                Storage::disk('public')->delete($buktiPath);
            }
            return back()->withInput()->with('error', 'Gagal mencatat kontribusi: ' . $e->getMessage());
        }
    }

    /**
     * Tampilkan detail bukti setoran kontribusi
     */
    public function show(KontribusiStruktur $kontribusi): View
    {
        $kontribusi->load(['penthong', 'ranting', 'mwc', 'user', 'verifier']);
        return view('pentasyarufan.kontribusi.show', compact('kontribusi'));
    }
}
