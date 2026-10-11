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

class PeriodePentongSetoranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_rejects_store_setoran_when_no_active_pentong_exists(): void
    {
        // Tutup semua penthong
        Penthong::query()->update(['status' => Penthong::STATUS_TUTUP, 'is_active' => false]);

        $userPc = User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();
        $relawan = Relawan::first();

        $this->actingAs($userPc);

        // 1. Web request ditolak
        $response = $this->from(route('transactions.create'))->post(route('transactions.store'), [
            'relawan_id'      => $relawan->id,
            'amount'          => 150000,
            'collection_date' => now()->toDateString(),
        ]);

        $response->assertRedirect(route('transactions.create'));
        $response->assertSessionHas('error', 'Tidak dapat menginput setoran. Periode pentong saat ini sedang ditutup atau belum dibuka oleh admin.');

        // 2. API request ditolak dengan 422 JSON
        $apiResponse = $this->postJson('/api/setoran', [
            'relawan_id'      => $relawan->id,
            'amount'          => 150000,
            'collection_date' => now()->toDateString(),
        ]);

        $apiResponse->assertStatus(422);
        $apiResponse->assertJson([
            'success' => false,
            'message' => 'Tidak dapat menginput setoran. Periode pentong saat ini sedang ditutup atau belum dibuka oleh admin.',
        ]);
    }

    public function test_rejects_store_setoran_when_explicit_pentong_is_closed(): void
    {
        $closedPenthong = Penthong::create([
            'nama_periode' => 'Penthong Masa Lalu',
            'bulan'        => 1,
            'tahun'        => 2025,
            'status'       => Penthong::STATUS_CLOSED,
            'is_active'    => false,
        ]);

        $userPc = User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();
        $relawan = Relawan::first();

        $this->actingAs($userPc);

        $response = $this->from(route('transactions.create'))->post(route('transactions.store'), [
            'periode_pentong_id' => $closedPenthong->id,
            'relawan_id'         => $relawan->id,
            'amount'             => 200000,
            'collection_date'    => now()->toDateString(),
        ]);

        $response->assertSessionHas('error', 'Tidak dapat menginput setoran. Periode pentong saat ini sedang ditutup atau belum dibuka oleh admin.');
    }

    public function test_successfully_stores_and_binds_to_active_periode_pentong(): void
    {
        // Pastikan ada 1 penthong aktif
        Penthong::query()->update(['status' => Penthong::STATUS_CLOSED, 'is_active' => false]);
        $activePenthong = Penthong::create([
            'nama_periode' => 'Penthong Berjalan Oktober 2026',
            'bulan'        => 10,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_AKTIF,
            'is_active'    => true,
        ]);

        $userPc = User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();
        $relawan = Relawan::first();

        $this->actingAs($userPc);

        $response = $this->post(route('transactions.store'), [
            'relawan_id'      => $relawan->id,
            'amount'          => 500000,
            'collection_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasNoErrors();
        
        $transaction = KoinTransaction::latest('id')->first();
        $this->assertNotNull($transaction);
        $this->assertEquals($activePenthong->id, $transaction->penthong_id);
        $this->assertEquals($activePenthong->id, $transaction->periode_pentong_id);
        $this->assertEquals(500000, $transaction->amount);
    }

    public function test_create_form_displays_active_periode_badge_or_frozen_banner(): void
    {
        $userPc = User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();
        $this->actingAs($userPc);

        // Kasus 1: Ada periode aktif
        Penthong::query()->update(['status' => Penthong::STATUS_CLOSED, 'is_active' => false]);
        $activePenthong = Penthong::create([
            'nama_periode' => 'Penthong Terbuka Edisi Khusus',
            'bulan'        => 10,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_AKTIF,
            'is_active'    => true,
        ]);

        $resActive = $this->get(route('transactions.create'));
        $resActive->assertStatus(200);
        $resActive->assertSee('Periode Pentong Aktif: Penthong Terbuka Edisi Khusus');
        $resActive->assertSee('Siap Terima Setoran');

        // Kasus 2: Semua periode ditutup
        Penthong::query()->update(['status' => Penthong::STATUS_CLOSED, 'is_active' => false]);
        $resClosed = $this->get(route('transactions.create'));
        $resClosed->assertStatus(200);
        $resClosed->assertSee('Transaksi Setoran Dibekukan Sementara');
        $resClosed->assertSee('Setoran Dikunci (Periode Tertutup)');
    }

    public function test_transactions_index_filter_by_periode_pentong(): void
    {
        $userPc = User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();
        $this->actingAs($userPc);
        $relawan = Relawan::first();

        // Buat Periode 1 (Aktif)
        $p1 = Penthong::create([
            'nama_periode' => 'Penthong Bulan 1',
            'bulan'        => 1,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_AKTIF,
            'is_active'    => true,
        ]);

        // Buat Periode 2 (Tutup)
        $p2 = Penthong::create([
            'nama_periode' => 'Penthong Bulan 2',
            'bulan'        => 2,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_CLOSED,
            'is_active'    => false,
        ]);

        // Transaksi di P1
        KoinTransaction::create([
            'penthong_id'        => $p1->id,
            'periode_pentong_id' => $p1->id,
            'relawan_id'         => $relawan->id,
            'ranting_id'         => $relawan->ranting_id,
            'mwc_id'             => $relawan->ranting->mwc_id,
            'amount'             => 300000,
            'collection_date'    => now()->toDateString(),
            'status'             => 'confirmed',
        ]);

        // Transaksi di P2
        KoinTransaction::create([
            'penthong_id'        => $p2->id,
            'periode_pentong_id' => $p2->id,
            'relawan_id'         => $relawan->id,
            'ranting_id'         => $relawan->ranting_id,
            'mwc_id'             => $relawan->ranting->mwc_id,
            'amount'             => 700000,
            'collection_date'    => now()->toDateString(),
            'status'             => 'confirmed',
        ]);

        // 1. Default View (Filter otomatis ke P1 yang sedang aktif)
        $resDefault = $this->get(route('transactions.index'));
        $resDefault->assertStatus(200);
        $resDefault->assertSee('Penthong Bulan 1');
        $resDefault->assertSee('300.000');

        // 2. Filter Spesifik ke P2
        $resP2 = $this->get(route('transactions.index', ['periode_id' => $p2->id]));
        $resP2->assertStatus(200);
        $resP2->assertSee('Penthong Bulan 2');
        $resP2->assertSee('700.000');

        // 3. Filter Opsi "Semua Periode" (Akumulasi seluruh transaksi)
        $resAll = $this->get(route('transactions.index', ['periode_id' => 'all']));
        $resAll->assertStatus(200);
        $resAll->assertSee('Akumulasi Seluruh Periode');
        $expectedAllTotal = KoinTransaction::where('status', 'confirmed')->sum('amount');
        $resAll->assertSee(number_format($expectedAllTotal, 0, ',', '.'));
    }
}
