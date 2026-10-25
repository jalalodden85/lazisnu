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
        $now = now();

        // 1. Pastikan record pengaturan Legacy (MWC 12%, PC 6%) tersimpan untuk Grandfathering
        $legacySetting = DB::table('distribution_settings')
            ->where('mwc_pct', 12.00)
            ->where('pc_pct', 6.00)
            ->first();

        if (!$legacySetting) {
            $legacyId = DB::table('distribution_settings')->insertGetId([
                'name'            => 'Standar Tasharruf KOIN NU Lamongan (Legacy s/d September 2026)',
                'operasional_pct' => 20.00,
                'ranting_pct'     => 80.00,
                'mwc_pct'         => 12.00,
                'pc_pct'          => 6.00,
                'pw_pct'          => 1.50,
                'pb_pct'          => 0.50,
                'is_active'       => false,
                'notes'           => 'Skema pembagian lama untuk periode penthong tertutup (Grandfathering)',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        } else {
            $legacyId = $legacySetting->id;
            DB::table('distribution_settings')->where('id', $legacyId)->update([
                'is_active'  => false,
                'updated_at' => $now,
            ]);
        }

        // 2. Buat atau perbarui Pengaturan Aktif Baru (MWC 13%, PC 5%)
        $activeSetting = DB::table('distribution_settings')
            ->where('mwc_pct', 13.00)
            ->where('pc_pct', 5.00)
            ->first();

        if (!$activeSetting) {
            $activeId = DB::table('distribution_settings')->insertGetId([
                'name'            => 'Standar Tasharruf KOIN NU Lamongan (Revisi MWC 13% PC 5%)',
                'operasional_pct' => 20.00,
                'ranting_pct'     => 80.00,
                'mwc_pct'         => 13.00,
                'pc_pct'          => 5.00,
                'pw_pct'          => 1.50,
                'pb_pct'          => 0.50,
                'is_active'       => true,
                'notes'           => 'Skema resmi baru: MWC naik menjadi 13% dan PC turun menjadi 5%. Berlaku untuk periode aktif dan masa depan.',
                'created_at'      => $now,
                'updated_at'      => $now,
            ]);
        } else {
            $activeId = $activeSetting->id;
            DB::table('distribution_settings')->where('id', $activeId)->update([
                'is_active'  => true,
                'updated_at' => $now,
            ]);
        }

        // Drop view jika ada untuk menghindari 'no such table' di SQLite saat rename __temp__penthongs
        DB::statement("DROP VIEW IF EXISTS vw_saldo_pentasyarufan");

        // 3. Tambahkan kolom foreign key distribution_setting_id pada tabel penthongs (jika belum ada)
        if (Schema::hasTable('penthongs') && !Schema::hasColumn('penthongs', 'distribution_setting_id')) {
            Schema::table('penthongs', function (Blueprint $table) {
                $table->foreignId('distribution_setting_id')
                    ->nullable()
                    ->after('status')
                    ->comment('Binding skema persentase nisbah pada periode penthong bersangkutan')
                    ->constrained('distribution_settings')
                    ->nullOnDelete();
            });
        }

        // Recreate view vw_saldo_pentasyarufan
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

        // 4. Backfill Data Grandfathering:
        // - Periode yang sudah tutup ('tutup') dikunci ke legacy setting (MWC 12%, PC 6%)
        // - Periode yang sedang aktif ('aktif') dikunci ke active setting (MWC 13%, PC 5%)
        if (Schema::hasTable('penthongs')) {
            DB::table('penthongs')
                ->where(function ($q) {
                    $q->where('status', 'tutup')
                      ->orWhere('is_active', false);
                })
                ->whereNull('distribution_setting_id')
                ->update(['distribution_setting_id' => $legacyId]);

            DB::table('penthongs')
                ->where(function ($q) {
                    $q->where('status', 'aktif')
                      ->orWhere('is_active', true);
                })
                ->whereNull('distribution_setting_id')
                ->update(['distribution_setting_id' => $activeId]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('penthongs') && Schema::hasColumn('penthongs', 'distribution_setting_id')) {
            Schema::table('penthongs', function (Blueprint $table) {
                $table->dropForeign(['distribution_setting_id']);
                $table->dropColumn('distribution_setting_id');
            });
        }

        // Kembalikan status aktif ke setting lama
        DB::table('distribution_settings')
            ->where('mwc_pct', 12.00)
            ->where('pc_pct', 6.00)
            ->update(['is_active' => true]);

        DB::table('distribution_settings')
            ->where('mwc_pct', 13.00)
            ->where('pc_pct', 5.00)
            ->update(['is_active' => false]);
    }
};
