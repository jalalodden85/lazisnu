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
        Schema::create('relawans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('ranting_id')->constrained('rantings')->cascadeOnDelete()->comment('Ranting / Desa tempat relawan bertugas');
            $table->string('nama', 150)->comment('Nama lengkap relawan koordinator RT/RW');
            $table->string('nik', 16)->unique()->index()->comment('NIK 16 digit relawan');
            $table->string('no_hp', 25)->nullable()->comment('No HP / WhatsApp');
            $table->string('area_tugas', 100)->comment('Area teritori tugas (contoh: RT 01 / RW 02)');
            $table->enum('status', ['active', 'inactive'])->default('active')->index()->comment('Status relawan');
            $table->date('joined_date')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['ranting_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('relawans');
    }
};
