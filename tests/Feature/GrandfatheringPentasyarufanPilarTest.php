<?php

namespace Tests\Feature;

use App\Models\DistributionSetting;
use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Pentasyarufan;
use App\Models\Penthong;
use App\Models\PilarProgram;
use App\Models\ProgramKerja;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\Role;
use App\Models\User;
use App\Services\BkuService;
use App\Services\DistributionService;
use App\Services\PentasyarufanBalanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class GrandfatheringPentasyarufanPilarTest extends TestCase
{
    use RefreshDatabase;

    protected User $userPc;
    protected Mwc $mwc;
    protected Ranting $ranting;

    protected function setUp(): void
    {
        parent::setUp();

        // Buat Role PC Super Admin
        $rolePc = Role::firstOrCreate(['name' => Role::PC_SUPERADMIN], [
            'label' => 'Super Admin PC',
            'description' => 'Akses penuh',
        ]);

        $this->userPc = User::factory()->create([
            'role_id' => $rolePc->id,
            'is_active' => true,
        ]);

        $this->mwc = Mwc::create([
            'code'      => 'M01',
            'name'      => 'MWC NU Babat',
            'kecamatan' => 'Babat',
        ]);

        $this->ranting = Ranting::create([
            'mwc_id'    => $this->mwc->id,
            'code'      => 'M01R01',
            'name'      => 'Ranting Babat Kota',
            'is_active' => true,
        ]);

        // Seed 5 Pilar Program
        $pilars = [
            ['kode' => 'PILAR-1', 'nama_pilar' => 'NU Care Cerdas (Pendidikan)', 'target_persentase' => 25.00, 'warna' => '#28a745', 'icon' => 'fas fa-graduation-cap', 'sort_order' => 1],
            ['kode' => 'PILAR-2', 'nama_pilar' => 'NU Care Sehat (Kesehatan)', 'target_persentase' => 20.00, 'warna' => '#17a2b8', 'icon' => 'fas fa-heartbeat', 'sort_order' => 2],
            ['kode' => 'PILAR-3', 'nama_pilar' => 'NU Care Berdaya (Ekonomi)', 'target_persentase' => 25.00, 'warna' => '#ffc107', 'icon' => 'fas fa-hand-holding-usd', 'sort_order' => 3],
            ['kode' => 'PILAR-4', 'nama_pilar' => 'NU Care Tanggap (Siaga Bencana)', 'target_persentase' => 15.00, 'warna' => '#dc3545', 'icon' => 'fas fa-shield-alt', 'sort_order' => 4],
            ['kode' => 'PILAR-5', 'nama_pilar' => 'NU Care Damai (Dakwah & Advokasi)', 'target_persentase' => 15.00, 'warna' => '#6c757d', 'icon' => 'fas fa-mosque', 'sort_order' => 5],
        ];

        foreach ($pilars as $p) {
            PilarProgram::create($p);
        }
    }

    public function test_active_distribution_scheme_reflects_13_percent_mwc_and_5_percent_pc(): void
    {
        $activeSetting = DistributionSetting::getActive();

        $this->assertEquals(13.00, (float) $activeSetting->mwc_pct, 'Persentase MWC aktif harus 13%');
        $this->assertEquals(5.00, (float) $activeSetting->pc_pct, 'Persentase PC aktif harus 5%');
        $this->assertEquals(80.00, (float) $activeSetting->ranting_pct);
        $this->assertEquals(1.50, (float) $activeSetting->pw_pct);
        $this->assertEquals(0.50, (float) $activeSetting->pb_pct);

        $distributionService = app(DistributionService::class);
        $split = $distributionService->calculateSplit(10000000); // 10 Jt Bruto

        // Net 80% = Rp 8.000.000
        $this->assertEquals(8000000, $split['net']);
        // MWC: 13% dari 8.000.000 = Rp 1.040.000
        $this->assertEquals(1040000, $split['hak_mwc']);
        // PC: 5% dari 8.000.000 = Rp 400.000
        $this->assertEquals(400000, $split['hak_pc']);
    }

    public function test_grandfathering_legacy_scheme_preserves_12_percent_mwc_and_6_percent_pc(): void
    {
        $legacySetting = DistributionSetting::getLegacy();

        $this->assertEquals(12.00, (float) $legacySetting->mwc_pct, 'Persentase MWC legacy harus 12%');
        $this->assertEquals(6.00, (float) $legacySetting->pc_pct, 'Persentase PC legacy harus 6%');

        $distributionService = app(DistributionService::class);
        $split = $distributionService->calculateSplit(10000000, $legacySetting);

        $this->assertEquals(960000, $split['hak_mwc']);  // 12%
        $this->assertEquals(480000, $split['hak_pc']);   // 6%
    }

    public function test_closed_penthong_resolves_to_legacy_setting_automatically(): void
    {
        $closedPenthong = Penthong::create([
            'nama_periode' => 'Penthong Masa Lalu Agustus 2026',
            'bulan'        => 8,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_TUTUP,
            'is_active'    => false,
        ]);

        $setting = $closedPenthong->getDistributionSetting();

        $this->assertEquals(12.00, (float) $setting->mwc_pct, 'Penthong tertutup harus memakai MWC 12%');
        $this->assertEquals(6.00, (float) $setting->pc_pct, 'Penthong tertutup harus memakai PC 6%');
    }

    public function test_pentasyarufan_pilar_view_renders_new_percentages_and_supports_closed_filter(): void
    {
        $this->actingAs($this->userPc);

        // Buat Penthong aktif
        $activePenthong = Penthong::create([
            'nama_periode' => 'Penthong Berjalan Oktober 2026',
            'bulan'        => 10,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_AKTIF,
            'is_active'    => true,
        ]);

        // Akses halaman /pentasyarufan/pilar tanpa filter (default aktif)
        $response = $this->get('/pentasyarufan/pilar');
        $response->assertStatus(200);
        $response->assertSee('13% Netto');
        $response->assertSee('5% Netto');
        $response->assertSee('Kalkulator Preview');
        $response->assertSee('0.13');
        $response->assertSee('0.05');

        // Buat Penthong tertutup
        $closedPenthong = Penthong::create([
            'nama_periode' => 'Penthong September 2026',
            'bulan'        => 9,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_TUTUP,
            'is_active'    => false,
        ]);

        // Akses dengan filter penthong_id tertutup -> harus menampilkan persentase grandfathered lama (12% dan 6%)
        $resClosed = $this->get('/pentasyarufan/pilar?penthong_id=' . $closedPenthong->id);
        $resClosed->assertStatus(200);
        $resClosed->assertSee('12% Netto');
        $resClosed->assertSee('6% Netto');
    }

    public function test_disbursement_on_closed_penthong_is_strictly_rejected(): void
    {
        $closedPenthong = Penthong::create([
            'nama_periode' => 'Penthong Lama',
            'bulan'        => 7,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_TUTUP,
            'is_active'    => false,
        ]);

        $proker = ProgramKerja::create([
            'nomor_proker'     => 'PK-TEST-2026-001',
            'nama_kegiatan'    => 'Santunan Anak Yatim',
            'pilar_program_id' => PilarProgram::first()->id,
            'tingkat'          => 'pc',
            'user_id'          => $this->userPc->id,
            'tahun_anggaran'   => 2026,
            'rencana_anggaran' => 5000000,
            'status'           => 'disetujui',
        ]);

        $balanceService = app(PentasyarufanBalanceService::class);
        $validation = $balanceService->validateDisbursement($closedPenthong->id, $proker->id, 1000000);

        $this->assertFalse($validation['valid']);
        $this->assertStringContainsString('telah ditutup', $validation['error']);

        // Controller store test
        $this->actingAs($this->userPc);

        $this->expectException(ValidationException::class);
        app(\App\Http\Controllers\PentasyarufanController::class)->store(new \Illuminate\Http\Request([
            'penthong_id'        => $closedPenthong->id,
            'program_kerja_id'   => $proker->id,
            'nominal_pencairan'  => 1000000,
            'tanggal_penyaluran' => now()->toDateString(),
            'penerima_manfaat'   => '10 Santri Yatim',
            'jumlah_penerima'    => 10,
        ]));
    }
}
