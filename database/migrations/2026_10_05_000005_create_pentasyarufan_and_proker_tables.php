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
        $now = now();

        // 1. Master Data 5 Pilar Program NU Care & Persentase Alokasi Pagu
        Schema::create('pilar_programs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_pilar', 100);
            $table->string('kode', 30)->unique();
            $table->decimal('target_persentase', 5, 2)->default(20.00)->comment('Persentase pagu (total 100%)');
            $table->text('deskripsi')->nullable();
            $table->string('warna', 20)->default('#0a6b38');
            $table->string('icon', 50)->default('fas fa-hands-helping');
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Seed 5 Pilar Program Standar NU Care LAZISNU (Total = 100%)
        $pilars = [
            [
                'nama_pilar'        => 'NU Care Cerdas (Pendidikan)',
                'kode'              => 'PENDIDIKAN',
                'target_persentase' => 20.00,
                'deskripsi'         => 'Bantuan beasiswa santri/pelajar, perlengkapan sekolah, dan renovasi madrasah/pesantren.',
                'warna'             => '#007bff',
                'icon'              => 'fas fa-graduation-cap',
                'sort_order'        => 1,
                'is_active'         => true,
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'nama_pilar'        => 'NU Care Sehat (Kesehatan)',
                'kode'              => 'KESEHATAN',
                'target_persentase' => 15.00,
                'deskripsi'         => 'Layanan kesehatan gratis, bantuan pengobatan warga dhuafa, dan operasional ambulans gratis.',
                'warna'             => '#28a745',
                'icon'              => 'fas fa-heartbeat',
                'sort_order'        => 2,
                'is_active'         => true,
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'nama_pilar'        => 'NU Care Berdaya (Ekonomi)',
                'kode'              => 'EKONOMI',
                'target_persentase' => 20.00,
                'deskripsi'         => 'Pemberdayaan UMKM warga nahdliyin, modal usaha produktif, dan pelatihan wirausaha.',
                'warna'             => '#ffc107',
                'icon'              => 'fas fa-hand-holding-usd',
                'sort_order'        => 3,
                'is_active'         => true,
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'nama_pilar'        => 'NU Care Tanggap (Sosial & Kebencanaan)',
                'kode'              => 'SOSIAL',
                'target_persentase' => 30.00,
                'deskripsi'         => 'Santunan yatim & dhuafa, bantuan sembako, tanggap darurat bencana alam, dan kemanusiaan.',
                'warna'             => '#dc3545',
                'icon'              => 'fas fa-hands-helping',
                'sort_order'        => 4,
                'is_active'         => true,
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
            [
                'nama_pilar'        => 'NU Care Damai (Dakwah & Keagamaan)',
                'kode'              => 'DAKWAH',
                'target_persentase' => 15.00,
                'deskripsi'         => 'Bisyarah guru ngaji/marbot masjid, penguatan dakwah islam aswaja, dan syiar keagamaan NU.',
                'warna'             => '#6f42c1',
                'icon'              => 'fas fa-mosque',
                'sort_order'        => 5,
                'is_active'         => true,
                'created_at'        => $now,
                'updated_at'        => $now,
            ],
        ];

        DB::table('pilar_programs')->insert($pilars);

        // 2. Tabel Program Kerja (Proker Berjenjang: Ranting, MWC, PC)
        Schema::create('program_kerjas', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_proker', 50)->unique();
            $table->string('nama_kegiatan', 255);
            $table->foreignId('pilar_program_id')->constrained('pilar_programs')->cascadeOnDelete();
            $table->enum('tingkat', ['pc', 'mwc', 'ranting'])->default('ranting');
            $table->foreignId('mwc_id')->nullable()->constrained('mwcs')->nullOnDelete();
            $table->foreignId('ranting_id')->nullable()->constrained('rantings')->nullOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->integer('tahun_anggaran')->default((int) date('Y'));
            $table->decimal('rencana_anggaran', 15, 2)->default(0.00);
            $table->text('deskripsi_kegiatan')->nullable();
            $table->string('target_penerima_manfaat', 255)->nullable();
            $table->date('tanggal_mulai')->nullable();
            $table->date('tanggal_selesai')->nullable();
            $table->enum('status', ['draft', 'menunggu_persetujuan', 'disetujui', 'ditolak', 'selesai'])->default('draft');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('approved_at')->nullable();
            $table->text('catatan_approval')->nullable();
            $table->timestamps();

            $table->index(['tingkat', 'status']);
            $table->index('pilar_program_id');
        });

        // 3. Tabel Realisasi Pentasyarufan & SPJ
        Schema::create('pentasyarufans', function (Blueprint $table) {
            $table->id();
            $table->string('nomor_transaksi', 50)->unique();
            $table->foreignId('program_kerja_id')->constrained('program_kerjas')->cascadeOnDelete();
            $table->foreignId('pilar_program_id')->constrained('pilar_programs')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('tingkat', ['pc', 'mwc', 'ranting'])->default('ranting');
            $table->foreignId('mwc_id')->nullable()->constrained('mwcs')->nullOnDelete();
            $table->foreignId('ranting_id')->nullable()->constrained('rantings')->nullOnDelete();
            $table->decimal('nominal_pencairan', 15, 2);
            $table->date('tanggal_penyaluran');
            $table->string('penerima_manfaat', 255);
            $table->integer('jumlah_penerima')->default(1);
            $table->text('keterangan')->nullable();
            $table->string('bukti_penyaluran', 255)->nullable();
            $table->string('berkas_spj', 255)->nullable();
            $table->enum('spj_status', ['belum_spj', 'menunggu_verifikasi', 'diterima', 'ditolak'])->default('belum_spj');
            $table->foreignId('spj_verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('spj_verified_at')->nullable();
            $table->text('spj_notes')->nullable();
            $table->timestamps();

            $table->index(['tingkat', 'tanggal_penyaluran']);
            $table->index(['pilar_program_id', 'spj_status']);
        });

        // 4. Daftarkan Master Menu Baru di Tabel menus
        if (Schema::hasTable('menus')) {
            $menuItems = [
                [
                    'name'        => 'Pilar Program & Pagu',
                    'route_name'  => 'pilar-program.index',
                    'url_pattern' => 'pentasyarufan/pilar*',
                    'icon'        => 'fas fa-shapes',
                    'category'    => 'Pentasyarufan & Penyaluran',
                    'sort_order'  => 25,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'name'        => 'Program Kerja (Proker)',
                    'route_name'  => 'proker.index',
                    'url_pattern' => 'pentasyarufan/proker*',
                    'icon'        => 'fas fa-clipboard-list',
                    'category'    => 'Pentasyarufan & Penyaluran',
                    'sort_order'  => 26,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'name'        => 'Penyaluran Dana & SPJ',
                    'route_name'  => 'pentasyarufan.index',
                    'url_pattern' => 'pentasyarufan/realisasi*',
                    'icon'        => 'fas fa-hand-holding-heart',
                    'category'    => 'Pentasyarufan & Penyaluran',
                    'sort_order'  => 27,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
                [
                    'name'        => 'Laporan Pentasyarufan',
                    'route_name'  => 'pentasyarufan.laporan',
                    'url_pattern' => 'pentasyarufan/laporan*',
                    'icon'        => 'fas fa-file-invoice',
                    'category'    => 'Pentasyarufan & Penyaluran',
                    'sort_order'  => 28,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ],
            ];

            foreach ($menuItems as $m) {
                $menuId = DB::table('menus')->insertGetId($m);

                // Sinkronkan ke Super Admin dan Pejabat PC
                $pcUserIds = DB::table('users')->whereIn('username', [
                    'adminpc', 'ketua_lazisnu', 'sekretaris_lazisnu', 'bendahara_lazisnu', 'admin_koin'
                ])->pluck('id');

                foreach ($pcUserIds as $uId) {
                    DB::table('user_has_menus')->insertOrIgnore([
                        'user_id'    => $uId,
                        'menu_id'    => $menuId,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]);
                }
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            $menuIds = DB::table('menus')->where('category', 'Pentasyarufan & Penyaluran')->pluck('id');
            DB::table('user_has_menus')->whereIn('menu_id', $menuIds)->delete();
            DB::table('menus')->whereIn('id', $menuIds)->delete();
        }

        Schema::dropIfExists('pentasyarufans');
        Schema::dropIfExists('program_kerjas');
        Schema::dropIfExists('pilar_programs');
    }
};
