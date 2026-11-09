<?php

namespace Tests\Feature;

use App\Models\Campaign;
use App\Models\OnlineDonation;
use App\Models\ProgramCategory;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OnlineDonationSecurityLockTest extends TestCase
{
    use RefreshDatabase;

    protected User $superAdmin;
    protected User $operator;
    protected Campaign $campaign;

    protected function setUp(): void
    {
        parent::setUp();

        $roleSuperAdmin = Role::firstOrCreate(
            ['name' => 'pc_superadmin'],
            ['label' => 'Super Admin PC']
        );

        $roleOperator = Role::firstOrCreate(
            ['name' => 'pc_admin_online'],
            ['label' => 'Admin Donasi Online']
        );

        $this->superAdmin = User::factory()->create([
            'role_id' => $roleSuperAdmin->id,
        ]);

        $this->operator = User::factory()->create([
            'role_id' => $roleOperator->id,
        ]);

        $menu = \App\Models\Menu::firstOrCreate(
            ['route_name' => 'admin.online-donations.index'],
            ['name' => 'Donasi Online', 'category' => 'portal', 'is_active' => true]
        );
        $this->operator->menus()->sync([$menu->id]);

        $category = ProgramCategory::firstOrCreate(
            ['slug' => 'sosial-kemanusiaan'],
            ['name' => 'Sosial Kemanusiaan']
        );

        $this->campaign = Campaign::firstOrCreate(
            ['slug' => 'bantuan-peduli-bencana'],
            [
                'category_id'       => $category->id,
                'title'             => 'Bantuan Peduli Bencana',
                'short_description' => 'Deskripsi singkat bantuan bencana',
                'story_description' => 'Cerita lengkap bantuan bencana',
                'target_amount'     => 50000000,
                'collected_amount'  => 10000000,
                'start_date'        => now()->toDateString(),
                'status'            => 'active',
            ]
        );
    }

    public function test_operator_sees_locked_icon_and_hidden_verify_for_donations_verified_over_24_hours(): void
    {
        $this->actingAs($this->operator);

        // Donasi diverifikasi 2 hari lalu
        $lockedDonation = OnlineDonation::create([
            'donation_code'         => 'DON-LOCKED-01',
            'campaign_id'           => $this->campaign->id,
            'donor_name'            => 'Donatur Terkunci',
            'donor_phone'           => '081234567890',
            'amount'                => 100000,
            'unique_code'           => 123,
            'total_amount'          => 100123,
            'payment_method'        => 'qris',
            'payment_status'        => 'verified',
            'verified_at'           => Carbon::now()->subDays(2),
            'nominal_terverifikasi' => 100123,
        ]);

        $response = $this->get(route('admin.online-donations.index'));
        $response->assertStatus(200);

        // Pastikan ikon gembok terkunci muncul
        $response->assertSee('fa-lock');
        $response->assertSee('Data terkunci permanen setelah 24 jam diverifikasi');

        // Pastikan form/tombol batalkan verifikasi dan hapus untuk donasi terkunci tidak dirender
        $response->assertDontSee(route('admin.online-donations.cancel', $lockedDonation->id));
        $response->assertDontSee('title="Hapus"', false);
        $response->assertDontSee('fa-trash');
    }

    public function test_super_admin_has_full_override_access_for_donations_verified_over_24_hours(): void
    {
        $this->actingAs($this->superAdmin);

        $lockedDonation = OnlineDonation::create([
            'donation_code'         => 'DON-LOCKED-02',
            'campaign_id'           => $this->campaign->id,
            'donor_name'            => 'Donatur Super Admin',
            'donor_phone'           => '081234567891',
            'amount'                => 200000,
            'unique_code'           => 456,
            'total_amount'          => 200456,
            'payment_method'        => 'qris',
            'payment_status'        => 'verified',
            'verified_at'           => Carbon::now()->subDays(3),
            'nominal_terverifikasi' => 200456,
        ]);

        $response = $this->get(route('admin.online-donations.index'));
        $response->assertStatus(200);

        // Super Admin tetap bisa melihat tombol Batal dan Hapus
        $response->assertSee(route('admin.online-donations.cancel', $lockedDonation->id));
        $response->assertSee(route('admin.online-donations.destroy', $lockedDonation->id));
    }

    public function test_operator_blocked_from_destroying_locked_donation_with_403_forbidden(): void
    {
        $this->actingAs($this->operator);

        $lockedDonation = OnlineDonation::create([
            'donation_code'         => 'DON-LOCKED-03',
            'campaign_id'           => $this->campaign->id,
            'donor_name'            => 'Donatur Hapus',
            'donor_phone'           => '081234567892',
            'amount'                => 150000,
            'unique_code'           => 789,
            'total_amount'          => 150789,
            'payment_method'        => 'qris',
            'payment_status'        => 'verified',
            'verified_at'           => Carbon::now()->subHours(25),
            'nominal_terverifikasi' => 150789,
        ]);

        $response = $this->delete(route('admin.online-donations.destroy', $lockedDonation->id));
        $response->assertStatus(403);
        $this->assertStringContainsString('Data donasi ini sudah terkunci secara permanen dan hanya bisa diubah oleh Super Admin', $response->getContent());

        // Pastikan data donasi tidak terhapus dari database
        $this->assertDatabaseHas('online_donations', ['id' => $lockedDonation->id]);
    }

    public function test_operator_blocked_from_verifying_or_canceling_locked_donation(): void
    {
        $this->actingAs($this->operator);

        $lockedDonation = OnlineDonation::create([
            'donation_code'         => 'DON-LOCKED-04',
            'campaign_id'           => $this->campaign->id,
            'donor_name'            => 'Donatur Batal',
            'donor_phone'           => '081234567893',
            'amount'                => 100000,
            'unique_code'           => 111,
            'total_amount'          => 100111,
            'payment_method'        => 'qris',
            'payment_status'        => 'verified',
            'verified_at'           => Carbon::now()->subHours(30),
            'nominal_terverifikasi' => 100111,
        ]);

        $cancelResponse = $this->post(route('admin.online-donations.cancel', $lockedDonation->id));
        $cancelResponse->assertStatus(403);

        $verifyResponse = $this->post(route('admin.online-donations.verify', $lockedDonation->id));
        $verifyResponse->assertStatus(403);
    }

    public function test_super_admin_can_successfully_destroy_locked_donation(): void
    {
        $this->actingAs($this->superAdmin);

        $lockedDonation = OnlineDonation::create([
            'donation_code'         => 'DON-LOCKED-05',
            'campaign_id'           => $this->campaign->id,
            'donor_name'            => 'Donatur Super Hapus',
            'donor_phone'           => '081234567894',
            'amount'                => 100000,
            'unique_code'           => 222,
            'total_amount'          => 100222,
            'payment_method'        => 'qris',
            'payment_status'        => 'verified',
            'verified_at'           => Carbon::now()->subDays(5),
            'nominal_terverifikasi' => 100222,
        ]);

        $response = $this->delete(route('admin.online-donations.destroy', $lockedDonation->id));
        $response->assertRedirect(route('admin.online-donations.index'));
        $this->assertDatabaseMissing('online_donations', ['id' => $lockedDonation->id]);
    }
}
