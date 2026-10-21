<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Patch kategori transaksi donasi kampanye agar sesuai dengan kategori_judul kampanye masing-masing (contoh: "Donasi NTT", "Masjid Gus Dur").
     */
    public function up(): void
    {
        $campaigns = DB::table('campaigns')
            ->whereNotNull('kategori_judul')
            ->where('kategori_judul', '!=', '')
            ->whereNotIn(DB::raw('LOWER(kategori_judul)'), ['infaq', 'zakat'])
            ->get(['id', 'kategori_judul']);

        foreach ($campaigns as $camp) {
            DB::table('online_donations')
                ->where('campaign_id', $camp->id)
                ->where(function ($q) {
                    $q->whereNull('kategori')
                      ->orWhereRaw('LOWER(kategori) != ?', ['zakat']);
                })
                ->whereNotIn('payment_method', ['transfer_bsi', 'qris_bsi'])
                ->update([
                    'kategori'   => $camp->kategori_judul,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
