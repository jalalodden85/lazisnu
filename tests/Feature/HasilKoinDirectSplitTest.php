<?php

namespace Tests\Feature;

use App\Http\Controllers\HasilKoinController;
use App\Models\KasMutasi;
use App\Models\KoinTransaction;
use App\Models\KontribusiStruktur;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\PenthongDistribution;
use App\Models\Ranting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HasilKoinDirectSplitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /**
     * Test unit kalkulasi Direct Split (Zero Leakage, Bypass 20% Amil Operasional)
     */
    public function test_direct_split_calculation_accuracy_and_percentages(): void
    {
        $gross = 10000000; // Rp 10.000.000
        $split = HasilKoinController::calculateDirectSplit($gross);

        // 100% Koin Bruto langsung dibagikan
        $this->assertEquals(8000000, $split['ranting_amount'], 'Ranting harus tepat 80%');
        $this->assertEquals(1300000, $split['mwc_amount'], 'MWC harus tepat 13%');
        $this->assertEquals(500000, $split['pc_amount'], 'PC harus tepat 5%');
        $this->assertEquals(150000, $split['pw_amount'], 'PW harus tepat 1.5%');
        $this->assertEquals(50000, $split['pb_amount'], 'PB harus tepat 0.5%');

        $totalAllocated = $split['ranting_amount'] + $split['mwc_amount'] + $split['pc_amount'] + $split['pw_amount'] + $split['pb_amount'];
        $this->assertEquals($gross, $totalAllocated, 'Total alokasi harus genap 100% dari bruto tanpa sisa (zero leakage)');
    }

    /**
     * Test tampilan halaman Hasil Koin dengan simulasi pembagian bruto 80-13-5-1.5-0.5
     */
    public function test_hasil_koin_page_displays_active_period_and_direct_split_simulation(): void
    {
        $user = User::whereHas('role', fn($q) => $q->where('name', 'pc_superadmin'))->first()
            ?? User::first();

        $response = $this->actingAs($user)->get(route('pentasyarufan.hasil-koin.index'));

        $response->assertStatus(200);
        $response->assertSee('Periode Pentong Aktif');
        $response->assertSee('Total Koin Bruto');
        $response->assertSee('Proses & Bagikan Hasil Koin');
        $response->assertSee('80% Ranting');
        $response->assertSee('13% MWC');
        $response->assertSee('5% PC');
    }

    /**
     * Test user MWC dapat mengakses halaman Hasil Koin dan melihat tombol proses hasil koin
     */
    public function test_mwc_user_can_access_hasil_koin_page_without_403(): void
    {
        $mwcUser = User::whereHas('role', fn($q) => $q->where('name', 'mwc'))->first();
        if (!$mwcUser) {
            $mwcRole = Role::firstOrCreate(['name' => 'mwc'], ['label' => 'Admin MWC']);
            $mwcUser = User::factory()->create([
                'role_id'   => $mwcRole->id,
                'mwc_id'    => Mwc::first()?->id ?? 1,
                'is_active' => true,
            ]);
        }

        $response = $this->actingAs($mwcUser)->get(route('pentasyarufan.hasil-koin.index'));

        $response->assertStatus(200);
        $response->assertSee('Hasil Koin');
        $response->assertDontSee('Mode Tinjauan (Read-Only)');
    }

    /**
     * Test user Ranting dapat mengakses halaman Hasil Koin dalam mode tinjauan (Read-Only)
     */
    public function test_ranting_user_can_access_hasil_koin_page_without_403(): void
    {
        $rantingUser = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();
        if (!$rantingUser) {
            $rantingRole = Role::firstOrCreate(['name' => 'ranting'], ['label' => 'Admin Ranting']);
            $rantingUser = User::factory()->create([
                'role_id'    => $rantingRole->id,
                'ranting_id' => Ranting::first()?->id ?? 1,
                'is_active'  => true,
            ]);
        }

        $response = $this->actingAs($rantingUser)->get(route('pentasyarufan.hasil-koin.index'));

        $response->assertStatus(200);
        $response->assertSee('Hasil Koin');
        $response->assertSee('Mode Tinjauan (Read-Only)');
    }

    /**
     * Test user Ranting ditolak saat mencoba mengeksekusi proses pembagian
     */
    public function test_ranting_user_cannot_execute_proses_hasil_koin(): void
    {
        $rantingUser = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();
        if (!$rantingUser) {
            $rantingRole = Role::firstOrCreate(['name' => 'ranting'], ['label' => 'Admin Ranting']);
            $rantingUser = User::factory()->create([
                'role_id'    => $rantingRole->id,
                'ranting_id' => Ranting::first()?->id ?? 1,
                'is_active'  => true,
            ]);
        }

        $response = $this->actingAs($rantingUser)->post(route('pentasyarufan.hasil-koin.proses'), [
            'penthong_id' => Penthong::first()?->id ?? 1,
        ]);

        $response->assertSessionHas('error');
    }

    /**
     * Test user MWC dapat mengeksekusi prosesHasilKoin dengan Data Scoping Ketat (hanya ranting wilayahnya)
     */
    public function test_mwc_user_can_execute_proses_hasil_koin_with_strict_scoping(): void
    {
        $mwcA = Mwc::first() ?? Mwc::create(['code' => 'MWC-A', 'name' => 'MWC A']);
        $mwcB = Mwc::where('id', '!=', $mwcA->id)->first() ?? Mwc::create(['code' => 'MWC-B', 'name' => 'MWC B']);

        $rantingA = Ranting::firstOrCreate(['code' => 'RTG-A'], ['name' => 'Ranting A', 'mwc_id' => $mwcA->id]);
        $rantingB = Ranting::firstOrCreate(['code' => 'RTG-B'], ['name' => 'Ranting B', 'mwc_id' => $mwcB->id]);

        $penthong = Penthong::create([
            'nama_periode' => 'Penthong Isolated Scoping',
            'bulan'        => 12,
            'tahun'        => 2026,
            'status'       => 'aktif',
            'is_active'    => true,
        ]);

        $mwcUser = User::factory()->create([
            'role_id'   => Role::where('name', 'mwc')->first()?->id ?? 1,
            'mwc_id'    => $mwcA->id,
            'is_active' => true,
        ]);

        $relawan = \App\Models\Relawan::first();

        // Transaksi koin Ranting A (di bawah MWC A)
        KoinTransaction::create([
            'penthong_id'        => $penthong->id,
            'periode_pentong_id' => $penthong->id,
            'ranting_id'         => $rantingA->id,
            'mwc_id'             => $mwcA->id,
            'relawan_id'         => $relawan?->id,
            'user_id'            => $mwcUser->id,
            'amount'             => 2000000,
            'collection_date'    => now()->toDateString(),
            'period'             => '2026-12',
            'status'             => KoinTransaction::STATUS_CONFIRMED,
        ]);

        // Transaksi koin Ranting B (di bawah MWC B)
        KoinTransaction::create([
            'penthong_id'        => $penthong->id,
            'periode_pentong_id' => $penthong->id,
            'ranting_id'         => $rantingB->id,
            'mwc_id'             => $mwcB->id,
            'relawan_id'         => $relawan?->id,
            'user_id'            => $mwcUser->id,
            'amount'             => 3000000,
            'collection_date'    => now()->toDateString(),
            'period'             => '2026-12',
            'status'             => KoinTransaction::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($mwcUser)->post(route('pentasyarufan.hasil-koin.proses'), [
            'penthong_id' => $penthong->id,
        ]);

        $response->assertRedirect(route('pentasyarufan.hasil-koin.index', ['penthong_id' => $penthong->id]));
        $response->assertSessionHas('success');

        // Pastikan distribusi hanya tersimpan untuk MWC A
        $distA = PenthongDistribution::where('penthong_id', $penthong->id)->where('mwc_id', $mwcA->id)->get();
        $this->assertNotEmpty($distA);
        $this->assertEquals(2000000 * 0.80, (float) $distA->first()->ranting_amount);

        // Pastikan koin milik MWC B TIDAK ikut diproses oleh MWC A
        $distBCount = PenthongDistribution::where('penthong_id', $penthong->id)->where('mwc_id', $mwcB->id)->count();
        $this->assertEquals(0, $distBCount, 'Distribusi MWC B tidak boleh diproses oleh user MWC A');
    }

    /**
     * Test eksekusi prosesHasilKoin membagi langsung koin bruto 100% tanpa potongan operasional 20%
     * dan mencatat ke BKU Tingkatan secara atomic (DB::transaction).
     */
    public function test_proses_hasil_koin_direct_split_and_updates_bku_atomic(): void
    {
        $user = User::whereHas('role', fn($q) => $q->where('name', 'pc_superadmin'))->first()
            ?? User::first();

        // Pastikan ada penthong aktif dan transaksi koin
        $penthong = Penthong::aktif()->first();
        if (!$penthong) {
            $penthong = Penthong::create([
                'nama_periode' => 'Penthong Uji Coba',
                'bulan'        => 10,
                'tahun'        => 2026,
                'status'       => 'aktif',
                'is_active'    => true,
            ]);
        }

        $ranting = Ranting::first();
        $this->assertNotNull($ranting);

        $relawan = \App\Models\Relawan::first();
        $this->assertNotNull($relawan);

        // Tambah transaksi koin jika belum ada
        KoinTransaction::create([
            'penthong_id'        => $penthong->id,
            'periode_pentong_id' => $penthong->id,
            'ranting_id'         => $ranting->id,
            'mwc_id'             => $ranting->mwc_id,
            'relawan_id'         => $relawan->id,
            'user_id'            => $user->id,
            'amount'             => 5000000,
            'collection_date'    => now()->toDateString(),
            'period'             => '2026-10',
            'status'             => KoinTransaction::STATUS_CONFIRMED,
        ]);

        $response = $this->actingAs($user)->post(route('pentasyarufan.hasil-koin.proses'), [
            'penthong_id' => $penthong->id,
        ]);

        $response->assertRedirect(route('pentasyarufan.hasil-koin.index', ['penthong_id' => $penthong->id]));
        $response->assertSessionHas('success');

        // Pastikan penthong_distributions tersimpan dengan pembagian 80-13-5-1.5-0.5
        $distributions = PenthongDistribution::where('penthong_id', $penthong->id)->get();
        $this->assertNotEmpty($distributions);

        foreach ($distributions as $dist) {
            $gross = (float) $dist->gross_amount;
            $this->assertEquals(0, (float) $dist->operasional_amount, 'Potongan operasional amil harus 0 (dibypass)');
            $this->assertEquals($gross, (float) $dist->net_amount);

            $this->assertEquals(round($gross * 0.80), (float) $dist->ranting_amount);
            $this->assertEquals(round($gross * 0.13), (float) $dist->mwc_amount);
            $this->assertEquals(round($gross * 0.05), (float) $dist->pc_amount);
            $this->assertEquals(round($gross * 0.015), (float) $dist->pw_amount);
            $this->assertEquals(round($gross * 0.005), (float) $dist->pb_amount);
        }

        // Pastikan kontribusi_strukturs tercatat
        $kontribusi = KontribusiStruktur::where('periode_pentong_id', $penthong->id)->get();
        $this->assertNotEmpty($kontribusi);

        // Pastikan kas_mutasis (BKU Saldo Utama) tercatat untuk ranting, mwc, dan pc
        $mutasiRanting = KasMutasi::where('periode_pentong_id', $penthong->id)
            ->where('tingkat', 'ranting')
            ->where('jenis', 'debet')
            ->count();
        $this->assertGreaterThan(0, $mutasiRanting, 'BKU Ranting harus menerima mutasi Debet 80% Saldo Utama');

        $mutasiMwc = KasMutasi::where('periode_pentong_id', $penthong->id)
            ->where('tingkat', 'mwc')
            ->where('jenis', 'debet')
            ->count();
        $this->assertGreaterThan(0, $mutasiMwc, 'BKU MWC harus menerima mutasi Debet 13% Saldo Utama MWC');

        $mutasiPc = KasMutasi::where('periode_pentong_id', $penthong->id)
            ->where('tingkat', 'pc')
            ->where('jenis', 'debet')
            ->count();
        $this->assertGreaterThan(0, $mutasiPc, 'BKU PC harus menerima mutasi Debet 5% Saldo Utama PC');

        $kasPcFirst = KasMutasi::where('periode_pentong_id', $penthong->id)
            ->where('tingkat', 'pc')
            ->where('jenis', 'debet')
            ->first();
        $this->assertNotNull($kasPcFirst);

        $rantingClean = preg_replace('/^(Ranting|PRNU)\s+/i', '', trim($ranting->name));
        $mwcClean = preg_replace('/^(MWC\s+NU|MWCNU|MWC)\s+/i', '', trim($ranting->mwc?->name ?? 'MWC'));
        $expectedUraianPc = "Bagi Hasil Koin NU - Ranting {$rantingClean} MWC NU {$mwcClean} Periode {$penthong->nama_periode}";
        $this->assertEquals($expectedUraianPc, $kasPcFirst->uraian, 'Uraian BKU PC harus memuat nama Ranting, MWC, dan Periode sesuai spesifikasi');
    }
}
