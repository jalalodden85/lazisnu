<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     * Data Patching: Mengoreksi data transaksi donasi Zakat lama yang terlanjur tercatat sebagai Infaq.
     */
    public function up(): void
    {
        // 1. Ubah transaksi dengan rekening tujuan BSI / QRIS BSI menjadi 'zakat'
        DB::table('online_donations')
            ->whereIn('payment_method', ['transfer_bsi', 'qris_bsi'])
            ->where(function ($q) {
                $q->whereNull('kategori')
                  ->orWhereRaw('LOWER(kategori) != ?', ['zakat']);
            })
            ->update([
                'kategori'   => 'zakat',
                'updated_at' => now(),
            ]);

        // 2. Pastikan transaksi dengan rekening tujuan BRI / QRIS BRI tercatat sebagai 'infaq'
        DB::table('online_donations')
            ->whereIn('payment_method', ['transfer_bri', 'qris', 'qris_bri', 'qris_bni'])
            ->where(function ($q) {
                $q->whereNull('kategori')
                  ->orWhereRaw('LOWER(kategori) = ?', ['zakat']);
            })
            ->update([
                'kategori'   => 'infaq',
                'updated_at' => now(),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data patching tidak perlu direverse karena sifatnya perbaikan integritas data syariat
    }
};
