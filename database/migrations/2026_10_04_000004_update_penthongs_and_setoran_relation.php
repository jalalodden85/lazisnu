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
        // 1. Tambah kolom is_active pada tabel penthongs (periode pentong)
        if (!Schema::hasColumn('penthongs', 'is_active')) {
            Schema::table('penthongs', function (Blueprint $table) {
                $table->boolean('is_active')->default(false)->after('status');
                $table->index(['is_active']);
            });

            // Set is_active = true untuk penthong berstatus aktif/open
            DB::table('penthongs')
                ->whereIn('status', ['aktif', 'open'])
                ->update(['is_active' => true]);
        }

        // 2. Tambah kolom periode_pentong_id pada tabel koin_transactions (setoran koin)
        if (!Schema::hasColumn('koin_transactions', 'periode_pentong_id')) {
            Schema::table('koin_transactions', function (Blueprint $table) {
                $table->foreignId('periode_pentong_id')
                      ->nullable()
                      ->after('penthong_id')
                      ->constrained('penthongs')
                      ->nullOnDelete();
                $table->index('periode_pentong_id');
            });

            // Sinkronisasi data awal periode_pentong_id = penthong_id
            DB::table('koin_transactions')
                ->whereNotNull('penthong_id')
                ->update(['periode_pentong_id' => DB::raw('penthong_id')]);
        }

        // 3. Buat View Alias database jika menggunakan MySQL (setoran_koin & periode_pentong)
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("CREATE OR REPLACE VIEW periode_pentong AS SELECT * FROM penthongs");
                DB::statement("CREATE OR REPLACE VIEW setoran_koin AS SELECT * FROM koin_transactions");
            }
        } catch (\Throwable $e) {
            // Ignore if views cannot be created in specific environment
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        try {
            if (DB::getDriverName() === 'mysql') {
                DB::statement("DROP VIEW IF EXISTS setoran_koin");
                DB::statement("DROP VIEW IF EXISTS periode_pentong");
            }
        } catch (\Throwable $e) {
            //
        }

        if (Schema::hasColumn('koin_transactions', 'periode_pentong_id')) {
            Schema::table('koin_transactions', function (Blueprint $table) {
                $table->dropForeign(['periode_pentong_id']);
                $table->dropColumn('periode_pentong_id');
            });
        }

        if (Schema::hasColumn('penthongs', 'is_active')) {
            Schema::table('penthongs', function (Blueprint $table) {
                $table->dropColumn('is_active');
            });
        }
    }
};
