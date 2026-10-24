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
        // =========================================================================
        // 1. UPDATE TABEL PROGRAM_KERJAS: Tambahkan periode_pentong_id & penthong_id
        // =========================================================================
        if (Schema::hasTable('program_kerjas')) {
            Schema::table('program_kerjas', function (Blueprint $table) {
                if (!Schema::hasColumn('program_kerjas', 'periode_pentong_id')) {
                    $table->foreignId('periode_pentong_id')
                        ->nullable()
                        ->after('pilar_program_id')
                        ->comment('Relasi ke Periode Penthong pengajuan proker')
                        ->constrained('penthongs')
                        ->nullOnDelete();
                }

                if (!Schema::hasColumn('program_kerjas', 'penthong_id')) {
                    $table->foreignId('penthong_id')
                        ->nullable()
                        ->after('periode_pentong_id')
                        ->comment('Alias penthong_id')
                        ->constrained('penthongs')
                        ->nullOnDelete();
                }

                if (!Schema::hasIndex('program_kerjas', 'idx_proker_pentong_tingkat')) {
                    $table->index(['periode_pentong_id', 'tingkat'], 'idx_proker_pentong_tingkat');
                }
            });
        }

        // =========================================================================
        // 2. BUAT TABEL KONTRIBUSI_STRUKTURS (Setoran Bagi Hasil Ranting ke MWC/PC/PW/PB)
        // =========================================================================
        if (!Schema::hasTable('kontribusi_strukturs')) {
            Schema::create('kontribusi_strukturs', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_transaksi', 60)->unique()->comment('Contoh: KTR-202610-0001');
                
                // Binding Periode Pentong
                $table->foreignId('periode_pentong_id')
                    ->comment('Periode penthong sumber dana koin')
                    ->constrained('penthongs')
                    ->cascadeOnDelete();
                $table->foreignId('penthong_id')
                    ->nullable()
                    ->constrained('penthongs')
                    ->nullOnDelete();

                // Scope Wilayah (Multi-Tenancy)
                $table->foreignId('ranting_id')
                    ->comment('Ranting penyetor')
                    ->constrained('rantings')
                    ->cascadeOnDelete();
                $table->foreignId('mwc_id')
                    ->nullable()
                    ->constrained('mwcs')
                    ->nullOnDelete();
                $table->foreignId('user_id')
                    ->comment('Petugas penginput')
                    ->constrained('users')
                    ->cascadeOnDelete();

                $table->date('tanggal_setor');
                
                // Nominal Kalkulasi Akuntansi
                $table->decimal('nominal_bruto', 15, 2)->default(0)->comment('Total koin bruto ranting');
                $table->decimal('persentase_operasional', 5, 2)->default(20.00)->comment('Potongan operasional (20%)');
                $table->decimal('nominal_operasional', 15, 2)->default(0)->comment('Nominal hak amil operasional');
                $table->decimal('nominal_netto', 15, 2)->default(0)->comment('Dana bersih setelah operasional');

                // Breakdown Alokasi Struktur Atas
                $table->decimal('nominal_mwc', 15, 2)->default(0)->comment('Porsi MWC (12%)');
                $table->decimal('nominal_pc', 15, 2)->default(0)->comment('Porsi PC (6%)');
                $table->decimal('nominal_pw', 15, 2)->default(0)->comment('Porsi PW (1.5%)');
                $table->decimal('nominal_pb', 15, 2)->default(0)->comment('Porsi PB (0.5%)');
                
                // Total Pengeluaran Kas Ranting yang disetor
                $table->decimal('total_disetor', 15, 2)->comment('Total kas keluar dari ranting (MWC+PC+PW+PB)');
                
                $table->json('persentase_setting_json')->nullable()->comment('Snapshot persentase saat transaksi');

                // Metadata Penyaluran
                $table->enum('tujuan_struktur', ['semua', 'mwc', 'pc', 'pw', 'pb'])->default('semua');
                $table->enum('metode_penyaluran', ['tunai', 'transfer', 'qris'])->default('transfer');
                $table->string('nama_bank', 100)->nullable();
                $table->string('nomor_rekening', 100)->nullable();
                $table->string('bukti_transfer', 255)->nullable();
                $table->text('catatan')->nullable();

                // Status & Verifikasi
                $table->enum('status', ['draft', 'disetor', 'diterima', 'dibatalkan'])->default('disetor');
                $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
                $table->dateTime('verified_at')->nullable();
                $table->text('catatan_verifikasi')->nullable();

                $table->timestamps();

                $table->index(['periode_pentong_id', 'ranting_id'], 'idx_ktr_pentong_ranting');
                $table->index(['ranting_id', 'tanggal_setor'], 'idx_ktr_ranting_tgl');
                $table->index(['mwc_id', 'status'], 'idx_ktr_mwc_status');
            });
        }

        // =========================================================================
        // 3. BUAT TABEL KAS_MUTASIS (Unified Ledger Mutasi Kas BKU)
        // =========================================================================
        if (!Schema::hasTable('kas_mutasis')) {
            Schema::create('kas_mutasis', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_bukti', 60)->index();
                
                $table->foreignId('periode_pentong_id')
                    ->nullable()
                    ->constrained('penthongs')
                    ->nullOnDelete();
                $table->foreignId('penthong_id')
                    ->nullable()
                    ->constrained('penthongs')
                    ->nullOnDelete();

                $table->enum('jenis', ['debet', 'kredit'])->comment('Debet = Pemasukan, Kredit = Pengeluaran');
                $table->string('kode_transaksi', 30)->comment('NU-PB, NU Cerdas, NU-KONTRIBUSI, dll');
                $table->string('uraian', 255);
                $table->decimal('nominal', 15, 2);
                $table->date('tanggal_transaksi');
                
                // Scoping Entitas
                $table->enum('tingkat', ['pc', 'mwc', 'ranting'])->default('ranting');
                $table->foreignId('ranting_id')->nullable()->constrained('rantings')->nullOnDelete();
                $table->foreignId('mwc_id')->nullable()->constrained('mwcs')->nullOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();

                // Polymorphic reference ke tabel asal
                $table->string('source_type', 100)->nullable();
                $table->unsignedBigInteger('source_id')->nullable();

                $table->text('keterangan')->nullable();
                $table->timestamps();

                $table->index(['periode_pentong_id', 'ranting_id', 'jenis'], 'idx_kas_pentong_ranting');
                $table->index(['tingkat', 'tanggal_transaksi'], 'idx_kas_tingkat_tgl');
                $table->index(['source_type', 'source_id'], 'idx_kas_source');
            });
        }

        // Backfill data pentong id jika ada
        $activePenthong = DB::table('penthongs')->where('is_active', true)->first()
            ?? DB::table('penthongs')->latest('id')->first();

        if ($activePenthong && Schema::hasTable('program_kerjas')) {
            DB::table('program_kerjas')
                ->whereNull('periode_pentong_id')
                ->update([
                    'periode_pentong_id' => $activePenthong->id,
                    'penthong_id'        => $activePenthong->id,
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('kas_mutasis');
        Schema::dropIfExists('kontribusi_strukturs');

        if (Schema::hasTable('program_kerjas')) {
            Schema::table('program_kerjas', function (Blueprint $table) {
                if (Schema::hasIndex('program_kerjas', 'idx_proker_pentong_tingkat')) {
                    $table->dropIndex('idx_proker_pentong_tingkat');
                }
                if (Schema::hasColumn('program_kerjas', 'periode_pentong_id')) {
                    $table->dropForeign(['periode_pentong_id']);
                    $table->dropColumn('periode_pentong_id');
                }
                if (Schema::hasColumn('program_kerjas', 'penthong_id')) {
                    $table->dropForeign(['penthong_id']);
                    $table->dropColumn('penthong_id');
                }
            });
        }
    }
};
