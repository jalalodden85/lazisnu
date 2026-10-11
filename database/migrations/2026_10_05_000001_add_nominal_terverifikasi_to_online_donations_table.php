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
            if (!Schema::hasColumn('online_donations', 'nominal_terverifikasi')) {
                $table->decimal('nominal_terverifikasi', 15, 2)->nullable()->after('total_amount')->comment('Nominal mutasi riil yang disetujui');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('online_donations', function (Blueprint $table) {
            if (Schema::hasColumn('online_donations', 'nominal_terverifikasi')) {
                $table->dropColumn('nominal_terverifikasi');
            }
        });
    }
};
