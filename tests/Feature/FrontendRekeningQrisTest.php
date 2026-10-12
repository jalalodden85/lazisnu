<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class FrontendRekeningQrisTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_utama_tidak_memuat_mandiri_dan_bca_di_metode_penyaluran(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // Memastikan option transfer_mandiri dan transfer_bca sudah dihapus dari select
        $response->assertDontSee('value="transfer_mandiri"', false);
        $response->assertDontSee('value="transfer_bca"', false);
        $response->assertDontSee('Transfer Bank Mandiri');
        $response->assertDontSee('Transfer Bank BCA');

        // Memastikan option BRI dan BSI serta QRIS tetap ada
        $response->assertSee('value="transfer_bri"', false);
        $response->assertSee('value="transfer_bsi"', false);
        $response->assertSee('value="qris"', false);
    }

    public function test_bagian_rekening_resmi_menampilkan_bri_bersanding_qris_dan_bsi_bersanding_qris(): void
    {
        $response = $this->get(route('home'));
        $response->assertStatus(200);

        // Pasangan 1: Rekening BRI dan QRIS BRI
        $response->assertSee('00411001448562');
        $response->assertSee('qris_lazisnu_bri.jpg');
        $response->assertSee('modalQrisBri');

        // Pasangan 2: Rekening BSI dan QRIS BSI
        $response->assertSee('2026070708');
        $response->assertSee('qris_lazisnu_bsi.jpg');
        $response->assertSee('modalQrisBsi');

        // Memastikan tidak menggunakan TAB nav-pills di modal
        $response->assertDontSee('pills-tab-qris');
        $response->assertDontSee('pills-tabContentQris');
    }
}
