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
        // 1. Tabel Master Menu & Modul Sistem
        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100)->comment('Nama label menu yang tampil di sidebar');
            $table->string('route_name', 120)->nullable()->comment('Route name Laravel (misal: transactions.index)');
            $table->string('url_pattern', 150)->nullable()->comment('Pattern URL / prefix rute untuk pencocokan (misal: transactions*)');
            $table->string('icon', 60)->default('fas fa-circle')->comment('Icon FontAwesome');
            $table->string('category', 60)->default('Umum')->comment('Kategori / Header Grup Menu');
            $table->foreignId('parent_id')->nullable()->constrained('menus')->cascadeOnDelete();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['category', 'sort_order']);
            $table->index('route_name');
        });

        // 2. Pivot Table: user_has_menus (Many-to-Many User & Menu Permissions)
        Schema::create('user_has_menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('menu_id')->constrained('menus')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['user_id', 'menu_id']);
        });

        // 3. Seed Master Data Menu LAZISNU Lamongan
        $now = now();
        $menuList = [
            // Kategori: DASHBOARD
            [
                'name'        => 'Dashboard',
                'route_name'  => 'dashboard',
                'url_pattern' => 'dashboard*',
                'icon'        => 'fas fa-tachometer-alt',
                'category'    => 'Dashboard',
                'sort_order'  => 1,
            ],

            // Kategori: MASTER DATA
            [
                'name'        => 'Master Data MWC',
                'route_name'  => 'mwcs.index',
                'url_pattern' => 'mwcs*',
                'icon'        => 'fas fa-landmark',
                'category'    => 'Master Data',
                'sort_order'  => 10,
            ],
            [
                'name'        => 'Master Data Ranting',
                'route_name'  => 'rantings.index',
                'url_pattern' => 'rantings*',
                'icon'        => 'fas fa-mosque',
                'category'    => 'Master Data',
                'sort_order'  => 11,
            ],
            [
                'name'        => 'Master Data Relawan',
                'route_name'  => 'relawans.index',
                'url_pattern' => 'relawans*',
                'icon'        => 'fas fa-users',
                'category'    => 'Master Data',
                'sort_order'  => 12,
            ],
            [
                'name'        => 'Donatur Kotak KOIN',
                'route_name'  => 'donators.index',
                'url_pattern' => 'donators*',
                'icon'        => 'fas fa-hand-holding-heart',
                'category'    => 'Master Data',
                'sort_order'  => 13,
            ],
            [
                'name'        => 'Pembagian Tugas Relawan',
                'route_name'  => 'donators.distribution',
                'url_pattern' => 'donators/distribution*',
                'icon'        => 'fas fa-map-marked-alt',
                'category'    => 'Master Data',
                'sort_order'  => 14,
            ],

            // Kategori: TRANSAKSI & KOIN NU
            [
                'name'        => 'Periode Penthong (Buka Kotak)',
                'route_name'  => 'penthongs.index',
                'url_pattern' => 'penthongs*',
                'icon'        => 'fas fa-drum',
                'category'    => 'Transaksi & Operasional',
                'sort_order'  => 20,
            ],
            [
                'name'        => 'Input Setoran Koin',
                'route_name'  => 'transactions.create',
                'url_pattern' => 'transactions/create*',
                'icon'        => 'fas fa-plus-circle',
                'category'    => 'Transaksi & Operasional',
                'sort_order'  => 21,
            ],
            [
                'name'        => 'Riwayat Setoran Koin',
                'route_name'  => 'transactions.index',
                'url_pattern' => 'transactions*',
                'icon'        => 'fas fa-coins',
                'category'    => 'Transaksi & Operasional',
                'sort_order'  => 22,
            ],
            [
                'name'        => 'Verifikasi Donasi Online',
                'route_name'  => 'admin.online-donations.index',
                'url_pattern' => 'admin/online-donations*',
                'icon'        => 'fas fa-receipt',
                'category'    => 'Transaksi & Operasional',
                'sort_order'  => 23,
            ],

            // Kategori: PORTAL PUBLIK & WEBSITE
            [
                'name'        => '5 Pilar Program NU Care',
                'route_name'  => 'admin.categories.index',
                'url_pattern' => 'admin/categories*',
                'icon'        => 'fas fa-shapes',
                'category'    => 'Portal Website & Crowdfunding',
                'sort_order'  => 30,
            ],
            [
                'name'        => 'Kampanye Donasi Online',
                'route_name'  => 'admin.campaigns.index',
                'url_pattern' => 'admin/campaigns*',
                'icon'        => 'fas fa-bullhorn',
                'category'    => 'Portal Website & Crowdfunding',
                'sort_order'  => 31,
            ],
            [
                'name'        => 'Berita & Kabar Penyaluran',
                'route_name'  => 'admin.articles.index',
                'url_pattern' => 'admin/articles*',
                'icon'        => 'fas fa-newspaper',
                'category'    => 'Portal Website & Crowdfunding',
                'sort_order'  => 32,
            ],
            [
                'name'        => 'Banner Slider Utama',
                'route_name'  => 'admin.sliders.index',
                'url_pattern' => 'admin/sliders*',
                'icon'        => 'fas fa-images',
                'category'    => 'Portal Website & Crowdfunding',
                'sort_order'  => 33,
            ],
            [
                'name'        => 'Pengaturan Portal & Rekening',
                'route_name'  => 'admin.site-settings.index',
                'url_pattern' => 'admin/site-settings*',
                'icon'        => 'fas fa-sliders-h',
                'category'    => 'Portal Website & Crowdfunding',
                'sort_order'  => 34,
            ],

            // Kategori: LAPORAN & KEUANGAN
            [
                'name'        => 'Laporan Bagi Hasil Penthong',
                'route_name'  => 'reports.penthong',
                'url_pattern' => 'laporan/penthong*',
                'icon'        => 'fas fa-hand-holding-usd',
                'category'    => 'Laporan & Keuangan',
                'sort_order'  => 40,
            ],
            [
                'name'        => 'Rekap Perolehan Koin NU',
                'route_name'  => 'reports.koin',
                'url_pattern' => 'laporan/koin*',
                'icon'        => 'fas fa-chart-pie',
                'category'    => 'Laporan & Keuangan',
                'sort_order'  => 41,
            ],
            [
                'name'        => 'Laporan Capaian Wilayah',
                'route_name'  => 'reports.wilayah',
                'url_pattern' => 'laporan/wilayah*',
                'icon'        => 'fas fa-landmark',
                'category'    => 'Laporan & Keuangan',
                'sort_order'  => 42,
            ],
            [
                'name'        => 'Laporan Kinerja Relawan',
                'route_name'  => 'reports.relawan',
                'url_pattern' => 'laporan/relawan*',
                'icon'        => 'fas fa-user-check',
                'category'    => 'Laporan & Keuangan',
                'sort_order'  => 43,
            ],
            [
                'name'        => 'Laporan Distribusi Kotak',
                'route_name'  => 'reports.donatur',
                'url_pattern' => 'laporan/donatur*',
                'icon'        => 'fas fa-boxes',
                'category'    => 'Laporan & Keuangan',
                'sort_order'  => 44,
            ],
            [
                'name'        => 'Laporan Keuangan Audit (WTP)',
                'route_name'  => 'admin.financial-reports.index',
                'url_pattern' => 'admin/financial-reports*',
                'icon'        => 'fas fa-file-invoice-dollar',
                'category'    => 'Laporan & Keuangan',
                'sort_order'  => 45,
            ],

            // Kategori: PENGATURAN SISTEM
            [
                'name'        => 'Manajemen Akun Pengguna',
                'route_name'  => 'users.index',
                'url_pattern' => 'users*',
                'icon'        => 'fas fa-users-cog',
                'category'    => 'Pengaturan Sistem',
                'sort_order'  => 50,
            ],
            [
                'name'        => 'Buku Panduan Sistem (PDF)',
                'route_name'  => 'buku-panduan',
                'url_pattern' => 'buku-panduan*',
                'icon'        => 'fas fa-book-reader',
                'category'    => 'Pengaturan Sistem',
                'sort_order'  => 51,
            ],
        ];

        foreach ($menuList as $item) {
            $item['created_at'] = $now;
            $item['updated_at'] = $now;
            DB::table('menus')->insert($item);
        }

        // Berikan semua akses menu ke Super Admin (adminpc)
        $superAdmin = DB::table('users')->where('username', 'adminpc')->first();
        if ($superAdmin) {
            $allMenuIds = DB::table('menus')->pluck('id');
            foreach ($allMenuIds as $mId) {
                DB::table('user_has_menus')->insertOrIgnore([
                    'user_id'    => $superAdmin->id,
                    'menu_id'    => $mId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_has_menus');
        Schema::dropIfExists('menus');
    }
};
