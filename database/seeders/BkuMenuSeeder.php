<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class BkuMenuSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (!Schema::hasTable('menus')) {
            return;
        }

        $now = now();

        $menu = DB::table('menus')->where('route_name', 'bku.index')->first();
        if (!$menu) {
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
            $menuId = $menu->id;
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

        DB::table('menus')->where('route_name', 'bku-operasional.index')->update([
            'sort_order' => 30,
            'updated_at' => $now,
        ]);

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
}
