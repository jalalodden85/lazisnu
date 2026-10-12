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
        // 1. Perluasan Kolom Pengurus pada Tabel mwcs
        Schema::table('mwcs', function (Blueprint $table) {
            $table->string('nama_ketua_mwc', 150)->nullable()->after('phone')->comment('Ketua Tanfidziyah NU MWC');
            $table->string('wa_ketua_mwc', 30)->nullable()->after('nama_ketua_mwc')->comment('WhatsApp Ketua Tanfidziyah MWC');
            
            $table->string('nama_ketua_lazisnu', 150)->nullable()->after('wa_ketua_mwc')->comment('Ketua LAZISNU MWC');
            $table->string('wa_ketua_lazisnu', 30)->nullable()->after('nama_ketua_lazisnu')->comment('WhatsApp Ketua LAZISNU MWC');
            
            $table->string('nama_manager_upzis', 150)->nullable()->after('wa_ketua_lazisnu')->comment('Manager UPZIS MWC');
            $table->string('wa_manager_upzis', 30)->nullable()->after('nama_manager_upzis')->comment('WhatsApp Manager UPZIS MWC');
            
            $table->string('nama_sekretaris', 150)->nullable()->after('wa_manager_upzis')->comment('Sekretaris UPZIS MWC');
            $table->string('wa_sekretaris', 30)->nullable()->after('nama_sekretaris')->comment('WhatsApp Sekretaris UPZIS MWC');
            
            $table->string('nama_bendahara', 150)->nullable()->after('wa_sekretaris')->comment('Bendahara UPZIS MWC');
            $table->string('wa_bendahara', 30)->nullable()->after('nama_bendahara')->comment('WhatsApp Bendahara UPZIS MWC');
        });

        // 2. Perluasan Kolom Pengurus pada Tabel rantings
        Schema::table('rantings', function (Blueprint $table) {
            $table->string('nama_ketua_ranting', 150)->nullable()->after('address')->comment('Ketua Tanfidziyah NU Ranting');
            $table->string('wa_ketua_ranting', 30)->nullable()->after('nama_ketua_ranting')->comment('WhatsApp Ketua Tanfidziyah Ranting');
            
            $table->string('nama_ketua_upzis', 150)->nullable()->after('wa_ketua_ranting')->comment('Ketua UPZIS Ranting');
            $table->string('wa_ketua_upzis', 30)->nullable()->after('nama_ketua_upzis')->comment('WhatsApp Ketua UPZIS Ranting');
            
            $table->string('nama_bendahara', 150)->nullable()->after('wa_ketua_upzis')->comment('Bendahara UPZIS Ranting');
            $table->string('wa_bendahara', 30)->nullable()->after('nama_bendahara')->comment('WhatsApp Bendahara UPZIS Ranting');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('mwcs', function (Blueprint $table) {
            $table->dropColumn([
                'nama_ketua_mwc',
                'wa_ketua_mwc',
                'nama_ketua_lazisnu',
                'wa_ketua_lazisnu',
                'nama_manager_upzis',
                'wa_manager_upzis',
                'nama_sekretaris',
                'wa_sekretaris',
                'nama_bendahara',
                'wa_bendahara',
            ]);
        });

        Schema::table('rantings', function (Blueprint $table) {
            $table->dropColumn([
                'nama_ketua_ranting',
                'wa_ketua_ranting',
                'nama_ketua_upzis',
                'wa_ketua_upzis',
                'nama_bendahara',
                'wa_bendahara',
            ]);
        });
    }
};
