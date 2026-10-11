<?php

namespace App\Services;

use App\Models\DistributionSetting;
use App\Models\KoinTransaction;
use App\Models\KontribusiStruktur;
use App\Models\Mwc;
use App\Models\Pentasyarufan;
use App\Models\Penthong;
use App\Models\Ranting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class BkuService
{
    /**
     * Pemetaan Kode Pilar ke Chart of Accounts (COA) Standar BKU LAZISNU:
     * - NU Cerdas       (Pendidikan)
     * - NU Berdaya      (Ekonomi)
     * - NU Sehat        (Kesehatan)
     * - NU Damai        (Sosial / Kemanusiaan)
     * - NU Hijau        (Lingkungan / Keagamaan / Dakwah)
     * - NU-O            (Operasional / Hak Amil / Bisyaroh)
     * - NU-KONTRIBUSI   (Setoran Bagian Struktur MWC/PC/PW/PB)
     * - NU-PB           (Pindah Buku / Saldo Rekening / Setoran Masuk)
     */
    public static function mapPilarToCoa(?string $pilarCode): string
    {
        return match (strtoupper(trim($pilarCode ?? ''))) {
            'PENDIDIKAN'           => 'NU Cerdas',
            'EKONOMI'              => 'NU Berdaya',
            'KESEHATAN'            => 'NU Sehat',
            'SOSIAL'               => 'NU Damai',
            'DAKWAH', 'LINGKUNGAN' => 'NU Hijau',
            'OPERASIONAL', 'OPS'   => 'NU-O',
            'KONTRIBUSI'           => 'NU-KONTRIBUSI',
            default                => 'NU-PB',
        };
    }

    /**
     * Mengambil dan mengonsolidasikan data BKU bulanan/periode dalam format T-Account seimbang
     * dengan Isolasi Data Berjenjang (Data Scoping), Pengikatan Periode Pentong, dan
     * Otomatisasi Alokasi Pembagian Struktur (MWC, PC, PW, PBNU) agar menyambung dengan Pagu 5 Pilar.
     *
     * @param  Penthong|int|null $penthongParam Periode Pentong aktif atau yang dipilih
     * @param  array             $filters       Filter hak akses: [mwc_id, ranting_id, tingkat, dsb]
     * @return array
     */
    public function getBkuDataByPenthong(Penthong|int|null $penthongParam, array $filters = []): array
    {
        // 1. Resolusi Objek Periode Penthong
        if ($penthongParam instanceof Penthong) {
            $currentPenthong = $penthongParam;
        } elseif (is_numeric($penthongParam) && $penthongParam > 0) {
            $currentPenthong = Penthong::find($penthongParam);
        } else {
            $currentPenthong = Penthong::aktif()->first() ?? Penthong::latest('id')->first();
        }

        // Fallback jika belum ada data penthong di database
        $bulan = $currentPenthong ? $currentPenthong->bulan : (int) date('n');
        $tahun = $currentPenthong ? $currentPenthong->tahun : (int) date('Y');

        $namaBulan = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ][$bulan] ?? 'Bulan ' . $bulan;

        $periodeTitle = $currentPenthong 
            ? "{$currentPenthong->nama_periode} ({$namaBulan} {$tahun})"
            : strtoupper($namaBulan . ' ' . $tahun);

        $startDate = $currentPenthong && $currentPenthong->start_date 
            ? Carbon::parse($currentPenthong->start_date)
            : Carbon::createFromDate($tahun, $bulan, 1)->startOfMonth();

        $endDate = $currentPenthong && $currentPenthong->end_date 
            ? Carbon::parse($currentPenthong->end_date)
            : Carbon::createFromDate($tahun, $bulan, 1)->endOfMonth();

        // Cari Periode Pentong Sebelumnya untuk referensi nama Saldo Awal
        $prevPenthong = null;
        if ($currentPenthong) {
            $prevPenthong = Penthong::where(function ($q) use ($currentPenthong) {
                $q->where('tahun', '<', $currentPenthong->tahun)
                  ->orWhere(function ($sub) use ($currentPenthong) {
                      $sub->where('tahun', $currentPenthong->tahun)
                          ->where('bulan', '<', $currentPenthong->bulan);
                  });
            })->orderByDesc('tahun')->orderByDesc('bulan')->first();
        }

        // Ambil Setting Persentase Resmi berdasarkan Periode Penthong saat ini (Grandfathering)
        $setting = $currentPenthong ? $currentPenthong->getDistributionSetting() : DistributionSetting::getActive();
        $operasionalPct = (float) ($setting->operasional_pct ?? 20.00); // 20%
        $rantingPct     = (float) ($setting->ranting_pct ?? 80.00);     // 80%
        $mwcPct         = (float) ($setting->mwc_pct ?? 13.00);         // 13% (active) / 12% (legacy)
        $pcPct          = (float) ($setting->pc_pct ?? 5.00);           // 5% (active) / 6% (legacy)
        $pwPct          = (float) ($setting->pw_pct ?? 1.50);           // 1.5%
        $pbPct          = (float) ($setting->pb_pct ?? 0.50);           // 0.5%

        // ---------------------------------------------------------------------
        // 2. HITUNG SALDO AWAL (SALDO BERKESINAMBUNGAN DARI PERIODE LALU)
        // Rumus: SUM(Pemasukan < Periode) - SUM(Pengeluaran < Periode)
        // ---------------------------------------------------------------------
        $prevInQuery = KoinTransaction::where('status', KoinTransaction::STATUS_CONFIRMED);
        $prevOutQuery = Pentasyarufan::query();
        $prevKtrQuery = KontribusiStruktur::validDisetor();

        if ($currentPenthong) {
            $prevInQuery->where(function ($q) use ($currentPenthong, $startDate) {
                $q->where('penthong_id', '<', $currentPenthong->id)
                  ->orWhereNull('penthong_id')
                  ->whereDate('collection_date', '<', $startDate->toDateString());
            });

            $prevOutQuery->where(function ($q) use ($currentPenthong, $startDate) {
                $q->where('periode_pentong_id', '<', $currentPenthong->id)
                  ->orWhere('penthong_id', '<', $currentPenthong->id)
                  ->orWhere(function ($sub) use ($startDate) {
                      $sub->whereNull('periode_pentong_id')
                          ->whereNull('penthong_id')
                          ->whereDate('tanggal_penyaluran', '<', $startDate->toDateString());
                  });
            });

            $prevKtrQuery->where(function ($q) use ($currentPenthong, $startDate) {
                $q->where('periode_pentong_id', '<', $currentPenthong->id)
                  ->orWhereDate('tanggal_setor', '<', $startDate->toDateString());
            });
        } else {
            $prevInQuery->whereDate('collection_date', '<', $startDate->toDateString());
            $prevOutQuery->whereDate('tanggal_penyaluran', '<', $startDate->toDateString());
            $prevKtrQuery->whereDate('tanggal_setor', '<', $startDate->toDateString());
        }

        // Terapkan Data Scoping Ketat
        $isRantingScope = !empty($filters['ranting_id']);
        $isMwcScope     = !empty($filters['mwc_id']) && empty($filters['ranting_id']);
        $isPcScope      = empty($filters['ranting_id']) && empty($filters['mwc_id']);

        if ($isRantingScope) {
            $rId = (int) $filters['ranting_id'];
            $prevInQuery->where('ranting_id', $rId);
            $prevOutQuery->where('ranting_id', $rId);
            $prevKtrQuery->where('ranting_id', $rId);

            $prevInTotal  = (float) $prevInQuery->sum('amount');
            $prevOutTotal = (float) $prevOutQuery->sum('nominal_pencairan');
            $prevKtrTotal = (float) $prevKtrQuery->sum('total_disetor');

            // Jika untuk ranting dan belum ada manual kontribusi lalu, perhitungkan alokasi struktur lalu
            if ($prevKtrTotal <= 0 && $prevInTotal > 0) {
                // 20% operasional + 20% dari 80% netto = 36% dari bruto keluar ke operasional + MWC/PC/PW/PB
                $prevKtrTotal = round($prevInTotal * 0.36);
            }

            $saldoAwal = max(0, $prevInTotal - ($prevOutTotal + $prevKtrTotal));
        } elseif ($isMwcScope) {
            $mId = (int) $filters['mwc_id'];
            // Saldo Awal MWC = Kontribusi Masuk MWC Lalu - Pentasyarufan MWC Lalu
            $prevKtrQuery->where('mwc_id', $mId)->forTujuan('MWC');
            $prevInTotal = (float) $prevKtrQuery->sum('nominal_mwc');

            if ($prevInTotal <= 0) {
                // Fallback otomatisasi dari total koin ranting di bawah MWC ini lalu
                // Selalu gunakan setting legacy (12%) untuk mengunci saldo awal masa lalu (Grandfathering)
                $prevSetting = $prevPenthong ? $prevPenthong->getDistributionSetting() : DistributionSetting::getLegacy();
                $prevMwcPct = (float) ($prevSetting->mwc_pct ?? 12.00);
                $prevKoinMwc = (float) (clone $prevInQuery)->where('mwc_id', $mId)->sum('amount');
                if ($prevKoinMwc > 0) {
                    $prevInTotal = round(($prevKoinMwc * 0.8) * ($prevMwcPct / 100));
                }
            }

            $prevOutQuery->where('mwc_id', $mId)->where('tingkat', 'mwc');
            $prevOutTotal = (float) $prevOutQuery->sum('nominal_pencairan');

            $saldoAwal = max(0, $prevInTotal - $prevOutTotal);
        } else {
            // Level PC Cabang
            // Saldo Awal PC = Kontribusi Masuk PC Lalu - Pentasyarufan PC Lalu
            $prevKtrQuery->forTujuan('PC');
            $prevInTotal = (float) $prevKtrQuery->sum('nominal_pc');

            if ($prevInTotal <= 0) {
                // Fallback otomatisasi dari koin se-kabupaten lalu
                // Selalu gunakan setting legacy (6%) untuk mengunci saldo awal masa lalu (Grandfathering)
                $prevSetting = $prevPenthong ? $prevPenthong->getDistributionSetting() : DistributionSetting::getLegacy();
                $prevPcPct = (float) ($prevSetting->pc_pct ?? 6.00);
                $prevKoinAll = (float) (clone $prevInQuery)->sum('amount');
                if ($prevKoinAll > 0) {
                    $prevInTotal = round(($prevKoinAll * 0.8) * ($prevPcPct / 100));
                }
            }

            $prevOutQuery->where('tingkat', 'pc');
            $prevOutTotal = (float) $prevOutQuery->sum('nominal_pencairan');

            $saldoAwal = max(0, $prevInTotal - $prevOutTotal);
        }

        // ---------------------------------------------------------------------
        // 3. PEMASUKAN PERIODE INI (SISI KIRI / DEBET)
        // Baris Pertama Wajib: "Saldo Awal / Pindahan dari Periode Sebelumnya"
        // ---------------------------------------------------------------------
        $pemasukanList = [];

        $uraianSaldoAwal = $prevPenthong 
            ? "Saldo Awal / Pindahan dari {$prevPenthong->nama_periode}"
            : "Saldo Awal Kas KOIN NU";

        $pemasukanList[] = [
            'tanggal'       => $startDate->format('d/m/Y'),
            'raw_date'      => $startDate->toDateString(),
            'kode'          => '-',
            'uraian'        => $uraianSaldoAwal,
            'jumlah'        => $saldoAwal,
            'is_saldo_awal' => true,
        ];

        $totalPemasukanPeriodeIni = 0;
        $penthongTitle = $currentPenthong?->nama_periode ?? ($namaBulan . ' ' . $tahun);

        // =====================================================================
        // SUMBER DATA PEMASUKAN BERDASARKAN TINGKATAN (RANTING vs MWC vs PC)
        // =====================================================================
        if ($isRantingScope) {
            // A. LOGIKA RANTING: Pemasukan dari Setoran Koin Bruto Relawan/Munfiq
            $inQuery = KoinTransaction::with(['ranting', 'relawan'])
                ->where('status', KoinTransaction::STATUS_CONFIRMED)
                ->where('ranting_id', (int) $filters['ranting_id']);

            if ($currentPenthong) {
                $inQuery->where(function ($q) use ($currentPenthong, $startDate, $endDate) {
                    $q->where('penthong_id', $currentPenthong->id)
                      ->orWhere('periode_pentong_id', $currentPenthong->id)
                      ->orWhere(function ($sub) use ($startDate, $endDate) {
                          $sub->whereNull('penthong_id')
                              ->whereNull('periode_pentong_id')
                              ->whereBetween('collection_date', [$startDate->toDateString(), $endDate->toDateString()]);
                      });
                });
            } else {
                $inQuery->whereBetween('collection_date', [$startDate->toDateString(), $endDate->toDateString()]);
            }

            $inTransactions = $inQuery->orderBy('collection_date')->get();

            foreach ($inTransactions as $tx) {
                $amt = (float) $tx->amount;
                $totalPemasukanPeriodeIni += $amt;

                $rantingName = $tx->ranting?->name ?? 'Koin Cabang';
                $relawanName = $tx->relawan ? " ({$tx->relawan->nama})" : "";

                $pemasukanList[] = [
                    'tanggal'       => Carbon::parse($tx->collection_date)->format('d/m/Y'),
                    'raw_date'      => $tx->collection_date->toDateString(),
                    'kode'          => 'NU-PB',
                    'uraian'        => "Setoran KOIN NU - {$rantingName}{$relawanName}",
                    'jumlah'        => $amt,
                    'is_saldo_awal' => false,
                ];
            }
        } elseif ($isMwcScope) {
            // B. LOGIKA MWC: Pemasukan HANYA dari Dana Kontribusi / Bagi Hasil (12% Netto) dari Ranting
            $mwcId = (int) $filters['mwc_id'];

            // 1. Tarik dari transaksi kontribusi struktur resmi yang disetor oleh ranting
            $ktrInQuery = KontribusiStruktur::with('ranting')
                ->validDisetor()
                ->where('mwc_id', $mwcId)
                ->forTujuan('MWC');

            if ($currentPenthong) {
                $ktrInQuery->forPeriode($currentPenthong->id);
            } else {
                $ktrInQuery->whereBetween('tanggal_setor', [$startDate->toDateString(), $endDate->toDateString()]);
            }

            $ktrInList = $ktrInQuery->orderBy('tanggal_setor')->get();
            $rantingsHandled = [];

            foreach ($ktrInList as $ktr) {
                $mwcAmount = ($ktr->tujuan_setoran === 'MWC' && (float) $ktr->nominal_mwc <= 0)
                    ? (float) $ktr->total_disetor
                    : (float) $ktr->nominal_mwc;

                if ($mwcAmount > 0) {
                    $totalPemasukanPeriodeIni += $mwcAmount;
                    $rantingName = $ktr->ranting?->name ?? 'Ranting';

                    $pemasukanList[] = [
                        'tanggal'       => Carbon::parse($ktr->tanggal_setor)->format('d/m/Y'),
                        'raw_date'      => $ktr->tanggal_setor->toDateString(),
                        'kode'          => 'NU-KONTRIBUSI',
                        'uraian'        => "Bagi Hasil Koin NU - Ranting {$rantingName} Periode {$penthongTitle}",
                        'jumlah'        => $mwcAmount,
                        'is_saldo_awal' => false,
                        'is_auto'       => false,
                    ];
                    $rantingsHandled[$ktr->ranting_id] = true;
                }
            }

            // 2. Fallback Otomatisasi: Untuk ranting di wilayah MWC ini yang koinnya sudah masuk tapi belum input manual kontribusi
            $rantingsInMwc = Ranting::where('mwc_id', $mwcId)->orderBy('name')->get();
            foreach ($rantingsInMwc as $r) {
                if (isset($rantingsHandled[$r->id])) {
                    continue;
                }

                $rKoinQuery = KoinTransaction::where('status', KoinTransaction::STATUS_CONFIRMED)
                    ->where('ranting_id', $r->id);

                if ($currentPenthong) {
                    $rKoinQuery->where(function ($q) use ($currentPenthong, $startDate, $endDate) {
                        $q->where('penthong_id', $currentPenthong->id)
                          ->orWhere('periode_pentong_id', $currentPenthong->id)
                          ->orWhere(function ($sub) use ($startDate, $endDate) {
                              $sub->whereNull('penthong_id')
                                  ->whereNull('periode_pentong_id')
                                  ->whereBetween('collection_date', [$startDate->toDateString(), $endDate->toDateString()]);
                          });
                    });
                } else {
                    $rKoinQuery->whereBetween('collection_date', [$startDate->toDateString(), $endDate->toDateString()]);
                }

                $rGross = (float) $rKoinQuery->sum('amount');
                if ($rGross > 0) {
                    $rNetto = max(0, $rGross * (1 - ($operasionalPct / 100)));
                    $rMwcShare = round($rNetto * ($mwcPct / 100));

                    if ($rMwcShare > 0) {
                        $totalPemasukanPeriodeIni += $rMwcShare;
                        $autoDate = $startDate->copy()->addDays(2)->format('d/m/Y');
                        $autoRawDate = $startDate->copy()->addDays(2)->toDateString();

                        $pemasukanList[] = [
                            'tanggal'       => $autoDate,
                            'raw_date'      => $autoRawDate,
                            'kode'          => 'NU-KONTRIBUSI',
                            'uraian'        => "Bagi Hasil Koin NU - Ranting {$r->name} Periode {$penthongTitle}",
                            'jumlah'        => (float) $rMwcShare,
                            'is_saldo_awal' => false,
                            'is_auto'       => true,
                        ];
                    }
                }
            }
        } else {
            // C. LOGIKA PC CABANG: Pemasukan HANYA dari Dana Kontribusi / Bagi Hasil (6% Netto)
            // 1. Tarik dari transaksi kontribusi struktur yang ditujukan untuk PC
            $ktrInQuery = KontribusiStruktur::with(['mwc', 'ranting'])
                ->validDisetor()
                ->forTujuan('PC');

            if ($currentPenthong) {
                $ktrInQuery->forPeriode($currentPenthong->id);
            } else {
                $ktrInQuery->whereBetween('tanggal_setor', [$startDate->toDateString(), $endDate->toDateString()]);
            }

            $ktrInList = $ktrInQuery->orderBy('tanggal_setor')->get();
            $mwcsHandled = [];

            foreach ($ktrInList as $ktr) {
                $pcAmount = ($ktr->tujuan_setoran === 'PC' && (float) $ktr->nominal_pc <= 0)
                    ? (float) $ktr->total_disetor
                    : (float) $ktr->nominal_pc;

                if ($pcAmount > 0) {
                    $totalPemasukanPeriodeIni += $pcAmount;
                    $mwcName = $ktr->mwc?->name ?? 'Kecamatan';

                    $pemasukanList[] = [
                        'tanggal'       => Carbon::parse($ktr->tanggal_setor)->format('d/m/Y'),
                        'raw_date'      => $ktr->tanggal_setor->toDateString(),
                        'kode'          => 'NU-KONTRIBUSI',
                        'uraian'        => "Bagi Hasil Koin NU - MWC {$mwcName} Periode {$penthongTitle}",
                        'jumlah'        => $pcAmount,
                        'is_saldo_awal' => false,
                        'is_auto'       => false,
                    ];
                    $mwcsHandled[$ktr->mwc_id] = true;
                }
            }

            // 2. Fallback Otomatisasi per MWC jika belum ada input manual kontribusi
            $allMwcs = Mwc::orderBy('name')->get();
            foreach ($allMwcs as $mwc) {
                if (isset($mwcsHandled[$mwc->id])) {
                    continue;
                }

                $mKoinQuery = KoinTransaction::where('status', KoinTransaction::STATUS_CONFIRMED)
                    ->where('mwc_id', $mwc->id);

                if ($currentPenthong) {
                    $mKoinQuery->where(function ($q) use ($currentPenthong, $startDate, $endDate) {
                        $q->where('penthong_id', $currentPenthong->id)
                          ->orWhere('periode_pentong_id', $currentPenthong->id)
                          ->orWhere(function ($sub) use ($startDate, $endDate) {
                              $sub->whereNull('penthong_id')
                                  ->whereNull('periode_pentong_id')
                                  ->whereBetween('collection_date', [$startDate->toDateString(), $endDate->toDateString()]);
                          });
                    });
                } else {
                    $mKoinQuery->whereBetween('collection_date', [$startDate->toDateString(), $endDate->toDateString()]);
                }

                $mGross = (float) $mKoinQuery->sum('amount');
                if ($mGross > 0) {
                    $mNetto = max(0, $mGross * (1 - ($operasionalPct / 100)));
                    $pcShare = round($mNetto * ($pcPct / 100));

                    if ($pcShare > 0) {
                        $totalPemasukanPeriodeIni += $pcShare;
                        $autoDate = $startDate->copy()->addDays(3)->format('d/m/Y');
                        $autoRawDate = $startDate->copy()->addDays(3)->toDateString();

                        $pemasukanList[] = [
                            'tanggal'       => $autoDate,
                            'raw_date'      => $autoRawDate,
                            'kode'          => 'NU-KONTRIBUSI',
                            'uraian'        => "Bagi Hasil Koin NU - MWC {$mwc->name} Periode {$penthongTitle}",
                            'jumlah'        => (float) $pcShare,
                            'is_saldo_awal' => false,
                            'is_auto'       => true,
                        ];
                    }
                }
            }
        }

        // ---------------------------------------------------------------------
        // 4. PENGELUARAN PERIODE INI (SISI KANAN / KREDIT)
        // ---------------------------------------------------------------------
        $pengeluaranList = [];
        $totalPengeluaranPeriodeIni = 0;

        // A. Realisasi Pentasyarufan 5 Pilar Program Berdasarkan Tingkatan
        $outQuery = Pentasyarufan::with(['pilarProgram', 'programKerja']);

        if ($currentPenthong) {
            $outQuery->where(function ($q) use ($currentPenthong, $startDate, $endDate) {
                $q->where('periode_pentong_id', $currentPenthong->id)
                  ->orWhere('penthong_id', $currentPenthong->id)
                  ->orWhere(function ($sub) use ($startDate, $endDate) {
                      $sub->whereNull('periode_pentong_id')
                          ->whereNull('penthong_id')
                          ->whereBetween('tanggal_penyaluran', [$startDate->toDateString(), $endDate->toDateString()]);
                  });
            });
        } else {
            $outQuery->whereBetween('tanggal_penyaluran', [$startDate->toDateString(), $endDate->toDateString()]);
        }

        if ($isRantingScope) {
            $outQuery->where('ranting_id', (int) $filters['ranting_id']);
        } elseif ($isMwcScope) {
            $outQuery->where('mwc_id', (int) $filters['mwc_id'])->where('tingkat', 'mwc');
        } else {
            $outQuery->where('tingkat', 'pc');
        }

        if (!empty($filters['tingkat']) && $filters['tingkat'] !== 'all') {
            $outQuery->where('tingkat', $filters['tingkat']);
        }

        $outTransactions = $outQuery->orderBy('tanggal_penyaluran')->get();

        foreach ($outTransactions as $out) {
            $nominal = (float) $out->nominal_pencairan;
            $totalPengeluaranPeriodeIni += $nominal;

            $coaCode = self::mapPilarToCoa($out->pilarProgram?->kode);
            $prokerName = $out->programKerja ? " - {$out->programKerja->nama_kegiatan}" : "";
            $uraian = "{$out->penerima_manfaat}{$prokerName}";

            $pengeluaranList[] = [
                'tanggal'  => Carbon::parse($out->tanggal_penyaluran)->format('d/m/Y'),
                'raw_date' => $out->tanggal_penyaluran->toDateString(),
                'kode'     => $coaCode,
                'uraian'   => $uraian,
                'jumlah'   => $nominal,
                'kategori' => 'pentasyarufan',
                'is_auto'  => false,
            ];
        }

        // B. Alokasi Pembagian Struktur & Operasional (HANYA BERLAKU DI TINGKAT RANTING)
        // MWC dan PC adalah penerima bagi hasil, bukan pembagi bagi hasil di kas koin ranting.
        if ($isRantingScope) {
            $ktrQuery = KontribusiStruktur::validDisetor()
                ->where('ranting_id', (int) $filters['ranting_id']);

            if ($currentPenthong) {
                $ktrQuery->forPeriode($currentPenthong->id);
            } else {
                $ktrQuery->whereBetween('tanggal_setor', [$startDate->toDateString(), $endDate->toDateString()]);
            }

            $ktrTransactions = $ktrQuery->orderBy('tanggal_setor')->get();

            if ($ktrTransactions->isNotEmpty()) {
                // Gunakan transaksi manual yang sudah tercatat
                foreach ($ktrTransactions as $ktr) {
                    $nominalKtr = (float) $ktr->total_disetor;
                    $totalPengeluaranPeriodeIni += $nominalKtr;

                    $uraianKtr = "Setoran Kontribusi Koin ke MWC/PC/PW/PB - {$ktr->nomor_transaksi}";
                    if ($ktr->catatan) {
                        $uraianKtr .= " ({$ktr->catatan})";
                    }

                    $pengeluaranList[] = [
                        'tanggal'  => Carbon::parse($ktr->tanggal_setor)->format('d/m/Y'),
                        'raw_date' => $ktr->tanggal_setor->toDateString(),
                        'kode'     => 'NU-KONTRIBUSI',
                        'uraian'   => $uraianKtr,
                        'jumlah'   => $nominalKtr,
                        'kategori' => 'kontribusi_struktur',
                        'is_auto'  => false,
                    ];

                    if ((float) $ktr->nominal_operasional > 0) {
                        $nomOps = (float) $ktr->nominal_operasional;
                        $totalPengeluaranPeriodeIni += $nomOps;
                        $pengeluaranList[] = [
                            'tanggal'  => Carbon::parse($ktr->tanggal_setor)->format('d/m/Y'),
                            'raw_date' => $ktr->tanggal_setor->toDateString(),
                            'kode'     => 'NU-O',
                            'uraian'   => "Alokasi Hak Amil & Biaya Operasional Ranting ({$ktr->persentase_operasional}% Bruto)",
                            'jumlah'   => $nomOps,
                            'kategori' => 'operasional_amil',
                            'is_auto'  => false,
                        ];
                    }
                }
            } elseif ($totalPemasukanPeriodeIni > 0) {
                // Otomatisasi alokasi keluar di BKU Ranting jika belum input manual
                $operasional = round(($totalPemasukanPeriodeIni * $operasionalPct) / 100);
                $netto = max(0, $totalPemasukanPeriodeIni - $operasional);

                $nominalMwc = round(($netto * $mwcPct) / 100);
                $nominalPc  = round(($netto * $pcPct) / 100);
                $nominalPw  = round(($netto * $pwPct) / 100);
                $nominalPb  = round(($netto * $pbPct) / 100);

                $autoDate = $startDate->copy()->addDay()->format('d/m/Y');
                $autoRawDate = $startDate->copy()->addDay()->toDateString();

                // 1. Pembagian ke MWC NU (12%)
                $pengeluaranList[] = [
                    'tanggal'  => $autoDate,
                    'raw_date' => $autoRawDate,
                    'kode'     => 'NU-KONTRIBUSI',
                    'uraian'   => "Pembagian Hasil Koin ke MWC NU ({$mwcPct}% Netto)",
                    'jumlah'   => (float) $nominalMwc,
                    'kategori' => 'kontribusi_struktur',
                    'is_auto'  => true,
                ];
                $totalPengeluaranPeriodeIni += $nominalMwc;

                // 2. Pembagian ke PC LAZISNU Lamongan (6%)
                $pengeluaranList[] = [
                    'tanggal'  => $autoDate,
                    'raw_date' => $autoRawDate,
                    'kode'     => 'NU-KONTRIBUSI',
                    'uraian'   => "Pembagian Hasil Koin ke PC LAZISNU Lamongan ({$pcPct}% Netto)",
                    'jumlah'   => (float) $nominalPc,
                    'kategori' => 'kontribusi_struktur',
                    'is_auto'  => true,
                ];
                $totalPengeluaranPeriodeIni += $nominalPc;

                // 3. Pembagian ke PW LAZISNU Jawa Timur (1.5%)
                $pengeluaranList[] = [
                    'tanggal'  => $autoDate,
                    'raw_date' => $autoRawDate,
                    'kode'     => 'NU-KONTRIBUSI',
                    'uraian'   => "Pembagian Hasil Koin ke PW LAZISNU Jatim ({$pwPct}% Netto)",
                    'jumlah'   => (float) $nominalPw,
                    'kategori' => 'kontribusi_struktur',
                    'is_auto'  => true,
                ];
                $totalPengeluaranPeriodeIni += $nominalPw;

                // 4. Pembagian ke PBNU Pusat (0.5%)
                $pengeluaranList[] = [
                    'tanggal'  => $autoDate,
                    'raw_date' => $autoRawDate,
                    'kode'     => 'NU-KONTRIBUSI',
                    'uraian'   => "Pembagian Hasil Koin ke PBNU Pusat ({$pbPct}% Netto)",
                    'jumlah'   => (float) $nominalPb,
                    'kategori' => 'kontribusi_struktur',
                    'is_auto'  => true,
                ];
                $totalPengeluaranPeriodeIni += $nominalPb;

                // 5. Hak Amil & Operasional Ranting (20%)
                $pengeluaranList[] = [
                    'tanggal'  => $autoDate,
                    'raw_date' => $autoRawDate,
                    'kode'     => 'NU-O',
                    'uraian'   => "Alokasi Hak Amil & Biaya Operasional Ranting ({$operasionalPct}% Bruto)",
                    'jumlah'   => (float) $operasional,
                    'kategori' => 'operasional_amil',
                    'is_auto'  => true,
                ];
                $totalPengeluaranPeriodeIni += $operasional;
            }
        }

        // Urutkan pengeluaran berdasarkan tanggal transaksi
        usort($pengeluaranList, fn ($a, $b) => strcmp($a['raw_date'], $b['raw_date']));

        // ---------------------------------------------------------------------
        // 5. KALKULASI AKUNTANSI FOOTER (T-ACCOUNT BALANCE)
        // ---------------------------------------------------------------------
        // 1. Jumlah Pemasukan = Saldo Awal + Total Pemasukan Periode Ini
        $jumlahPemasukan = $saldoAwal + $totalPemasukanPeriodeIni;

        // 2. Jumlah Pengeluaran = Total Pengeluaran Periode Ini
        $jumlahPengeluaran = $totalPengeluaranPeriodeIni;

        // 3. Saldo Buku (Saldo Akhir Kas Ranting) = Jumlah Pemasukan - Jumlah Pengeluaran
        // CATATAN PENTING: Nilai Saldo Buku ini PERSIS SAMA dengan SISA PAGU 5 PILAR RANTING!
        $saldoBuku = $jumlahPemasukan - $jumlahPengeluaran;

        // Total Sisi Kanan Penyeimbang (Pengeluaran + Saldo Buku)
        $totalSeimbangKanan = $jumlahPengeluaran + $saldoBuku; // Selalu seimbang dengan $jumlahPemasukan

        // ---------------------------------------------------------------------
        // 6. PENYEIMBANGAN BARIS TABEL (ROW BALANCING / T-ACCOUNT LAYOUT)
        // ---------------------------------------------------------------------
        $countIn  = count($pemasukanList);
        $countOut = count($pengeluaranList);
        $maxRows  = max($countIn, $countOut, 1);

        $tAccountRows = [];
        for ($i = 0; $i < $maxRows; $i++) {
            $tAccountRows[] = [
                'debet'  => $pemasukanList[$i] ?? null,
                'kredit' => $pengeluaranList[$i] ?? null,
            ];
        }

        return [
            'penthong'                  => $currentPenthong,
            'prev_penthong'             => $prevPenthong,
            'bulan'                     => $bulan,
            'tahun'                     => $tahun,
            'nama_bulan'                => $namaBulan,
            'periode_title'             => $periodeTitle,
            'start_date'                => $startDate,
            'end_date'                  => $endDate,
            'saldo_awal'                => $saldoAwal,
            'total_pemasukan_bulan_ini' => $totalPemasukanPeriodeIni,
            'jumlah_pemasukan'          => $jumlahPemasukan,
            'total_pengeluaran'         => $totalPengeluaranPeriodeIni,
            'saldo_buku'                => $saldoBuku,
            'total_seimbang_kanan'      => $totalSeimbangKanan,
            'pemasukan_list'            => $pemasukanList,
            'pengeluaran_list'          => $pengeluaranList,
            't_account_rows'            => $tAccountRows,
            'count_in'                  => $countIn,
            'count_out'                 => $countOut,
            'max_rows'                  => $maxRows,
            'is_ranting_scope'          => $isRantingScope,
            'is_mwc_scope'              => $isMwcScope,
            'is_pc_scope'               => $isPcScope,
            'scope_level'               => $isRantingScope ? 'ranting' : ($isMwcScope ? 'mwc' : 'pc'),
            'operasional_pct'           => $operasionalPct,
            'ranting_pct'               => $rantingPct,
            'mwc_pct'                   => $mwcPct,
            'pc_pct'                    => $pcPct,
            'pw_pct'                    => $pwPct,
            'pb_pct'                    => $pbPct,
        ];
    }

    /**
     * Backward-compatibility wrapper untuk controller lama
     */
    public function getBkuData(int $bulan, int $tahun, array $filters = []): array
    {
        $penthongId = $filters['penthong_id'] ?? null;
        if (!$penthongId) {
            $penthong = Penthong::where('bulan', $bulan)->where('tahun', $tahun)->first();
            $penthongId = $penthong?->id;
        }

        return $this->getBkuDataByPenthong($penthongId, $filters);
    }
}
