<?php

namespace Tests\Feature;

use App\Models\BkuTransaction;
use App\Models\KasMutasi;
use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class BkuPengeluaranTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        Storage::fake('public');
    }

    /**
     * Test tampilan halaman BKU memuat tombol + Tambah Pengeluaran dan 3 Widget Card
     */
    public function test_bku_index_page_displays_tambah_pengeluaran_button_and_3_widget_cards(): void
    {
        $user = User::whereHas('role', fn($q) => $q->where('name', 'pc_superadmin'))->first()
            ?? User::first();

        $response = $this->actingAs($user)->get(route('bku.index'));

        $response->assertStatus(200);
        $response->assertSee('+ Tambah Pengeluaran');
        $response->assertSee('TOTAL PEMASUKAN');
        $response->assertSee('TOTAL PENGELUARAN');
        $response->assertSee('SISA SALDO AKHIR');
        $response->assertSee('modalTambahPengeluaran');
    }

    /**
     * Test validasi backend: Pengeluaran gagal jika melebihi saldo kas (anti-defisit)
     */
    public function test_store_pengeluaran_fails_when_nominal_exceeds_saldo_akhir(): void
    {
        $user = User::whereHas('role', fn($q) => $q->where('name', 'pc_superadmin'))->first()
            ?? User::first();

        $penthong = Penthong::first() ?? Penthong::create([
            'nama_periode' => 'Penthong Defisit Test',
            'bulan'        => 10,
            'tahun'        => 2026,
            'status'       => 'aktif',
            'is_active'    => true,
        ]);

        // Kirim nominal pengeluaran sebesar Rp 999.000.000 (jauh melebihi saldo kas yang ada)
        $response = $this->actingAs($user)->post(route('bku.pengeluaran.store'), [
            'penthong_id'     => $penthong->id,
            'kategori'        => 'Pendidikan',
            'tanggal'         => now()->toDateString(),
            'nominal'         => '999.000.000',
            'penerima_vendor' => 'Toko Buku Cerdas',
            'uraian'          => 'Bantuan beasiswa santri berprestasi',
        ]);

        $response->assertSessionHas('error');
        $response->assertSessionHas('error', function ($value) {
            return str_contains($value, 'Saldo Kas tidak mencukupi!');
        });

        // Pastikan tidak ada data yang tersimpan di bku_transactions
        $this->assertDatabaseMissing('bku_transactions', [
            'uraian' => 'Bantuan beasiswa santri berprestasi',
        ]);
    }

    /**
     * Test pengeluaran berhasil jika dalam batas saldo dan menyimpan file bukti kwitansi
     */
    public function test_store_pengeluaran_succeeds_when_within_balance_and_uploads_receipt(): void
    {
        $ranting = Ranting::first();
        $this->assertNotNull($ranting);

        $mwc = $ranting->mwc ?? Mwc::first();
        $penthong = Penthong::first();

        $user = User::factory()->create([
            'role_id'    => Role::where('name', 'ranting')->first()?->id ?? 1,
            'ranting_id' => $ranting->id,
            'mwc_id'     => $mwc?->id,
            'is_active'  => true,
        ]);

        $relawan = \App\Models\Relawan::first();

        // Buat pemasukan koin agar ada saldo kas sebesar Rp 5.000.000
        KoinTransaction::create([
            'penthong_id'        => $penthong->id,
            'periode_pentong_id' => $penthong->id,
            'ranting_id'         => $ranting->id,
            'mwc_id'             => $mwc?->id,
            'relawan_id'         => $relawan?->id,
            'user_id'            => $user->id,
            'amount'             => 5000000,
            'collection_date'    => now()->toDateString(),
            'period'             => '2026-10',
            'status'             => KoinTransaction::STATUS_CONFIRMED,
        ]);

        // Upload file bukti kwitansi dummy
        $file = UploadedFile::fake()->image('nota_kwitansi.jpg', 600, 400);

        $response = $this->actingAs($user)->post(route('bku.pengeluaran.store'), [
            'penthong_id'     => $penthong->id,
            'kategori'        => 'Biaya Operasional',
            'tanggal'         => now()->toDateString(),
            'nominal'         => '250.000',
            'penerima_vendor' => 'Percetakan Berkah',
            'uraian'          => 'Bisyaroh petugas dan konsumsi rapat koordinasi',
            'bukti_kwitansi'  => $file,
        ]);

        $response->assertSessionHas('success');

        // Pastikan transaksi tersimpan di database
        $bkuTx = BkuTransaction::where('uraian', 'Bisyaroh petugas dan konsumsi rapat koordinasi')->first();
        $this->assertNotNull($bkuTx);
        $this->assertEquals(250000, (float) $bkuTx->nominal);
        $this->assertEquals('NU-O', $bkuTx->kode_transaksi);
        $this->assertEquals('Biaya Operasional', $bkuTx->kategori);
        $this->assertNotNull($bkuTx->bukti_kwitansi);

        // Pastikan file tersimpan di storage public kwitansi
        Storage::disk('public')->assertExists($bkuTx->bukti_kwitansi);

        // Pastikan halaman BKU memuat tombol "Lihat Bukti"
        $pageResponse = $this->actingAs($user)->get(route('bku.index', ['penthong_id' => $penthong->id]));
        $pageResponse->assertStatus(200);
        $pageResponse->assertSee('Lihat Bukti');
    }
}
