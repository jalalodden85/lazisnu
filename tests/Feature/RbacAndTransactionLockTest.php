<?php

namespace Tests\Feature;

use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RbacAndTransactionLockTest extends TestCase
{
    use RefreshDatabase;

    protected User $userPc;
    protected User $userMwc;
    protected User $userRanting;
    protected Relawan $relawan;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        $this->userPc = User::whereHas('role', fn($q) => $q->where('name', Role::PC))->first();
        $this->userMwc = User::whereHas('role', fn($q) => $q->where('name', Role::MWC))->first();
        $this->userRanting = User::whereHas('role', fn($q) => $q->where('name', Role::RANTING))->first();

        // Ambil relawan yang terdaftar di ranting milik userRanting
        $this->relawan = Relawan::where('ranting_id', $this->userRanting->ranting_id)->first()
            ?? Relawan::create([
                'ranting_id' => $this->userRanting->ranting_id,
                'nama'       => 'Ahmad Relawan Ranting',
                'area_tugas' => 'RT 01 / RW 02',
                'status'     => 'active',
            ]);
    }

    /**
     * SPESIFIKASI 1: HAK AKSES PERIODE PENTONG (ADMIN PC ONLY)
     */
    public function test_admin_pc_can_manage_pentong_but_lower_roles_receive_403(): void
    {
        // 1. Admin PC: Diizinkan membuka form create dan menyimpan periode baru
        $this->actingAs($this->userPc);

        $resCreatePc = $this->get(route('penthongs.create'));
        $resCreatePc->assertStatus(200);

        $resStorePc = $this->post(route('penthongs.store'), [
            'nama_periode' => 'Penthong Uji Coba PC',
            'bulan'        => 11,
            'tahun'        => 2026,
        ]);
        $resStorePc->assertRedirect();

        $createdPenthong = Penthong::where('nama_periode', 'Penthong Uji Coba PC')->first();
        $this->assertNotNull($createdPenthong);

        // 2. Admin MWC: DITOLAK HTTP 403 Forbidden
        $this->actingAs($this->userMwc);

        $resCreateMwc = $this->get(route('penthongs.create'));
        $resCreateMwc->assertStatus(403);

        $resStoreMwc = $this->post(route('penthongs.store'), [
            'nama_periode' => 'Penthong Ilegal MWC',
            'bulan'        => 12,
            'tahun'        => 2026,
        ]);
        $resStoreMwc->assertStatus(403);

        $resCloseMwc = $this->post(route('penthongs.close', $createdPenthong->id));
        $resCloseMwc->assertStatus(403);

        // 3. Admin Ranting: DITOLAK HTTP 403 Forbidden
        $this->actingAs($this->userRanting);

        $resCreateRanting = $this->get(route('penthongs.create'));
        $resCreateRanting->assertStatus(403);

        $resStoreRanting = $this->post(route('penthongs.store'), [
            'nama_periode' => 'Penthong Ilegal Ranting',
            'bulan'        => 12,
            'tahun'        => 2026,
        ]);
        $resStoreRanting->assertStatus(403);

        $resCloseRanting = $this->post(route('penthongs.close', $createdPenthong->id));
        $resCloseRanting->assertStatus(403);

        // 4. API Mutation Protection: Tolak unauthenticated/non-PC dengan 403
        $apiMutation = $this->postJson('/api/periode-pentong', [
            'nama_periode' => 'Penthong API Ilegal',
            'bulan'        => 1,
            'tahun'        => 2027,
        ]);
        $apiMutation->assertStatus(403);
    }

    public function test_lower_roles_have_read_only_access_and_buttons_hidden(): void
    {
        $this->actingAs($this->userRanting);

        $resIndex = $this->get(route('penthongs.index'));
        $resIndex->assertStatus(200);

        // Tombol mutasi tidak boleh muncul untuk user Ranting
        $resIndex->assertDontSee('Buka Periode Penthong Baru');
        $resIndex->assertDontSee('Tutup & Kunci Penthong');
    }

    /**
     * SPESIFIKASI 2: RULES EDIT & HAPUS SETORAN TINGKAT RANTING / MWC (LOCKING MECHANISM)
     */
    public function test_ranting_user_can_edit_and_delete_transaction_when_periode_is_open(): void
    {
        // Pastikan ada penthong berstatus AKTIF (Open)
        Penthong::query()->update(['status' => Penthong::STATUS_TUTUP, 'is_active' => false]);
        $openPenthong = Penthong::create([
            'nama_periode' => 'Penthong Terbuka Aktif',
            'bulan'        => 10,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_AKTIF,
            'is_active'    => true,
        ]);

        $tx = KoinTransaction::create([
            'penthong_id'        => $openPenthong->id,
            'periode_pentong_id' => $openPenthong->id,
            'relawan_id'         => $this->relawan->id,
            'ranting_id'         => $this->userRanting->ranting_id,
            'mwc_id'             => $this->userRanting->ranting->mwc_id,
            'user_id'            => $this->userRanting->id,
            'amount'             => 100000,
            'collection_date'    => now()->toDateString(),
            'status'             => 'confirmed',
        ]);

        $this->actingAs($this->userRanting);

        // 1. Edit view accessible
        $resEdit = $this->get(route('transactions.edit', $tx->id));
        $resEdit->assertStatus(200);
        $resEdit->assertSee('Formulir Edit Transaksi');

        // 2. Update via Web
        $resUpdate = $this->put(route('transactions.update', $tx->id), [
            'amount'          => 250000,
            'collection_date' => now()->toDateString(),
            'notes'           => 'Revisi nominal setoran dari relawan',
        ]);
        $resUpdate->assertRedirect(route('transactions.show', $tx->id));
        $this->assertEquals(250000, $tx->fresh()->amount);

        // 3. Delete via Web
        $resDelete = $this->delete(route('transactions.destroy', $tx->id));
        $resDelete->assertRedirect(route('transactions.index'));
        $this->assertDatabaseMissing('koin_transactions', ['id' => $tx->id]);
    }

    public function test_transaction_is_locked_when_periode_pentong_is_closed(): void
    {
        // Buat penthong tertutup (Closed)
        $closedPenthong = Penthong::create([
            'nama_periode' => 'Penthong Resmi Ditutup',
            'bulan'        => 9,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_TUTUP,
            'is_active'    => false,
        ]);

        $tx = KoinTransaction::create([
            'penthong_id'        => $closedPenthong->id,
            'periode_pentong_id' => $closedPenthong->id,
            'relawan_id'         => $this->relawan->id,
            'ranting_id'         => $this->userRanting->ranting_id,
            'mwc_id'             => $this->userRanting->ranting->mwc_id,
            'user_id'            => $this->userRanting->id,
            'amount'             => 150000,
            'collection_date'    => now()->subDays(10)->toDateString(),
            'status'             => 'confirmed',
        ]);

        $this->actingAs($this->userRanting);

        // 1. Frontend: Pada tabel riwayat setoran, muncul tooltip penguncian
        $resIndex = $this->get(route('transactions.index', ['periode_id' => $closedPenthong->id]));
        $resIndex->assertStatus(200);
        $resIndex->assertSee('Transaksi terkunci karena periode pentong telah ditutup.');

        // 2. Edit route dialihkan dengan error message
        $resEdit = $this->get(route('transactions.edit', $tx->id));
        $resEdit->assertRedirect(route('transactions.index'));
        $resEdit->assertSessionHas('error', 'Data transaksi tidak dapat diubah atau dihapus karena periode pentong telah resmi ditutup oleh Admin PC.');

        // 3. Update Web DITOLAK
        $resUpdate = $this->from(route('transactions.index'))->put(route('transactions.update', $tx->id), [
            'amount'          => 300000,
            'collection_date' => now()->toDateString(),
        ]);
        $resUpdate->assertRedirect(route('transactions.index'));
        $resUpdate->assertSessionHas('error', 'Data transaksi tidak dapat diubah atau dihapus karena periode pentong telah resmi ditutup oleh Admin PC.');
        $this->assertEquals(150000, $tx->fresh()->amount);

        // 4. Update API DITOLAK HTTP 422 JSON
        $resApiUpdate = $this->putJson("/api/setoran/{$tx->id}", [
            'amount'          => 400000,
            'collection_date' => now()->toDateString(),
        ]);
        $resApiUpdate->assertStatus(422);
        $resApiUpdate->assertJson([
            'success' => false,
            'message' => 'Data transaksi tidak dapat diubah atau dihapus karena periode pentong telah resmi ditutup oleh Admin PC.',
        ]);

        // 5. Delete Web DITOLAK
        $resDelete = $this->from(route('transactions.index'))->delete(route('transactions.destroy', $tx->id));
        $resDelete->assertRedirect(route('transactions.index'));
        $resDelete->assertSessionHas('error', 'Data transaksi tidak dapat diubah atau dihapus karena periode pentong telah resmi ditutup oleh Admin PC.');
        $this->assertDatabaseHas('koin_transactions', ['id' => $tx->id]);

        // 6. Delete API DITOLAK HTTP 422 JSON
        $resApiDelete = $this->deleteJson("/api/setoran/{$tx->id}");
        $resApiDelete->assertStatus(422);
        $resApiDelete->assertJson([
            'success' => false,
            'message' => 'Data transaksi tidak dapat diubah atau dihapus karena periode pentong telah resmi ditutup oleh Admin PC.',
        ]);
        $this->assertDatabaseHas('koin_transactions', ['id' => $tx->id]);
    }

    /**
     * SPESIFIKASI 3: MODUL CETAK LAPORAN (EXPORT EXCEL & PDF)
     */
    public function test_export_pdf_and_excel_reports(): void
    {
        $penthong = Penthong::create([
            'nama_periode' => 'Penthong Laporan Resmi',
            'bulan'        => 10,
            'tahun'        => 2026,
            'status'       => Penthong::STATUS_AKTIF,
            'is_active'    => true,
        ]);

        KoinTransaction::create([
            'penthong_id'        => $penthong->id,
            'periode_pentong_id' => $penthong->id,
            'relawan_id'         => $this->relawan->id,
            'ranting_id'         => $this->userRanting->ranting_id,
            'mwc_id'             => $this->userRanting->ranting->mwc_id,
            'amount'             => 500000,
            'collection_date'    => now()->toDateString(),
            'status'             => 'confirmed',
        ]);

        $this->actingAs($this->userPc);

        // 1. Export PDF / Print View
        $resPdf = $this->get(route('penthongs.export.pdf', $penthong->id));
        $resPdf->assertStatus(200);

        // Verifikasi Kop Surat Resmi
        $resPdf->assertSee('PENGURUS CABANG NAHDLATUL ULAMA LAMONGAN');
        $resPdf->assertSee('NU CARE - LAZISNU');
        $resPdf->assertSee('Jl. Kyai Amin No. 9 Lamongan 62213');
        $resPdf->assertSee('Hotline: 0851 8899 1926');

        // Verifikasi Judul & Metadata Laporan
        $resPdf->assertSee('LAPORAN PEROLEHAN PENTONG KOIN NU');
        $resPdf->assertSee('Penthong Laporan Resmi');

        // Verifikasi Struktur Tabel & Total Row
        $resPdf->assertSee('Tanggal Setoran');
        $resPdf->assertSee('Nama Ranting / MWC');
        $resPdf->assertSee('Nama Munfiq / Koordinator');
        $resPdf->assertSee('No. Kaleng / Identitas');
        $resPdf->assertSee('Nominal Setoran');
        $resPdf->assertSee('TOTAL AKUMULASI PENERIMAAN DANA (RP)');
        $resPdf->assertSee('500.000');

        // Verifikasi Lembar Pengesahan Dua Kolom
        $resPdf->assertSee('Mengetahui / Verifikator,');
        $resPdf->assertSee('Direktur Eksekutif / Admin Cabang');
        $resPdf->assertSee('Pimpinan PC LAZISNU Lamongan (Ketua)');

        // 2. Export Excel (.xlsx)
        $resExcel = $this->get(route('penthongs.export.excel', $penthong->id));
        $resExcel->assertStatus(200);
        $resExcel->assertHeader('content-type', 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
    }
}
