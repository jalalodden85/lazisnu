<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProfileManagementTest extends TestCase
{
    use RefreshDatabase;

    protected User $userPc;
    protected User $userMwc;
    protected User $userRanting;

    protected function setUp(): void
    {
        parent::setUp();

        // Siapkan roles
        $rolePc = Role::firstOrCreate(['name' => 'pc_superadmin'], [
            'label'       => 'Super Admin PC',
            'description' => 'Super Admin',
        ]);
        $roleMwc = Role::firstOrCreate(['name' => 'mwc'], [
            'label'       => 'Admin MWC',
            'description' => 'MWC Kecamatan',
        ]);
        $roleRanting = Role::firstOrCreate(['name' => 'ranting'], [
            'label'       => 'Admin Ranting',
            'description' => 'Ranting Desa',
        ]);

        $this->userPc = User::where('username', 'adminpc')->first() ?? User::factory()->create([
            'name'       => 'Super Admin PC',
            'username'   => 'adminpc',
            'email'      => 'admin.pc@lazisnu.or.id',
            'role_id'    => $rolePc->id,
            'is_active'  => true,
            'password'   => Hash::make('password'),
        ]);

        $this->userMwc = User::where('username', 'M01')->first() ?? User::factory()->create([
            'name'       => 'Pengurus MWCNU Babat',
            'username'   => 'M01',
            'email'      => 'mwc.babat@lazisnu.or.id',
            'role_id'    => $roleMwc->id,
            'is_active'  => true,
            'password'   => Hash::make('password'),
        ]);

        $this->userRanting = User::where('username', 'M01R01')->first() ?? User::factory()->create([
            'name'       => 'Petugas Ranting Brengkok',
            'username'   => 'M01R01',
            'email'      => 'm01r01@lazisnu.or.id',
            'role_id'    => $roleRanting->id,
            'is_active'  => true,
            'password'   => Hash::make('password'),
        ]);

        $this->userPc->update(['password' => Hash::make('password')]);
        $this->userMwc->update(['password' => Hash::make('password')]);
        $this->userRanting->update(['password' => Hash::make('password')]);
    }

    /**
     * TEST 1: Tamu (Guest) ditolak dan diarahkan ke login saat membuka profil
     */
    public function test_guest_cannot_access_profile_page(): void
    {
        $response = $this->get(route('profile.edit'));
        $response->assertStatus(302);
        $response->assertRedirect(route('login'));

        $responsePut = $this->put(route('profile.update'), ['name' => 'Hack']);
        $responsePut->assertStatus(302);
        $responsePut->assertRedirect(route('login'));
    }

    /**
     * TEST 2: SELURUH Role (PC, MWC, Ranting) dapat mengakses halaman profil
     */
    public function test_all_user_roles_can_view_profile_page(): void
    {
        // 1. Role PC
        $this->actingAs($this->userPc);
        $responsePc = $this->get(route('profile.edit'));
        $responsePc->assertStatus(200);
        $responsePc->assertSee('Informasi Akun', false);
        $responsePc->assertSee($this->userPc->name);

        // 2. Role MWC
        $this->actingAs($this->userMwc);
        $responseMwc = $this->get(route('profile.edit'));
        $responseMwc->assertStatus(200);
        $responseMwc->assertSee($this->userMwc->name);

        // 3. Role Ranting
        $this->actingAs($this->userRanting);
        $responseRanting = $this->get(route('profile.edit'));
        $responseRanting->assertStatus(200);
        $responseRanting->assertSee($this->userRanting->name);
    }

    /**
     * TEST 3: User dapat memperbarui nama, email, dan mengunggah foto profil
     */
    public function test_user_can_update_profile_info_and_upload_avatar(): void
    {
        Storage::fake('public');

        $this->actingAs($this->userPc);

        $fakeAvatar = UploadedFile::fake()->create('avatar_baru.jpg', 200, 'image/jpeg');

        $response = $this->put(route('profile.update'), [
            'name'   => 'Nama Lengkap Baru PC',
            'email'  => 'emailbaru.pc@lazisnu.or.id',
            'phone'  => '081299887766',
            'avatar' => $fakeAvatar,
        ]);

        $response->assertRedirect(route('profile.edit'));
        $response->assertSessionHas('profile_success');

        $this->userPc->refresh();
        $this->assertEquals('Nama Lengkap Baru PC', $this->userPc->name);
        $this->assertEquals('emailbaru.pc@lazisnu.or.id', $this->userPc->email);
        $this->assertNotNull($this->userPc->avatar);

        Storage::disk('public')->assertExists($this->userPc->avatar);
    }

    /**
     * TEST 4: Mengunggah foto profil baru otomatis menghapus file foto profil lama di server
     */
    public function test_uploading_new_avatar_deletes_old_avatar_from_storage(): void
    {
        Storage::fake('public');

        $this->actingAs($this->userMwc);

        // Upload avatar ke-1
        $avatar1 = UploadedFile::fake()->create('avatar1.png', 100, 'image/png');
        $this->put(route('profile.update'), [
            'name'   => $this->userMwc->name,
            'email'  => $this->userMwc->email,
            'avatar' => $avatar1,
        ]);

        $this->userMwc->refresh();
        $oldAvatarPath = $this->userMwc->avatar;
        Storage::disk('public')->assertExists($oldAvatarPath);

        // Upload avatar ke-2
        $avatar2 = UploadedFile::fake()->create('avatar2.webp', 120, 'image/webp');
        $this->put(route('profile.update'), [
            'name'   => $this->userMwc->name,
            'email'  => $this->userMwc->email,
            'avatar' => $avatar2,
        ]);

        $this->userMwc->refresh();
        $newAvatarPath = $this->userMwc->avatar;

        // Pastikan file lama terhapus dari storage dan file baru eksis
        Storage::disk('public')->assertMissing($oldAvatarPath);
        Storage::disk('public')->assertExists($newAvatarPath);
        $this->assertNotEquals($oldAvatarPath, $newAvatarPath);
    }

    /**
     * TEST 5: Validasi foto profil menolak file non-gambar dan ukuran melebihi 2MB
     */
    public function test_avatar_validation_rejects_invalid_files(): void
    {
        Storage::fake('public');

        $this->actingAs($this->userPc);

        // 1. File text / bukan gambar
        $textDoc = UploadedFile::fake()->create('dokumen.pdf', 500, 'application/pdf');
        $responseInvalid = $this->put(route('profile.update'), [
            'name'   => $this->userPc->name,
            'email'  => $this->userPc->email,
            'avatar' => $textDoc,
        ]);
        $responseInvalid->assertSessionHasErrors('avatar');

        // 2. Gambar melebihi batas 2MB (2500 KB)
        $largeImage = UploadedFile::fake()->create('large.jpg', 2500, 'image/jpeg');
        $responseOversize = $this->put(route('profile.update'), [
            'name'   => $this->userPc->name,
            'email'  => $this->userPc->email,
            'avatar' => $largeImage,
        ]);
        $responseOversize->assertSessionHasErrors('avatar');
    }

    /**
     * TEST 6: Ubah Password GAGAL jika password saat ini salah
     */
    public function test_user_cannot_change_password_with_incorrect_current_password(): void
    {
        $this->actingAs($this->userPc);

        $response = $this->put(route('profile.password.update'), [
            'current_password'      => 'passwordsalah123',
            'password'              => 'passwordBaru2026',
            'password_confirmation' => 'passwordBaru2026',
        ]);

        $response->assertSessionHasErrors(['current_password']);
        $this->assertEquals('Password saat ini tidak sesuai.', session('errors')->first('current_password'));

        // Pastikan password di database tidak berubah
        $this->userPc->refresh();
        $this->assertTrue(Hash::check('password', $this->userPc->password));
    }

    /**
     * TEST 7: Ubah Password SUKSES jika password saat ini sesuai
     */
    public function test_user_can_change_password_with_correct_current_password(): void
    {
        $this->actingAs($this->userPc);

        $response = $this->put(route('profile.password.update'), [
            'current_password'      => 'password',
            'password'              => 'passwordBaru2026',
            'password_confirmation' => 'passwordBaru2026',
        ]);

        $response->assertRedirect(route('profile.edit') . '#card-password');
        $response->assertSessionHas('password_success');

        // Pastikan password baru tersimpan dan dapat diverifikasi
        $this->userPc->refresh();
        $this->assertTrue(Hash::check('passwordBaru2026', $this->userPc->password));
        $this->assertFalse(Hash::check('password', $this->userPc->password));
    }
}
