<?php

namespace Tests\Feature;

use App\Models\DistributionSetting;
use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\PenthongDistribution;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\Role;
use App\Models\User;
use App\Services\DistributionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PenthongDistributionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_distribution_service_calculates_split_accurately_with_even_amount(): void
    {
        $service = app(DistributionService::class);
        $totalGross = 10000000; // Rp 10.000.000

        // 1. Skema Aktif Baru (MWC 13% NAIK, PC 5% TURUN)
        $split = $service->calculateSplit($totalGross);

        // Operasional 20%
        $this->assertEquals(2000000, $split['operasional']);
        // Net 80% = Rp 8.000.000
        $this->assertEquals(8000000, $split['net']);
        // Ranting 80% dari Net = Rp 6.400.000
        $this->assertEquals(6400000, $split['hak_ranting']);
        // MWC 13% dari Net = Rp 1.040.000 (Naik dari 960.000)
        $this->assertEquals(1040000, $split['hak_mwc']);
        // PC 5% dari Net = Rp 400.000 (Turun dari 480.000)
        $this->assertEquals(400000, $split['hak_pc']);
        // PW 1.5% dari Net = Rp 120.000
        $this->assertEquals(120000, $split['hak_pw']);
        // PB 0.5% dari Net = Rp 40.000
        $this->assertEquals(40000, $split['hak_pb']);

        // Verifikasi zero discrepancy pada skema aktif
        $sum = $split['operasional'] +
               $split['hak_ranting'] +
               $split['hak_mwc'] +
               $split['hak_pc'] +
               $split['hak_pw'] +
               $split['hak_pb'];

        $this->assertEquals($totalGross, $sum);

        // 2. Skema Legacy Grandfathering (MWC 12%, PC 6%)
        $legacySetting = DistributionSetting::getLegacy();
        $legacySplit = $service->calculateSplit($totalGross, $legacySetting);

        $this->assertEquals(6400000, $legacySplit['hak_ranting']);
        $this->assertEquals(960000, $legacySplit['hak_mwc']);
        $this->assertEquals(480000, $legacySplit['hak_pc']);
        $this->assertEquals(120000, $legacySplit['hak_pw']);
        $this->assertEquals(40000, $legacySplit['hak_pb']);
    }

    public function test_distribution_service_guarantees_zero_leakage_rounding_with_odd_amounts(): void
    {
        $service = app(DistributionService::class);
        $oddAmounts = [1234567, 9876543, 3333333, 777, 100001];

        foreach ($oddAmounts as $amount) {
            $split = $service->calculateSplit($amount);

            $sumSplits = $split['hak_ranting'] +
                         $split['hak_mwc'] +
                         $split['hak_pc'] +
                         $split['hak_pw'] +
                         $split['hak_pb'];

            $this->assertEquals($split['net'], $sumSplits, "Net split sum mismatch for {$amount}");
            $this->assertEquals($amount, $split['operasional'] + $sumSplits, "Gross total sum mismatch for {$amount}");
        }
    }

    public function test_transaction_automatically_binds_to_active_penthong(): void
    {
        $mwc = Mwc::first();
        $ranting = Ranting::first();
        $relawan = Relawan::first();

        // Buat Penthong aktif
        $penthong = Penthong::create([
            'nama_periode' => 'Penthong Uji Coba',
            'bulan'        => now()->month,
            'tahun'        => now()->year,
            'status'       => 'aktif',
            'mwc_id'       => $mwc->id,
        ]);

        $transaction = KoinTransaction::create([
            'transaction_code' => 'TX-TEST-001',
            'relawan_id'       => $relawan->id,
            'mwc_id'           => $mwc->id,
            'ranting_id'       => $ranting->id,
            'amount'           => 250000,
            'collection_date'  => now()->toDateString(),
            'status'           => 'confirmed',
        ]);

        $this->assertEquals($penthong->id, $transaction->penthong_id);
    }

    public function test_only_one_active_penthong_can_exist_in_scope(): void
    {
        $service = app(DistributionService::class);
        $mwc = Mwc::first();

        $penthong1 = $service->createPenthong([
            'nama_periode' => 'Penthong 1',
            'bulan'        => 1,
            'tahun'        => 2026,
            'status'       => 'aktif',
            'mwc_id'       => $mwc->id,
        ]);

        $this->assertEquals('aktif', $penthong1->fresh()->status);

        // Buat Penthong ke-2 berstatus aktif
        $penthong2 = $service->createPenthong([
            'nama_periode' => 'Penthong 2',
            'bulan'        => 2,
            'tahun'        => 2026,
            'status'       => 'aktif',
            'mwc_id'       => $mwc->id,
        ]);

        $this->assertEquals('tutup', $penthong1->fresh()->status);
        $this->assertEquals('aktif', $penthong2->fresh()->status);
    }

    public function test_close_penthong_locks_data_and_saves_distributions(): void
    {
        $service = app(DistributionService::class);
        $mwc = Mwc::first();
        $ranting = Ranting::first();
        $relawan = Relawan::first();
        $userPc = User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();

        $penthong = $service->createPenthong([
            'nama_periode' => 'Penthong 1 - Tasharruf',
            'bulan'        => 10,
            'tahun'        => 2026,
            'status'       => 'aktif',
            'mwc_id'       => $mwc->id,
        ]);

        // Input 2 transaksi koin
        KoinTransaction::create([
            'transaction_code' => 'TX-01',
            'relawan_id'       => $relawan->id,
            'mwc_id'           => $mwc->id,
            'ranting_id'       => $ranting->id,
            'amount'           => 1000000,
            'collection_date'  => now()->toDateString(),
            'status'           => 'confirmed',
            'penthong_id'      => $penthong->id,
        ]);

        KoinTransaction::create([
            'transaction_code' => 'TX-02',
            'relawan_id'       => $relawan->id,
            'mwc_id'           => $mwc->id,
            'ranting_id'       => $ranting->id,
            'amount'           => 1500000,
            'collection_date'  => now()->toDateString(),
            'status'           => 'confirmed',
            'penthong_id'      => $penthong->id,
        ]);

        // Tutup Penthong (menghitung berdasarkan skema aktif: MWC 13%, PC 5%)
        $closed = $service->closePenthong($penthong, $userPc);

        $this->assertEquals('tutup', $closed->status);
        $this->assertEquals(2500000, $closed->total_collected);
        $this->assertEquals(500000, $closed->operasional_amount); // 20%
        $this->assertEquals(2000000, $closed->net_amount);        // 80%
        $this->assertEquals(1600000, $closed->ranting_amount);    // 80% dari net
        $this->assertEquals(260000, $closed->mwc_amount);         // 13% dari net (Naik dari 240.000)
        $this->assertEquals(100000, $closed->pc_amount);          // 5% dari net (Turun dari 120.000)
        $this->assertEquals(30000, $closed->pw_amount);           // 1.5% dari net
        $this->assertEquals(10000, $closed->pb_amount);           // 0.5% dari net

        // Cek tabel penthong_distributions per Ranting
        $distributionRecord = PenthongDistribution::where('penthong_id', $penthong->id)
            ->where('ranting_id', $ranting->id)
            ->first();

        $this->assertNotNull($distributionRecord);
        $this->assertEquals(2500000, $distributionRecord->gross_amount);
        $this->assertEquals(1600000, $distributionRecord->hak_ranting);
        $this->assertEquals(260000, $distributionRecord->hak_mwc);
        $this->assertEquals(100000, $distributionRecord->hak_pc);
    }

    public function test_penthong_controller_index_and_report_pages_render_properly(): void
    {
        $userPc = User::whereHas('role', fn($q) => $q->whereIn('name', ['pc', 'pc_superadmin']))->first() ?? User::where('username', 'adminpc')->first();
        $this->actingAs($userPc);

        $penthong = Penthong::create([
            'nama_periode' => 'Penthong Oktober',
            'bulan'        => 10,
            'tahun'        => 2026,
            'status'       => 'aktif',
        ]);

        $resIndex = $this->get(route('penthongs.index'));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('Periode Penthong');
        $resIndex->assertSee('Penthong Oktober');

        $resShow = $this->get(route('penthongs.show', $penthong->id));
        $resShow->assertStatus(200);
        $resShow->assertSee('Rincian Tasharruf Bagi Hasil per Ranting NU');
        $resShow->assertSee('Operasional (20%)');

        $resReport = $this->get(route('reports.penthong'));
        $resReport->assertStatus(200);
        $resReport->assertSee('Laporan Distribusi & Bagi Hasil Penthong');
    }
}
