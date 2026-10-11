<?php

namespace Tests\Feature;

use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LaporanCetakHierarkiTest extends TestCase
{
    use RefreshDatabase;

    protected User $userPc;
    protected User $userMwc1;
    protected User $userMwc2;
    protected User $userRanting1;
    protected User $userRanting2;
    protected Mwc $mwc1;
    protected Mwc $mwc2;
    protected Ranting $ranting1;
    protected Ranting $ranting2;
    protected Ranting $ranting3;
    protected Penthong $penthong;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->userPc = User::whereHas('role', fn($q) => $q->whereIn('name', ['pc', 'pc_superadmin']))->first();
        $this->userMwc1 = User::where('username', 'M01')->first();
        $this->userMwc2 = User::where('username', 'M02')->first();
        $this->userRanting1 = User::where('username', 'M01R01')->first();

        $this->mwc1 = Mwc::where('code', 'M01')->first();
        $this->mwc2 = Mwc::where('code', 'M02')->first();

        $this->ranting1 = Ranting::where('mwc_id', $this->mwc1->id)->first();
        $this->ranting2 = Ranting::where('mwc_id', $this->mwc1->id)->where('id', '!=', $this->ranting1->id)->first();
        $this->ranting3 = Ranting::where('mwc_id', $this->mwc2->id)->first();

        // Cari user untuk ranting 3 jika ada atau buat jika belum ada
        $this->userRanting2 = User::where('ranting_id', $this->ranting3->id)->first() ?? User::create([
            'name'       => 'Admin Ranting M02R01',
            'username'   => 'M02R01',
            'email'      => 'm02r01@lazisnu.id',
            'password'   => bcrypt('password'),
            'role_id'    => $this->userRanting1->role_id,
            'mwc_id'     => $this->mwc2->id,
            'ranting_id' => $this->ranting3->id,
            'is_active'  => true,
        ]);

        $this->penthong = Penthong::create([
            'nama_periode' => 'Penthong Uji Cetak Berjenjang',
            'bulan'        => 10,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_AKTIF,
            'is_active'    => true,
        ]);

        // Buat relawan untuk tiap ranting
        $relawan1 = Relawan::firstOrCreate(
            ['ranting_id' => $this->ranting1->id],
            ['nama' => 'Relawan Ranting 1', 'nik' => '3524019900000001', 'no_hp' => '081299990001', 'status' => 'active']
        );
        $relawan2 = Relawan::firstOrCreate(
            ['ranting_id' => $this->ranting2->id],
            ['nama' => 'Relawan Ranting 2', 'nik' => '3524019900000002', 'no_hp' => '081299990002', 'status' => 'active']
        );
        $relawan3 = Relawan::firstOrCreate(
            ['ranting_id' => $this->ranting3->id],
            ['nama' => 'Relawan Ranting 3', 'nik' => '3524019900000003', 'no_hp' => '081299990003', 'status' => 'active']
        );

        // Buat transaksi di masing-masing ranting
        KoinTransaction::create([
            'penthong_id'        => $this->penthong->id,
            'periode_pentong_id' => $this->penthong->id,
            'relawan_id'         => $relawan1->id,
            'ranting_id'         => $this->ranting1->id,
            'mwc_id'             => $this->mwc1->id,
            'amount'             => 100000,
            'collection_date'    => now()->toDateString(),
            'status'             => 'confirmed',
        ]);

        KoinTransaction::create([
            'penthong_id'        => $this->penthong->id,
            'periode_pentong_id' => $this->penthong->id,
            'relawan_id'         => $relawan2->id,
            'ranting_id'         => $this->ranting2->id,
            'mwc_id'             => $this->mwc1->id,
            'amount'             => 200000,
            'collection_date'    => now()->toDateString(),
            'status'             => 'confirmed',
        ]);

        KoinTransaction::create([
            'penthong_id'        => $this->penthong->id,
            'periode_pentong_id' => $this->penthong->id,
            'relawan_id'         => $relawan3->id,
            'ranting_id'         => $this->ranting3->id,
            'mwc_id'             => $this->mwc2->id,
            'amount'             => 300000,
            'collection_date'    => now()->toDateString(),
            'status'             => 'confirmed',
        ]);
    }

    /**
     * TEST 1: Role Admin Ranting Hanya Dapat Mengakses Data Ranting Sendiri
     */
    public function test_ranting_admin_can_only_access_own_ranting_data(): void
    {
        $this->actingAs($this->userRanting1);

        // Akses laporan format JSON
        $response = $this->getJson("/api/laporan/cetak?penthong_id={$this->penthong->id}&format=json");
        $response->assertStatus(200);
        $response->assertJson(['success' => true]);

        // Harus hanya berisi transaksi Ranting 1 (Rp 100.000)
        $data = $response->json('data');
        $this->assertCount(1, $data);
        $this->assertEquals(100000, $response->json('summary.total_nominal'));
        $this->assertEquals($this->ranting1->name, $data[0]['ranting_name']);

        // Verifikasi Kop Surat Tingkat Ranting
        $kop = $response->json('kop');
        $this->assertEquals('ranting', $kop['level']);
        $this->assertStringContainsString('PENGURUS RANTING', $kop['instansi_header']);
        $this->assertStringContainsString('UPZISPR NU CARE - LAZISNU', $kop['lembaga_title']);
    }

    /**
     * TEST 2: Role Admin Ranting DITOLAK (HTTP 403) Jika Memanipulasi Parameter ke Ranting atau MWC Lain
     */
    public function test_ranting_admin_tampering_query_parameters_is_rejected_with_403(): void
    {
        $this->actingAs($this->userRanting1);

        // A. Coba minta data Ranting 2 (di MWC yang sama tapi beda ranting)
        $tamperRantingResponse = $this->getJson("/api/laporan/cetak?penthong_id={$this->penthong->id}&ranting_id={$this->ranting2->id}&format=json");
        $tamperRantingResponse->assertStatus(403);
        $tamperRantingResponse->assertJson([
            'success' => false,
            'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk meminta data Ranting lain.',
        ]);

        // B. Coba minta data MWC 2 (lintas kecamatan)
        $tamperMwcResponse = $this->getJson("/api/laporan/cetak?penthong_id={$this->penthong->id}&mwc_id={$this->mwc2->id}&format=json");
        $tamperMwcResponse->assertStatus(403);
        $tamperMwcResponse->assertJson([
            'success' => false,
            'message' => 'Akses ditolak: Anda tidak memiliki wewenang untuk meminta data MWC lain.',
        ]);
    }

    /**
     * TEST 3: Role Admin MWC Dapat Mencetak Akumulasi Seluruh Ranting di MWC-nya atau Memilih 1 Ranting
     */
    public function test_mwc_admin_can_access_all_rantings_in_territory_or_specific_one(): void
    {
        $this->actingAs($this->userMwc1);

        // A. Cetak Seluruh Ranting di MWC 1 (Ranting 1 + Ranting 2 = 100k + 200k = 300k)
        $allMwcResponse = $this->getJson("/api/laporan/cetak?penthong_id={$this->penthong->id}&format=json");
        $allMwcResponse->assertStatus(200);
        $this->assertCount(2, $allMwcResponse->json('data'));
        $this->assertEquals(300000, $allMwcResponse->json('summary.total_nominal'));

        // Verifikasi Kop Surat Tingkat MWC
        $kop = $allMwcResponse->json('kop');
        $this->assertEquals('mwc', $kop['level']);
        $this->assertStringContainsString('PENGURUS MAJELIS WAKIL CABANG', $kop['instansi_header']);
        $this->assertStringContainsString('UPZIS NU CARE - LAZISNU MWC', $kop['lembaga_title']);

        // B. Cetak 1 Ranting Spesifik dalam MWC 1 (Ranting 1 saja = 100k)
        $singleRantingResponse = $this->getJson("/api/laporan/cetak?penthong_id={$this->penthong->id}&ranting_id={$this->ranting1->id}&format=json");
        $singleRantingResponse->assertStatus(200);
        $this->assertCount(1, $singleRantingResponse->json('data'));
        $this->assertEquals(100000, $singleRantingResponse->json('summary.total_nominal'));
    }

    /**
     * TEST 4: Role Admin MWC DITOLAK (HTTP 403) Jika Meminta Data MWC Lain atau Ranting di Luar Wilayahnya
     */
    public function test_mwc_admin_tampering_cross_mwc_is_rejected_with_403(): void
    {
        $this->actingAs($this->userMwc1);

        // Coba minta data MWC 2
        $foreignMwcResponse = $this->getJson("/api/laporan/cetak?penthong_id={$this->penthong->id}&mwc_id={$this->mwc2->id}&format=json");
        $foreignMwcResponse->assertStatus(403);

        // Coba minta data Ranting 3 (milik MWC 2)
        $foreignRantingResponse = $this->getJson("/api/laporan/cetak?penthong_id={$this->penthong->id}&ranting_id={$this->ranting3->id}&format=json");
        $foreignRantingResponse->assertStatus(403);
    }

    /**
     * TEST 5: Role Admin PC Memiliki Akses Penuh (Global, Per-MWC, Per-Ranting)
     */
    public function test_pc_admin_has_full_dropdown_scoping_access(): void
    {
        $this->actingAs($this->userPc);

        // A. Seluruh Wilayah PC (Ranting 1 + 2 + 3 = 100k + 200k + 300k = 600k)
        $globalResponse = $this->getJson("/api/laporan/cetak?penthong_id={$this->penthong->id}&format=json");
        $globalResponse->assertStatus(200);
        $this->assertCount(3, $globalResponse->json('data'));
        $this->assertEquals(600000, $globalResponse->json('summary.total_nominal'));

        // B. Filter per-MWC (MWC 2 saja = 300k)
        $mwcResponse = $this->getJson("/api/laporan/cetak?penthong_id={$this->penthong->id}&mwc_id={$this->mwc2->id}&format=json");
        $mwcResponse->assertStatus(200);
        $this->assertCount(1, $mwcResponse->json('data'));
        $this->assertEquals(300000, $mwcResponse->json('summary.total_nominal'));

        // C. Filter per-Ranting (Ranting 2 saja = 200k)
        $rantingResponse = $this->getJson("/api/laporan/cetak?penthong_id={$this->penthong->id}&ranting_id={$this->ranting2->id}&format=json");
        $rantingResponse->assertStatus(200);
        $this->assertCount(1, $rantingResponse->json('data'));
        $this->assertEquals(200000, $rantingResponse->json('summary.total_nominal'));
    }

    /**
     * TEST 6: Konfigurasi Pejabat Penandatangan via Modal Form Diterima & Dirender di PDF dan Excel
     */
    public function test_custom_signatories_from_modal_are_rendered_in_pdf_and_excel(): void
    {
        $this->actingAs($this->userPc);

        $params = [
            'penthong_id'    => $this->penthong->id,
            'tempat_tanggal' => 'Lamongan, 10 Oktober 2026',
            'pihak1_jabatan' => 'Bendahara Pengurus Wilayah',
            'pihak1_nama'    => 'K.H. Musthofa Kamal',
            'pihak1_nip'     => 'NIA. 35.24.99.001',
            'pihak2_jabatan' => 'Ketua Tanfidziyah PCNU Lamongan',
            'pihak2_nama'    => 'K.H. Supandi Awaluddin',
            'pihak2_nip'     => 'NIA. 35.24.99.002',
        ];

        // 1. Uji Print PDF View
        $pdfResponse = $this->get(route('api.laporan.cetak', array_merge($params, ['format' => 'pdf'])));
        $pdfResponse->assertStatus(200);

        // Verifikasi nama dan jabatan penandatangan kustom muncul di HTML/PDF
        $pdfResponse->assertSee('Lamongan, 10 Oktober 2026');
        $pdfResponse->assertSee('Bendahara Pengurus Wilayah');
        $pdfResponse->assertSee('K.H. Musthofa Kamal');
        $pdfResponse->assertSee('NIA. 35.24.99.001');
        $pdfResponse->assertSee('Ketua Tanfidziyah PCNU Lamongan');
        $pdfResponse->assertSee('K.H. Supandi Awaluddin');
        $pdfResponse->assertSee('NIA. 35.24.99.002');

        // Verifikasi Judul & Baris Total
        $pdfResponse->assertSee('LAPORAN PEROLEHAN PENTONG KOIN NU');
        $pdfResponse->assertSee('TOTAL AKUMULASI PENERIMAAN DANA (RP)');

        // 2. Uji Excel Export
        $excelResponse = $this->get(route('api.laporan.cetak', array_merge($params, ['format' => 'excel'])));
        $excelResponse->assertStatus(200);
        $excelResponse->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }

    /**
     * TEST 7: Endpoint API Scope Options Mengembalikan Data Sesuai Role
     */
    public function test_scope_options_api_endpoint_returns_correct_role_mode(): void
    {
        // Admin Ranting
        $this->actingAs($this->userRanting1);
        $rantingOpts = $this->getJson('/api/laporan/scope-options');
        $rantingOpts->assertStatus(200);
        $this->assertEquals('locked_ranting', $rantingOpts->json('data.mode'));
        $this->assertEquals($this->ranting1->id, $rantingOpts->json('data.locked_ranting.id'));

        // Admin MWC
        $this->actingAs($this->userMwc1);
        $mwcOpts = $this->getJson('/api/laporan/scope-options');
        $mwcOpts->assertStatus(200);
        $this->assertEquals('scoped_mwc', $mwcOpts->json('data.mode'));
        $this->assertEquals($this->mwc1->id, $mwcOpts->json('data.locked_mwc.id'));

        // Admin PC
        $this->actingAs($this->userPc);
        $pcOpts = $this->getJson('/api/laporan/scope-options');
        $pcOpts->assertStatus(200);
        $this->assertEquals('full_pc', $pcOpts->json('data.mode'));
    }
}
