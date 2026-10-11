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
        // 1. Pastikan role Super Admin PC terupdate & role baru ditambahkan
        $now = now();
        $roles = [
            'pc_superadmin' => [
                'name'        => 'pc_superadmin',
                'label'       => 'Super Admin PC',
                'description' => 'Akses absolut seluruh modul, konfigurasi, dan manajemen akun pengguna.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            'pc_ketua' => [
                'name'        => 'pc_ketua',
                'label'       => 'Ketua LAZISNU PC',
                'description' => 'Akses eksekutif monitoring dashboard statistik dan laporan real-time (Read-Only).',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            'pc_bendahara' => [
                'name'        => 'pc_bendahara',
                'label'       => 'Bendahara / Keuangan PC',
                'description' => 'Rekonsiliasi kas masuk-keluar, pencetakan LPJ, dan approval pencairan dana.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            'pc_admin_koin' => [
                'name'        => 'pc_admin_koin',
                'label'       => 'Admin Program KOIN NU',
                'description' => 'Mengelola periode penthong, verifikasi setoran koin kaleng MWC & Ranting.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
            'pc_admin_online' => [
                'name'        => 'pc_admin_online',
                'label'       => 'Admin Donasi Online & Website',
                'description' => 'Mengelola kampanye crowdfunding, banner portal, dan verifikasi mutasi donasi online.',
                'created_at'  => $now,
                'updated_at'  => $now,
            ],
        ];

        // Update role 'pc' yang lama jika ada agar tetap kompatibel atau buat pc_superadmin
        $rolePc = DB::table('roles')->where('name', 'pc')->first();
        if ($rolePc) {
            DB::table('roles')->where('id', $rolePc->id)->update([
                'name'        => 'pc_superadmin',
                'label'       => 'Super Admin PC',
                'description' => 'Akses absolut seluruh modul, konfigurasi, dan manajemen akun pengguna.',
            ]);
            unset($roles['pc_superadmin']);
        }

        foreach ($roles as $roleData) {
            DB::table('roles')->updateOrInsert(['name' => $roleData['name']], $roleData);
        }

        // Ambil mapping role_id yang sudah ada
        $roleSuperAdmin = DB::table('roles')->where('name', 'pc_superadmin')->value('id');
        $roleKetua      = DB::table('roles')->where('name', 'pc_ketua')->value('id');
        $roleBendahara  = DB::table('roles')->where('name', 'pc_bendahara')->value('id');
        $roleAdminKoin  = DB::table('roles')->where('name', 'pc_admin_koin')->value('id');
        $roleAdminOnline = DB::table('roles')->where('name', 'pc_admin_online')->value('id');

        $defaultPassword = Hash::make('password');

        // 2. Buat / Update 5 Akun Pengguna Tingkat PC
        $pcUsers = [
            [
                'username'  => 'adminpc',
                'name'      => 'Super Admin PC Lazisnu',
                'email'     => 'admin.pc@lazisnu.or.id',
                'phone'     => '081234567800',
                'role_id'   => $roleSuperAdmin,
                'mwc_id'    => null,
                'ranting_id'=> null,
                'is_active' => true,
                'password'  => $defaultPassword,
                'created_at'=> $now,
                'updated_at'=> $now,
            ],
            [
                'username'  => 'ketua_lazisnu',
                'name'      => 'Drs. H. Suharjuddin, M.Pd.I (Ketua LAZISNU)',
                'email'     => 'ketua.pc@lazisnu.or.id',
                'phone'     => '081234567801',
                'role_id'   => $roleKetua,
                'mwc_id'    => null,
                'ranting_id'=> null,
                'is_active' => true,
                'password'  => $defaultPassword,
                'created_at'=> $now,
                'updated_at'=> $now,
            ],
            [
                'username'  => 'bendahara_lazisnu',
                'name'      => 'H. Ahmad Syarifuddin, SE (Bendahara PC)',
                'email'     => 'bendahara.pc@lazisnu.or.id',
                'phone'     => '081234567802',
                'role_id'   => $roleBendahara,
                'mwc_id'    => null,
                'ranting_id'=> null,
                'is_active' => true,
                'password'  => $defaultPassword,
                'created_at'=> $now,
                'updated_at'=> $now,
            ],
            [
                'username'  => 'admin_koin',
                'name'      => 'Moch. Machrus, S.Kom (Admin KOIN NU)',
                'email'     => 'koin.pc@lazisnu.or.id',
                'phone'     => '081234567803',
                'role_id'   => $roleAdminKoin,
                'mwc_id'    => null,
                'ranting_id'=> null,
                'is_active' => true,
                'password'  => $defaultPassword,
                'created_at'=> $now,
                'updated_at'=> $now,
            ],
            [
                'username'  => 'admin_online',
                'name'      => 'Fauzan Adhim, S.Kom (Admin Donasi Online)',
                'email'     => 'online.pc@lazisnu.or.id',
                'phone'     => '081234567804',
                'role_id'   => $roleAdminOnline,
                'mwc_id'    => null,
                'ranting_id'=> null,
                'is_active' => true,
                'password'  => $defaultPassword,
                'created_at'=> $now,
                'updated_at'=> $now,
            ],
        ];

        foreach ($pcUsers as $userData) {
            $existing = DB::table('users')->where('username', $userData['username'])->first();
            if ($existing) {
                DB::table('users')->where('id', $existing->id)->update([
                    'name'     => $userData['name'],
                    'role_id'  => $userData['role_id'],
                    'email'    => $userData['email'],
                    'phone'    => $userData['phone'],
                    'is_active'=> true,
                ]);
            } else {
                DB::table('users')->insert($userData);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('users')->whereIn('username', [
            'ketua_lazisnu', 'bendahara_lazisnu', 'admin_koin', 'admin_online'
        ])->delete();
    }
};
