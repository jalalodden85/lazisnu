<?php

namespace App\Services;

use App\Models\BkuOperasional;
use App\Models\DistributionSetting;
use App\Models\KoinTransaction;
use App\Models\KontribusiStruktur;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OperasionalWalletService
{
    /**
     * Hitung sisa saldo operasional yang tersedia untuk entitas saat ini
     */
    public function getAvailableBalance(string $tingkat, ?int $rantingId = null, ?int $mwcId = null): float
    {
        $query = BkuOperasional::where('tingkat', $tingkat);

        if ($tingkat === 'ranting' && $rantingId) {
            $query->where('ranting_id', $rantingId);
        } elseif ($tingkat === 'mwc' && $mwcId) {
            $query->where('mwc_id', $mwcId);
        }

        $totalDebet  = (float) (clone $query)->where('jenis', 'debet')->sum('nominal');
        $totalKredit = (float) (clone $query)->where('jenis', 'kredit')->sum('nominal');

        return max(0, $totalDebet - $totalKredit);
    }

    /**
     * Otomatisasi Alokasi Potongan Operasional (Hak Amil) dari Pemasukan Riil Berdasarkan Tingkatan (Ranting, MWC, PC)
     * Menggunakan DB::transaction() agar proses kalkulasi dan pembukuan berjalan secara atomic dan aman.
     */
    public function syncOperationalIncomeByLevel(
        Penthong $penthong,
        string $tingkat = 'ranting',
        ?int $rantingId = null,
        ?int $mwcId = null,
        ?int $userId = null
    ): ?BkuOperasional {
        return DB::transaction(function () use ($penthong, $tingkat, $rantingId, $mwcId, $userId) {
            // 1. Ambil Pengaturan Persentase Operasional Dinamis (Default 20% dari database)
            $setting = $penthong->getDistributionSetting();
            $persenOps = (float) ($setting->operasional_pct ?? 20.00);

            $pemasukanRiil = 0;
            $nomorTransaksi = '';
            $uraian = '';
            $vendorName = '';
            $resolvedRantingId = null;
            $resolvedMwcId = null;

            // 2. Logika Pemasukan Riil Berdasarkan Tingkatan Struktur (Level-Based Income)
            switch ($tingkat) {
                case 'ranting':
                    $ranting = Ranting::findOrFail($rantingId);
                    $resolvedRantingId = $ranting->id;
                    $resolvedMwcId = $ranting->mwc_id;

                    // Tingkat Ranting: Pemasukan = Total Setoran Koin Bruto dari kaleng di ranting tersebut
                    $pemasukanRiil = (float) KoinTransaction::where('ranting_id', $ranting->id)
                        ->where(function ($q) use ($penthong) {
                            if ($penthong->start_date && $penthong->end_date) {
                                $q->whereBetween('collection_date', [$penthong->start_date, $penthong->end_date]);
                            } else {
                                $q->where('period', sprintf('%04d-%02d', $penthong->tahun, $penthong->bulan));
                            }
                        })
                        ->where('status', KoinTransaction::STATUS_CONFIRMED)
                        ->sum('amount');

                    $nomorTransaksi = sprintf('BKO-AUTO-%s-P%03d', $ranting->code, $penthong->id);
                    $uraian = sprintf('Hak Amil Operasional Ranting (%g%%) dari Rekap Koin Bruto Periode %s', $persenOps, $penthong->nama_periode);
                    $vendorName = 'Kas Operasional Ranting ' . $ranting->name;
                    break;

                case 'mwc':
                    $mwc = Mwc::findOrFail($mwcId);
                    $resolvedMwcId = $mwc->id;

                    // Tingkat MWC: Pemasukan = Total Dana Kontribusi/Bagi Hasil yang diterima MWC dari ranting (13%)
                    $kontribusiMasuk = (float) KontribusiStruktur::validDisetor()
                        ->where('mwc_id', $mwc->id)
                        ->forPeriode($penthong->id)
                        ->sum('nominal_mwc');

                    // Fallback kalkulasi otomatis jika belum tercatat manual di kontribusi_strukturs
                    if ($kontribusiMasuk <= 0) {
                        $koinBrutoMwc = (float) KoinTransaction::where('mwc_id', $mwc->id)
                            ->where(function ($q) use ($penthong) {
                                if ($penthong->start_date && $penthong->end_date) {
                                    $q->whereBetween('collection_date', [$penthong->start_date, $penthong->end_date]);
                                } else {
                                    $q->where('period', sprintf('%04d-%02d', $penthong->tahun, $penthong->bulan));
                                }
                            })
                            ->where('status', KoinTransaction::STATUS_CONFIRMED)
                            ->sum('amount');

                        $nettoKoin = max(0, $koinBrutoMwc * (1 - ($persenOps / 100)));
                        $kontribusiMasuk = round($nettoKoin * ($setting->mwc_pct / 100));
                    }

                    $pemasukanRiil = $kontribusiMasuk;
                    $nomorTransaksi = sprintf('BKO-AUTO-MWC%s-P%03d', $mwc->code, $penthong->id);
                    $uraian = sprintf('Hak Amil Operasional MWC (%g%%) dari Dana Kontribusi Masuk Periode %s', $persenOps, $penthong->nama_periode);
                    $vendorName = 'Kas Operasional MWC ' . $mwc->name;
                    break;

                case 'pc':
                    // Tingkat PC: Pemasukan = Total Dana Kontribusi/Bagi Hasil yang disetorkan ke PC (5%)
                    $kontribusiMasuk = (float) KontribusiStruktur::validDisetor()
                        ->forPeriode($penthong->id)
                        ->sum('nominal_pc');

                    // Fallback kalkulasi otomatis dari seluruh koin se-cabang jika belum tercatat manual
                    if ($kontribusiMasuk <= 0) {
                        $koinBrutoCabang = (float) KoinTransaction::where('status', KoinTransaction::STATUS_CONFIRMED)
                            ->where(function ($q) use ($penthong) {
                                if ($penthong->start_date && $penthong->end_date) {
                                    $q->whereBetween('collection_date', [$penthong->start_date, $penthong->end_date]);
                                } else {
                                    $q->where('period', sprintf('%04d-%02d', $penthong->tahun, $penthong->bulan));
                                }
                            })
                            ->sum('amount');

                        $nettoKoin = max(0, $koinBrutoCabang * (1 - ($persenOps / 100)));
                        $kontribusiMasuk = round($nettoKoin * ($setting->pc_pct / 100));
                    }

                    $pemasukanRiil = $kontribusiMasuk;
                    $nomorTransaksi = sprintf('BKO-AUTO-PC-P%03d', $penthong->id);
                    $uraian = sprintf('Hak Amil Operasional Cabang (%g%%) dari Dana Kontribusi Masuk Periode %s', $persenOps, $penthong->nama_periode);
                    $vendorName = 'Kas Operasional PCNU Lamongan';
                    break;

                default:
                    throw new \InvalidArgumentException("Tingkatan tidak valid: {$tingkat}");
            }

            if ($pemasukanRiil <= 0) {
                return null;
            }

            // 3. Hitung Nominal Pemotongan 20% untuk Saldo Kas Operasional (Hak Amil)
            $nominalOperasional = round($pemasukanRiil * ($persenOps / 100));

            // 4. Simpan / Perbarui secara Idempoten di BKU Operasional
            return BkuOperasional::updateOrCreate(
                [
                    'nomor_transaksi'    => $nomorTransaksi,
                ],
                [
                    'periode_pentong_id' => $penthong->id,
                    'jenis'              => 'debet', // Pemasukan Saldo Amil
                    'sumber_dana'        => 'alokasi_pentong',
                    'nominal'            => $nominalOperasional,
                    'tanggal_transaksi'  => $penthong->end_date ?? now()->toDateString(),
                    'uraian'             => $uraian,
                    'penerima_atau_vendor' => $vendorName,
                    'tingkat'            => $tingkat,
                    'mwc_id'             => $resolvedMwcId,
                    'ranting_id'         => $resolvedRantingId,
                    'created_by'         => $userId ?? auth()->id(),
                ]
            );
        });
    }

    /**
     * Wrapper kompatibilitas untuk otomatisasi tingkat Ranting
     */
    public function syncPenthongOperationalIncome(Penthong $penthong, int $rantingId): ?BkuOperasional
    {
        return $this->syncOperationalIncomeByLevel($penthong, 'ranting', $rantingId);
    }

    /**
     * Catat Pengeluaran Operasional dengan Validasi Saldo Ketat (Anti-Defisit)
     */
    public function recordExpense(array $data, User $user): BkuOperasional
    {
        $tingkat = $user->isRanting() ? 'ranting' : ($user->isMwc() ? 'mwc' : 'pc');
        $rantingId = $user->isRanting() ? $user->ranting_id : ($data['ranting_id'] ?? null);
        $mwcId = $user->isMwc() ? $user->mwc_id : ($data['mwc_id'] ?? ($user->ranting?->mwc_id ?? null));

        $nominal = (float) $data['nominal'];

        // CEK INTEGRITAS SALDO OPERASIONAL
        $saldoTersedia = $this->getAvailableBalance($tingkat, $rantingId, $mwcId);

        if ($nominal > $saldoTersedia) {
            throw ValidationException::withMessages([
                'nominal' => sprintf(
                    'Saldo Kas Operasional tidak mencukupi! Sisa saldo amil saat ini: Rp %s, pengajuan pengeluaran: Rp %s.',
                    number_format($saldoTersedia, 0, ',', '.'),
                    number_format($nominal, 0, ',', '.')
                ),
            ]);
        }

        // Generate Nomor Bukti Pengeluaran
        $kodeWilayah = $user->isRanting() ? ($user->ranting?->code ?? 'RTG') : ($user->isMwc() ? 'MWC' : 'PC');
        $nomorTransaksi = sprintf('BKO-EXP-%s-%s-%04d', $kodeWilayah, date('Ym'), rand(1000, 9999));

        return BkuOperasional::create([
            'nomor_transaksi'         => $nomorTransaksi,
            'periode_pentong_id'      => $data['periode_pentong_id'] ?? null,
            'kategori_operasional_id' => $data['kategori_operasional_id'],
            'jenis'                   => 'kredit', // Pengeluaran Beban Operasional
            'sumber_dana'             => 'alokasi_pentong',
            'nominal'                 => $nominal,
            'tanggal_transaksi'       => $data['tanggal_transaksi'],
            'uraian'                  => $data['uraian'],
            'penerima_atau_vendor'    => $data['penerima_atau_vendor'] ?? null,
            'bukti_transaksi'         => $data['bukti_transaksi'] ?? null,
            'tingkat'                 => $tingkat,
            'mwc_id'                  => $mwcId,
            'ranting_id'              => $rantingId,
            'created_by'              => $user->id,
        ]);
    }

    /**
     * Catat Pemasukan Operasional Mandiri / Donasi Amil Khusus
     */
    public function recordIncome(array $data, User $user): BkuOperasional
    {
        $tingkat = $user->isRanting() ? 'ranting' : ($user->isMwc() ? 'mwc' : 'pc');
        $rantingId = $user->isRanting() ? $user->ranting_id : ($data['ranting_id'] ?? null);
        $mwcId = $user->isMwc() ? $user->mwc_id : ($data['mwc_id'] ?? ($user->ranting?->mwc_id ?? null));

        $kodeWilayah = $user->isRanting() ? ($user->ranting?->code ?? 'RTG') : ($user->isMwc() ? 'MWC' : 'PC');
        $nomorTransaksi = sprintf('BKO-INC-%s-%s-%04d', $kodeWilayah, date('Ym'), rand(1000, 9999));

        return BkuOperasional::create([
            'nomor_transaksi'      => $nomorTransaksi,
            'periode_pentong_id'   => $data['periode_pentong_id'] ?? null,
            'jenis'                => 'debet',
            'sumber_dana'          => $data['sumber_dana'] ?? 'infaq_operasional',
            'nominal'              => (float) $data['nominal'],
            'tanggal_transaksi'    => $data['tanggal_transaksi'],
            'uraian'               => $data['uraian'],
            'penerima_atau_vendor' => 'Kas Operasional',
            'tingkat'              => $tingkat,
            'mwc_id'               => $mwcId,
            'ranting_id'           => $rantingId,
            'created_by'           => $user->id,
        ]);
    }
}
