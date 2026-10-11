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

    public function test_admin_can_access_report_print(): void
    {
        $response = $this->actingAs($this->adminUser)->get('/laporan/cetak');
        $response->assertStatus(200);
        $response->assertSee('NU CARE - LAZISNU PCNU LAMONGAN');
    }
}
