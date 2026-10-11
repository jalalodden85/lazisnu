<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pentasyarufans', function (Blueprint $table) {
            $table->foreignId('penthong_id')
                ->nullable()
                ->after('nomor_transaksi')
                ->comment('Sumber Dana: Relasi ke Periode Penthong')
                ->constrained('penthongs')
                ->nullOnDelete();

            $table->foreignId('periode_pentong_id')
                ->nullable()
                ->after('penthong_id')
                ->comment('Alias periode_pentong_id untuk kompatibilitas penamaan')
                ->constrained('penthongs')
                ->nullOnDelete();

            $table->decimal('potongan_operasional_pct', 5, 2)
                ->default(20.00)
                ->after('nominal_pencairan')
                ->comment('Persentase hak amil / operasional yang berlaku saat pentasyarufan');

            $table->index(['penthong_id', 'pilar_program_id']);
        });

        // Hubungkan pentasyarufan yang sudah ada ke periode penthong aktif terbaru (jika ada)
        $latestPenthong = DB::table('penthongs')->where('is_active', true)->first() 
            ?? DB::table('penthongs')->latest('id')->first();

        if ($latestPenthong) {
            DB::table('pentasyarufans')
                ->whereNull('penthong_id')
                ->update([
                    'penthong_id'        => $latestPenthong->id,
                    'periode_pentong_id' => $latestPenthong->id,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pentasyarufans', function (Blueprint $table) {
            $table->dropForeign(['penthong_id']);
            $table->dropForeign(['periode_pentong_id']);
            $table->dropIndex(['penthong_id', 'pilar_program_id']);
            $table->dropColumn(['penthong_id', 'periode_pentong_id', 'potongan_operasional_pct']);
        });
    }
};
