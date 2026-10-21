<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('online_donations', function (Blueprint $table) {
            // 1. Kolom Alamat Lengkap Donatur
            if (!Schema::hasColumn('online_donations', 'alamat')) {
                $table->string('alamat', 255)->nullable()->after('donor_phone')
                      ->comment('Alamat lengkap donatur (Desa/Kecamatan/Kabupaten)');
            }

            // 2. Kolom Kategori Donasi (Zakat, Infaq, Shadaqah, Wakaf, KOIN NU, Kampanye Bencana)
            if (!Schema::hasColumn('online_donations', 'kategori')) {
                $table->string('kategori', 50)->default('Infaq')->after('campaign_id')
                      ->comment('Kategori ZIS: Zakat, Infaq, Shadaqah, Wakaf, KOIN NU, Kampanye Bencana');
            }

            // 3. Composite Index untuk query cepat server-side
            $table->index(['payment_status', 'kategori'], 'idx_donations_status_kategori');
            $table->index(['payment_status', 'created_at'], 'idx_donations_status_created');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('online_donations', function (Blueprint $table) {
            $table->dropIndex('idx_donations_status_kategori');
            $table->dropIndex('idx_donations_status_created');

            if (Schema::hasColumn('online_donations', 'alamat')) {
                $table->dropColumn('alamat');
            }

            if (Schema::hasColumn('online_donations', 'kategori')) {
                $table->dropColumn('kategori');
            }
        });
    }
};
