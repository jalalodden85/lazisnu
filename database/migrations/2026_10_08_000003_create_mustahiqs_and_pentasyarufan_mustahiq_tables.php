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
        // 1. Tabel Master Data Mustahiq (Penerima Manfaat Berbasis Ranting)
        if (!Schema::hasTable('mustahiqs')) {
            Schema::create('mustahiqs', function (Blueprint $table) {
            $table->id();
            $table->string('nik', 20)->unique()->comment('Nomor Induk Kependudukan (Unik Nasional)');
            $table->string('nama', 255)->comment('Nama Lengkap Mustahiq');
            $table->enum('jenis_kelamin', ['L', 'P'])->default('L');
            $table->string('telepon', 25)->nullable();
            $table->text('alamat')->comment('Alamat lengkap: Dusun, RT/RW, Desa');
            
            // Relasi Kewilayahan NU
            $table->foreignId('ranting_id')->constrained('rantings')->cascadeOnDelete();
            $table->foreignId('mwc_id')->constrained('mwcs')->cascadeOnDelete();
            
            // Klasifikasi 8 Asnaf Zakat
            $table->enum('asnaf', [
                'fakir',
                'miskin',
                'amil',
                'muallaf',
                'riqab',
                'gharim',
                'fisabilillah',
                'ibnu_sabil',
            ])->default('miskin')->comment('Kategori 8 Asnaf Penerima ZIS');
            
            // Data Kondisi Sosial-Ekonomi
            $table->string('kondisi_spesifik', 255)->nullable()->comment('Contoh: Janda lansia, yatim piatu, disabilitas, sakit menahun');
            $table->string('pekerjaan', 100)->nullable();
            $table->decimal('penghasilan_rata_rata', 15, 2)->nullable()->default(0);
            $table->unsignedSmallInteger('jumlah_tanggungan')->default(0);
            
            // Status Kelayakan Bantuan
            $table->enum('status', ['aktif', 'tidak_aktif'])->default('aktif')->comment('Aktif = layak dibantu, Tidak Aktif = mandiri/meninggal');
            $table->string('alasan_tidak_aktif', 255)->nullable();
            
            // Bukti Validasi Lapangan (Privat & Sensitif)
            $table->string('foto_kondisi', 255)->nullable()->comment('Path file foto rumah/kondisi fisik');
            $table->text('catatan_verifikasi')->nullable();
            
            // Audit Trail
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            // Indeks Performa Scoping & Pencarian
            $table->index(['ranting_id', 'status']);
            $table->index(['mwc_id', 'asnaf']);
            $table->index(['nama', 'nik']);
        });
        }

        // 2. Tabel Pivot Relasi Pentasyarufan & Mustahiq (Many-to-Many Rekam Jejak Bantuan)
        if (!Schema::hasTable('pentasyarufan_mustahiq')) {
            Schema::create('pentasyarufan_mustahiq', function (Blueprint $table) {
                $table->id();
                $table->foreignId('pentasyarufan_id')->constrained('pentasyarufans')->cascadeOnDelete();
                $table->foreignId('mustahiq_id')->constrained('mustahiqs')->cascadeOnDelete();
                $table->decimal('nominal_bantuan', 15, 2)->nullable()->comment('Nominal bantuan riil per penerima');
                $table->string('keterangan', 255)->nullable()->comment('Bentuk bantuan (e.g. Sembako, Uang Tunai, Kursi Roda)');
                $table->timestamps();

                $table->unique(['pentasyarufan_id', 'mustahiq_id'], 'pentasyarufan_mustahiq_unique');
                $table->index('mustahiq_id');
            });
        }

        // Drop view jika ada untuk menghindari 'no such table' di SQLite saat rename __temp__pentasyarufans
        \Illuminate\Support\Facades\DB::statement("DROP VIEW IF EXISTS vw_saldo_pentasyarufan");

        // 3. Tambahkan kolom mustahiq_id opsional pada tabel pentasyarufans (untuk transaksi personal 1-on-1)
        Schema::table('pentasyarufans', function (Blueprint $table) {
            if (!Schema::hasColumn('pentasyarufans', 'mustahiq_id')) {
                $table->foreignId('mustahiq_id')->nullable()->after('pilar_program_id')
                    ->constrained('mustahiqs')->nullOnDelete()
                    ->comment('Terisi jika pentasyarufan tunggal 1-on-1');
            }
        });

        // Recreate view vw_saldo_pentasyarufan
        \Illuminate\Support\Facades\DB::statement("
            CREATE OR REPLACE VIEW vw_saldo_pentasyarufan AS
            SELECT 
                p.penthong_id,
                pt.nama_periode,
                pt.bulan,
                pt.tahun,
                p.pilar_program_id,
                pil.kode AS pilar_kode,
                pil.nama_pilar,
                pil.target_persentase,
                COUNT(p.id) AS total_transaksi,
                COALESCE(SUM(p.nominal_pencairan), 0) AS total_pentasyarufan
            FROM pentasyarufans p
            LEFT JOIN penthongs pt ON p.penthong_id = pt.id
            LEFT JOIN pilar_programs pil ON p.pilar_program_id = pil.id
            GROUP BY 
                p.penthong_id, 
                pt.nama_periode, 
                pt.bulan, 
                pt.tahun, 
                p.pilar_program_id, 
                pil.kode, 
                pil.nama_pilar, 
                pil.target_persentase
        ");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        \Illuminate\Support\Facades\DB::statement("DROP VIEW IF EXISTS vw_saldo_pentasyarufan");

        Schema::table('pentasyarufans', function (Blueprint $table) {
            if (Schema::hasColumn('pentasyarufans', 'mustahiq_id')) {
                $table->dropForeign(['mustahiq_id']);
                $table->dropColumn('mustahiq_id');
            }
        });

        \Illuminate\Support\Facades\DB::statement("
            CREATE OR REPLACE VIEW vw_saldo_pentasyarufan AS
            SELECT 
                p.penthong_id,
                pt.nama_periode,
                pt.bulan,
                pt.tahun,
                p.pilar_program_id,
                pil.kode AS pilar_kode,
                pil.nama_pilar,
                pil.target_persentase,
                COUNT(p.id) AS total_transaksi,
                COALESCE(SUM(p.nominal_pencairan), 0) AS total_pentasyarufan
            FROM pentasyarufans p
            LEFT JOIN penthongs pt ON p.penthong_id = pt.id
            LEFT JOIN pilar_programs pil ON p.pilar_program_id = pil.id
            GROUP BY 
                p.penthong_id, 
                pt.nama_periode, 
                pt.bulan, 
                pt.tahun, 
                p.pilar_program_id, 
                pil.kode, 
                pil.nama_pilar, 
                pil.target_persentase
        ");

        Schema::dropIfExists('pentasyarufan_mustahiq');
        Schema::dropIfExists('mustahiqs');
    }
};
