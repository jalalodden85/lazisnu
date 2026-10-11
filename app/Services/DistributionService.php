<?php

namespace App\Services;

use App\Models\DistributionSetting;
use App\Models\KoinTransaction;
use App\Models\Penthong;
use App\Models\PenthongDistribution;
use App\Models\Ranting;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DistributionService
{
    /**
     * Hitung Pembagian / Tasharruf Dana KOIN NU Berdasarkan Persentase Resmi:
     * 1. Total Dana Kotor dipotong Operasional (20%)
     * 2. Sisa Dana Bersih (80%) didistribusikan:
     *    - Ranting : 80% dari dana bersih
     *    - MWC     : 12% dari dana bersih
     *    - PC      : 6%  dari dana bersih
     *    - PW      : 1.5% dari dana bersih
     *    - PB      : 0.5% dari dana bersih
     *
     * @param  float|int  $totalAmount Nominal kotor dalam Rupiah
     * @param  DistributionSetting|null  $setting Pengaturan persentase custom (opsional)
     * @return array Hasil pembagian angka bulat tanpa selisih desimal
     */
    public function calculateSplit(float|int $totalAmount, ?DistributionSetting $setting = null): array
    {
        $gross = max(0, round((float) $totalAmount));
        $setting = $setting ?? DistributionSetting::getActive();

        $operasionalPct = (float) $setting->operasional_pct; // 20.00%
        $rantingPct     = (float) $setting->ranting_pct;     // 80.00%
        $mwcPct         = (float) $setting->mwc_pct;         // 12.00%
        $pcPct          = (float) $setting->pc_pct;          // 6.00%
        $pwPct          = (float) $setting->pw_pct;          // 1.50%
        $pbPct          = (float) $setting->pb_pct;          // 0.50%

        if ($gross <= 0) {
            return [
                'total_gross'        => 0,
                'operasional_pct'    => $operasionalPct,
                'operasional'        => 0,
                'operasional_amount' => 0,
                'net'                => 0,
                'net_amount'         => 0,
                'hak_ranting'        => 0,
                'hak_mwc'            => 0,
                'hak_pc'             => 0,
                'hak_pw'             => 0,
                'hak_pb'             => 0,
                'percentages'        => [
                    'operasional' => $operasionalPct,
                    'ranting'     => $rantingPct,
                    'mwc'         => $mwcPct,
                    'pc'          => $pcPct,
                    'pw'          => $pwPct,
                    'pb'          => $pbPct,
                ],
            ];
        }

        // 1. Potongan Operasional (20%)
        $operasional = (int) round($gross * ($operasionalPct / 100));

        // 2. Dana Bersih untuk Alokasi Lembaga (80%)
        $net = (int) ($gross - $operasional);

        // 3. Porsi masing-masing tingkat dari Dana Bersih (100% Net)
        $hakRanting = (int) round($net * ($rantingPct / 100));
        $hakMwc     = (int) round($net * ($mwcPct / 100));
        $hakPc      = (int) round($net * ($pcPct / 100));
        $hakPw      = (int) round($net * ($pwPct / 100));
        $hakPb      = (int) round($net * ($pbPct / 100));

        // 4. Koreksi Pembulatan Zero-Leakage (Mencegah selisih pecahan rupiah)
        $allocatedNet = $hakRanting + $hakMwc + $hakPc + $hakPw + $hakPb;
        $diffNet = $net - $allocatedNet;

        if ($diffNet !== 0) {
            // Selisih sen/rupiah dialokasikan ke porsi utama Ranting
            $hakRanting += $diffNet;
        }

        return [
            'total_gross'        => (int) $gross,
            'operasional_pct'    => $operasionalPct,
            'operasional'        => $operasional,
            'operasional_amount' => $operasional,
            'net'                => $net,
            'net_amount'         => $net,
            'hak_ranting'        => $hakRanting,
            'hak_mwc'            => $hakMwc,
            'hak_pc'             => $hakPc,
            'hak_pw'             => $hakPw,
            'hak_pb'             => $hakPb,
            'percentages'        => [
                'operasional' => $operasionalPct,
                'ranting'     => $rantingPct,
                'mwc'         => $mwcPct,
                'pc'          => $pcPct,
                'pw'          => $pwPct,
                'pb'          => $pbPct,
            ],
        ];
    }

    /**
     * Mengunci dan Menutup Periode Penthong:
     * - Menghitung total transaksi confirmed
     * - Membagi hasil agregat dan per Ranting
     * - Mengunci data status = 'tutup'
     */
    public function closePenthong(Penthong $penthong, ?User $closedBy = null): Penthong
    {
        return DB::transaction(function () use ($penthong, $closedBy) {
            // Ambil seluruh transaksi yang terikat pada penthong ini (atau periode yang sama jika belum terikat)
            $transactionsQuery = $penthong->transactions()
                ->where('status', KoinTransaction::STATUS_CONFIRMED);

            $totalGross = (float) $transactionsQuery->sum('amount');
            
            // Grandfathering: gunakan setting yang mengikat penthong ini (setting legacy jika penthong lama)
            $setting = $penthong->getDistributionSetting();

            // 1. Hitung Agregat Penthong
            $split = $this->calculateSplit($totalGross, $setting);

            // 2. Hitung dan simpan rincian distribusi per Ranting
            $rantingTotals = $penthong->transactions()
                ->where('status', KoinTransaction::STATUS_CONFIRMED)
                ->select('ranting_id', 'mwc_id', DB::raw('SUM(amount) as gross_amount'))
                ->groupBy('ranting_id', 'mwc_id')
                ->get();

            // Bersihkan data distribusi lama jika ada
            PenthongDistribution::where('penthong_id', $penthong->id)->delete();

            foreach ($rantingTotals as $item) {
                $rantingSplit = $this->calculateSplit((float) $item->gross_amount, $setting);

                PenthongDistribution::create([
                    'penthong_id'        => $penthong->id,
                    'ranting_id'         => $item->ranting_id,
                    'mwc_id'             => $item->mwc_id,
                    'gross_amount'       => $rantingSplit['total_gross'],
                    'operasional_amount' => $rantingSplit['operasional_amount'],
                    'net_amount'         => $rantingSplit['net_amount'],
                    'ranting_amount'     => $rantingSplit['hak_ranting'],
                    'mwc_amount'         => $rantingSplit['hak_mwc'],
                    'pc_amount'          => $rantingSplit['hak_pc'],
                    'pw_amount'          => $rantingSplit['hak_pw'],
                    'pb_amount'          => $rantingSplit['hak_pb'],
                ]);
            }

            // 3. Update status Penthong menjadi Tutup & ikat ke setting distribusi
            $penthong->update([
                'distribution_setting_id' => $setting->id,
                'status'                  => Penthong::STATUS_TUTUP,
                'is_active'               => false,
                'total_collected'         => $split['total_gross'],
                'operasional_amount'      => $split['operasional_amount'],
                'net_amount'              => $split['net_amount'],
                'ranting_amount'          => $split['hak_ranting'],
                'mwc_amount'              => $split['hak_mwc'],
                'pc_amount'               => $split['hak_pc'],
                'pw_amount'               => $split['hak_pw'],
                'pb_amount'               => $split['hak_pb'],
                'closed_at'               => now(),
                'closed_by'               => $closedBy?->id,
            ]);

            return $penthong->fresh(['distributions.ranting.mwc']);
        });
    }

    /**
     * Buat Penthong baru dan pastikan hanya ada 1 penthong aktif
     */
    public function createPenthong(array $data): Penthong
    {
        return DB::transaction(function () use ($data) {
            $status = $data['status'] ?? Penthong::STATUS_AKTIF;
            $mwcId = $data['mwc_id'] ?? null;
            $rantingId = $data['ranting_id'] ?? null;

            if ($status === Penthong::STATUS_AKTIF) {
                // Nonaktifkan penthong lain yang sedang aktif pada scope yang sama
                $query = Penthong::where('status', Penthong::STATUS_AKTIF);
                if ($rantingId) {
                    $query->where('ranting_id', $rantingId);
                } elseif ($mwcId) {
                    $query->where('mwc_id', $mwcId);
                }
                $query->update(['status' => Penthong::STATUS_TUTUP, 'closed_at' => now()]);
            }

            $activeSetting = DistributionSetting::getActive();
            if ($activeSetting->id) {
                $data['distribution_setting_id'] = $data['distribution_setting_id'] ?? $activeSetting->id;
            }

            return Penthong::create($data);
        });
    }
}
