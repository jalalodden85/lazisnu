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
        if (!Schema::hasTable('menus')) {
            return;
        }

        $now = now();

        // 1. Daftarkan / Perbarui Menu "Buku Kas Umum (BKU)" di tabel menus
        $existingMenu = DB::table('menus')->where('route_name', 'bku.index')->first();

        if (!$existingMenu) {
            $menuId = DB::table('menus')->insertGetId([
                'name'        => 'Buku Kas Umum (BKU)',
                'route_name'  => 'bku.index',
                'url_pattern' => 'bku*',
                'icon'        => 'fas fa-book',
                'category'    => 'Pentasyarufan & Penyaluran',
                'sort_order'  => 29,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        } else {
            $menuId = $existingMenu->id;
            DB::table('menus')->where('id', $menuId)->update([
                'name'        => 'Buku Kas Umum (BKU)',
                'url_pattern' => 'bku*',
                'icon'        => 'fas fa-book',
                'category'    => 'Pentasyarufan & Penyaluran',
                'sort_order'  => 29,
                'is_active'   => true,
                'updated_at'  => $now,
            ]);
        }

        // Pastikan urutan BKU Operasional setelah BKU
        DB::table('menus')->where('route_name', 'bku-operasional.index')->update([
            'sort_order' => 30,
            'updated_at' => $now,
        ]);

        // 2. Sinkronkan akses menu BKU ke user_has_menus untuk seluruh akun pengguna saat ini
        if (Schema::hasTable('user_has_menus') && Schema::hasTable('users')) {
            $userIds = DB::table('users')->pluck('id');
            foreach ($userIds as $userId) {
                DB::table('user_has_menus')->insertOrIgnore([
                    'user_id'    => $userId,
                    'menu_id'    => $menuId,
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
        if (Schema::hasTable('menus')) {
            $menu = DB::table('menus')->where('route_name', 'bku.index')->first();
            if ($menu && Schema::hasTable('user_has_menus')) {
                DB::table('user_has_menus')->where('menu_id', $menu->id)->delete();
                DB::table('menus')->where('id', $menu->id)->delete();
            }
        }
    }
};
