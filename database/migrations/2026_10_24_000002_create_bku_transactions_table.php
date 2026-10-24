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
        if (!Schema::hasTable('bku_transactions')) {
            Schema::create('bku_transactions', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_transaksi', 60)->unique()->comment('Contoh: BKU-OUT-202610-0001');
                
                $table->foreignId('periode_pentong_id')
                    ->nullable()
                    ->constrained('penthongs')
                    ->nullOnDelete();

                $table->enum('jenis', ['pemasukan', 'pengeluaran'])->default('pengeluaran');
                $table->string('kategori', 50)->comment('Pendidikan, Kesehatan, Ekonomi, Sosial, Dakwah, Biaya Operasional');
                $table->string('kode_transaksi', 30)->comment('COA: NU Cerdas, NU Sehat, NU Berdaya, NU Damai, NU Hijau, NU-O');
                $table->date('tanggal');
                $table->decimal('nominal', 15, 2);
                $table->string('penerima_vendor', 150)->nullable()->comment('Nama Toko, Vendor, atau Penerima Manfaat');
                $table->text('uraian')->comment('Uraian keperluan belanja atau pentasyarufan');
                $table->string('bukti_kwitansi', 255)->nullable()->comment('Path file bukti nota/kwitansi');

                // Scope Multi-Tenancy Tingkatan
                $table->enum('tingkat', ['pc', 'mwc', 'ranting'])->default('ranting');
                $table->foreignId('mwc_id')->nullable()->constrained('mwcs')->nullOnDelete();
                $table->foreignId('ranting_id')->nullable()->constrained('rantings')->nullOnDelete();
                $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();

                $table->timestamps();

                $table->index(['tingkat', 'ranting_id', 'jenis', 'tanggal'], 'idx_bku_tx_ranting');
                $table->index(['tingkat', 'mwc_id', 'jenis', 'tanggal'], 'idx_bku_tx_mwc');
                $table->index(['periode_pentong_id', 'jenis'], 'idx_bku_tx_pentong');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bku_transactions');
    }
};
