<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\OnlineDonation;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransparencyDonationSeeder extends Seeder
{
    public function run(): void
    {
        $campaign = Campaign::first();
        $campaignId = $campaign ? $campaign->id : 1;

        $samples = [
            [
                'donor_name' => 'H. Ahmad Muzakki',
                'kategori' => 'Zakat',
                'alamat' => 'Desa Plosowahyu RT 02 RW 01, Kec. Lamongan',
                'amount' => 2500000.00,
                'is_anonymous' => false,
                'verified_at' => Carbon::now()->subMinutes(15),
            ],
            [
                'donor_name' => 'Hj. Siti Aminah',
                'kategori' => 'Infaq',
                'alamat' => 'Kelurahan Sukorejo, Kec. Lamongan',
                'amount' => 150000.00,
                'is_anonymous' => false,
                'verified_at' => Carbon::now()->subMinutes(42),
            ],
            [
                'donor_name' => 'Fulan bin Fulan',
                'kategori' => 'Shadaqah',
                'alamat' => 'Desa Sugio RT 05 RW 02, Kec. Sugio',
                'amount' => 100000.00,
                'is_anonymous' => true,
                'verified_at' => Carbon::now()->subHours(1),
            ],
            [
                'donor_name' => 'K.H. Nur Hadi',
                'kategori' => 'Wakaf',
                'alamat' => 'Ds. Datinawong, Kec. Babat',
                'amount' => 1000000.00,
                'is_anonymous' => false,
                'verified_at' => Carbon::now()->subHours(2),
            ],
            [
                'donor_name' => 'Jamaah Tahlil An-Nahdliyah',
                'kategori' => 'KOIN NU',
                'alamat' => 'Ranting Deket Kulon, Kec. Deket',
                'amount' => 450000.00,
                'is_anonymous' => false,
                'verified_at' => Carbon::now()->subHours(3),
            ],
            [
                'donor_name' => 'Hamba Allah Peduli',
                'kategori' => 'Kampanye Bencana',
                'alamat' => 'Kec. Karanggeneng, Kab. Lamongan',
                'amount' => 500000.00,
                'is_anonymous' => true,
                'verified_at' => Carbon::now()->subHours(4),
            ],
            [
                'donor_name' => 'Drs. H. Miftahul Huda',
                'kategori' => 'Zakat',
                'alamat' => 'Jl. Veteran No. 12 RT 01, Kec. Lamongan',
                'amount' => 1250000.00,
                'is_anonymous' => false,
                'verified_at' => Carbon::now()->subHours(5),
            ],
            [
                'donor_name' => 'Keluarga Besar Alm. H. Sholihin',
                'kategori' => 'Infaq',
                'alamat' => 'Ds. Paji, Kec. Pucuk',
                'amount' => 300000.00,
                'is_anonymous' => false,
                'verified_at' => Carbon::now()->subHours(6),
            ],
            [
                'donor_name' => 'Sahabat Ansor Lamongan',
                'kategori' => 'KOIN NU',
                'alamat' => 'Kec. Tikung',
                'amount' => 750000.00,
                'is_anonymous' => false,
                'verified_at' => Carbon::now()->subHours(8),
            ],
            [
                'donor_name' => 'Hamba Allah',
                'kategori' => 'Shadaqah',
                'alamat' => 'Ds. Moropelang, Kec. Babat',
                'amount' => 50000.00,
                'is_anonymous' => true,
                'verified_at' => Carbon::now()->subHours(10),
            ],
            [
                'donor_name' => 'Hj. Mahmudah',
                'kategori' => 'Wakaf',
                'alamat' => 'Ds. Karanglangit, Kec. Lamongan',
                'amount' => 2000000.00,
                'is_anonymous' => false,
                'verified_at' => Carbon::now()->subHours(12),
            ],
            [
                'donor_name' => 'Warga Nahdliyin Maduran',
                'kategori' => 'KOIN NU',
                'alamat' => 'Kec. Maduran',
                'amount' => 620000.00,
                'is_anonymous' => false,
                'verified_at' => Carbon::now()->subDay(),
            ],
            [
                'donor_name' => 'K.H. Masruri Syahid',
                'kategori' => 'Zakat',
                'alamat' => 'Ds. Mantup, Kec. Mantup',
                'amount' => 3500000.00,
                'is_anonymous' => false,
                'verified_at' => Carbon::now()->subDays(2),
            ]
        ];

        foreach ($samples as $s) {
            OnlineDonation::create([
                'donation_code' => 'DON-' . date('Ymd') . '-' . strtoupper(Str::random(5)),
                'campaign_id' => $campaignId,
                'kategori' => $s['kategori'],
                'donor_name' => $s['donor_name'],
                'donor_phone' => '0812' . rand(10000000, 99999999),
                'donor_email' => null,
                'alamat' => $s['alamat'],
                'is_anonymous' => $s['is_anonymous'],
                'amount' => $s['amount'],
                'unique_code' => rand(101, 899),
                'total_amount' => $s['amount'],
                'nominal_terverifikasi' => $s['amount'],
                'payment_method' => 'transfer_bsi',
                'payment_status' => 'verified',
                'verified_at' => $s['verified_at'],
            ]);
        }
    }
}
