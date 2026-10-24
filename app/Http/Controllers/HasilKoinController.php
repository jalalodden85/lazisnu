<?php

namespace App\Http\Controllers;

use App\Models\KasMutasi;
use App\Models\KoinTransaction;
use App\Models\KontribusiStruktur;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\PenthongDistribution;
use App\Models\Ranting;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class HasilKoinController extends Controller
{
    /**
     * Persentase Skema Direct Split Simplifikasi Tahap Awal (Total 100.0%)
     * Bypass potongan operasional/amil 20% di awal.
     */
    public const PCT_RANTING = 80.0;
    public const PCT_MWC     = 13.0;
    public const PCT_PC      = 5.0;
    public const PCT_PW      = 1.5;
    public const PCT_PB      = 0.5;

    /**
     * Tampilkan Halaman "Hasil Koin" (Simulasi & Rincian Pembagian Koin Bruto)
     */
    public function index(Request $request): View
    {
        $user = Auth::user();

        // 1. Ambil Periode Pentong Aktif (atau spesifik dari filter penthong_id)
        $penthongId = $request->input('penthong_id');
        if ($penthongId) {
            $currentPenthong = Penthong::find($penthongId);
        } else {
            $currentPenthong = Penthong::aktif()->first() ?? Penthong::latest('id')->first();
        }

        // Daftar semua periode untuk filter
        $penthongs = Penthong::orderByDesc('tahun')->orderByDesc('bulan')->get();

        if (!$currentPenthong) {
            return view('pentasyarufan.hasil_koin', [
                'currentPenthong'  => null,
                'penthongs'        => $penthongs,
                'totalKoinBruto'   => 0,
                'summary'          => $this->emptySummary(),
                'rantingBreakdown' => collect(),
                'isProcessed'      => false,
            ]);
        }

        // 2. Query Transaksi Koin Terkonfirmasi pada Periode Ini
        $transactionsQuery = KoinTransaction::with(['ranting', 'mwc'])
            ->where('status', KoinTransaction::STATUS_CONFIRMED)
            ->where(function ($q) use ($currentPenthong) {
                $q->where('penthong_id', $currentPenthong->id)
                  ->orWhere('periode_pentong_id', $currentPenthong->id);
            });

        // Data Scoping berdasarkan user yang login
        if ($user && $user->isRanting()) {
            $transactionsQuery->where('ranting_id', $user->ranting_id);
        } elseif ($user && $user->isMwc()) {
            $transactionsQuery->where('mwc_id', $user->mwc_id);
        }

        $transactions = $transactionsQuery->get();

        // 3. Kalkulasi Total Koin Bruto Keseluruhan
        $totalKoinBruto = (float) $transactions->sum('amount');

        // 4. Simulasi Pembagian Direct Split per Ranting
        $rantingBreakdown = $transactions->groupBy('ranting_id')->map(function ($txs, $rantingId) {
            $first = $txs->first();
            $gross = (float) $txs->sum('amount');

            $split = self::calculateDirectSplit($gross);

            return [
                'ranting_id'      => $rantingId,
                'ranting_name'    => $first->ranting?->name ?? "Ranting #{$rantingId}",
                'ranting_code'    => $first->ranting?->code ?? '-',
                'mwc_id'          => $first->mwc_id ?? $first->ranting?->mwc_id,
                'mwc_name'        => $first->mwc?->name ?? ($first->ranting?->mwc?->name ?? 'MWC'),
                'total_transaksi' => $txs->count(),
                'gross_amount'    => $gross,
                'ranting_amount'  => $split['ranting_amount'],
                'mwc_amount'      => $split['mwc_amount'],
                'pc_amount'       => $split['pc_amount'],
                'pw_amount'       => $split['pw_amount'],
                'pb_amount'       => $split['pb_amount'],
            ];
        })->values();

        // 5. Ringkasan Agregat Simulasi Direct Split
        $summary = [
            'gross_total'    => $totalKoinBruto,
            'ranting_total'  => $rantingBreakdown->sum('ranting_amount'),
            'mwc_total'      => $rantingBreakdown->sum('mwc_amount'),
            'pc_total'       => $rantingBreakdown->sum('pc_amount'),
            'pw_total'       => $rantingBreakdown->sum('pw_amount'),
            'pb_total'       => $rantingBreakdown->sum('pb_amount'),
            'percentages'    => [
                'ranting' => self::PCT_RANTING,
                'mwc'     => self::PCT_MWC,
                'pc'      => self::PCT_PC,
                'pw'      => self::PCT_PW,
                'pb'      => self::PCT_PB,
            ],
        ];

        // 6. Cek Status Apakah Hasil Koin Periode Ini Sudah Pernah Diproses
        $isProcessed = PenthongDistribution::where('penthong_id', $currentPenthong->id)->exists();

        return view('pentasyarufan.hasil_koin', compact(
            'currentPenthong',
            'penthongs',
            'totalKoinBruto',
            'summary',
            'rantingBreakdown',
            'isProcessed'
        ));
    }

    /**
     * Memproses dan Membagikan Hasil Koin Bruto:
     * 1. Bypass potongan operasional 20% di awal.
     * 2. Langsung bagi 100% Koin Bruto: 80% Ranting, 13% MWC, 5% PC, 1.5% PW, 0.5% PB.
     * 3. Simpan ke penthong_distributions, kontribusi_strukturs, dan mutasi BKU (kas_mutasis).
     * 4. Atomic Execution menggunakan DB::transaction().
     */
    public function prosesHasilKoin(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // Validasi Otorisasi: PC, Superadmin, dan MWC berhak memproses hasil koin
        if (!\Illuminate\Support\Facades\Gate::allows('proses-hasil-koin')) {
            return back()->with('error', 'Akses Ditolak: Anda tidak memiliki wewenang untuk memproses pembagian hasil koin.');
        }

        // 1. Tentukan Periode Pentong yang Diproses
        $penthongId = $request->input('penthong_id');
        $penthong = $penthongId 
            ? Penthong::findOrFail($penthongId)
            : (Penthong::aktif()->first() ?? Penthong::latest('id')->first());

        if (!$penthong) {
            return back()->with('error', 'Gagal memproses: Periode Pentong aktif tidak ditemukan.');
        }

        // 2. Ambil Transaksi Koin Terkonfirmasi dengan DATA SCOPING KETAT
        $transactionsQuery = KoinTransaction::with(['ranting', 'mwc'])
            ->where('status', KoinTransaction::STATUS_CONFIRMED)
            ->where(function ($q) use ($penthong) {
                $q->where('penthong_id', $penthong->id)
                  ->orWhere('periode_pentong_id', $penthong->id);
            });

        // DATA SCOPING: Jika MWC yang memproses, HANYA proses Ranting di bawah wilayah MWC tersebut!
        if ($user->isMwc()) {
            if (!$user->mwc_id) {
                return back()->with('error', 'Akun MWC Anda belum terikat ke data MWC manapun.');
            }
            $transactionsQuery->where('mwc_id', $user->mwc_id);
        }

        $transactions = $transactionsQuery->get();

        if ($transactions->isEmpty()) {
            $scopeInfo = $user->isMwc() ? "di wilayah MWC " . ($user->mwc?->name ?? '') : "";
            return back()->with('error', "Tidak ada transaksi koin yang terkonfirmasi {$scopeInfo} pada {$penthong->nama_periode}.");
        }

        $totalKoinBruto = (float) $transactions->sum('amount');
        if ($totalKoinBruto <= 0) {
            return back()->with('error', 'Total Koin Bruto bernilai Rp 0. Tidak ada dana yang dapat dibagikan.');
        }

        // 3. Hitung Kalkulasi per Ranting
        $rantingBreakdown = $transactions->groupBy('ranting_id')->map(function ($txs, $rantingId) {
            $first = $txs->first();
            $gross = (float) $txs->sum('amount');

            $split = self::calculateDirectSplit($gross);

            return [
                'ranting_id'     => $rantingId,
                'ranting_name'   => $first->ranting?->name ?? "Ranting #{$rantingId}",
                'mwc_id'         => $first->mwc_id ?? $first->ranting?->mwc_id,
                'mwc_name'       => $first->mwc?->name ?? ($first->ranting?->mwc?->name ?? 'MWC'),
                'gross_amount'   => $gross,
                'ranting_amount' => $split['ranting_amount'],
                'mwc_amount'     => $split['mwc_amount'],
                'pc_amount'      => $split['pc_amount'],
                'pw_amount'      => $split['pw_amount'],
                'pb_amount'      => $split['pb_amount'],
            ];
        })->values();

        // 4. Eksekusi Pembagian secara Aman & Atomic dengan DB::transaction()
        try {
            DB::transaction(function () use ($penthong, $rantingBreakdown, $totalKoinBruto, $user) {
                $today = now()->toDateString();
                $userId = $user?->id ?? 1;

                // A. Reset data pembagian lama secara TERISOLASI (Scoping Reset)
                // Jika MWC login: HANYA reset data MWC miliknya, data MWC lain TIDAK BOLEH terhapus!
                if ($user->isMwc()) {
                    PenthongDistribution::where('penthong_id', $penthong->id)
                        ->where('mwc_id', $user->mwc_id)
                        ->delete();

                    KontribusiStruktur::where('periode_pentong_id', $penthong->id)
                        ->where('mwc_id', $user->mwc_id)
                        ->delete();

                    KasMutasi::where('periode_pentong_id', $penthong->id)
                        ->where('mwc_id', $user->mwc_id)
                        ->where('source_type', PenthongDistribution::class)
                        ->delete();
                } else {
                    PenthongDistribution::where('penthong_id', $penthong->id)->delete();
                    KontribusiStruktur::where('periode_pentong_id', $penthong->id)->delete();
                    KasMutasi::where('periode_pentong_id', $penthong->id)
                        ->where('source_type', PenthongDistribution::class)
                        ->delete();
                }

                $totalRantingAll = 0;
                $totalMwcAll     = 0;
                $totalPcAll      = 0;
                $totalPwAll      = 0;
                $totalPbAll      = 0;

                // B. Loop Ranting: Simpan ke tabel distribusi, setoran kontribusi struktur, dan BKU Kas Mutasi
                foreach ($rantingBreakdown as $item) {
                    $totalRantingAll += $item['ranting_amount'];
                    $totalMwcAll     += $item['mwc_amount'];
                    $totalPcAll      += $item['pc_amount'];
                    $totalPwAll      += $item['pw_amount'];
                    $totalPbAll      += $item['pb_amount'];

                    // Format Uraian Khusus BKU PC sesuai spesifikasi:
                    // "Bagi Hasil Koin NU - Ranting [NAMA_RANTING] MWC NU [NAMA_MWC] Periode [NAMA_PERIODE]"
                    $rantingClean = preg_replace('/^(Ranting|PRNU)\s+/i', '', trim($item['ranting_name']));
                    $mwcClean     = preg_replace('/^(MWC\s+NU|MWCNU|MWC)\s+/i', '', trim($item['mwc_name']));
                    $uraianPc     = "Bagi Hasil Koin NU - Ranting {$rantingClean} MWC NU {$mwcClean} Periode {$penthong->nama_periode}";

                    // 1. Simpan Snapshot ke tabel PenthongDistribution
                    $dist = PenthongDistribution::create([
                        'penthong_id'        => $penthong->id,
                        'ranting_id'         => $item['ranting_id'],
                        'mwc_id'             => $item['mwc_id'],
                        'gross_amount'       => $item['gross_amount'],
                        'operasional_amount' => 0.00, // Bypass amil/operasional di awal
                        'net_amount'         => $item['gross_amount'], // 100% langsung dibagi
                        'ranting_amount'     => $item['ranting_amount'], // 80%
                        'mwc_amount'         => $item['mwc_amount'],     // 13%
                        'pc_amount'          => $item['pc_amount'],      // 5%
                        'pw_amount'          => $item['pw_amount'],      // 1.5%
                        'pb_amount'          => $item['pb_amount'],      // 0.5%
                    ]);

                    // 2. Simpan Rekapitulasi Setoran Hak Struktur ke tabel KontribusiStruktur
                    // Total yang disetor ke struktur atas = MWC (13%) + PC (5%) + PW (1.5%) + PB (0.5%) = 20%
                    $totalDisetorStruktur = $item['mwc_amount'] + $item['pc_amount'] + $item['pw_amount'] + $item['pb_amount'];
                    $nomorTransaksi = KontribusiStruktur::generateNomorTransaksi($item['ranting_id']);

                    KontribusiStruktur::create([
                        'nomor_transaksi'        => $nomorTransaksi,
                        'periode_pentong_id'     => $penthong->id,
                        'penthong_id'            => $penthong->id,
                        'ranting_id'             => $item['ranting_id'],
                        'mwc_id'                 => $item['mwc_id'],
                        'user_id'                => $userId,
                        'tanggal_setor'          => $today,
                        'nominal_bruto'          => $item['gross_amount'],
                        'persentase_operasional' => 0.00,
                        'nominal_operasional'    => 0.00,
                        'nominal_netto'          => $item['gross_amount'],
                        'nominal_mwc'            => $item['mwc_amount'],
                        'nominal_pc'             => $item['pc_amount'],
                        'nominal_pw'             => $item['pw_amount'],
                        'nominal_pb'             => $item['pb_amount'],
                        'total_disetor'          => $totalDisetorStruktur,
                        'persentase_setting_json'=> [
                            'skema'       => 'Direct Split Simplifikasi Tahap Awal',
                            'operasional' => 0.00,
                            'ranting'     => self::PCT_RANTING,
                            'mwc'         => self::PCT_MWC,
                            'pc'          => self::PCT_PC,
                            'pw'          => self::PCT_PW,
                            'pb'          => self::PCT_PB,
                        ],
                        'tujuan_struktur'        => 'semua',
                        'tujuan_setoran'         => 'SEMUA',
                        'metode_penyaluran'      => 'transfer',
                        'catatan'                => $uraianPc,
                        'status'                 => 'disetor',
                        'verified_by'            => $userId,
                        'verified_at'            => now(),
                    ]);

                    // 3. Masukkan Otomatis ke Rekapitulasi BKU Tingkatan (KasMutasi)
                    // 3a. BKU Ranting: 80% Koin Bruto masuk sebagai Saldo Utama Ranting
                    KasMutasi::create([
                        'nomor_bukti'        => "BKU-RTG-{$penthong->id}-{$item['ranting_id']}",
                        'periode_pentong_id' => $penthong->id,
                        'penthong_id'        => $penthong->id,
                        'jenis'              => 'debet',
                        'kode_transaksi'     => 'NU-PB',
                        'uraian'             => "Penerimaan Bagian Hasil Koin (80%) - {$item['ranting_name']}",
                        'nominal'            => $item['ranting_amount'],
                        'tanggal_transaksi'  => $today,
                        'tingkat'            => 'ranting',
                        'ranting_id'         => $item['ranting_id'],
                        'mwc_id'             => $item['mwc_id'],
                        'user_id'            => $userId,
                        'source_type'        => PenthongDistribution::class,
                        'source_id'          => $dist->id,
                        'keterangan'         => "Saldo Utama BKU Ranting hasil pembagian 80% Koin Bruto",
                    ]);

                    // 3b. BKU MWC: 13% Koin Bruto masuk sebagai Saldo Utama MWC
                    KasMutasi::create([
                        'nomor_bukti'        => "BKU-MWC-{$penthong->id}-{$item['ranting_id']}",
                        'periode_pentong_id' => $penthong->id,
                        'penthong_id'        => $penthong->id,
                        'jenis'              => 'debet',
                        'kode_transaksi'     => 'NU-KONTRIBUSI',
                        'uraian'             => "Penerimaan Bagian Hasil Koin (13%) dari {$item['ranting_name']}",
                        'nominal'            => $item['mwc_amount'],
                        'tanggal_transaksi'  => $today,
                        'tingkat'            => 'mwc',
                        'ranting_id'         => $item['ranting_id'],
                        'mwc_id'             => $item['mwc_id'],
                        'user_id'            => $userId,
                        'source_type'        => PenthongDistribution::class,
                        'source_id'          => $dist->id,
                        'keterangan'         => "Saldo Utama BKU MWC hasil pembagian 13% Koin Bruto",
                    ]);

                    // 3c. BKU PC: 5% Koin Bruto masuk sebagai Saldo Utama PC
                    KasMutasi::create([
                        'nomor_bukti'        => "BKU-PC-{$penthong->id}-{$item['ranting_id']}",
                        'periode_pentong_id' => $penthong->id,
                        'penthong_id'        => $penthong->id,
                        'jenis'              => 'debet',
                        'kode_transaksi'     => 'NU-KONTRIBUSI',
                        'uraian'             => $uraianPc,
                        'nominal'            => $item['pc_amount'],
                        'tanggal_transaksi'  => $today,
                        'tingkat'            => 'pc',
                        'ranting_id'         => $item['ranting_id'],
                        'mwc_id'             => $item['mwc_id'],
                        'user_id'            => $userId,
                        'source_type'        => PenthongDistribution::class,
                        'source_id'          => $dist->id,
                        'keterangan'         => "Saldo Utama BKU PC Cabang hasil pembagian 5% Koin Bruto",
                    ]);
                }

                // C. Update Agregat Periode Penthong dari seluruh distribusi yang ada
                $allDist = PenthongDistribution::where('penthong_id', $penthong->id)->get();
                $penthong->update([
                    'total_collected'    => (float) $allDist->sum('gross_amount'),
                    'operasional_amount' => 0.00,
                    'net_amount'         => (float) $allDist->sum('gross_amount'),
                    'ranting_amount'     => (float) $allDist->sum('ranting_amount'),
                    'mwc_amount'         => (float) $allDist->sum('mwc_amount'),
                    'pc_amount'          => (float) $allDist->sum('pc_amount'),
                    'pw_amount'          => (float) $allDist->sum('pw_amount'),
                    'pb_amount'          => (float) $allDist->sum('pb_amount'),
                ]);
            });

            $scopeMsg = $user->isMwc() ? "wilayah MWC " . ($user->mwc?->name ?? '') : "seluruh wilayah";
            return redirect()->route('pentasyarufan.hasil-koin.index', ['penthong_id' => $penthong->id])
                ->with('success', "Alhamdulillah! Hasil Koin Periode {$penthong->nama_periode} ({$scopeMsg}) sebesar Rp " . number_format($totalKoinBruto, 0, ',', '.') . " berhasil diproses dan dibagikan secara direct split (80% Ranting, 13% MWC, 5% PC, 1.5% PW, 0.5% PB) ke BKU masing-masing tingkatan.");

        } catch (Exception $e) {
            return back()->with('error', 'Terjadi kesalahan sistem saat memproses pembagian hasil koin: ' . $e->getMessage());
        }
    }

    /**
     * Hitung Direct Split Koin Bruto (Zero-Leakage Precision)
     */
    public static function calculateDirectSplit(float $grossAmount): array
    {
        $gross = max(0, round($grossAmount, 2));

        if ($gross <= 0) {
            return [
                'gross'          => 0,
                'ranting_amount' => 0,
                'mwc_amount'     => 0,
                'pc_amount'      => 0,
                'pw_amount'      => 0,
                'pb_amount'      => 0,
            ];
        }

        // Kalkulasi perkalian persentase secara langsung terhadap Koin Bruto
        $ranting = (float) round($gross * (self::PCT_RANTING / 100)); // 80%
        $mwc     = (float) round($gross * (self::PCT_MWC / 100));     // 13%
        $pc      = (float) round($gross * (self::PCT_PC / 100));      // 5%
        $pw      = (float) round($gross * (self::PCT_PW / 100));      // 1.5%
        $pb      = (float) round($gross * (self::PCT_PB / 100));      // 0.5%

        // Proteksi pembulatan: Total hasil penjumlahan harus tepat sama dengan Koin Bruto (Zero Leakage)
        $allocated = $ranting + $mwc + $pc + $pw + $pb;
        $diff = $gross - $allocated;
        if ($diff != 0) {
            $ranting += $diff;
        }

        return [
            'gross'          => $gross,
            'ranting_amount' => $ranting,
            'mwc_amount'     => $mwc,
            'pc_amount'      => $pc,
            'pw_amount'      => $pw,
            'pb_amount'      => $pb,
        ];
    }

    /**
     * Fallback Empty Summary
     */
    private function emptySummary(): array
    {
        return [
            'gross_total'   => 0,
            'ranting_total' => 0,
            'mwc_total'     => 0,
            'pc_total'      => 0,
            'pw_total'      => 0,
            'pb_total'      => 0,
            'percentages'   => [
                'ranting' => self::PCT_RANTING,
                'mwc'     => self::PCT_MWC,
                'pc'      => self::PCT_PC,
                'pw'      => self::PCT_PW,
                'pb'      => self::PCT_PB,
            ],
        ];
    }
}
