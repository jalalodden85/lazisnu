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
        // 1. Master Data Kategori Pengeluaran Operasional (COA Amil)
        if (!Schema::hasTable('kategori_operasionals')) {
            Schema::create('kategori_operasionals', function (Blueprint $table) {
                $table->id();
                $table->string('kode', 30)->unique()->comment('Contoh: OPS-BISYAROH, OPS-ATK');
                $table->string('nama_kategori', 100);
                $table->text('deskripsi')->nullable();
                $table->string('icon', 50)->default('fas fa-file-invoice-dollar');
                $table->integer('sort_order')->default(0);
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            // Seeding Awal COA Khusus Hak Amil
            $now = now();
            DB::table('kategori_operasionals')->insert([
                [
                    'kode'          => 'OPS-BISYAROH',
                    'nama_kategori' => 'Bisyaroh & Insentif Petugas Lapangan (PLK)',
                    'deskripsi'     => 'Honorarium amil, insentif koordinator RT/RW, dan uang lelah relawan koin.',
                    'icon'          => 'fas fa-hand-holding-usd',
                    'sort_order'    => 1,
                    'is_active'     => true,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ],
                [
                    'kode'          => 'OPS-RAPAT',
                    'nama_kategori' => 'Biaya Rapat & Konsolidasi Pengurus',
                    'deskripsi'     => 'Konsumsi rapat rutin selapanan, koordinasi takmir, dan musyawarah amil.',
                    'icon'          => 'fas fa-users-cog',
                    'sort_order'    => 2,
                    'is_active'     => true,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ],
                [
                    'kode'          => 'OPS-ATK',
                    'nama_kategori' => 'Administrasi, ATK, Cetak Kwitansi & Kaleng',
                    'deskripsi'     => 'Pengadaan buku kas, kertas, cetak stiker kaleng koin, amplop, dan map arsip.',
                    'icon'          => 'fas fa-print',
                    'sort_order'    => 3,
                    'is_active'     => true,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ],
                [
                    'kode'          => 'OPS-TRANSPORT',
                    'nama_kategori' => 'Transportasi & Akomodasi Amil',
                    'deskripsi'     => 'BBM penjemputan koin, perjalanan dinas koordinasi ke MWC/PC.',
                    'icon'          => 'fas fa-gas-pump',
                    'sort_order'    => 4,
                    'is_active'     => true,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ],
                [
                    'kode'          => 'OPS-MAINTENANCE',
                    'nama_kategori' => 'Pemeliharaan & Perawatan Aset Operasional',
                    'deskripsi'     => 'Service motor/mobil operasional, perbaikan inventaris kantor amil.',
                    'icon'          => 'fas fa-tools',
                    'sort_order'    => 5,
                    'is_active'     => true,
                    'created_at'    => $now,
                    'updated_at'    => $now,
                ],
            ]);
        }

        // 2. Buku Kas Umum (BKU) Operasional / Hak Amil
        if (!Schema::hasTable('bku_operasionals')) {
            Schema::create('bku_operasionals', function (Blueprint $table) {
                $table->id();
                $table->string('nomor_transaksi', 60)->unique()->comment('Contoh: BKO-RTG01-202610-0001');
                
                // Binding Periode Pentong (Nullable jika donasi amil langsung)
                $table->foreignId('periode_pentong_id')
                    ->nullable()
                    ->constrained('penthongs')
                    ->nullOnDelete();

                // COA Kategori (Wajib untuk Kredit/Pengeluaran)
                $table->foreignId('kategori_operasional_id')
                    ->nullable()
                    ->constrained('kategori_operasionals')
                    ->nullOnDelete();

                // Mutasi Kas BKU: Debet (Pemasukan) vs Kredit (Pengeluaran)
                $table->enum('jenis', ['debet', 'kredit'])->comment('debet = Pemasukan Saldo Amil, kredit = Belanja Operasional');
                
                // Asal Usul Sumber Dana
                $table->enum('sumber_dana', [
                    'alokasi_pentong',      // Potongan resmi % dari buka kaleng KOIN NU
                    'infaq_operasional',    // Infaq khusus operasional langsung dari donatur
                    'subsidi_struktur',     // Bantuan operasional dari MWC / PC
                    'saldo_awal',           // Saldo awal periode sebelumnya
                    'lainnya'
                ])->default('alokasi_pentong');

                $table->decimal('nominal', 15, 2);
                $table->date('tanggal_transaksi');
                $table->string('uraian', 255)->comment('Deskripsi transaksi operasional');
                
                // Penerima / Pihak Ketiga (Toko / Nama Amil Penerima Bisyaroh)
                $table->string('penerima_atau_vendor', 150)->nullable();
                
                // Bukti Pengeluaran (Nota, Kwitansi, Bukti Bayar)
                $table->string('bukti_transaksi', 255)->nullable();

                // Multi-Tenancy Scoping (Isolasi Berjenjang)
                $table->enum('tingkat', ['pc', 'mwc', 'ranting'])->default('ranting');
                $table->foreignId('mwc_id')->nullable()->constrained('mwcs')->nullOnDelete();
                $table->foreignId('ranting_id')->nullable()->constrained('rantings')->nullOnDelete();
                $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();

                $table->timestamps();

                // Indeks Performa Laporan & Keuangan
                $table->index(['tingkat', 'ranting_id', 'jenis', 'tanggal_transaksi'], 'idx_bko_ranting_scope');
                $table->index(['tingkat', 'mwc_id', 'jenis', 'tanggal_transaksi'], 'idx_bko_mwc_scope');
                $table->index(['periode_pentong_id', 'sumber_dana'], 'idx_bko_pentong_source');
            });
        }

        // 3. Daftarkan Menu BKU Operasional di tabel menus (jika ada)
        if (Schema::hasTable('menus')) {
            $now = now();
            $existing = DB::table('menus')->where('route_name', 'bku-operasional.index')->first();
            if (!$existing) {
                $menuId = DB::table('menus')->insertGetId([
                    'name'        => 'BKU Operasional (Amil)',
                    'route_name'  => 'bku-operasional.index',
                    'url_pattern' => 'bku-operasional*',
                    'icon'        => 'fas fa-wallet',
                    'category'    => 'Pentasyarufan & Penyaluran',
                    'sort_order'  => 28,
                    'is_active'   => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);

                if (Schema::hasTable('user_has_menus')) {
                    $allUserIds = DB::table('users')->pluck('id');
                    foreach ($allUserIds as $userId) {
                        DB::table('user_has_menus')->insertOrIgnore([
                            'user_id'    => $userId,
                            'menu_id'    => $menuId,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ]);
                    }
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
            $menu = DB::table('menus')->where('route_name', 'bku-operasional.index')->first();
            if ($menu) {
                if (Schema::hasTable('user_has_menus')) {
                    DB::table('user_has_menus')->where('menu_id', $menu->id)->delete();
                }
                DB::table('menus')->where('id', $menu->id)->delete();
            }
        }

        Schema::dropIfExists('bku_operasionals');
        Schema::dropIfExists('kategori_operasionals');
    }
};
