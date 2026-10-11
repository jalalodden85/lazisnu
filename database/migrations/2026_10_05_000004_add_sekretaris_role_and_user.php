<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = now();

        // 1. Tambahkan role pc_sekretaris ke tabel roles
        DB::table('roles')->updateOrInsert(
            ['name' => 'pc_sekretaris'],
            [
                'name'        => 'pc_sekretaris',
                'label'       => 'Sekretaris PC',
                'description' => 'Administrasi kesekretariatan, pengarsipan laporan capaian, data MWC/Ranting/Relawan, dan publikasi berita.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ]
        );

        $roleSekretaris = DB::table('roles')->where('name', 'pc_sekretaris')->value('id');

        // 2. Buat akun default Sekretaris PC LAZISNU Lamongan
        $existingUser = DB::table('users')->where('username', 'sekretaris_lazisnu')->first();
        if (!$existingUser) {
            $userId = DB::table('users')->insertGetId([
                'username'   => 'sekretaris_lazisnu',
                'name'       => 'H. Mochammad Baihaqi, S.Ag (Sekretaris PC)',
                'email'      => 'sekretaris.pc@lazisnu.or.id',
                'phone'      => '081234567805',
                'role_id'    => $roleSekretaris,
                'mwc_id'     => null,
                'ranting_id' => null,
                'is_active'  => true,
                'password'   => Hash::make('password'),
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        } else {
            $userId = $existingUser->id;
            DB::table('users')->where('id', $userId)->update([
                'role_id' => $roleSekretaris,
            ]);
        }

        // 3. Daftarkan Hak Akses Menu Dinamis default untuk Sekretaris PC di user_has_menus
        if (Schema::hasTable('menus') && Schema::hasTable('user_has_menus')) {
            $sekretarisRoutes = [
                'dashboard',
                'mwcs.index',
                'rantings.index',
                'relawans.index',
                'donators.index',
                'donators.distribution',
                'admin.articles.index',
                'reports.penthong',
                'reports.koin',
                'reports.wilayah',
                'reports.relawan',
                'reports.donatur',
                'admin.financial-reports.index',
                'buku-panduan',
            ];

            $menuIds = DB::table('menus')->whereIn('route_name', $sekretarisRoutes)->pluck('id');

            foreach ($menuIds as $menuId) {
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
        $sekretarisUser = DB::table('users')->where('username', 'sekretaris_lazisnu')->first();
        if ($sekretarisUser) {
            DB::table('user_has_menus')->where('user_id', $sekretarisUser->id)->delete();
            DB::table('users')->where('id', $sekretarisUser->id)->delete();
        }

        DB::table('roles')->where('name', 'pc_sekretaris')->delete();
    }
};
