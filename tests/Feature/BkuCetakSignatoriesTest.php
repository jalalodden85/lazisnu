<?php

namespace Tests\Feature;

use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BkuCetakSignatoriesTest extends TestCase
{
    use RefreshDatabase;

    protected User $userRanting;
    protected User $userMwc;
    protected User $userPc;
    protected Mwc $mwc;
    protected Ranting $ranting;
    protected Penthong $penthong;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->userPc = User::whereHas('role', fn($q) => $q->whereIn('name', ['pc', 'pc_superadmin']))->first();
        $this->userMwc = User::where('username', 'M01')->first();
        $this->userRanting = User::where('username', 'M01R01')->first();

        $this->mwc = Mwc::where('code', 'M01')->first();
        $this->ranting = Ranting::where('id', $this->userRanting->ranting_id)->first();

        // Entrikan data susunan pengurus pada MWC
        $this->mwc->update([
            'nama_ketua_mwc'     => 'K.H. Ahmad Dahlan MWC',
            'nama_ketua_lazisnu' => 'H. Mahfudz LAZISNU MWC',
            'nama_bendahara'     => 'Hj. Siti Aminah Bendahara MWC',
        ]);

        // Entrikan data susunan pengurus pada Ranting
        $this->ranting->update([
            'nama_ketua_ranting' => 'K.H. Shodiq Ranting',
            'nama_ketua_upzis'   => 'Ust. Fauzi UPZIS Ranting',
            'nama_bendahara'     => 'H. Zainuddin Bendahara Ranting',
        ]);

        $this->penthong = Penthong::first() ?? Penthong::create([
            'nama_periode' => 'Periode Uji Signatories',
            'bulan'        => (int) date('m'),
            'tahun'        => (int) date('Y'),
            'status'       => 'open',
        ]);

        $bkuOpMenu = \App\Models\Menu::where('route_name', 'bku-operasional.index')->first();
        if ($bkuOpMenu) {
            $this->userRanting->menus()->syncWithoutDetaching([$bkuOpMenu->id]);
            $this->userMwc->menus()->syncWithoutDetaching([$bkuOpMenu->id]);
        }
    }

    public function test_cetak_bku_ranting_memuat_logo_dan_tanda_tangan_otomatis_tiga_pihak(): void
    {
        $response = $this->actingAs($this->userRanting)->get(route('bku.cetak', [
            'penthong_id' => $this->penthong->id,
        ]));

        $response->assertStatus(200);

        // Memastikan Logo NU Care Lazisnu termuat
        $response->assertSee('logo_nucare_lazisnu.png');

        // Memastikan Tiga Pihak Penandatangan:
        // 1. Paling Kiri: Mengetahui Ketua Tanfidz (K.H. Shodiq Ranting)
        $response->assertSee('Mengetahui,');
        $response->assertSee('Ketua Tanfidziyah Ranting NU');
        $response->assertSee('K.H. Shodiq Ranting');

        // 2. Tengah: Ketua UPZIS / LAZISNU (Ust. Fauzi UPZIS Ranting)
        $response->assertSee('Ketua UPZIS Ranting');
        $response->assertSee('Ust. Fauzi UPZIS Ranting');

        // 3. Paling Kanan: Bendahara (H. Zainuddin Bendahara Ranting)
        $response->assertSee('Bendahara UPZIS Ranting');
        $response->assertSee('H. Zainuddin Bendahara Ranting');
    }

    public function test_cetak_bku_mwc_memuat_logo_dan_tanda_tangan_otomatis_tiga_pihak(): void
    {
        $response = $this->actingAs($this->userMwc)->get(route('bku.cetak', [
            'penthong_id' => $this->penthong->id,
            'mwc_id'      => $this->mwc->id,
        ]));

        $response->assertStatus(200);

        // Logo
        $response->assertSee('logo_nucare_lazisnu.png');

        // 1. Paling Kiri: Mengetahui Ketua Tanfidz MWC
        $response->assertSee('Ketua Tanfidziyah MWC NU');
        $response->assertSee('K.H. Ahmad Dahlan MWC');

        // 2. Tengah: Ketua LAZISNU MWC
        $response->assertSee('Ketua LAZISNU MWC');
        $response->assertSee('H. Mahfudz LAZISNU MWC');

        // 3. Paling Kanan: Bendahara MWC
        $response->assertSee('Bendahara UPZIS MWC');
        $response->assertSee('Hj. Siti Aminah Bendahara MWC');
    }

    public function test_cetak_bku_operasional_ranting_memuat_logo_dan_tanda_tangan_tiga_pihak(): void
    {
        $response = $this->actingAs($this->userRanting)->get(route('bku-operasional.export.pdf', [
            'penthong_id' => $this->penthong->id,
            'tingkat'     => 'ranting',
        ]));

        $response->assertStatus(200);

        // Logo
        $response->assertSee('logo_nucare_lazisnu.png');

        // Tanda Tangan: Kiri (Tanfidz), Tengah (UPZIS), Kanan (Bendahara)
        $response->assertSee('Ketua Tanfidziyah Ranting NU');
        $response->assertSee('K.H. Shodiq Ranting');
        $response->assertSee('Ketua UPZIS Ranting');
        $response->assertSee('Ust. Fauzi UPZIS Ranting');
        $response->assertSee('Bendahara UPZIS Ranting');
        $response->assertSee('H. Zainuddin Bendahara Ranting');
    }

    public function test_cetak_bku_operasional_mwc_memuat_logo_dan_tanda_tangan_tiga_pihak(): void
    {
        $response = $this->actingAs($this->userMwc)->get(route('bku-operasional.export.pdf', [
            'penthong_id' => $this->penthong->id,
            'tingkat'     => 'mwc',
        ]));

        $response->assertStatus(200);

        // Logo
        $response->assertSee('logo_nucare_lazisnu.png');

        // Tanda Tangan: Kiri (Tanfidz), Tengah (LAZISNU), Kanan (Bendahara)
        $response->assertSee('Ketua Tanfidziyah MWC NU');
        $response->assertSee('K.H. Ahmad Dahlan MWC');
        $response->assertSee('Ketua LAZISNU MWC');
        $response->assertSee('H. Mahfudz LAZISNU MWC');
        $response->assertSee('Bendahara UPZIS MWC');
        $response->assertSee('Hj. Siti Aminah Bendahara MWC');
    }
}
