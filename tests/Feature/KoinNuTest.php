<?php

namespace Tests\Feature;

use App\Models\KoinTransaction;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class KoinNuTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_root_renders_portal_home(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);
        $response->assertSee('NU CARE - LAZISNU');
    }

    public function test_pc_dashboard_renders_with_15_mwcs_comparison(): void
    {
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Pimpinan Cabang');
        $response->assertSee('MWC NU Brondong');
        $response->assertSee('MWCNU Paciran');
        $response->assertSee('MWCNU Lamongan');
        $response->assertSee('MWCNU Kalitengah');
        $response->assertSee('Grafik Performa Komparasi 15 Majelis Wakil Cabang');
    }

    public function test_relawan_index_and_template_download(): void
    {
        $response = $this->get('/relawans');
        $response->assertStatus(200);
        $response->assertSee('Manajemen Relawan (Kordinator RT / RW)');
        $response->assertSee('Bulk Import Excel / CSV');

        $templateResponse = $this->get('/relawans/template');
        $templateResponse->assertStatus(200);
        $templateResponse->assertHeader('Content-Disposition', 'attachment; filename="template_import_relawan_koin_nu.csv"');
    }

    public function test_can_bulk_import_relawan_via_csv(): void
    {
        $userRanting = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();
        $this->actingAs($userRanting);

        $csvContent = "nama,nik,no_hp,area_tugas\n"
                    . "Kord Baru RT 09,3524019900000001,081299990001,RT 09 / RW 03\n"
                    . "Kord Baru RT 10,3524019900000002,081299990002,RT 10 / RW 03\n";

        $file = UploadedFile::fake()->createWithContent('relawans.csv', $csvContent);

        $response = $this->post('/relawans/import', [
            'file' => $file,
        ]);

        $response->assertRedirect('/relawans');
        $this->assertDatabaseHas('relawans', [
            'nik'        => '3524019900000001',
            'nama'       => 'Kord Baru RT 09',
            'ranting_id' => $userRanting->ranting_id,
        ]);
    }

    public function test_secure_transaction_store_prevents_cross_ranting_tampering(): void
    {
        $userRanting = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();
        $this->actingAs($userRanting);

        // Relawan di ranting yang SAMA -> Berhasil
        $ownRelawan = Relawan::where('ranting_id', $userRanting->ranting_id)->first();

        $response = $this->post('/transactions', [
            'relawan_id'      => $ownRelawan->id,
            'amount'          => 1500000,
            'collection_date' => now()->toDateString(),
            'notes'           => 'Setoran infaq koin aman',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('koin_transactions', [
            'relawan_id' => $ownRelawan->id,
            'ranting_id' => $userRanting->ranting_id,
            'amount'     => 1500000.00,
        ]);

        // Relawan di ranting LAIN -> Ditolak (403 Forbidden)
        $otherRanting = Ranting::where('id', '!=', $userRanting->ranting_id)->first();
        $otherRelawan = Relawan::where('ranting_id', $otherRanting->id)->first();

        $forbiddenResponse = $this->post('/transactions', [
            'relawan_id'      => $otherRelawan->id,
            'amount'          => 2000000,
            'collection_date' => now()->toDateString(),
        ]);

        $forbiddenResponse->assertStatus(403);
    }

    public function test_mwc_master_data_views(): void
    {
        $response = $this->get('/mwcs');
        $response->assertStatus(200);
        $response->assertSee('MWC NU Brondong');
        $response->assertSee('M01');

        $showResponse = $this->get('/mwcs/1');
        $showResponse->assertStatus(200);
        $showResponse->assertSee('M01R01');
        $showResponse->assertSee('Ranting Brengkok');
    }

    public function test_ranting_master_data_and_code_generation(): void
    {
        $response = $this->get('/rantings');
        $response->assertStatus(200);
        $response->assertSee('M01R01');
        $response->assertSee('Ranting Brengkok');

        // Test auto code generator endpoint
        $ajaxResponse = $this->get('/rantings/get-next-code?mwc_id=1&number=5');
        $ajaxResponse->assertStatus(200);
        $ajaxResponse->assertJson(['code' => 'M01R05']);

        // Test create new ranting with auto-code {MWC}R{No}
        $postResponse = $this->post('/rantings', [
            'mwc_id'     => 1,
            'name'       => 'Ranting Uji Coba Baru',
            'desa'       => 'Desa Uji Coba',
            'nomor_urut' => 99,
        ]);

        $postResponse->assertRedirect('/rantings');
        $this->assertDatabaseHas('rantings', [
            'code'   => 'M01R99',
            'name'   => 'Ranting Uji Coba Baru',
            'mwc_id' => 1,
        ]);
    }

    public function test_login_page_renders_successfully(): void
    {
        $response = $this->get('/login');
        $response->assertStatus(200);
        $response->assertSee('Masuk ke Akun Anda');
        $response->assertSee('Akun Login Default');
        $response->assertSee('adminpc');
        $response->assertSee('M01');
        $response->assertSee('M01R01');
    }

    public function test_pc_mwc_and_ranting_can_authenticate(): void
    {
        // 1. Login sebagai PC menggunakan Username 'adminpc'
        $pcLogin = $this->post('/login', [
            'login'    => 'adminpc',
            'password' => 'password',
        ]);
        $pcLogin->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->isPc());

        // Logout
        $this->get('/logout');
        $this->assertGuest();

        // 2. Login sebagai MWC menggunakan Kode MWC 'M01'
        $mwcLogin = $this->post('/login', [
            'login'    => 'M01',
            'password' => 'password',
        ]);
        $mwcLogin->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->isMwc());

        // Logout
        $this->get('/logout');

        // 3. Login sebagai Ranting menggunakan Kode Ranting 'M01R01'
        $rantingLogin = $this->post('/login', [
            'login'    => 'M01R01',
            'password' => 'password',
        ]);
        $rantingLogin->assertRedirect('/dashboard');
        $this->assertAuthenticated();
        $this->assertTrue(auth()->user()->isRanting());
    }

    public function test_user_management_index_and_password_update(): void
    {
        $response = $this->get('/users');
        $response->assertStatus(200);
        $response->assertSee('Daftar Akun Pengguna');
        $response->assertSee('adminpc');
        $response->assertSee('M01');
        $response->assertSee('M01R01');

        // Edit salah satu user dan ubah password
        $testUser = User::where('username', 'M01R01')->first();
        $editResponse = $this->put("/users/{$testUser->id}", [
            'name'      => 'Petugas Ranting Brengkok Updated',
            'username'  => 'M01R01',
            'email'     => 'm01r01@lazisnu.or.id',
            'phone'     => '081234567890',
            'is_active' => 1,
            'password'  => 'passwordbaru123',
        ]);

        $editResponse->assertRedirect('/users');
        $this->assertDatabaseHas('users', [
            'id'    => $testUser->id,
            'name'  => 'Petugas Ranting Brengkok Updated',
            'phone' => '081234567890',
        ]);
    }
}

