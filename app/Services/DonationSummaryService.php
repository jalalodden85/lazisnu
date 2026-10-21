<?php

namespace App\Services;

use App\Models\KoinTransaction;
use App\Models\OnlineDonation;
use Illuminate\Support\Facades\DB;

class DonationSummaryService
{
    /**
     * Hitung rekapitulasi real-time 3 kategori pemasukan & Grand Total
     * Hanya menghitung transaksi dengan status 'verified' / 'confirmed' / 'success'
     *
     * @return array
     */
    public function getSummaryTotals(): array
    {
        // 1. Total Zakat: SUM() dari transaksi donasi online/offline yang berkategori 'Zakat'
        $totalZakat = (float) OnlineDonation::whereIn('payment_status', ['verified', 'success'])
            ->where(function ($q) {
                $q->where('kategori', 'like', '%zakat%')
                  ->orWhereHas('campaign', function ($camp) {
                      $camp->where('kategori_judul', 'like', '%zakat%')
                           ->orWhere('title', 'like', '%zakat%')
                           ->orWhereHas('category', function ($c) {
                               $c->where('name', 'like', '%zakat%');
                           });
                  });
            })
            ->sum(DB::raw('COALESCE(NULLIF(nominal_terverifikasi, 0), amount)'));

        // 2. Total KOIN NU: SUM() dari penerimaan kaleng KOIN NU dari seluruh ranting/periode (+ online KOIN NU)
        $totalKoinKaleng = (float) KoinTransaction::where('status', KoinTransaction::STATUS_CONFIRMED)->sum('amount');
        $totalKoinOnline = (float) OnlineDonation::whereIn('payment_status', ['verified', 'success'])
            ->where(function ($q) {
                $q->where('kategori', 'like', '%koin%')
                  ->orWhereHas('campaign', function ($camp) {
                      $camp->where('kategori_judul', 'like', '%koin%')
                           ->orWhere('title', 'like', '%koin%');
                  });
            })
            ->sum(DB::raw('COALESCE(NULLIF(nominal_terverifikasi, 0), amount)'));
        $totalKoinNu = $totalKoinKaleng + $totalKoinOnline;

        // 3. Total Infaq/Sedekah/Donasi: SUM() dari donasi (termasuk Kampanye/Program) berkategori Infaq, Sedekah, Kemanusiaan, dll (selain Zakat dan KOIN NU)
        $totalInfaqSedekah = (float) OnlineDonation::whereIn('payment_status', ['verified', 'success'])
            ->where(function ($q) {
                $q->where('kategori', 'not like', '%zakat%')
                  ->where('kategori', 'not like', '%koin%')
                  ->orWhereNull('kategori');
            })
            ->whereDoesntHave('campaign', function ($camp) {
                $camp->where('kategori_judul', 'like', '%zakat%')
                     ->orWhere('kategori_judul', 'like', '%koin%')
                     ->orWhere('title', 'like', '%zakat%')
                     ->orWhere('title', 'like', '%koin%')
                     ->orWhereHas('category', function ($c) {
                         $c->where('name', 'like', '%zakat%');
                     });
            })
            ->sum(DB::raw('COALESCE(NULLIF(nominal_terverifikasi, 0), amount)'));

        // 4. Grand Total (Total Keseluruhan): Akumulasi penjumlahan dari (Total Zakat + Total Infaq/Sedekah + Total KOIN NU)
        $grandTotal = $totalZakat + $totalInfaqSedekah + $totalKoinNu;

        return [
            'total_zakat'         => $totalZakat,
            'total_infaq_sedekah' => $totalInfaqSedekah,
            'total_koin_nu'       => $totalKoinNu,
            'total_koin_kaleng'   => $totalKoinKaleng,
            'total_koin_online'   => $totalKoinOnline,
            'grand_total'         => $grandTotal,
        ];
    }
}
