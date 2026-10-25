<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\FrontendPortalSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat user Admin PC
        $rolePc = \App\Models\Role::firstOrCreate(
            ['name' => \App\Models\Role::PC],
            ['label' => 'PC Lazisnu', 'display_name' => 'PC Lazisnu', 'description' => 'Super Admin Cabang']
        );

        $this->adminUser = User::where('username', 'adminpc')->first() ?? User::create([
            'name' => 'Super Admin PC',
            'username' => 'adminpc',
            'email' => 'adminpc@lazisnu.or.id',
            'role_id' => $rolePc->id,
            'is_active' => true,
            'password' => bcrypt('password'),
        ]);

        $this->seed(FrontendPortalSeeder::class);
    }

    public function test_admin_can_access_categories_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/categories');
        $response->assertStatus(200);
        $response->assertSee('5 Pilar Program');
    }

    public function test_admin_can_access_campaigns_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/campaigns');
        $response->assertStatus(200);
        $response->assertSee('Kampanye Donasi');
    }

    public function test_admin_can_access_online_donations_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/online-donations');
        $response->assertStatus(200);
        $response->assertSee('Donasi Online Masuk');
    }

    public function test_admin_can_access_articles_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/articles');
        $response->assertStatus(200);
        $response->assertSee('Portal Berita');
    }

    public function test_admin_can_access_sliders_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/sliders');
        $response->assertStatus(200);
        $response->assertSee('Banner Slider');
    }

    public function test_admin_can_access_financial_reports_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/financial-reports');
        $response->assertStatus(200);
        $response->assertSee('Laporan Keuangan');
    }

    public function test_admin_can_access_site_settings_index(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/site-settings');
        $response->assertStatus(200);
        $response->assertSee('Pengaturan Portal');
    }

    public function test_admin_can_access_report_koin(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/laporan/koin');
        $response->assertStatus(200);
        $response->assertSee('Rekapitulasi Perolehan Koin NU');
    }

    public function test_admin_can_access_report_wilayah(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/laporan/wilayah');
        $response->assertStatus(200);
        $response->assertSee('Laporan Capaian');
    }

    public function test_admin_can_access_report_relawan(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/laporan/relawan');
        $response->assertStatus(200);
        $response->assertSee('Kinerja Relawan');
    }

    public function test_admin_can_access_report_donatur(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/laporan/donatur');
        $response->assertStatus(200);
        $response->assertSee('Distribusi Kotak Koin');
    }

    public function test_admin_can_paginate_online_donations_with_10_50_and_all(): void
    {
        $campaign = \App\Models\Campaign::first();
        for ($i = 1; $i <= 15; $i++) {
            \App\Models\OnlineDonation::create([
                'donation_code' => 'DON-TEST-' . $i,
                'campaign_id' => $campaign?->id ?? 1,
                'donor_name' => 'Donatur Test ' . $i,
                'donor_phone' => '0812345678' . $i,
                'amount' => 50000,
                'total_amount' => 50000,
                'payment_method' => 'qris',
                'payment_status' => 'verified',
            ]);
        }

        $response10 = $this->actingAs($this->adminUser)->get('/admin/online-donations?per_page=10');
        $response10->assertStatus(200);
        $response10->assertSee('10 baris');
        $response10->assertSee('pagination');
        $response10->assertSee('page-item');

        $responsePage2 = $this->actingAs($this->adminUser)->get('/admin/online-donations?per_page=10&page=2');
        $responsePage2->assertStatus(200);
        $responsePage2->assertSee('pagination');
        $responsePage2->assertSee('page-item');

        $response50 = $this->actingAs($this->adminUser)->get('/admin/online-donations?per_page=50');
        $response50->assertStatus(200);
        $response50->assertSee('50 baris');

        $responseAll = $this->actingAs($this->adminUser)->get('/admin/online-donations?per_page=all');
        $responseAll->assertStatus(200);
        $responseAll->assertSee('Seluruh baris');
    }

    public function test_admin_can_filter_online_donations_by_kategori_and_month(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/admin/online-donations?kategori=zakat&month=2026-10');
        $response->assertStatus(200);
        $response->assertSee('Donasi Online Masuk');
    }

    public function test_admin_can_export_online_donations_to_excel_and_pdf(): void
    {
        // 1. Export Excel dengan filter
        $excelResponse = $this->actingAs($this->adminUser)->get('/admin/online-donations/export/excel?kategori=zakat&month=2026-10');
        $excelResponse->assertStatus(200);
        $this->assertTrue(
            str_contains($excelResponse->headers->get('content-disposition', ''), '.xlsx'),
            'Response header Content-Disposition harus menyertakan file .xlsx'
        );

        // 2. Export PDF dengan filter
        $pdfResponse = $this->actingAs($this->adminUser)->get('/admin/online-donations/export/pdf?kategori=zakat&month=2026-10');
        $pdfResponse->assertStatus(200);
        $pdfResponse->assertSee('LAPORAN DATA PENERIMAAN DONASI ONLINE');
        $pdfResponse->assertSee('Zakat');

        // 3. Export Semua Data tanpa filter (Excel)
        $allExcelResponse = $this->actingAs($this->adminUser)->get('/admin/online-donations/export/excel');
        $allExcelResponse->assertStatus(200);
        $this->assertTrue(
            str_contains($allExcelResponse->headers->get('content-disposition', ''), '.xlsx')
        );

        // 4. Export Semua Data tanpa filter (PDF)
        $allPdfResponse = $this->actingAs($this->adminUser)->get('/admin/online-donations/export/pdf');
        $allPdfResponse->assertStatus(200);
        $allPdfResponse->assertSee('LAPORAN DATA PENERIMAAN DONASI ONLINE');
        $allPdfResponse->assertSee('Semua Bulan');
    }
}

