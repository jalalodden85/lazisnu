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

        // 1. Daftarkan / Perbarui Menu "Hasil Koin" di tabel menus
        $existingMenu = DB::table('menus')->where('route_name', 'pentasyarufan.hasil-koin.index')->first();

        if (!$existingMenu) {
            $menuId = DB::table('menus')->insertGetId([
                'name'        => 'Hasil Koin',
                'route_name'  => 'pentasyarufan.hasil-koin.index',
                'url_pattern' => 'pentasyarufan/hasil-koin*',
                'icon'        => 'fas fa-coins',
                'category'    => 'Pentasyarufan & Penyaluran',
                'parent_id'   => null,
                'sort_order'  => 24,
                'is_active'   => true,
                'created_at'  => $now,
                'updated_at'  => $now,
            ]);
        } else {
            $menuId = $existingMenu->id;
            DB::table('menus')->where('id', $menuId)->update([
                'name'        => 'Hasil Koin',
                'url_pattern' => 'pentasyarufan/hasil-koin*',
                'icon'        => 'fas fa-coins',
                'category'    => 'Pentasyarufan & Penyaluran',
                'sort_order'  => 24,
                'is_active'   => true,
                'updated_at'  => $now,
            ]);
        }

        // 2. Sinkronkan hak akses menu "Hasil Koin" ke seluruh akun pengguna (Superadmin, PC, MWC, Ranting)
        if (Schema::hasTable('user_has_menus') && Schema::hasTable('users')) {
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            $menu = DB::table('menus')->where('route_name', 'pentasyarufan.hasil-koin.index')->first();
            if ($menu && Schema::hasTable('user_has_menus')) {
                DB::table('user_has_menus')->where('menu_id', $menu->id)->delete();
                DB::table('menus')->where('id', $menu->id)->delete();
            }
        }
    }
};
