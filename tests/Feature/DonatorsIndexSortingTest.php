<?php

namespace Tests\Feature;

use App\Models\Donator;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonatorsIndexSortingTest extends TestCase
{
    use RefreshDatabase;

    protected User $adminPc;
    protected Mwc $mwc;
    protected Ranting $ranting;

    protected function setUp(): void
    {
        parent::setUp();

        $rolePc = Role::whereIn('name', ['pc', 'pc_superadmin'])->first()
            ?? Role::create(['name' => 'pc', 'display_name' => 'PC Superadmin', 'level' => 'cabang']);

        $this->mwc = Mwc::create([
            'code'      => 'M01',
            'name'      => 'MWC Karangbinangun',
            'kecamatan' => 'Karangbinangun',
        ]);

        $this->ranting = Ranting::create([
            'mwc_id'    => $this->mwc->id,
            'code'      => 'M01R01',
            'name'      => 'Mayong',
            'is_active' => true,
        ]);

        $this->adminPc = User::factory()->create([
            'role_id'    => $rolePc->id,
            'ranting_id' => null,
            'mwc_id'     => null,
        ]);
    }

    public function test_donators_index_is_sorted_by_box_number_ascending_by_default(): void
    {
        $this->actingAs($this->adminPc);

        // Masukkan data acak
        $boxes = ['KLG-10', 'KLG-1', 'KLG-20', 'KLG-2', 'KLG-11', 'KLG-3'];

        foreach ($boxes as $box) {
            Donator::create([
                'box_number'  => $box,
                'name'        => 'Donatur ' . $box,
                'ranting_id'  => $this->ranting->id,
                'address'     => 'Alamat ' . $box,
                'status'      => Donator::STATUS_ACTIVE,
                'joined_date' => '2026-10-01',
            ]);
        }

        $response = $this->get(route('donators.index'));
        $response->assertStatus(200);

        // Ambil donatur dari view data
        $viewDonators = $response->viewData('donators');
        $boxNumbers = $viewDonators->pluck('box_number')->toArray();

        $expected = ['KLG-1', 'KLG-2', 'KLG-3', 'KLG-10', 'KLG-11', 'KLG-20'];
        $this->assertEquals($expected, $boxNumbers, 'Daftar donatur harus diurutkan dari No Kotak terkecil ke terbesar secara alami');
    }

    public function test_donators_index_supports_descending_sort(): void
    {
        $this->actingAs($this->adminPc);

        $boxes = ['KLG-1', 'KLG-2', 'KLG-10'];

        foreach ($boxes as $box) {
            Donator::create([
                'box_number'  => $box,
                'name'        => 'Donatur ' . $box,
                'ranting_id'  => $this->ranting->id,
                'address'     => 'Alamat ' . $box,
                'status'      => Donator::STATUS_ACTIVE,
                'joined_date' => '2026-10-01',
            ]);
        }

        $response = $this->get(route('donators.index', ['sort_box' => 'desc']));
        $response->assertStatus(200);

        $viewDonators = $response->viewData('donators');
        $boxNumbers = $viewDonators->pluck('box_number')->toArray();

        $expected = ['KLG-10', 'KLG-2', 'KLG-1'];
        $this->assertEquals($expected, $boxNumbers, 'Daftar donatur harus diurutkan descending saat sort_box=desc');
    }
}
