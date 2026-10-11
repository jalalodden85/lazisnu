<?php

namespace Tests\Feature;

use Database\Seeders\FrontendPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendPortalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(FrontendPortalSeeder::class);
    }

    public function test_homepage_is_accessible(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('NU CARE - LAZISNU');
        $response->assertSee('5 Pilar Program');
    }

    public function test_programs_page_is_accessible(): void
    {
        $response = $this->get('/program');
        $response->assertStatus(200);
        $response->assertSee('Program Kebaikan');
    }

    public function test_articles_page_is_accessible(): void
    {
        $response = $this->get('/berita');
        $response->assertStatus(200);
        $response->assertSee('Kabar Pentasyarufan');
    }

    public function test_calculator_page_is_accessible(): void
    {
        $response = $this->get('/kalkulator-zakat');
        $response->assertStatus(200);
        $response->assertSee('Kalkulator Zakat');
    }

    public function test_reports_page_is_accessible(): void
    {
        $response = $this->get('/laporan-publik');
        $response->assertStatus(200);
        $response->assertSee('Transparansi');
    }

    public function test_online_donation_submission(): void
    {
        $campaign = \App\Models\Campaign::first();

        $response = $this->post('/donasi/proses', [
            'campaign_id' => $campaign->id,
            'donor_name' => 'Ahmad Test',
            'donor_phone' => '081234567890',
            'donor_email' => 'ahmad@test.com',
            'amount' => 50000,
            'payment_method' => 'transfer_bsi',
            'prayer_message' => 'Semoga berkah',
            'is_anonymous' => 0,
        ]);

        $response->assertStatus(302);
        $donation = \App\Models\OnlineDonation::where('donor_name', 'Ahmad Test')->first();
        $this->assertNotNull($donation);
        $this->assertEquals(50000, $donation->amount);
    }
}
