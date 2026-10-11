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
        Schema::create('mwcs', function (Blueprint $table) {
            $table->id();
            $table->string('code', 20)->unique()->comment('Kode unik MWC');
            $table->string('name', 100)->comment('Nama MWC (Kecamatan)');
            $table->string('kecamatan', 100);
            $table->text('address')->nullable();
            $table->string('phone', 25)->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('mwcs');
    }
};
