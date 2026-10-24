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
        Schema::table('kontribusi_strukturs', function (Blueprint $table) {
            if (!Schema::hasColumn('kontribusi_strukturs', 'tujuan_setoran')) {
                $table->string('tujuan_setoran', 30)->default('SEMUA')->after('tujuan_struktur')->comment('Tujuan setoran: MWC, PC, PW, PBNU, atau SEMUA');
            }
            if (!Schema::hasIndex('kontribusi_strukturs', 'idx_ktr_tujuan_pentong')) {
                $table->index(['tujuan_setoran', 'periode_pentong_id'], 'idx_ktr_tujuan_pentong');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('kontribusi_strukturs', function (Blueprint $table) {
            if (Schema::hasIndex('kontribusi_strukturs', 'idx_ktr_tujuan_pentong')) {
                $table->dropIndex('idx_ktr_tujuan_pentong');
            }
            if (Schema::hasColumn('kontribusi_strukturs', 'tujuan_setoran')) {
                $table->dropColumn('tujuan_setoran');
            }
        });
    }
};
