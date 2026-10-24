<?php

namespace Database\Seeders;

use App\Models\Donator;
use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed database dengan data resmi 15 MWC, Petugas, Ranting, Relawan, dan Transaksi
     */
    public function run(): void
    {
        // 1. Buat Roles Hirarki
        $rolePc = Role::firstOrCreate(
            ['name' => Role::PC],
            [
                'label'       => 'PC Lazisnu (Super Admin Cabang)',
                'description' => 'Akses penuh dashboard cabang & monitoring 15 MWC',
            ]
        );

        $roleMwc = Role::firstOrCreate(
            ['name' => Role::MWC],
            [
                'label'       => 'MWC Lazisnu (Admin Kecamatan)',
                'description' => 'Memantau data & mengelola Ranting di bawah naungan MWC',
            ]
        );

        $roleRanting = Role::firstOrCreate(
            ['name' => Role::RANTING],
            [
                'label'       => 'Ranting Lazisnu (Admin Desa / Operator)',
                'description' => 'Mengelola Relawan RT/RW & menginput setoran koin bulanan',
            ]
        );

        // 2. Data Resmi 15 MWC
        $mwcData = [
            ['code' => 'M01', 'name' => 'MWC NU Brondong', 'kecamatan' => 'Brondong', 'petugas' => 'Petugas MWCNU Brondong', 'email' => 'mwc.brondong@lazisnu.or.id'],
            ['code' => 'M02', 'name' => 'MWC NU Deket', 'kecamatan' => 'Deket', 'petugas' => 'Petugas MWCNU Deket', 'email' => 'mwc.deket@lazisnu.or.id'],
            ['code' => 'M03', 'name' => 'MWC NU Glagah', 'kecamatan' => 'Glagah', 'petugas' => 'Petugas MWCNU Glagah', 'email' => 'mwc.glagah@lazisnu.or.id'],
            ['code' => 'M04', 'name' => 'MWCNU Kalitengah', 'kecamatan' => 'Kalitengah', 'petugas' => 'Petugas MWCNU Kalitengah', 'email' => 'mwc.kalitengah@lazisnu.or.id'],
            ['code' => 'M05', 'name' => 'MWCNU Karangbinangun', 'kecamatan' => 'Karangbinangun', 'petugas' => 'Petugas MWCNU Karangbinangun', 'email' => 'mwc.karangbinangun@lazisnu.or.id'],
            ['code' => 'M06', 'name' => 'MWCNU Karanggeneng', 'kecamatan' => 'Karanggeneng', 'petugas' => 'Petugas MWCNU Karanggeneng', 'email' => 'mwc.karanggeneng@lazisnu.or.id'],
            ['code' => 'M07', 'name' => 'MWCNU Kembangbahu', 'kecamatan' => 'Kembangbahu', 'petugas' => 'Petugas MWCNU Kembangbahu', 'email' => 'mwc.kembangbahu@lazisnu.or.id'],
            ['code' => 'M08', 'name' => 'MWCNU Lamongan', 'kecamatan' => 'Lamongan', 'petugas' => 'Petugas MWCNU Lamongan', 'email' => 'mwc.lamongan@lazisnu.or.id'],
            ['code' => 'M09', 'name' => 'MWCNU Laren', 'kecamatan' => 'Laren', 'petugas' => 'Petugas MWCNU Laren', 'email' => 'mwc.laren@lazisnu.or.id'],
            ['code' => 'M10', 'name' => 'MWCNU Mantup', 'kecamatan' => 'Mantup', 'petugas' => 'Petugas MWCNU Mantup', 'email' => 'mwc.mantup@lazisnu.or.id'],
            ['code' => 'M11', 'name' => 'MWCNU Paciran', 'kecamatan' => 'Paciran', 'petugas' => 'Petugas MWCNU Paciran', 'email' => 'mwc.paciran@lazisnu.or.id'],
            ['code' => 'M12', 'name' => 'MWCNU Sarirejo', 'kecamatan' => 'Sarirejo', 'petugas' => 'Petugas MWCNU Sarirejo', 'email' => 'mwc.sarirejo@lazisnu.or.id'],
            ['code' => 'M13', 'name' => 'MWCNU Solokuro', 'kecamatan' => 'Solokuro', 'petugas' => 'Petugas MWCNU Solokuro', 'email' => 'mwc.solokuro@lazisnu.or.id'],
            ['code' => 'M14', 'name' => 'MWCNU Tikung', 'kecamatan' => 'Tikung', 'petugas' => 'Petugas MWCNU Tikung', 'email' => 'mwc.tikung@lazisnu.or.id'],
            ['code' => 'M15', 'name' => 'MWCNU Turi', 'kecamatan' => 'Turi', 'petugas' => 'Petugas MWCNU Turi', 'email' => 'mwc.turi@lazisnu.or.id'],
        ];

        // 3. Buat Akun Super Admin PC (Cabang)
        $defaultPassword = Hash::make('password');

        $userPc = User::where('username', 'adminpc')->orWhere('email', 'admin.pc@lazisnu.or.id')->first();
        if (!$userPc) {
            $userPc = User::create([
                'name'       => 'Super Admin PC Lazisnu',
                'username'   => 'adminpc',
                'email'      => 'admin.pc@lazisnu.or.id',
                'phone'      => '081122334400',
                'role_id'    => $rolePc->id,
                'is_active'  => true,
                'password'   => $defaultPassword,
            ]);
        }

        $mwcs = [];

        foreach ($mwcData as $item) {
            // A. Simpan data MWC
            $mwc = Mwc::create([
                'code'      => $item['code'],
                'name'      => $item['name'],
                'kecamatan' => $item['kecamatan'],
                'address'   => "Kantor {$item['name']}",
                'phone'     => '0812' . rand(10000000, 99999999),
            ]);
            $mwcs[] = $mwc;

            // B. Buat Akun Petugas / Admin MWC (15 Akun)
            User::create([
                'name'      => $item['petugas'],
                'username'  => $item['code'], // M01, M02, s/d M15
                'email'     => $item['email'],
                'phone'     => '0813' . rand(10000000, 99999999),
                'role_id'   => $roleMwc->id,
                'mwc_id'    => $mwc->id,
                'is_active' => true,
                'password'  => $defaultPassword,
            ]);
        }

        // 3. Buat Seluruh 258 Data Ranting NU Resmi se-Kabupaten Lamongan
        $this->call(RantingDataSeeder::class);
        $allRantings = Ranting::all();

        // 4. Buat Akun Pengguna untuk SELURUH 258 Ranting NU
        $rantingUsers = [];
        foreach ($allRantings as $ranting) {
            $rantingUsers[$ranting->id] = User::create([
                'name'       => $ranting->pj_name ?: ("Petugas " . $ranting->name),
                'username'   => $ranting->code, // M01R01, M01R02, s/d M15R19
                'email'      => strtolower($ranting->code) . '@lazisnu.or.id',
                'phone'      => '0852' . rand(10000000, 99999999),
                'role_id'    => $roleRanting->id,
                'mwc_id'     => $ranting->mwc_id,
                'ranting_id' => $ranting->id,
                'is_active'  => true,
                'password'   => $defaultPassword,
            ]);
        }
        $userRanting = $rantingUsers[$allRantings[0]->id];

        // 5. Buat Relawan Koordinator RT/RW pada setiap Ranting
        $allRelawans = [];
        $nikBase = 3524011000000001;

        foreach ($allRantings as $idx => $ranting) {
            for ($k = 1; $k <= 2; $k++) {
                $allRelawans[] = Relawan::create([
                    'ranting_id'  => $ranting->id,
                    'nama'        => "Relawan Kord. RT 0{$k} ({$ranting->name})",
                    'nik'         => (string) ($nikBase + ($idx * 10) + $k),
                    'no_hp'       => '0812' . rand(10000000, 99999999),
                    'area_tugas'  => "RT 0{$k} / RW 01",
                    'status'      => Relawan::STATUS_ACTIVE,
                    'joined_date' => Carbon::now()->subMonths(6)->toDateString(),
                ]);
            }
        }

        // 6. Buat Transaksi Koin Infaq per Relawan (3 bulan terakhir hingga bulan ini)
        // Menghasilkan data performa untuk seluruh 15 MWC
        $amountTiers = [
            'M01' => [2500000, 3200000, 2800000], // Brondong (Pesisir kuat)
            'M11' => [2900000, 3500000, 3100000], // Paciran (Pesisir kuat)
            'M08' => [2200000, 2800000, 2500000], // Lamongan Kota
            'M06' => [1800000, 2100000, 1950000], // Karanggeneng
            'M04' => [1500000, 1800000, 1650000], // Kalitengah
            'default' => [900000, 1300000, 1600000],
        ];

        for ($m = 2; $m >= 0; $m--) {
            $monthDate = Carbon::now()->subMonths($m);
            $period = $monthDate->format('Y-m');

            $penthong = \App\Models\Penthong::firstOrCreate(
                ['bulan' => (int) $monthDate->month, 'tahun' => (int) $monthDate->year],
                [
                    'nama_periode' => 'Penthong ' . $monthDate->month . ' - ' . $monthDate->year,
                    'status'       => ($m === 0 ? \App\Models\Penthong::STATUS_AKTIF : \App\Models\Penthong::STATUS_TUTUP),
                    'is_active'    => ($m === 0),
                    'start_date'   => $monthDate->copy()->startOfMonth()->toDateString(),
                    'end_date'     => $monthDate->copy()->endOfMonth()->toDateString(),
                ]
            );

            foreach ($allRelawans as $relawan) {
                $mwcCode = $relawan->ranting->mwc->code;
                $tier = $amountTiers[$mwcCode] ?? $amountTiers['default'];
                $nominal = $tier[array_rand($tier)] + (rand(1, 10) * 10000);

                $collectionDay = rand(5, 25);
                $collectionDate = Carbon::create($monthDate->year, $monthDate->month, $collectionDay)->toDateString();

                KoinTransaction::create([
                    'penthong_id'        => $penthong->id,
                    'periode_pentong_id' => $penthong->id,
                    'relawan_id'         => $relawan->id,
                    'ranting_id'         => $relawan->ranting_id,
                    'mwc_id'             => $relawan->ranting->mwc_id,
                    'user_id'            => $rantingUsers[$relawan->ranting_id]->id ?? $userRanting->id,
                    'amount'             => $nominal,
                    'collection_date'    => $collectionDate,
                    'period'             => $period,
                    'notes'              => "Penghimpunan koin warga wilayah {$relawan->area_tugas}",
                    'status'             => KoinTransaction::STATUS_CONFIRMED,
                ]);
            }
        }

        // 7. Buat Data Donatur / Pemilik Kotak KOIN NU dan Pembagian Petugas Wilayah
        $donorNames = [
            'H. Achmad Sholihin', 'Hj. Siti Aminah', 'Ust. M. Khoiruddin', 'Bpk. Slamet Riyadi',
            'Ibu Nur Hayati', 'Bpk. H. Zainuddin', 'Ibu Hj. Maryam', 'Bpk. Abdul Wahab',
            'Ibu Siti Fatimah', 'Bpk. M. Syukron', 'Bpk. Kasno Wibowo', 'Ibu Anisah Rahmawati'
        ];

        // Seed donatur pada 15 ranting pertama (mewakili berbagai MWC)
        $sampleRantings = $allRantings->take(15);
        $boxCounter = 1;

        foreach ($sampleRantings as $ranting) {
            $rantingRelawans = Relawan::where('ranting_id', $ranting->id)->get();
            $relawan1 = $rantingRelawans[0] ?? null;
            $relawan2 = $rantingRelawans[1] ?? null;

            // Kotak 1 & 2 untuk Relawan 1 (RT 01 RW 01)
            if ($relawan1) {
                for ($d = 1; $d <= 2; $d++) {
                    $boxNum = sprintf("KLG-%s-0101-%03d", $ranting->code, $d);
                    Donator::create([
                        'box_number'  => $boxNum,
                        'name'        => $donorNames[($boxCounter++) % count($donorNames)],
                        'nik'         => sprintf("35240155%08d", $boxCounter),
                        'phone'       => '0812' . rand(20000000, 89999999),
                        'ranting_id'  => $ranting->id,
                        'relawan_id'  => $relawan1->id,
                        'address'     => "Jl. Pesantren No. " . rand(1, 99) . ", Dusun Krajan",
                        'rt'          => '01',
                        'rw'          => '01',
                        'status'      => Donator::STATUS_ACTIVE,
                        'joined_date' => Carbon::now()->subMonths(rand(2, 8))->toDateString(),
                        'notes'       => "Kotak KOIN dipegang donatur tetap wilayah {$relawan1->area_tugas}",
                    ]);
                }
            }

            // Kotak 3 & 4 untuk Relawan 2 (RT 02 RW 01)
            if ($relawan2) {
                for ($d = 3; $d <= 4; $d++) {
                    $boxNum = sprintf("KLG-%s-0201-%03d", $ranting->code, $d);
                    Donator::create([
                        'box_number'  => $boxNum,
                        'name'        => $donorNames[($boxCounter++) % count($donorNames)],
                        'nik'         => sprintf("35240155%08d", $boxCounter),
                        'phone'       => '0812' . rand(20000000, 89999999),
                        'ranting_id'  => $ranting->id,
                        'relawan_id'  => $relawan2->id,
                        'address'     => "Jl. Masjid Baitul Muttaqin No. " . rand(1, 99),
                        'rt'          => '02',
                        'rw'          => '01',
                        'status'      => Donator::STATUS_ACTIVE,
                        'joined_date' => Carbon::now()->subMonths(rand(2, 8))->toDateString(),
                        'notes'       => "Kotak KOIN dipegang donatur tetap wilayah {$relawan2->area_tugas}",
                    ]);
                }
            }

            // Kotak 5: Kotak yang belum memiliki petugas pengampu (RT 03 RW 02)
            $boxNum = sprintf("KLG-%s-0302-%03d", $ranting->code, 5);
            Donator::create([
                'box_number'  => $boxNum,
                'name'        => $donorNames[($boxCounter++) % count($donorNames)],
                'nik'         => sprintf("35240155%08d", $boxCounter),
                'phone'       => '0813' . rand(20000000, 89999999),
                'ranting_id'  => $ranting->id,
                'relawan_id'  => null,
                'address'     => "Jl. Balai Desa Lama No. " . rand(1, 50),
                'rt'          => '03',
                'rw'          => '02',
                'status'      => Donator::STATUS_ACTIVE,
                'joined_date' => Carbon::now()->subMonths(rand(1, 3))->toDateString(),
                'notes'       => "Donatur baru mendaftar, perlu pembagian petugas relawan",
            ]);
        }

        // 8. Daftarkan dan sinkronkan menu Buku Kas Umum (BKU)
        $this->call(BkuMenuSeeder::class);
    }
}
