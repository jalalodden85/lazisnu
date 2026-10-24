<?php

namespace Tests\Feature;

use App\Models\Menu;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class BkuRbacTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * 1. Verifikasi registrasi menu Buku Kas Umum (BKU) di database
     */
    public function test_bku_menu_is_registered_in_database_menus_table(): void
    {
        $bkuMenu = Menu::where('route_name', 'bku.index')->first();

        $this->assertNotNull($bkuMenu, 'Menu Buku Kas Umum (BKU) harus terdaftar di tabel menus');
        $this->assertEquals('Buku Kas Umum (BKU)', $bkuMenu->name);
        $this->assertEquals('bku*', $bkuMenu->url_pattern);
        $this->assertEquals('Pentasyarufan & Penyaluran', $bkuMenu->category);
        $this->assertTrue((bool) $bkuMenu->is_active);
    }

    /**
     * 2. Verifikasi menu BKU muncul di halaman Manajemen Pengguna (sisi Super Admin)
     */
    public function test_bku_menu_appears_in_user_edit_toggle_switches(): void
    {
        $superAdmin = User::whereHas('role', fn($q) => $q->where('name', 'pc_superadmin'))->first()
            ?? User::where('username', 'adminpc')->first();

        $rantingUser = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();

        $response = $this->actingAs($superAdmin)->get(route('users.edit', $rantingUser->id));
        $response->assertStatus(200);
        $response->assertSee('Buku Kas Umum (BKU)');
        $response->assertSee('BKU Operasional (Amil)');
    }

    /**
     * 3. Verifikasi sidebar menampilkan menu BKU & BKU Operasional saat aktif
     */
    public function test_sidebar_renders_menus_when_user_has_permissions(): void
    {
        $rantingUser = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();
        $bkuMenu = Menu::where('route_name', 'bku.index')->first();
        $bkuOpsMenu = Menu::where('route_name', 'bku-operasional.index')->first();

        // Berikan izin menu
        $rantingUser->menus()->syncWithoutDetaching([$bkuMenu->id, $bkuOpsMenu->id]);
        $rantingUser->clearMenuCache();

        $response = $this->actingAs($rantingUser)->get(route('dashboard'));
        $response->assertStatus(200);
        $response->assertSee('Buku Kas Umum (BKU)');
        $response->assertSee('BKU Operasional (Amil)');
        $response->assertSee(route('bku.index'));
        $response->assertSee(route('bku-operasional.index'));

        // Gate checking
        $this->assertTrue(Gate::forUser($rantingUser)->allows('akses-bku'));
        $this->assertTrue(Gate::forUser($rantingUser)->allows('akses-bku-operasional'));
    }

    /**
     * 4. Verifikasi sidebar menyembunyikan menu BKU & BKU Operasional saat dinonaktifkan
     */
    public function test_sidebar_hides_menus_when_user_permissions_are_deactivated(): void
    {
        $rantingUser = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();
        $bkuMenu = Menu::where('route_name', 'bku.index')->first();
        $bkuOpsMenu = Menu::where('route_name', 'bku-operasional.index')->first();

        // Cabut izin menu
        $rantingUser->menus()->detach([$bkuMenu->id, $bkuOpsMenu->id]);
        $rantingUser->clearMenuCache();

        $response = $this->actingAs($rantingUser)->get(route('dashboard'));
        $response->assertStatus(200);

        // Sidebar TIDAK boleh me-render link atau label menu yang dinonaktifkan
        $response->assertDontSee(route('bku.index'));
        $response->assertDontSee(route('bku-operasional.index'));

        // Gate checking
        $this->assertFalse(Gate::forUser($rantingUser)->allows('akses-bku'));
        $this->assertFalse(Gate::forUser($rantingUser)->allows('akses-bku-operasional'));
    }

    /**
     * 5. Verifikasi URL manual ditolak middleware dan diredirect ke Dashboard dengan flash error
     */
    public function test_accessing_deactivated_bku_or_bku_operasional_redirects_to_dashboard_with_error(): void
    {
        $rantingUser = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();
        $bkuMenu = Menu::where('route_name', 'bku.index')->first();
        $bkuOpsMenu = Menu::where('route_name', 'bku-operasional.index')->first();

        // Cabut hak akses BKU dan BKU Operasional
        $rantingUser->menus()->detach([$bkuMenu->id, $bkuOpsMenu->id]);
        $rantingUser->clearMenuCache();

        // 1. Coba akses manual /bku
        $responseBku = $this->actingAs($rantingUser)->get(route('bku.index'));
        $responseBku->assertRedirect(route('dashboard'));
        $responseBku->assertSessionHas('error', 'Fitur ini sedang dinonaktifkan oleh Administrator.');

        // 2. Coba akses manual /bku-operasional
        $responseOps = $this->actingAs($rantingUser)->get(route('bku-operasional.index'));
        $responseOps->assertRedirect(route('dashboard'));
        $responseOps->assertSessionHas('error', 'Fitur ini sedang dinonaktifkan oleh Administrator.');

        // 3. Coba akses via alias /pentasyarufan/bku-operasional
        $responseAlias = $this->actingAs($rantingUser)->get('/pentasyarufan/bku-operasional');
        $responseAlias->assertRedirect(route('bku-operasional.index'));
    }

    /**
     * 6. Verifikasi request AJAX/JSON ke fitur yang dimatikan mengembalikan 403 Forbidden
     */
    public function test_json_request_to_deactivated_menu_returns_403_forbidden(): void
    {
        $mwcUser = User::whereHas('role', fn($q) => $q->where('name', 'mwc'))->first();
        $bkuOpsMenu = Menu::where('route_name', 'bku-operasional.index')->first();

        // Nonaktifkan BKU Operasional
        $mwcUser->menus()->detach([$bkuOpsMenu->id]);
        $mwcUser->clearMenuCache();

        $response = $this->actingAs($mwcUser)
            ->withHeaders(['Accept' => 'application/json'])
            ->get(route('bku-operasional.index'));

        $response->assertStatus(403);
        $response->assertJson([
            'success' => false,
            'message' => 'Fitur ini sedang dinonaktifkan oleh Administrator.',
        ]);
    }

    /**
     * 7. Verifikasi Super Admin tetap memiliki akses penuh meskipun user biasa dibatasi
     */
    public function test_super_admin_always_has_full_access(): void
    {
        $superAdmin = User::whereHas('role', fn($q) => $q->where('name', 'pc_superadmin'))->first()
            ?? User::where('username', 'adminpc')->first();

        $this->assertTrue($superAdmin->hasMenuAccess('bku.index'));
        $this->assertTrue($superAdmin->hasMenuAccess('bku-operasional.index'));

        $resBku = $this->actingAs($superAdmin)->get(route('bku.index'));
        $resBku->assertStatus(200);

        $resOps = $this->actingAs($superAdmin)->get(route('bku-operasional.index'));
        $resOps->assertStatus(200);
    }

    /**
     * 8. Verifikasi saat Super Admin mengubah toggle di UserController, cache terhapus & langsung berefek
     */
    public function test_super_admin_toggling_off_menu_clears_cache_and_takes_effect_immediately(): void
    {
        $superAdmin = User::whereHas('role', fn($q) => $q->where('name', 'pc_superadmin'))->first()
            ?? User::where('username', 'adminpc')->first();

        $rantingUser = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();
        $bkuMenu = Menu::where('route_name', 'bku.index')->first();
        $bkuOpsMenu = Menu::where('route_name', 'bku-operasional.index')->first();

        // Awalnya berikan akses ke BKU dan BKU Ops
        $rantingUser->menus()->sync([$bkuMenu->id, $bkuOpsMenu->id]);
        $this->assertTrue($rantingUser->hasMenuAccess('bku.index'));
        $this->assertTrue($rantingUser->hasMenuAccess('bku-operasional.index'));

        // Super Admin update: Hanya memberikan BKU, mematikan BKU Operasional
        $updatePayload = [
            'name'      => $rantingUser->name,
            'username'  => $rantingUser->username,
            'email'     => $rantingUser->email,
            'phone'     => $rantingUser->phone,
            'is_active' => 1,
            'menus'     => [$bkuMenu->id], // BKU Operasional tidak dicentang (OFF)
        ];

        $response = $this->actingAs($superAdmin)->put(route('users.update', $rantingUser->id), $updatePayload);
        $response->assertRedirect(route('users.index'));

        // Refresh user instance & clear in-memory cache
        $rantingUser->clearMenuCache();
        $rantingUser->refresh();

        // BKU tetap aktif, BKU Operasional langsung mati tanpa stale cache
        $this->assertTrue($rantingUser->hasMenuAccess('bku.index'));
        $this->assertFalse($rantingUser->hasMenuAccess('bku-operasional.index'));

        // Request oleh Ranting: BKU sukses (200), BKU Operasional diblokir (redirect ke Dashboard)
        $this->actingAs($rantingUser)->get(route('bku.index'))->assertStatus(200);
        $this->actingAs($rantingUser)->get(route('bku-operasional.index'))
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('error', 'Fitur ini sedang dinonaktifkan oleh Administrator.');
    }
}
