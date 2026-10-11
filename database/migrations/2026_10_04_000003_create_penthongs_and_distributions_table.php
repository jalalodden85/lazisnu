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
        // 1. Tabel Pengaturan Persentase Distribusi Dana (Tasharruf)
        Schema::create('distribution_settings', function (Blueprint $table) {
            $table->id();
            $table->string('name')->default('Standar Tasharruf KOIN NU Lamongan');
            $table->decimal('operasional_pct', 5, 2)->default(20.00)->comment('Potongan Operasional di awal (contoh: 20%)');
            $table->decimal('ranting_pct', 5, 2)->default(80.00)->comment('Porsi Ranting dari dana bersih (80%)');
            $table->decimal('mwc_pct', 5, 2)->default(12.00)->comment('Porsi MWC dari dana bersih (12%)');
            $table->decimal('pc_pct', 5, 2)->default(6.00)->comment('Porsi PC Cabang dari dana bersih (6%)');
            $table->decimal('pw_pct', 5, 2)->default(1.50)->comment('Porsi PW Wilayah dari dana bersih (1.5%)');
            $table->decimal('pb_pct', 5, 2)->default(0.50)->comment('Porsi PB Pusat dari dana bersih (0.5%)');
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // 2. Tabel Penthong (Periode Buka Kotak Infaq)
        Schema::create('penthongs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_periode')->comment('Contoh: Penthong 1, Penthong 2, dst');
            $table->unsignedTinyInteger('bulan')->comment('Bulan periode (1 - 12)');
            $table->unsignedSmallInteger('tahun')->comment('Tahun periode (contoh: 2026)');
            $table->enum('status', ['aktif', 'tutup'])->default('aktif')->comment('Hanya boleh ada 1 penthong aktif per scope');
            
            // Scope wilayah (Opsional: null berarti berlaku cabang/mwc global)
            $table->foreignId('mwc_id')->nullable()->constrained('mwcs')->nullOnDelete();
            $table->foreignId('ranting_id')->nullable()->constrained('rantings')->nullOnDelete();
            
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            
            // Agregat Total Distribusi saat Penthong Ditutup
            $table->decimal('total_collected', 15, 2)->default(0);
            $table->decimal('operasional_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('ranting_amount', 15, 2)->default(0);
            $table->decimal('mwc_amount', 15, 2)->default(0);
            $table->decimal('pc_amount', 15, 2)->default(0);
            $table->decimal('pw_amount', 15, 2)->default(0);
            $table->decimal('pb_amount', 15, 2)->default(0);
            
            $table->timestamp('closed_at')->nullable();
            $table->foreignId('closed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['status']);
            $table->index(['tahun', 'bulan']);
            $table->index(['mwc_id', 'status']);
            $table->index(['ranting_id', 'status']);
        });

        // 3. Revisi tabel koin_transactions: Tambahkan foreign key penthong_id
        Schema::table('koin_transactions', function (Blueprint $table) {
            $table->foreignId('penthong_id')
                  ->nullable()
                  ->after('amount')
                  ->constrained('penthongs')
                  ->nullOnDelete();
            $table->index('penthong_id');
        });

        // 4. Tabel Rincian Distribusi Bagi Hasil per Ranting untuk Penthong Tertutup
        Schema::create('penthong_distributions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('penthong_id')->constrained('penthongs')->cascadeOnDelete();
            $table->foreignId('ranting_id')->constrained('rantings')->cascadeOnDelete();
            $table->foreignId('mwc_id')->constrained('mwcs')->cascadeOnDelete();
            
            $table->decimal('gross_amount', 15, 2)->default(0);
            $table->decimal('operasional_amount', 15, 2)->default(0);
            $table->decimal('net_amount', 15, 2)->default(0);
            $table->decimal('ranting_amount', 15, 2)->default(0);
            $table->decimal('mwc_amount', 15, 2)->default(0);
            $table->decimal('pc_amount', 15, 2)->default(0);
            $table->decimal('pw_amount', 15, 2)->default(0);
            $table->decimal('pb_amount', 15, 2)->default(0);
            $table->timestamps();

            $table->unique(['penthong_id', 'ranting_id']);
            $table->index(['penthong_id', 'mwc_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('penthong_distributions');

        Schema::table('koin_transactions', function (Blueprint $table) {
            $table->dropForeign(['penthong_id']);
            $table->dropIndex(['penthong_id']);
            $table->dropColumn('penthong_id');
        });

        Schema::dropIfExists('penthongs');
        Schema::dropIfExists('distribution_settings');
    }
};
