<?php

namespace Tests\Feature;

use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Role;
use App\Models\User;
use App\Services\WhatsAppHelper;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WilayahPengurusTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function createPcUser(): User
    {
        $rolePc = Role::where('name', Role::PC_SUPERADMIN)->first() 
            ?? Role::where('name', Role::PC)->first();

        $user = User::factory()->create([
            'role_id'    => $rolePc->id,
            'mwc_id'     => null,
            'ranting_id' => null,
        ]);

        return $user;
    }

    public function test_whatsapp_helper_formats_indonesian_numbers_accurately()
    {
        // 0812... -> 62812...
        $this->assertEquals('6281234567890', WhatsAppHelper::formatNumber('081234567890'));
        // Spasi dan dash
        $this->assertEquals('6281234567890', WhatsAppHelper::formatNumber('0812-3456-7890'));
        // Awalan +62
        $this->assertEquals('6281234567890', WhatsAppHelper::formatNumber('+62 812-3456-7890'));
        // Awalan 8
        $this->assertEquals('6281234567890', WhatsAppHelper::formatNumber('81234567890'));
        // Nomor tidak valid / kosong
        $this->assertNull(WhatsAppHelper::formatNumber(''));
        $this->assertNull(WhatsAppHelper::formatNumber('123'));
    }

    public function test_mwc_auto_formats_whatsapp_numbers_on_save()
    {
        $mwc = Mwc::create([
            'code'               => 'M99',
            'name'               => 'MWCNU Sukodadi',
            'kecamatan'          => 'Sukodadi',
            'nama_ketua_mwc'     => 'K.H. Musthofa',
            'wa_ketua_mwc'       => '0812-3344-5566',
            'nama_ketua_lazisnu' => 'Ust. Sholihun',
            'wa_ketua_lazisnu'   => '0857-1122-3344',
            'nama_bendahara'     => 'H. Zainal',
            'wa_bendahara'       => '+62 813-9988-7766',
        ]);

        $mwc->refresh();

        // Terformat menjadi 628...
        $this->assertEquals('6281233445566', $mwc->wa_ketua_mwc);
        $this->assertEquals('6285711223344', $mwc->wa_ketua_lazisnu);
        $this->assertEquals('6281399887766', $mwc->wa_bendahara);

        // Pengurus list accessor
        $pengurusList = $mwc->pengurus_list;
        $this->assertCount(5, $pengurusList);
        $this->assertStringContainsString('6281233445566', $pengurusList[0]['link']);
    }

    public function test_ranting_auto_formats_whatsapp_numbers_on_save()
    {
        $mwc = Mwc::first();

        $ranting = Ranting::create([
            'mwc_id'             => $mwc->id,
            'code'               => 'M99R99',
            'name'               => 'Ranting Banaran',
            'nama_ketua_ranting' => 'K.H. Nur Hadi',
            'wa_ketua_ranting'   => '0812-9988-1122',
            'nama_ketua_upzis'   => 'H. M. Syukron',
            'wa_ketua_upzis'     => '0858-7766-5544',
            'nama_bendahara'     => 'Ahmad Rifai',
            'wa_bendahara'       => '0821-4433-2211',
        ]);

        $ranting->refresh();

        $this->assertEquals('6281299881122', $ranting->wa_ketua_ranting);
        $this->assertEquals('6285877665544', $ranting->wa_ketua_upzis);
        $this->assertEquals('6282144332211', $ranting->wa_bendahara);

        $pengurusList = $ranting->pengurus_list;
        $this->assertCount(3, $pengurusList);
        $this->assertStringContainsString('6281299881122', $pengurusList[0]['link']);
    }

    public function test_mwc_detail_page_renders_committee_and_whatsapp_buttons()
    {
        $adminPc = $this->createPcUser();

        $mwc = Mwc::create([
            'code'               => 'M98',
            'name'               => 'MWCNU Lamongan Kota',
            'kecamatan'          => 'Lamongan',
            'nama_ketua_mwc'     => 'K.H. Munirul Anam',
            'wa_ketua_mwc'       => '081234567890',
            'nama_ketua_lazisnu' => 'Ust. Syamsul Huda',
            'wa_ketua_lazisnu'   => '085712345678',
        ]);

        $response = $this->actingAs($adminPc)->get(route('mwcs.show', $mwc->id));
        $response->assertStatus(200);
        $response->assertSee('Susunan Pengurus MWC');
        $response->assertSee('K.H. Munirul Anam');
        $response->assertSee('Ust. Syamsul Huda');
        $response->assertSee('wa.me/6281234567890');
    }

    public function test_ranting_detail_page_renders_committee_and_whatsapp_buttons()
    {
        $adminPc = $this->createPcUser();
        $mwc = Mwc::first();

        $ranting = Ranting::create([
            'mwc_id'             => $mwc->id,
            'code'               => 'M98R01',
            'name'               => 'Ranting Miru',
            'nama_ketua_ranting' => 'K.H. Suwandi',
            'wa_ketua_ranting'   => '081233221100',
            'nama_ketua_upzis'   => 'H. M. Ali',
            'wa_ketua_upzis'     => '085699887766',
            'nama_bendahara'     => 'M. Fuad',
            'wa_bendahara'       => '081322110099',
        ]);

        $response = $this->actingAs($adminPc)->get(route('rantings.show', $ranting->id));
        $response->assertStatus(200);
        $response->assertSee('Susunan Pengurus Ranting');
        $response->assertSee('K.H. Suwandi');
        $response->assertSee('H. M. Ali');
        $response->assertSee('M. Fuad');
        $response->assertSee('wa.me/6281233221100');
    }

    public function test_pdf_export_shows_dynamic_committee_signatories_for_ranting()
    {
        $adminPc = $this->createPcUser();
        $mwc = Mwc::first();

        $ranting = Ranting::create([
            'mwc_id'             => $mwc->id,
            'code'               => 'M97R01',
            'name'               => 'Ranting Bakalanpule',
            'nama_ketua_ranting' => 'K.H. Zaidun',
            'nama_ketua_upzis'   => 'H. M. Basyir',
            'nama_bendahara'     => 'Hj. Siti Aminah',
        ]);

        $response = $this->actingAs($adminPc)->get(route('bku-operasional.export.pdf', [
            'tingkat'    => 'ranting',
            'ranting_id' => $ranting->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('H. M. Basyir');    // Ketua UPZIS di kiri
        $response->assertSee('K.H. Zaidun');     // Mengetahui di tengah
        $response->assertSee('Hj. Siti Aminah'); // Bendahara di kanan
    }
}
