<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendRekeningQrisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_utama_tidak_memuat_mandiri_dan_bca_di_metode_penyaluran(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // Memastikan option transfer_mandiri dan transfer_bca sudah dihapus dari select
        $response->assertDontSee('value="transfer_mandiri"', false);
        $response->assertDontSee('value="transfer_bca"', false);
        $response->assertDontSee('Transfer Bank Mandiri');
        $response->assertDontSee('Transfer Bank BCA');

        // Memastikan option BRI dan BSI serta QRIS tetap ada
        $response->assertSee('value="transfer_bri"', false);
        $response->assertSee('value="transfer_bsi"', false);
        $response->assertSee('value="qris"', false);
    }

    public function test_bagian_rekening_resmi_menampilkan_bri_bersanding_qris_dan_bsi_bersanding_qris(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // Pasangan 1: Rekening BRI dan QRIS BRI
        $response->assertSee('00411001448562');
        $response->assertSee('qris_lazisnu_bri.jpg');
        $response->assertSee('modalQrisBri');

        // Pasangan 2: Rekening BSI dan QRIS BSI
        $response->assertSee('2026070708');
        $response->assertSee('qris_lazisnu_bsi.jpg');
        $response->assertSee('modalQrisBsi');

        // Memastikan tidak menggunakan TAB nav-pills di modal
        $response->assertDontSee('pills-tab-qris');
        $response->assertDontSee('pills-tabContentQris');
    }

    public function test_donasi_online_qris_menampilkan_qris_statis_bri(): void
    {
        $this->seed(\Database\Seeders\FrontendPortalSeeder::class);
        $campaign = \App\Models\Campaign::first();

        $postResponse = $this->post(route('donation.store'), [
            'campaign_id'    => $campaign->id,
            'kategori'       => 'infaq',
            'donor_name'     => 'Ahmad Donatur',
            'donor_phone'    => '081234567890',
            'amount'         => 50000,
            'payment_method' => 'qris',
        ]);

        $postResponse->assertSessionHasNoErrors();
        $donation = \App\Models\OnlineDonation::where('donor_phone', '081234567890')->first();
        $this->assertNotNull($donation);

        $postResponse->assertRedirect(route('donation.success', ['code' => $donation->donation_code]));

        $successResponse = $this->get(route('donation.success', ['code' => $donation->donation_code]));
        $successResponse->assertStatus(200);
        $successResponse->assertSee('qris_lazisnu_bri.jpg');
        $successResponse->assertSee('QRIS Resmi Bank BRI');
        $successResponse->assertSee('LAZISNU LAMONGAN');
        $successResponse->assertDontSee('QRIS DINAMIS');
    }

    public function test_download_qris_statis_mengunduh_file_qris_bri(): void
    {
        $response = $this->get(route('donation.download-qris', ['code' => 'SAMPLE_CODE']));
        $response->assertStatus(200);
        $this->assertTrue(str_contains($response->headers->get('content-disposition') ?? '', 'QRIS_LAZISNU_BRI_LAMONGAN.jpg'));
    }

    public function test_struktur_tab_donasi_kilat_dan_restrukturisasi_highlight_4_pilar(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // 1. Tab Donasi Kilat: Hanya Infaq dan Zakat (Program 5 Pilar dihapus dari tab)
        $response->assertSee('id="infaq-tab"', false);
        $response->assertSee('id="zakat-tab"', false);
        $response->assertDontSee('id="program-tab"', false);

        // 2. Section 4 counter-section di bawah tabel transparansi dihapus
        $response->assertDontSee('class="counter-section"', false);
        $response->assertDontSee('Kotak Koin Beredar');

        // 3. Highlight KOIN NU memiliki 4 pilar informasi
        $response->assertSee('AKUMULASI TERVERIFIKASI');
        $response->assertSee('TERSEBAR DI KELUARGA');
        $response->assertSee('PENYALURAN TEREALISASI');
        $response->assertSee('JARINGAN PELAYANAN');
        $response->assertSee('MWC /');
    }

    public function test_form_donasi_kilat_memuat_input_kategori_donasi(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // Memastikan input hidden kategori_donasi ada dengan default infaq
        $response->assertSee('<input type="hidden" name="kategori" id="kategori_donasi" value="infaq">', false);
    }

    public function test_donasi_kampanye_spesifik_ntt_tersimpan_dan_tertampil_sebagai_donasi_ntt(): void
    {
        $this->seed(\Database\Seeders\FrontendPortalSeeder::class);
        $campaignNtt = \App\Models\Campaign::create([
            'category_id'       => 1,
            'kategori_judul'    => 'Donasi NTT',
            'title'             => 'Peduli Bencana NTT',
            'slug'              => 'peduli-bencana-ntt-' . uniqid(),
            'short_description' => 'Bantuan untuk NTT',
            'story_description' => 'Kisah bantuan',
            'target_amount'     => 50000000,
            'collected_amount'  => 0,
            'start_date'        => now(),
            'status'            => 'active',
            'is_featured'       => true,
            'is_emergency'      => true,
        ]);

        // Simpan donasi dengan kategori dari kampanye NTT
        $response = $this->post(route('donation.store'), [
            'campaign_id'    => $campaignNtt->id,
            'kategori'       => 'Donasi NTT',
            'donor_name'     => 'Donatur NTT',
            'donor_phone'    => '081299990099',
            'amount'         => 100000,
            'payment_method' => 'transfer_bri',
        ]);

        $response->assertSessionHasNoErrors();

        // 1. Pastikan tersimpan di database dengan kategori "Donasi NTT"
        $this->assertDatabaseHas('online_donations', [
            'donor_phone' => '081299990099',
            'kategori'    => 'Donasi NTT',
        ]);

        $donation = \App\Models\OnlineDonation::where('donor_phone', '081299990099')->first();
        $this->assertNotNull($donation);

        // 2. Pastikan accessor getKategoriLabelAttribute mengembalikan "Donasi NTT"
        $this->assertEquals('Donasi NTT', $donation->kategori_label);

        // 3. Pastikan halaman success menampilkan label "Donasi NTT"
        $successResponse = $this->get(route('donation.success', ['code' => $donation->donation_code]));
        $successResponse->assertStatus(200);
        $successResponse->assertSee('Donasi NTT');
    }

    public function test_backend_menolak_zakat_dengan_rekening_infaq_dan_sebaliknya(): void
    {
        $this->seed(\Database\Seeders\FrontendPortalSeeder::class);
        $campaign = \App\Models\Campaign::first();

        // 1. Zakat mencoba pakai Transfer BRI -> Harus DITOLAK
        $failZakat = $this->post(route('donation.store'), [
            'campaign_id'    => $campaign->id,
            'kategori'       => 'zakat',
            'donor_name'     => 'Donatur Zakat',
            'donor_phone'    => '081299990001',
            'amount'         => 100000,
            'payment_method' => 'transfer_bri',
        ]);
        $failZakat->assertSessionHasErrors(['payment_method']);

        // 2. Infaq mencoba pakai Transfer BSI -> Harus DITOLAK
        $failInfaq = $this->post(route('donation.store'), [
            'campaign_id'    => $campaign->id,
            'kategori'       => 'infaq',
            'donor_name'     => 'Donatur Infaq',
            'donor_phone'    => '081299990002',
            'amount'         => 50000,
            'payment_method' => 'transfer_bsi',
        ]);
        $failInfaq->assertSessionHasErrors(['payment_method']);

        // 3. Zakat dengan Transfer BSI -> Berhasil dan tersimpan sebagai 'zakat'
        $successZakat = $this->post(route('donation.store'), [
            'campaign_id'    => $campaign->id,
            'kategori'       => 'zakat',
            'donor_name'     => 'Donatur Zakat Sah',
            'donor_phone'    => '081299990003',
            'amount'         => 250000,
            'payment_method' => 'transfer_bsi',
        ]);
        $successZakat->assertSessionHasNoErrors();
        $this->assertDatabaseHas('online_donations', [
            'donor_phone'    => '081299990003',
            'payment_method' => 'transfer_bsi',
            'kategori'       => 'zakat',
        ]);

        // 4. Infaq dengan Transfer BRI -> Berhasil dan tersimpan sebagai 'infaq'
        $successInfaq = $this->post(route('donation.store'), [
            'campaign_id'    => $campaign->id,
            'kategori'       => 'infaq',
            'donor_name'     => 'Donatur Infaq Sah',
            'donor_phone'    => '081299990004',
            'amount'         => 50000,
            'payment_method' => 'transfer_bri',
        ]);
        $successInfaq->assertSessionHasNoErrors();
        $this->assertDatabaseHas('online_donations', [
            'donor_phone'    => '081299990004',
            'payment_method' => 'transfer_bri',
            'kategori'       => 'infaq',
        ]);
    }

    public function test_data_patching_mengubah_transaksi_bsi_menjadi_zakat(): void
    {
        $this->seed(\Database\Seeders\FrontendPortalSeeder::class);
        $campaign = \App\Models\Campaign::first();

        // Buat donasi lama yang sempat salah tercatat sebagai infaq padahal pakai transfer_bsi
        $oldDonationId = \Illuminate\Support\Facades\DB::table('online_donations')->insertGetId([
            'donation_code'   => 'DON-TEST-OLD01',
            'campaign_id'     => $campaign->id,
            'kategori'        => 'infaq',
            'donor_name'      => 'Donatur Lama BSI',
            'donor_phone'     => '081288880001',
            'amount'          => 200000,
            'unique_code'     => 123,
            'total_amount'    => 200123,
            'payment_method'  => 'transfer_bsi',
            'payment_status'  => 'pending',
            'created_at'      => now(),
            'updated_at'      => now(),
        ]);

        // Jalankan logika patching
        \Illuminate\Support\Facades\DB::table('online_donations')
            ->whereIn('payment_method', ['transfer_bsi', 'qris_bsi'])
            ->where(function ($q) {
                $q->whereNull('kategori')
                  ->orWhereRaw('LOWER(kategori) != ?', ['zakat']);
            })
            ->update([
                'kategori'   => 'zakat',
                'updated_at' => now(),
            ]);

        // Verifikasi kategori sudah terpatch menjadi zakat
        $patched = \Illuminate\Support\Facades\DB::table('online_donations')->find($oldDonationId);
        $this->assertEquals('zakat', $patched->kategori);
    }
}
