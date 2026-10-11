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
        Schema::create('donators', function (Blueprint $table) {
            $table->id();
            $table->string('box_number', 50)->unique()->index()->comment('Nomor Kaleng / Kotak KOIN NU');
            $table->string('name', 150)->comment('Nama Donatur / Kepala Keluarga penerima kotak');
            $table->string('nik', 16)->nullable()->index()->comment('NIK 16 digit');
            $table->string('phone', 25)->nullable()->comment('No HP / WhatsApp');
            $table->foreignId('ranting_id')->constrained('rantings')->cascadeOnDelete()->comment('Ranting / Desa wilayah domisili');
            $table->foreignId('relawan_id')->nullable()->constrained('relawans')->nullOnDelete()->comment('Petugas Relawan Lapangan Pengampu');
            $table->string('address', 500)->comment('Alamat rumah lengkap');
            $table->string('rt', 10)->nullable();
            $table->string('rw', 10)->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->index();
            $table->date('joined_date')->nullable()->comment('Tanggal penyerahan kotak infaq');
            $table->text('notes')->nullable();
            $table->timestamps();

            // Indeks komposit performa query
            $table->index(['ranting_id', 'status']);
            $table->index(['relawan_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('donators');
    }
};
