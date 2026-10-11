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
        Schema::create('rantings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('mwc_id')->constrained('mwcs')->cascadeOnDelete()->comment('MWC Induk');
            $table->string('code', 20)->unique()->comment('Kode unik Ranting');
            $table->string('name', 100)->comment('Nama Ranting (Desa / Kelurahan)');
            $table->string('desa', 100)->nullable();
            $table->string('pj_name', 150)->nullable()->comment('Nama Penanggung Jawab (PJ) / Petugas Ranting');
            $table->text('address')->nullable();
            $table->timestamps();

            $table->index(['mwc_id', 'name']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rantings');
    }
};
