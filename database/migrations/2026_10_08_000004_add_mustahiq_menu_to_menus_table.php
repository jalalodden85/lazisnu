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
        if (Schema::hasTable('menus')) {
            $now = now();
            
            // 1. Cek atau tambahkan menu Data Mustahiq
            $existing = DB::table('menus')->where('route_name', 'mustahiqs.index')->first();
            
            if (!$existing) {
                $menuId = DB::table('menus')->insertGetId([
                    'name'        => 'Data Mustahiq',
                    'route_name'  => 'mustahiqs.index',
                    'url_pattern' => 'mustahiqs*',
                    'icon'        => 'fas fa-hand-holding-heart',
                    'category'    => 'Master Data',
                    'sort_order'  => 15,
                    'is_active'   => true,
                    'created_at'  => $now,
                    'updated_at'  => $now,
                ]);
            } else {
                $menuId = $existing->id;
            }

            // 2. Berikan hak akses menu ini secara otomatis ke semua user yang ada (PC, MWC, Ranting)
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

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('menus')) {
            $menu = DB::table('menus')->where('route_name', 'mustahiqs.index')->first();
            if ($menu) {
                if (Schema::hasTable('user_has_menus')) {
                    DB::table('user_has_menus')->where('menu_id', $menu->id)->delete();
                }
                DB::table('menus')->where('id', $menu->id)->delete();
            }
        }
    }
};
