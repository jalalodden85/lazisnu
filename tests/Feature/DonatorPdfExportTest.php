<?php

namespace Tests\Feature;

use App\Models\Donator;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DonatorPdfExportTest extends TestCase
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

    public function test_donator_pdf_export_screen_renders_successfully(): void
    {
        $this->actingAs($this->adminPc);

        Donator::create([
            'box_number'  => 'KLG-001',
            'name'        => 'Ahmad Donatur',
            'ranting_id'  => $this->ranting->id,
            'address'     => 'Dusun Krajan',
            'status'      => Donator::STATUS_ACTIVE,
            'joined_date' => '2026-10-01',
        ]);

        $response = $this->get(route('donators.export-pdf'));

        $response->assertStatus(200);
        $response->assertSee('LAPORAN DATA DONATUR (BERDASARKAN KODE KALENG)');
        $response->assertSee('NU CARE - LAZISNU PCNU LAMONGAN');
        $response->assertSee('TOTAL PEROLEHAN DANA');
        $response->assertSee('Kode Kaleng');
        $response->assertSee('KLG-001');
        $response->assertSee('Ahmad Donatur');
        $response->assertSee('Petugas / Admin Keuangan');
        $response->assertSee('Pimpinan PC LAZISNU Lamongan (Ketua)');
    }

    public function test_natural_sorting_orders_kode_kaleng_logically(): void
    {
        $this->actingAs($this->adminPc);

        // Masukkan data acak: 1, 10, 2, 20, 3, 11
        // Jika alfabetis: 1, 10, 11, 2, 20, 3 (SALAH)
        // Jika natural sort: 1, 2, 3, 10, 11, 20 (BENAR)
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

        $response = $this->get(route('donators.export-pdf'));
        $response->assertStatus(200);

        $content = $response->getContent();

        // Cari posisi kemunculan masing-masing kode di HTML
        $pos1  = strpos($content, 'KLG-1');
        $pos2  = strpos($content, 'KLG-2');
        $pos3  = strpos($content, 'KLG-3');
        $pos10 = strpos($content, 'KLG-10');
        $pos11 = strpos($content, 'KLG-11');
        $pos20 = strpos($content, 'KLG-20');

        $this->assertNotFalse($pos1);
        $this->assertNotFalse($pos2);
        $this->assertNotFalse($pos3);
        $this->assertNotFalse($pos10);
        $this->assertNotFalse($pos11);
        $this->assertNotFalse($pos20);

        // Verifikasi Urutan Natural: KLG-1 < KLG-2 < KLG-3 < KLG-10 < KLG-11 < KLG-20
        $this->assertTrue($pos1 < $pos2, 'KLG-1 harus muncul sebelum KLG-2');
        $this->assertTrue($pos2 < $pos3, 'KLG-2 harus muncul sebelum KLG-3');
        $this->assertTrue($pos3 < $pos10, 'KLG-3 harus muncul sebelum KLG-10 (bukan 1, 10, 2)');
        $this->assertTrue($pos10 < $pos11, 'KLG-10 harus muncul sebelum KLG-11');
        $this->assertTrue($pos11 < $pos20, 'KLG-11 harus muncul sebelum KLG-20');
    }

    public function test_filters_by_region_and_date_range(): void
    {
        $this->actingAs($this->adminPc);

        $otherRanting = Ranting::create([
            'mwc_id'    => $this->mwc->id,
            'code'      => 'M01R02',
            'name'      => 'Banyubang',
            'is_active' => true,
        ]);

        // Donatur di Ranting Mayong periode Oktober
        $d1 = Donator::create([
            'box_number'  => 'KLG-MAYONG-01',
            'name'        => 'Donatur Mayong',
            'ranting_id'  => $this->ranting->id,
            'address'     => 'Mayong Krajan',
            'status'      => Donator::STATUS_ACTIVE,
            'joined_date' => '2026-10-05',
        ]);

        // Donatur di Ranting Banyubang periode Oktober
        $d2 = Donator::create([
            'box_number'  => 'KLG-BANYU-01',
            'name'        => 'Donatur Banyubang',
            'ranting_id'  => $otherRanting->id,
            'address'     => 'Banyubang Krajan',
            'status'      => Donator::STATUS_ACTIVE,
            'joined_date' => '2026-10-05',
        ]);

        // Filter Ranting Mayong saja
        $resFilterRanting = $this->get(route('donators.export-pdf', [
            'ranting_id' => $this->ranting->id,
            'start_date' => '2026-10-01',
            'end_date'   => '2026-10-31',
        ]));

        $resFilterRanting->assertStatus(200);
        $resFilterRanting->assertSee('KLG-MAYONG-01');
        $resFilterRanting->assertDontSee('KLG-BANYU-01');
    }

    public function test_filter_by_status_terverifikasi_or_all(): void
    {
        $this->actingAs($this->adminPc);

        $activeDonator = Donator::create([
            'box_number'  => 'KLG-ACTIVE-01',
            'name'        => 'Donatur Aktif',
            'ranting_id'  => $this->ranting->id,
            'address'     => 'Dusun Krajan',
            'status'      => Donator::STATUS_ACTIVE,
        ]);

        $inactiveDonator = Donator::create([
            'box_number'  => 'KLG-INACTIVE-01',
            'name'        => 'Donatur Nonaktif',
            'ranting_id'  => $this->ranting->id,
            'address'     => 'Dusun Krajan',
            'status'      => Donator::STATUS_INACTIVE,
        ]);

        // Default: Terverifikasi (hanya aktif)
        $resDefault = $this->get(route('donators.export-pdf'));
        $resDefault->assertStatus(200);
        $resDefault->assertSee('KLG-ACTIVE-01');
        $resDefault->assertDontSee('KLG-INACTIVE-01');

        // Parameter all: menampilkan semua status
        $resAll = $this->get(route('donators.export-pdf', ['status' => 'all']));
        $resAll->assertStatus(200);
        $resAll->assertSee('KLG-ACTIVE-01');
        $resAll->assertSee('KLG-INACTIVE-01');
    }

    public function test_donators_index_page_contains_direct_export_link_and_modal(): void
    {
        $this->actingAs($this->adminPc);

        $url = route('donators.index', [
            'search'     => '',
            'mwc_id'     => $this->mwc->id,
            'ranting_id' => $this->ranting->id,
            'relawan_id' => '',
            'assignment' => '',
        ]);

        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertSee('Cetak / Export PDF');
        $response->assertSee(route('donators.export-pdf'));
        $response->assertSee('id="exportPdfDonaturModal"', false);
    }

    public function test_export_pdf_with_exact_user_query_parameters_returns_matching_data(): void
    {
        $this->actingAs($this->adminPc);

        Donator::create([
            'box_number'  => 'KLG-KEBEN-001',
            'name'        => 'Warga Keben 1',
            'ranting_id'  => $this->ranting->id,
            'address'     => 'Dusun Keben',
            'status'      => Donator::STATUS_ACTIVE,
        ]);

        $url = route('donators.export-pdf', [
            'search'     => '',
            'mwc_id'     => $this->mwc->id,
            'ranting_id' => $this->ranting->id,
            'relawan_id' => '',
            'assignment' => '',
        ]);

        $response = $this->get($url);
        $response->assertStatus(200);
        $response->assertSee('KLG-KEBEN-001');
        $response->assertSee('Warga Keben 1');
    }
}
