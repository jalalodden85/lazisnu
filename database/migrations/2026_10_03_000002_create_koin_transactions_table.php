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
        Schema::create('koin_transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_code', 50)->unique()->comment('Nomor kuitansi transaksi setoran relawan (TRX-YYYYMMDD-XXXX)');
            $table->foreignId('relawan_id')->constrained('relawans')->cascadeOnDelete()->comment('Relawan koordinator penyetor koin');
            $table->foreignId('ranting_id')->constrained('rantings')->cascadeOnDelete()->comment('Ranting asal setoran');
            $table->foreignId('mwc_id')->constrained('mwcs')->cascadeOnDelete()->comment('MWC asal setoran (denormalized untuk agregasi cepat)');
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete()->comment('Admin Ranting yang menginput setoran');
            $table->decimal('amount', 14, 2)->comment('Nominal uang infaq total yang dihimpun relawan');
            $table->string('period', 7)->index()->comment('Periode infaq bulanan (format YYYY-MM)');
            $table->date('collection_date')->index()->comment('Tanggal koin diterima dari relawan');
            $table->text('notes')->nullable()->comment('Catatan koin (misal: pecahan receh, infaq kaleng RT 01)');
            $table->string('proof_image', 255)->nullable()->comment('Foto kuitansi / fisik penyerahan uang');
            $table->enum('status', ['confirmed', 'pending', 'cancelled'])->default('confirmed')->index();
            $table->timestamps();

            // Indeks komposit performa tinggi untuk agregasi hierarki PC, MWC, dan Ranting
            $table->index(['mwc_id', 'period', 'amount'], 'idx_mwc_period_amount');
            $table->index(['ranting_id', 'period', 'amount'], 'idx_ranting_period_amount');
            $table->index(['relawan_id', 'period'], 'idx_relawan_period');
            $table->index(['collection_date', 'amount'], 'idx_collection_date_amount');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('koin_transactions');
    }
};
