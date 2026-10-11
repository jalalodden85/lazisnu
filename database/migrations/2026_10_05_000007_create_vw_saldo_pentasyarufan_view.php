<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("DROP VIEW IF EXISTS vw_saldo_pentasyarufan");
        DB::statement("
            CREATE VIEW vw_saldo_pentasyarufan AS
            SELECT 
                p.penthong_id,
                pt.nama_periode,
                pt.bulan,
                pt.tahun,
                p.pilar_program_id,
                pil.kode AS pilar_kode,
                pil.nama_pilar,
                pil.target_persentase,
                COUNT(p.id) AS total_transaksi,
                COALESCE(SUM(p.nominal_pencairan), 0) AS total_pentasyarufan
            FROM pentasyarufans p
            LEFT JOIN penthongs pt ON p.penthong_id = pt.id
            LEFT JOIN pilar_programs pil ON p.pilar_program_id = pil.id
            GROUP BY 
                p.penthong_id, 
                pt.nama_periode, 
                pt.bulan, 
                pt.tahun, 
                p.pilar_program_id, 
                pil.kode, 
                pil.nama_pilar, 
                pil.target_persentase
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP VIEW IF EXISTS vw_saldo_pentasyarufan");
    }
};
