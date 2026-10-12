<?php

namespace Tests\Feature;

use App\Models\BkuOperasional;
use App\Models\DistributionSetting;
use App\Models\KategoriOperasional;
use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\Role;
use App\Models\User;
use App\Services\OperasionalWalletService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BkuOperasionalTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    protected function createRantingUser(): User
    {
        $roleRanting = Role::where('name', 'ranting')->first();
        $ranting = Ranting::first();

        $user = User::factory()->create([
            'role_id'    => $roleRanting->id,
            'ranting_id' => $ranting->id,
            'mwc_id'     => $ranting->mwc_id,
        ]);

        $menu = \App\Models\Menu::where('route_name', 'bku-operasional.index')->first();
        if ($menu) {
            $user->menus()->syncWithoutDetaching([$menu->id]);
        }

        return $user;
    }

    public function test_bku_operasional_index_page_loads_and_shows_metrics(): void
    {
        $userRanting = $this->createRantingUser();

        $response = $this->actingAs($userRanting)->get(route('bku-operasional.index'));
        $response->assertStatus(200);
        $response->assertSee('BKU Operasional');
        $response->assertSee('Sisa Saldo Kas Operasional');
    }

    public function test_operasional_income_recording_and_balance_calculation(): void
    {
        $userRanting = $this->createRantingUser();
        $ranting = $userRanting->ranting;

        $postData = [
            'sumber_dana'       => 'infaq_operasional',
            'nominal'           => 1000000,
            'tanggal_transaksi' => '2026-10-12',
            'uraian'            => 'Infaq khusus amil dari donatur',
        ];

        $response = $this->actingAs($userRanting)->post(route('bku-operasional.income'), $postData);
        $response->assertRedirect(route('bku-operasional.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bku_operasionals', [
            'jenis'       => 'debet',
            'nominal'     => 1000000,
            'sumber_dana' => 'infaq_operasional',
            'ranting_id'  => $ranting->id,
        ]);

        $walletService = app(OperasionalWalletService::class);
        $balance = $walletService->getAvailableBalance('ranting', $ranting->id);
        $this->assertEquals(1000000, $balance);
    }

    public function test_expense_recording_fails_when_balance_insufficient(): void
    {
        $userRanting = $this->createRantingUser();
        $ranting = $userRanting->ranting;

        $kategori = KategoriOperasional::first();

        // Saldo saat ini 0, mencoba belanja Rp 200.000
        $expenseData = [
            'kategori_operasional_id' => $kategori->id,
            'nominal'                 => 200000,
            'tanggal_transaksi'       => '2026-10-12',
            'uraian'                  => 'Pembelian ATK dan kertas HVS',
        ];

        $response = $this->actingAs($userRanting)->post(route('bku-operasional.expense'), $expenseData);
        $response->assertSessionHasErrors('nominal');

        $this->assertDatabaseMissing('bku_operasionals', [
            'jenis'   => 'kredit',
            'nominal' => 200000,
        ]);
    }

    public function test_expense_recording_succeeds_when_within_balance(): void
    {
        $userRanting = $this->createRantingUser();
        $ranting = $userRanting->ranting;

        $kategori = KategoriOperasional::first();

        // 1. Tambah saldo pemasukan Rp 500.000
        BkuOperasional::create([
            'nomor_transaksi'   => 'BKO-INC-TEST-001',
            'jenis'             => 'debet',
            'sumber_dana'       => 'infaq_operasional',
            'nominal'           => 500000,
            'tanggal_transaksi' => '2026-10-10',
            'uraian'            => 'Saldo Awal Operasional',
            'tingkat'           => 'ranting',
            'ranting_id'        => $ranting->id,
            'mwc_id'            => $ranting->mwc_id,
            'created_by'        => $userRanting->id,
        ]);

        // 2. Belanja Rp 150.000 (Kurang dari Rp 500.000)
        $expenseData = [
            'kategori_operasional_id' => $kategori->id,
            'nominal'                 => 150000,
            'tanggal_transaksi'       => '2026-10-12',
            'uraian'                  => 'Beli Tinta dan Kertas',
            'penerima_atau_vendor'    => 'Toko Berkah',
        ];

        $response = $this->actingAs($userRanting)->post(route('bku-operasional.expense'), $expenseData);
        $response->assertRedirect(route('bku-operasional.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('bku_operasionals', [
            'jenis'                => 'kredit',
            'nominal'              => 150000,
            'penerima_atau_vendor' => 'Toko Berkah',
        ]);

        // 3. Cek sisa saldo operasional: 500.000 - 150.000 = 350.000
        $walletService = app(OperasionalWalletService::class);
        $balance = $walletService->getAvailableBalance('ranting', $ranting->id);
        $this->assertEquals(350000, $balance);
    }

    public function test_sync_penthong_allocates_operational_income_accurately(): void
    {
        $userRanting = $this->createRantingUser();
        $ranting = $userRanting->ranting;
        $relawan = Relawan::where('ranting_id', $ranting->id)->first() ?? Relawan::factory()->create(['ranting_id' => $ranting->id]);

        $penthong = Penthong::create([
            'nama_periode' => 'Pentong Uji Coba Oktober 2026',
            'bulan'        => 10,
            'tahun'        => 2026,
            'start_date'   => '2026-10-01',
            'end_date'     => '2026-10-31',
            'is_active'    => true,
            'created_by'   => $userRanting->id,
        ]);

        // Bersihkan transaksi koin bawaan seeder untuk ranting ini agar nominal presisi
        KoinTransaction::where('ranting_id', $ranting->id)->delete();

        // Buat transaksi koin Rp 10.000.000 di ranting ini
        KoinTransaction::create([
            'transaction_code' => 'TRX-TEST-BKO-01',
            'relawan_id'       => $relawan->id,
            'ranting_id'       => $ranting->id,
            'mwc_id'           => $ranting->mwc_id,
            'amount'           => 10000000,
            'collection_date'  => '2026-10-15',
            'period'           => '2026-10',
            'status'           => KoinTransaction::STATUS_CONFIRMED,
            'verified_by'      => $userRanting->id,
        ]);

        // Trigger Sync Alokasi Pentong
        $response = $this->actingAs($userRanting)->post(route('bku-operasional.sync-penthong'), [
            'penthong_id' => $penthong->id,
        ]);
        $response->assertRedirect(route('bku-operasional.index'));
        $response->assertSessionHas('success');

        // Dengan operasional_pct = 20%, dari Rp 10.000.000 koin kotor, jatah amil ranting adalah Rp 2.000.000
        $this->assertDatabaseHas('bku_operasionals', [
            'periode_pentong_id' => $penthong->id,
            'jenis'              => 'debet',
            'sumber_dana'        => 'alokasi_pentong',
            'nominal'            => 2000000,
            'ranting_id'         => $ranting->id,
        ]);
    }

    public function test_export_pdf_and_excel(): void
    {
        $userRanting = $this->createRantingUser();
        $ranting = $userRanting->ranting;

        // Test Export PDF View
        $responsePdf = $this->actingAs($userRanting)->get(route('bku-operasional.export.pdf'));
        $responsePdf->assertStatus(200);
        $responsePdf->assertSee('LAPORAN PERTANGGUNGJAWABAN DANA OPERASIONAL (HAK AMIL)');

        // Test Export Excel
        $responseExcel = $this->actingAs($userRanting)->get(route('bku-operasional.export.excel'));
        $responseExcel->assertStatus(200);
    }

    public function test_level_based_operational_income_sync_for_ranting_mwc_and_pc(): void
    {
        $walletService = app(OperasionalWalletService::class);
        $ranting = Ranting::first();
        $mwc = Mwc::find($ranting->mwc_id);

        $penthong = Penthong::create([
            'nama_periode' => 'Pentong Tingkatan Test 2026',
            'bulan'        => 10,
            'tahun'        => 2026,
            'start_date'   => '2026-10-01',
            'end_date'     => '2026-10-31',
            'is_active'    => true,
        ]);

        // 1. TINGKAT RANTING: Pemasukan Riil = Total Koin Rp 1.000.000
        $relawan = Relawan::where('ranting_id', $ranting->id)->first() ?? Relawan::factory()->create(['ranting_id' => $ranting->id]);
        KoinTransaction::where('ranting_id', $ranting->id)->delete();
        KoinTransaction::create([
            'transaction_code' => 'TRX-LEVEL-R01',
            'relawan_id'       => $relawan->id,
            'ranting_id'       => $ranting->id,
            'mwc_id'           => $mwc->id,
            'amount'           => 1000000,
            'collection_date'  => '2026-10-10',
            'period'           => '2026-10',
            'status'           => KoinTransaction::STATUS_CONFIRMED,
        ]);

        $resRanting = $walletService->syncOperationalIncomeByLevel($penthong, 'ranting', $ranting->id, $mwc->id);
        $this->assertNotNull($resRanting);
        $this->assertEquals(200000, $resRanting->nominal, 'Operasional Ranting harus 20% dari Rp 1.000.000 = Rp 200.000');
        $this->assertEquals('ranting', $resRanting->tingkat);

        // 2. TINGKAT MWC: Pemasukan Riil = Kontribusi Masuk Rp 104.000 (13%)
        \App\Models\KontribusiStruktur::create([
            'nomor_transaksi'        => 'KTR-MWC-TEST-01',
            'periode_pentong_id'     => $penthong->id,
            'ranting_id'             => $ranting->id,
            'mwc_id'                 => $mwc->id,
            'user_id'                => 1,
            'tanggal_setor'          => '2026-10-15',
            'nominal_bruto'          => 1000000,
            'persentase_operasional' => 20,
            'nominal_operasional'    => 200000,
            'nominal_netto'          => 800000,
            'nominal_mwc'            => 104000,
            'nominal_pc'             => 40000,
            'nominal_pw'             => 12000,
            'nominal_pb'             => 4000,
            'total_disetor'          => 160000,
            'tujuan_struktur'        => 'semua',
            'metode_penyaluran'      => 'tunai',
            'status'                 => 'disetor',
        ]);

        $resMwc = $walletService->syncOperationalIncomeByLevel($penthong, 'mwc', null, $mwc->id);
        $this->assertNotNull($resMwc);
        $this->assertEquals(20800, $resMwc->nominal, 'Operasional MWC harus 20% dari Rp 104.000 = Rp 20.800');
        $this->assertEquals('mwc', $resMwc->tingkat);

        // 3. TINGKAT PC: Pemasukan Riil = Kontribusi Masuk Rp 40.000 (5%)
        $resPc = $walletService->syncOperationalIncomeByLevel($penthong, 'pc', null, null);
        $this->assertNotNull($resPc);
        $this->assertEquals(8000, $resPc->nominal, 'Operasional PC harus 20% dari Rp 40.000 = Rp 8.000');
        $this->assertEquals('pc', $resPc->tingkat);
    }

    public function test_recalculating_5_pilar_balance_with_80_percent_netto_across_all_levels(): void
    {
        $balanceService = app(\App\Services\PentasyarufanBalanceService::class);
        $ranting = Ranting::first();
        $mwc = Mwc::find($ranting->mwc_id);

        $penthong = Penthong::create([
            'nama_periode' => 'Pentong Pilar Recalc 2026',
            'bulan'        => 10,
            'tahun'        => 2026,
            'start_date'   => '2026-10-01',
            'end_date'     => '2026-10-31',
            'is_active'    => true,
        ]);

        // Siapkan kontribusi masuk: MWC Rp 104.000, PC Rp 40.000
        \App\Models\KontribusiStruktur::create([
            'nomor_transaksi'        => 'KTR-PILAR-TEST-01',
            'periode_pentong_id'     => $penthong->id,
            'ranting_id'             => $ranting->id,
            'mwc_id'                 => $mwc->id,
            'user_id'                => 1,
            'tanggal_setor'          => '2026-10-15',
            'nominal_bruto'          => 1000000,
            'persentase_operasional' => 20,
            'nominal_operasional'    => 200000,
            'nominal_netto'          => 800000,
            'nominal_mwc'            => 104000,
            'nominal_pc'             => 40000,
            'nominal_pw'             => 12000,
            'nominal_pb'             => 4000,
            'total_disetor'          => 160000,
            'tujuan_struktur'        => 'semua',
            'metode_penyaluran'      => 'tunai',
            'status'                 => 'disetor',
        ]);

        // A. Cek Kalkulasi 5 Pilar MWC
        $budgetMwc = $balanceService->calculatePeriodBudget($penthong, null, null, $mwc->id, 'mwc');
        // MWC: Pemasukan Kontribusi Rp 104.000, Operasional 20% = Rp 20.800, Basis 5 Pilar = Rp 83.200 (80%)
        $this->assertEquals(20800, $budgetMwc['potongan_operasional']);
        $this->assertEquals(83200, $budgetMwc['dana_pilar_basis']);

        // B. Cek Kalkulasi 5 Pilar PC
        $budgetPc = $balanceService->calculatePeriodBudget($penthong, null, null, null, 'pc');
        // PC: Pemasukan Kontribusi Rp 40.000, Operasional 20% = Rp 8.000, Basis 5 Pilar = Rp 32.000 (80%)
        $this->assertEquals(8000, $budgetPc['potongan_operasional']);
        $this->assertEquals(32000, $budgetPc['dana_pilar_basis']);
    }
}
