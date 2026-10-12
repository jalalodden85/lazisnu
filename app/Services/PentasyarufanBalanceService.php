<?php

namespace App\Services;

use App\Models\DistributionSetting;
use App\Models\KoinTransaction;
use App\Models\KontribusiStruktur;
use App\Models\Pentasyarufan;
use App\Models\Penthong;
use App\Models\PilarProgram;
use App\Models\ProgramKerja;

class PentasyarufanBalanceService
{
    /**
     * Hitung Pembukuan Saldo & Pagu 5 Pilar Program Berdasarkan Satu Periode Penthong Spesifik
     * dengan dukungan Data Scoping Ranting (menyambung dengan pembagian struktur MWC, PC, PW, PB).
     *
     * Jika dihitung untuk tingkat RANTING:
     * 1. Total Koin Bruto Ranting = SUM(Setoran Koin Ranting pada Periode Ini)
     * 2. Potongan Hak Amil/Operasional Ranting = 20% Bruto
     * 3. Dana Bersih (Netto) = 80% Bruto
     * 4. Pembagian ke Struktur Atas:
     *    - MWC  : 12% dari Netto
     *    - PC   : 6%  dari Netto
     *    - PW   : 1.5% dari Netto
     *    - PBNU : 0.5% dari Netto
     * 5. Dana Bersih Khusus 5 Pilar Ranting = 80% dari Netto
     * 6. Pagu 5 Pilar Ranting dihitung dari 80% Netto tersebut sehingga 100% SINKRON dengan BKU Ranting.
     *
     * @param  int|Penthong  $penthong
     * @param  float|null    $customOperasionalPct
     * @param  int|null      $rantingId
     * @param  int|null      $mwcId
     * @return array
     */
    public function calculatePeriodBudget(
        int|Penthong $penthong, 
        ?float $customOperasionalPct = null,
        ?int $rantingId = null,
        ?int $mwcId = null,
        ?string $explicitTingkat = null
    ): array {
        $penthongModel = is_numeric($penthong) ? Penthong::findOrFail($penthong) : $penthong;

        // Scoping tingkatan jika eksplisit diberikan
        if ($explicitTingkat === 'pc') {
            $rantingId = null;
            $mwcId = null;
        } elseif ($explicitTingkat === 'mwc') {
            $rantingId = null;
            if (!$mwcId) {
                $mwcId = Mwc::first()?->id;
            }
        } elseif ($explicitTingkat === 'ranting') {
            if (!$rantingId) {
                $rantingId = $mwcId ? Ranting::where('mwc_id', $mwcId)->first()?->id : Ranting::first()?->id;
            }
        }

        // 1. Ambil setting persentase operasional & bagi hasil (Grandfathering support)
        $setting = $penthongModel->getDistributionSetting();
        $operasionalPct = $customOperasionalPct !== null 
            ? (float) $customOperasionalPct 
            : (float) ($setting->operasional_pct ?? 20.00);

        $rantingPct = (float) ($setting->ranting_pct ?? 80.00);
        $mwcPct     = (float) ($setting->mwc_pct ?? 13.00);
        $pcPct      = (float) ($setting->pc_pct ?? 5.00);
        $pwPct      = (float) ($setting->pw_pct ?? 1.50);
        $pbPct      = (float) ($setting->pb_pct ?? 0.50);

        // 2. Query Transaksi Koin Terkonfirmasi
        $txQuery = KoinTransaction::where('status', KoinTransaction::STATUS_CONFIRMED);

        if ($penthongModel->start_date && $penthongModel->end_date) {
            $txQuery->where(function ($q) use ($penthongModel) {
                $q->where('penthong_id', $penthongModel->id)
                  ->orWhere(function ($sub) use ($penthongModel) {
                      $sub->whereNull('penthong_id')
                          ->whereBetween('collection_date', [
                              $penthongModel->start_date->toDateString(),
                              $penthongModel->end_date->toDateString()
                          ]);
                  });
            });
        } else {
            $txQuery->where('penthong_id', $penthongModel->id);
        }

        // Terapkan Data Scoping
        if ($rantingId) {
            $txQuery->where('ranting_id', $rantingId);
        } elseif ($mwcId) {
            $txQuery->where('mwc_id', $mwcId);
        }

        $totalBruto = (float) $txQuery->sum('amount');

        // Fallback jika belum ada transaksi di database dan tidak difilter ranting
        if ($totalBruto <= 0 && !$rantingId && !$mwcId) {
            $totalBruto = (float) ($penthongModel->total_collected ?? 0);
        }

        // =========================================================================
        // REKALKULASI SALDO VIRTUAL (VIRTUAL BALANCE) 5 PILAR PER TINGKATAN
        // =========================================================================
        $tingkat = 'pc';
        $totalDanaKontribusiMasuk = 0;
        $potonganOperasional = 0;
        $danaPilarBasis = 0;

        if ($rantingId) {
            $tingkat = 'ranting';
            // 1. TINGKAT RANTING:
            // Basis 5 Pilar = Hak Ranting (80% dari Dana Bersih Netto setelah 20% amil & porsi struktur)
            $potonganOperasional = round(($totalBruto * $operasionalPct) / 100);
            $danaBersihNetto = max(0, $totalBruto - $potonganOperasional);

            $alokasiMwc = round(($danaBersihNetto * $mwcPct) / 100);
            $alokasiPc  = round(($danaBersihNetto * $pcPct) / 100);
            $alokasiPw  = round(($danaBersihNetto * $pwPct) / 100);
            $alokasiPb  = round(($danaBersihNetto * $pbPct) / 100);
            $totalAlokasiStrukturAtas = $alokasiMwc + $alokasiPc + $alokasiPw + $alokasiPb;

            $danaPilarBasis = max(0, $danaBersihNetto - $totalAlokasiStrukturAtas); // 80% Netto (Hak Ranting)


        } elseif ($mwcId) {
            $tingkat = 'mwc';
            // 2. TINGKAT MWC (KECAMATAN):
            // Rumus: (Total Dana Kontribusi Masuk MWC pada Periode X) - (Biaya Operasional MWC jika ada) = Dana Bersih MWC
            $ktrMwcQuery = KontribusiStruktur::validDisetor()
                ->where('mwc_id', $mwcId)
                ->forTujuan('MWC')
                ->forPeriode($penthongModel->id);

            $totalDanaKontribusiMasuk = (float) $ktrMwcQuery->sum('nominal_mwc');

            // Fallback otomatisasi jika ranting belum setor manual tapi koin sudah dihimpun
            if ($totalDanaKontribusiMasuk <= 0) {
                $mwcGross = (float) $txQuery->sum('amount');
                $mwcNetto = max(0, $mwcGross * (1 - ($operasionalPct / 100)));
                $totalDanaKontribusiMasuk = round($mwcNetto * ($mwcPct / 100));
            }

            // Potongan Operasional MWC (Hak Amil MWC = 20% dari Pemasukan Riil Kontribusi)
            $opsMwcPct = $customOperasionalPct !== null 
                ? (float) $customOperasionalPct 
                : (float) $operasionalPct;
            $potonganOperasional = round(($totalDanaKontribusiMasuk * $opsMwcPct) / 100);

            $danaBersihNetto = max(0, $totalDanaKontribusiMasuk - $potonganOperasional);
            $danaPilarBasis = $danaBersihNetto;

            $alokasiMwc = $totalDanaKontribusiMasuk;
            $alokasiPc  = 0;
            $alokasiPw  = 0;
            $alokasiPb  = 0;
            $totalAlokasiStrukturAtas = 0;

        } else {
            $tingkat = 'pc';
            // 3. TINGKAT PC (CABANG):
            // Rumus: (Total Dana Kontribusi Masuk PC pada Periode X) - (Biaya Operasional PC 20%) = Dana Bersih 5 Pilar PC (80%)
            $ktrPcQuery = KontribusiStruktur::validDisetor()
                ->forTujuan('PC')
                ->forPeriode($penthongModel->id);

            $totalDanaKontribusiMasuk = (float) $ktrPcQuery->sum('nominal_pc');

            // Fallback otomatisasi jika belum ada catatan manual di kontribusi_strukturs
            if ($totalDanaKontribusiMasuk <= 0) {
                $pcGross = $totalBruto;
                $pcNetto = max(0, $pcGross * (1 - ($operasionalPct / 100)));
                $totalDanaKontribusiMasuk = round($pcNetto * ($pcPct / 100));
            }

            // Potongan Operasional PC (Hak Amil PC = 20% dari Pemasukan Riil Kontribusi)
            $opsPcPct = $customOperasionalPct !== null 
                ? (float) $customOperasionalPct 
                : (float) $operasionalPct;
            $potonganOperasional = round(($totalDanaKontribusiMasuk * $opsPcPct) / 100);

            $danaBersihNetto = max(0, $totalDanaKontribusiMasuk - $potonganOperasional);
            $danaPilarBasis = $danaBersihNetto;

            $alokasiPc  = $totalDanaKontribusiMasuk;
            $alokasiMwc = 0;
            $alokasiPw  = 0;
            $alokasiPb  = 0;
            $totalAlokasiStrukturAtas = 0;
        }

        // 7. Distribusikan ke 5 Pilar Program NU Care Sesuai Tingkatan
        $pilars = PilarProgram::where('is_active', true)->orderBy('sort_order')->get();
        $breakdownPilars = [];
        $totalPenyaluranPeriode = 0;

        foreach ($pilars as $pilar) {
            $paguPilar = round(($danaPilarBasis * $pilar->target_persentase) / 100);

            // Hitung akumulasi pentasyarufan pilar ini yang mengambil dari periode penthong ini
            $outQuery = Pentasyarufan::where('pilar_program_id', $pilar->id);

            if ($penthongModel->start_date && $penthongModel->end_date) {
                $outQuery->where(function ($q) use ($penthongModel) {
                    $q->where('periode_pentong_id', $penthongModel->id)
                      ->orWhere('penthong_id', $penthongModel->id)
                      ->orWhere(function ($sub) use ($penthongModel) {
                          $sub->whereNull('periode_pentong_id')
                              ->whereNull('penthong_id')
                              ->whereBetween('tanggal_penyaluran', [
                                  $penthongModel->start_date->toDateString(),
                                  $penthongModel->end_date->toDateString()
                              ]);
                      });
                });
            } else {
                $outQuery->where(function ($q) use ($penthongModel) {
                    $q->where('periode_pentong_id', $penthongModel->id)
                      ->orWhere('penthong_id', $penthongModel->id);
                });
            }

            // Filter Scoping Pentasyarufan Berdasarkan Tingkatan
            if ($rantingId) {
                $outQuery->where('ranting_id', $rantingId)->where('tingkat', 'ranting');
            } elseif ($mwcId) {
                $outQuery->where('mwc_id', $mwcId)->where('tingkat', 'mwc');
            } else {
                $outQuery->where('tingkat', 'pc');
            }

            $terpakai = (float) $outQuery->sum('nominal_pencairan');
            $sisaSaldo = $paguPilar - $terpakai;
            $persenSerapan = $paguPilar > 0 ? round(($terpakai / $paguPilar) * 100, 2) : 0;
            $totalPenyaluranPeriode += $terpakai;

            // Status evaluasi serapan
            if ($paguPilar > 0 && $sisaSaldo < 0) {
                $status = [
                    'code'    => 'overbudget',
                    'label'   => 'Overbudget (Defisit)',
                    'badge'   => 'badge-danger',
                    'variant' => '#dc3545',
                ];
            } elseif ($paguPilar > 0 && $persenSerapan < 30.0) {
                $status = [
                    'code'    => 'low_utilization',
                    'label'   => 'Serapan Rendah (< 30%)',
                    'badge'   => 'badge-warning',
                    'variant' => '#ffc107',
                ];
            } else {
                $status = [
                    'code'    => 'optimal',
                    'label'   => 'Optimal',
                    'badge'   => 'badge-success',
                    'variant' => '#28a745',
                ];
            }

            $breakdownPilars[$pilar->id] = [
                'pilar_id'          => $pilar->id,
                'kode'              => $pilar->kode,
                'nama_pilar'        => $pilar->nama_pilar,
                'icon'              => $pilar->icon,
                'warna'             => $pilar->warna,
                'target_persentase' => (float) $pilar->target_persentase,
                'pagu_nominal'      => $paguPilar,
                'total_terpakai'    => $terpakai,
                'sisa_saldo'        => $sisaSaldo,
                'persentase_serap'  => $persenSerapan,
                'status_evaluasi'   => $status,
            ];
        }

        $tingkatLabel = match($tingkat) {
            'ranting' => 'Ranting (Tingkat Desa / Kelurahan)',
            'mwc'     => 'MWC NU (Tingkat Kecamatan)',
            default   => 'PC LAZISNU (Tingkat Cabang / Kabupaten)',
        };

        $wilayahLabel = match($tingkat) {
            'ranting' => ($rantingId ? \App\Models\Ranting::find($rantingId)?->name : 'Ranting'),
            'mwc'     => ($mwcId ? \App\Models\Mwc::find($mwcId)?->name : 'MWC NU'),
            default   => 'PC LAZISNU Kabupaten Lamongan',
        };

        return [
            'penthong_id'                 => $penthongModel->id,
            'nama_periode'                => $penthongModel->nama_periode,
            'bulan'                       => $penthongModel->bulan,
            'tahun'                       => $penthongModel->tahun,
            'status_periode'              => $penthongModel->status,
            'is_open'                     => $penthongModel->isOpen(),
            'tingkat'                     => $tingkat,
            'tingkat_label'               => $tingkatLabel,
            'wilayah_label'               => $wilayahLabel,
            'ranting_id'                  => $rantingId,
            'mwc_id'                      => $mwcId,
            'total_koin_bruto'            => $totalBruto,
            'total_dana_kontribusi_masuk' => $totalDanaKontribusiMasuk,
            'operasional_pct'             => $operasionalPct,
            'ranting_pct'                 => $rantingPct,
            'mwc_pct'                     => $mwcPct,
            'pc_pct'                      => $pcPct,
            'pw_pct'                      => $pwPct,
            'pb_pct'                      => $pbPct,
            'potongan_operasional'        => $potonganOperasional,
            'dana_bersih_netto'           => $danaBersihNetto,
            // Alokasi Struktur Atas
            'alokasi_mwc'                 => $alokasiMwc,
            'alokasi_pc'                  => $alokasiPc,
            'alokasi_pw'                  => $alokasiPw,
            'alokasi_pb'                  => $alokasiPb,
            'total_alokasi_struktur_atas' => $totalAlokasiStrukturAtas,
            // Dasar Dana 5 Pilar
            'dana_pilar_basis'            => $danaPilarBasis,
            'total_penyaluran_periode'    => $totalPenyaluranPeriode,
            'sisa_netto_periode'          => max(0, $danaPilarBasis - $totalPenyaluranPeriode),
            'pilars'                      => $breakdownPilars,
        ];
    }

    /**
     * Ambil Sisa Saldo Satu Pilar Program Spesifik pada Suatu Periode Penthong
     */
    public function getPilarBalance(
        int|Penthong $penthong, 
        int|PilarProgram $pilar, 
        ?int $rantingId = null, 
        ?int $mwcId = null
    ): array {
        $periodData = $this->calculatePeriodBudget($penthong, null, $rantingId, $mwcId);
        $pilarId = is_numeric($pilar) ? (int) $pilar : $pilar->id;

        $pilarData = $periodData['pilars'][$pilarId] ?? null;

        return [
            'period_data' => $periodData,
            'pilar_data'  => $pilarData,
            'sisa_saldo'  => $pilarData ? $pilarData['sisa_saldo'] : 0,
        ];
    }

    /**
     * Validasi pencairan pentasyarufan terhadap sisa RAB proker & sisa dompet pilar periode terpilih
     * Dilengkapi Grandfathering Protection: Melarang pencairan baru pada Periode Pentong yang sudah ditutup ('tutup')
     */
    public function validateDisbursement(int $penthongId, int $prokerId, float $nominal): array
    {
        $proker = ProgramKerja::with(['pilarProgram', 'mwc', 'ranting'])->findOrFail($prokerId);
        $penthong = Penthong::findOrFail($penthongId);

        // Proteksi Grandfathering: Periode tertutup terkunci dari mutasi pencairan baru
        if ($penthong->isClosed()) {
            return [
                'valid' => false,
                'error' => "Periode Pentong [{$penthong->nama_periode}] telah ditutup ('closed'). Pencairan dana baru tidak diizinkan pada periode yang sudah terkunci demi menjaga integritas buku kas.",
            ];
        }

        if ($proker->status !== 'disetujui') {
            return [
                'valid' => false,
                'error' => 'Program Kerja ini belum berstatus disetujui.',
            ];
        }

        if ($nominal > (float) $proker->sisa_anggaran) {
            return [
                'valid' => false,
                'error' => 'Nominal pencairan (Rp ' . number_format($nominal, 0, ',', '.') . ') melebihi sisa RAB Proker (Rp ' . number_format($proker->sisa_anggaran, 0, ',', '.') . ').',
            ];
        }

        // Ambil saldo pilar sesuai tingkatan proker
        $pilarBalance = $this->getPilarBalance(
            $penthong, 
            $proker->pilar_program_id, 
            $proker->ranting_id, 
            $proker->mwc_id
        );

        $sisaPilar = (float) ($pilarBalance['sisa_saldo'] ?? 0);

        if ($nominal > $sisaPilar) {
            return [
                'valid' => false,
                'error' => 'Nominal pencairan (Rp ' . number_format($nominal, 0, ',', '.') . ') melebihi sisa dompet Pilar ' . ($proker->pilarProgram?->nama_pilar ?? '') . ' pada periode ' . $penthong->nama_periode . ' untuk ' . $proker->wilayah_label . ' (Tersedia: Rp ' . number_format($sisaPilar, 0, ',', '.') . ').',
            ];
        }

        return [
            'valid' => true,
            'data'  => [
                'proker'       => $proker,
                'penthong'     => $penthong,
                'period_data'  => $pilarBalance['period_data'],
                'pilar_data'   => $pilarBalance['pilar_data'],
                'sisa_pilar'   => $sisaPilar,
            ],
        ];
    }
}
