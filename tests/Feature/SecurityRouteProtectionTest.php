<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SecurityRouteProtectionTest extends TestCase
{
    use RefreshDatabase;

    protected User $userSuperAdmin;

    protected function setUp(): void
    {
        parent::setUp();

        $roleSuperAdmin = Role::firstOrCreate(['name' => 'pc_superadmin'], [
            'display_name' => 'Super Admin PC',
            'level'        => 'pc',
            'description'  => 'Super Admin',
        ]);

        $this->userSuperAdmin = User::firstOrCreate(['username' => 'adminpc'], [
            'name'       => 'Super Admin PC Lazisnu',
            'email'      => 'admin.pc@lazisnu.or.id',
            'phone'      => '081234567800',
            'role_id'    => $roleSuperAdmin->id,
            'is_active'  => true,
            'password'   => bcrypt('password'),
        ]);
    }

    /**
     * TEST 1: Tamu (Guest / Tanpa Login) DILARANG mengakses seluruh halaman admin & internal.
     * Harus otomatis ditolak dan diarahkan (Redirect 302) ke /login dengan notifikasi sesi habis.
     */
    public function test_unauthenticated_user_cannot_access_internal_admin_pages(): void
    {
        $internalRoutes = [
            '/dashboard',
            '/users',
            '/mwcs',
            '/rantings',
            '/transactions',
            '/relawans',
            '/donators',
            '/laporan/koin',
            '/penthongs',
            '/admin/categories',
            '/admin/campaigns',
            '/admin/online-donations',
            '/admin/articles',
            '/admin/sliders',
            '/admin/financial-reports',
            '/admin/site-settings',
            '/pentasyarufan/pilar',
            '/pentasyarufan/proker',
            '/pentasyarufan/realisasi',
            '/pentasyarufan/laporan',
            '/bku',
            '/buku-panduan',
        ];

        foreach ($internalRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(302, "Route [{$route}] harus menolak unauthenticated access dengan redirect 302");
            $response->assertRedirect(route('login'), "Route [{$route}] harus mengarahkan ke halaman login");
            $response->assertSessionHas('error', 'Sesi Anda telah habis, silakan login kembali.');
        }
    }

    /**
     * TEST 2: Request API / AJAX tanpa token/sesi valid WAJIB ditolak dengan HTTP 401 Unauthorized.
     */
    public function test_unauthenticated_api_request_returns_401_unauthorized(): void
    {
        $apiEndpoints = [
            ['method' => 'postJson', 'url' => '/api/setoran'],
            ['method' => 'getJson',  'url' => '/api/periode-pentong'],
            ['method' => 'getJson',  'url' => '/api/periode-pentong/aktif'],
            ['method' => 'getJson',  'url' => '/api/laporan/cetak'],
            ['method' => 'getJson',  'url' => '/api/laporan/scope-options'],
            ['method' => 'postJson', 'url' => '/api/periode-pentong'],
        ];

        foreach ($apiEndpoints as $endpoint) {
            $method = $endpoint['method'];
            $url = $endpoint['url'];

            $response = $this->$method($url);
            $response->assertStatus(401, "API endpoint [{$url}] harus mengembalikan HTTP 401 untuk unauthenticated request");
            $response->assertJson([
                'success' => false,
            ]);
            $this->assertStringContainsString('Unauthenticated', $response->json('message'));
        }
    }

    /**
     * TEST 3: User yang terotentikasi (Logged In) dapat mengakses halaman internal tanpa hambatan.
     */
    public function test_authenticated_user_can_access_internal_pages(): void
    {
        $this->actingAs($this->userSuperAdmin);

        $response = $this->get(route('dashboard'));
        $response->assertStatus(200);

        $responseUsers = $this->get(route('users.index'));
        $responseUsers->assertStatus(200);
    }

    /**
     * TEST 4: Portal Publik tetap dapat diakses bebas oleh masyarakat / tamu tanpa harus login.
     */
    public function test_public_routes_remain_accessible_to_guests(): void
    {
        $publicRoutes = [
            '/',
            '/program',
            '/berita',
            '/kalkulator-zakat',
            '/laporan-publik',
            '/login',
        ];

        foreach ($publicRoutes as $route) {
            $response = $this->get($route);
            $response->assertStatus(200, "Portal publik [{$route}] harus tetap dapat diakses publik tanpa login");
        }
    }

    /**
     * TEST 5: Pengguna yang sudah login DILARANG mengakses halaman login kembali.
     * Sistem harus otomatis me-redirect ke halaman Dashboard (/dashboard) dengan HTTP 302.
     */
    public function test_authenticated_user_accessing_login_page_is_redirected_to_dashboard(): void
    {
        $this->actingAs($this->userSuperAdmin);

        $response = $this->get('/login');
        $response->assertStatus(302);
        $response->assertRedirect(route('dashboard'));

        // Begitu juga saat mencoba POST ke /login
        $responsePost = $this->post('/login', [
            'login'    => 'adminpc',
            'password' => 'password',
        ]);
        $responsePost->assertStatus(302);
        $responsePost->assertRedirect(route('dashboard'));
    }

    /**
     * TEST 6: Conditional Rendering Navbar - Pengguna Belum Login (Guest)
     * Menampilkan tombol Login, menyembunyikan tombol Dashboard dan dropdown logout.
     */
    public function test_guest_landing_page_navbar_shows_login_button(): void
    {
        $response = $this->get('/');
        $response->assertStatus(200);

        // Harus ada tombol Login
        $response->assertSee('Login');
        $response->assertDontSee('Login Petugas');

        // Tidak boleh ada tombol Dashboard di navbar utama atau form logout
        $response->assertDontSee('id="userMenuDropdown"', false);
    }

    /**
     * TEST 7: Conditional Rendering Navbar - Pengguna Sudah Login (Authenticated)
     * Menyembunyikan tombol Login, menampilkan tombol Dashboard dan Profil Dropdown dengan Logout.
     */
    public function test_authenticated_landing_page_navbar_shows_dashboard_and_logout(): void
    {
        $this->actingAs($this->userSuperAdmin);

        $response = $this->get('/');
        $response->assertStatus(200);

        // Menampilkan tombol Dashboard dan user profile
        $response->assertSee('Dashboard');
        $response->assertSee($this->userSuperAdmin->name);
        $response->assertSee('Logout');
        $response->assertSee('id="userMenuDropdown"', false);

        // Memastikan tombol Login Portal tamu disembunyikan
        $response->assertDontSee('<a href="' . route('login') . '" class="btn-login-portal"', false);
    }
}
