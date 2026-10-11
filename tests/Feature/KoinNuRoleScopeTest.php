<?php

namespace Tests\Feature;

use App\Models\Donator;
use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class KoinNuRoleScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_pc_user_can_view_all_data(): void
    {
        $userPc = User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();
        $this->actingAs($userPc);

        // 1. Dashboard PC
        $response = $this->get('/dashboard');
        $response->assertStatus(200);
        $response->assertSee('Dashboard Pimpinan Cabang');
        $response->assertSee('Grafik Performa Komparasi 15 Majelis Wakil Cabang');

        // 2. Master MWC
        $mwcResponse = $this->get('/mwcs');
        $mwcResponse->assertStatus(200);
        $mwcResponse->assertSee('MWC NU Brondong');
        $mwcResponse->assertSee('MWCNU Paciran');

        // 3. Master Ranting
        $rantingResponse = $this->get('/rantings');
        $rantingResponse->assertStatus(200);
        $rantingResponse->assertSee('M01R01');

        // 4. Relawans
        $relawanResponse = $this->get('/relawans');
        $relawanResponse->assertStatus(200);

        // 5. Transactions
        $txResponse = $this->get('/transactions');
        $txResponse->assertStatus(200);

        // 6. Users
        $usersResponse = $this->get('/users');
        $usersResponse->assertStatus(200);
        $usersResponse->assertSee('adminpc');
        $usersResponse->assertSee('M01');
        $usersResponse->assertSee('M01R01');
    }

    public function test_mwc_user_can_only_see_rantings_in_their_territory(): void
    {
        // Login sebagai Admin MWC Brondong (M01)
        $userMwc1 = User::where('username', 'M01')->first();
        $this->actingAs($userMwc1);

        $mwc1 = Mwc::where('code', 'M01')->first();
        $mwc2 = Mwc::where('code', 'M02')->first();

        $ownRanting = Ranting::where('mwc_id', $mwc1->id)->first();
        $otherRanting = Ranting::where('mwc_id', $mwc2->id)->first();

        // 1. Dashboard MWC
        $dashResponse = $this->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee("Dashboard MWC NU {$mwc1->name}");
        $dashResponse->assertSee("Grafik Performa Komparasi Ranting di Wilayah MWC {$mwc1->name}");

        // 2. Daftar Ranting: Hanya melihat ranting di M01, tidak boleh melihat ranting di M02
        $rantingResponse = $this->get('/rantings');
        $rantingResponse->assertStatus(200);
        $rantingResponse->assertSee($ownRanting->name);
        $rantingResponse->assertDontSee($otherRanting->name);

        // 3. Detail Ranting: Dapat melihat ranting sendiri, DILARANG melihat ranting MWC lain (403)
        $ownShowResponse = $this->get("/rantings/{$ownRanting->id}");
        $ownShowResponse->assertStatus(200);

        $forbiddenShow = $this->get("/rantings/{$otherRanting->id}");
        $forbiddenShow->assertStatus(403);

        // 4. Edit Ranting: Dilarang mengedit ranting dari MWC lain (403)
        $forbiddenEdit = $this->get("/rantings/{$otherRanting->id}/edit");
        $forbiddenEdit->assertStatus(403);

        $forbiddenUpdate = $this->put("/rantings/{$otherRanting->id}", [
            'mwc_id' => $mwc2->id,
            'code'   => $otherRanting->code,
            'name'   => 'Hacked Ranting Name',
        ]);
        $forbiddenUpdate->assertStatus(403);
    }

    public function test_mwc_user_cannot_see_or_manage_relawans_of_other_mwcs(): void
    {
        $userMwc1 = User::where('username', 'M01')->first();
        $this->actingAs($userMwc1);

        $mwc1 = Mwc::where('code', 'M01')->first();
        $mwc2 = Mwc::where('code', 'M02')->first();

        $rantingMwc1 = Ranting::where('mwc_id', $mwc1->id)->first();
        $rantingMwc2 = Ranting::where('mwc_id', $mwc2->id)->first();

        // Buat relawan di masing-masing ranting
        $ownRelawan = Relawan::create([
            'ranting_id' => $rantingMwc1->id,
            'nama'       => 'Relawan Wilayah Sendiri M01',
            'nik'        => '3524018800000001',
            'no_hp'      => '081200000001',
            'area_tugas' => 'RT 01 / RW 01',
            'status'     => Relawan::STATUS_ACTIVE,
        ]);

        $otherRelawan = Relawan::create([
            'ranting_id' => $rantingMwc2->id,
            'nama'       => 'Relawan Wilayah Lain M02',
            'nik'        => '3524018800000002',
            'no_hp'      => '081200000002',
            'area_tugas' => 'RT 02 / RW 02',
            'status'     => Relawan::STATUS_ACTIVE,
        ]);

        // 1. Index relawan: Hanya tampil relawan di M01
        $response = $this->get('/relawans');
        $response->assertStatus(200);
        $response->assertSee('Relawan Wilayah Sendiri M01');
        $response->assertDontSee('Relawan Wilayah Lain M02');

        // 2. Edit relawan MWC lain: Ditolak (403)
        $editResponse = $this->get("/relawans/{$otherRelawan->id}/edit");
        $editResponse->assertStatus(403);

        // 3. Hapus relawan MWC lain: Ditolak (403)
        $deleteResponse = $this->delete("/relawans/{$otherRelawan->id}");
        $deleteResponse->assertStatus(403);
    }

    public function test_ranting_user_can_only_control_relawans_in_their_own_ranting(): void
    {
        // Login sebagai Admin Ranting Brengkok (M01R01)
        $userRanting = User::where('username', 'M01R01')->first();
        $this->actingAs($userRanting);

        $otherRanting = Ranting::where('id', '!=', $userRanting->ranting_id)->first();

        // 1. Dashboard Ranting
        $dashResponse = $this->get('/dashboard');
        $dashResponse->assertStatus(200);
        $dashResponse->assertSee("Dashboard Ranting NU {$userRanting->ranting->name}");

        // 2. Master Ranting: Hanya melihat rantingnya sendiri
        $rantingResponse = $this->get('/rantings');
        $rantingResponse->assertStatus(200);
        $rantingResponse->assertSee($userRanting->ranting->name);
        $rantingResponse->assertDontSee($otherRanting->name);

        // Dilarang melihat ranting lain
        $forbiddenShow = $this->get("/rantings/{$otherRanting->id}");
        $forbiddenShow->assertStatus(403);

        // Dilarang menambah ranting baru
        $forbiddenCreate = $this->get('/rantings/create');
        $forbiddenCreate->assertStatus(403);

        // 3. Mengontrol Relawan di Wilayah Sendiri
        // A. Tambah Relawan di wilayahnya
        $storeRelawanResponse = $this->post('/relawans', [
            'nama'       => 'Relawan Sah Brengkok',
            'nik'        => '3524017700000001',
            'no_hp'      => '081277770001',
            'area_tugas' => 'RT 05 / RW 02',
        ]);
        $storeRelawanResponse->assertRedirect('/relawans');
        $this->assertDatabaseHas('relawans', [
            'nik'        => '3524017700000001',
            'ranting_id' => $userRanting->ranting_id,
        ]);

        $createdRelawan = Relawan::where('nik', '3524017700000001')->first();

        // B. Edit & Perbarui Relawan di wilayahnya -> BERHASIL
        $editFormResponse = $this->get("/relawans/{$createdRelawan->id}/edit");
        $editFormResponse->assertStatus(200);
        $editFormResponse->assertSee('Relawan Sah Brengkok');

        $updateResponse = $this->put("/relawans/{$createdRelawan->id}", [
            'nama'       => 'Relawan Sah Brengkok Diperbarui',
            'nik'        => '3524017700000001',
            'no_hp'      => '081277779999',
            'area_tugas' => 'RT 05 / RW 02 Barat',
            'status'     => 'active',
        ]);
        $updateResponse->assertRedirect('/relawans');
        $this->assertDatabaseHas('relawans', [
            'id'   => $createdRelawan->id,
            'nama' => 'Relawan Sah Brengkok Diperbarui',
        ]);

        // C. Hapus Relawan di wilayahnya -> BERHASIL
        $deleteResponse = $this->delete("/relawans/{$createdRelawan->id}");
        $deleteResponse->assertRedirect('/relawans');
        $this->assertDatabaseMissing('relawans', [
            'id' => $createdRelawan->id,
        ]);

        // 4. DILARANG Mengontrol Relawan dari Ranting Lain (403)
        $foreignRelawan = Relawan::create([
            'ranting_id' => $otherRanting->id,
            'nama'       => 'Relawan Ranting Sebelah',
            'nik'        => '3524017700000002',
            'no_hp'      => '081277770002',
            'area_tugas' => 'RT 01 / RW 01',
            'status'     => Relawan::STATUS_ACTIVE,
        ]);

        // Tidak boleh mengedit relawan ranting lain
        $forbiddenEditRelawan = $this->get("/relawans/{$foreignRelawan->id}/edit");
        $forbiddenEditRelawan->assertStatus(403);

        $forbiddenUpdateRelawan = $this->put("/relawans/{$foreignRelawan->id}", [
            'nama'       => 'Hacked Relawan',
            'nik'        => '3524017700000002',
            'area_tugas' => 'RT 01 / RW 01',
            'status'     => 'active',
        ]);
        $forbiddenUpdateRelawan->assertStatus(403);

        // Tidak boleh menghapus relawan ranting lain
        $forbiddenDeleteRelawan = $this->delete("/relawans/{$foreignRelawan->id}");
        $forbiddenDeleteRelawan->assertStatus(403);
    }

    public function test_user_management_scoped_by_roles(): void
    {
        // 1. MWC hanya melihat user MWC-nya dan Ranting di bawahnya
        $userMwc1 = User::where('username', 'M01')->first();
        $this->actingAs($userMwc1);

        $responseMwc = $this->get('/users');
        $responseMwc->assertStatus(200);
        $responseMwc->assertSee('M01');
        $responseMwc->assertSee('M01R01');
        $responseMwc->assertDontSee('M02');
        $responseMwc->assertDontSee('Super Admin PC');

        // MWC dilarang mengedit akun PC
        $userPc = User::where('username', 'adminpc')->first();
        $forbiddenEditPc = $this->get("/users/{$userPc->id}/edit");
        $forbiddenEditPc->assertStatus(403);

        // MWC dilarang mengedit akun MWC lain
        $userMwc2 = User::where('username', 'M02')->first();
        $forbiddenEditMwc2 = $this->get("/users/{$userMwc2->id}/edit");
        $forbiddenEditMwc2->assertStatus(403);

        // 2. Ranting hanya melihat akunnya sendiri
        $userRanting = User::where('username', 'M01R01')->first();
        $this->actingAs($userRanting);

        $responseRanting = $this->get('/users');
        $responseRanting->assertStatus(200);
        $responseRanting->assertSee('M01R01');
        $responseRanting->assertDontSee('M01R02');
        $responseRanting->assertDontSee('Petugas MWCNU Brondong');

        // Ranting dilarang mengedit akun lain
        $otherUserRanting = User::where('username', 'M01R02')->first();
        $forbiddenEditOther = $this->get("/users/{$otherUserRanting->id}/edit");
        $forbiddenEditOther->assertStatus(403);
    }

    public function test_pc_user_can_view_all_donators_and_distribution(): void
    {
        $userPc = User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();
        $this->actingAs($userPc);

        // 1. Index Donatur
        $response = $this->get('/donators');
        $response->assertStatus(200);
        $response->assertSee('Data Donatur & Penerima Kotak Infaq');
        $response->assertSee('Total Penerima Kotak');
        $response->assertSee('Pembagian Petugas');

        // 2. Halaman Pembagian Petugas Wilayah
        $distResponse = $this->get('/donators/distribution');
        $distResponse->assertStatus(200);
        $distResponse->assertSee('Pembagian Petugas di Wilayah Masing-Masing');
        $distResponse->assertSee('Daftar Petugas Relawan');
        $distResponse->assertSee('Alokasi Kotak Binaan');
    }

    public function test_mwc_user_can_only_see_donators_in_their_territory(): void
    {
        $userMwc1 = User::where('username', 'M01')->first();
        $this->actingAs($userMwc1);

        $mwc1 = Mwc::where('code', 'M01')->first();
        $mwc2 = Mwc::where('code', 'M02')->first();

        $rantingMwc1 = Ranting::where('mwc_id', $mwc1->id)->first();
        $rantingMwc2 = Ranting::where('mwc_id', $mwc2->id)->first();

        $donatorMwc1 = Donator::where('ranting_id', $rantingMwc1->id)->first();
        $donatorMwc2 = Donator::where('ranting_id', $rantingMwc2->id)->first();

        // 1. Index: Melihat donatur wilayah sendiri jika dicari
        $response = $this->get('/donators?search=' . $donatorMwc1->box_number);
        $response->assertStatus(200);
        $response->assertSee($donatorMwc1->box_number);

        // Donatur dari MWC lain TIDAK BOLEH tampil walau dicari dengan box_number yang tepat
        if ($donatorMwc2) {
            $responseOther = $this->get('/donators?search=' . $donatorMwc2->box_number);
            $responseOther->assertStatus(200);
            $responseOther->assertSee('Menampilkan 0 data kotak');
            $responseOther->assertDontSee($donatorMwc2->name);
        }

        // 2. Dilarang melihat detail donatur dari MWC lain (403)
        if ($donatorMwc2) {
            $forbiddenShow = $this->get("/donators/{$donatorMwc2->id}");
            $forbiddenShow->assertStatus(403);

            $forbiddenEdit = $this->get("/donators/{$donatorMwc2->id}/edit");
            $forbiddenEdit->assertStatus(403);
        }
    }

    public function test_ranting_user_can_manage_donators_and_officer_assignment_in_their_own_wilayah(): void
    {
        $userRanting = User::where('username', 'M01R01')->first();
        $this->actingAs($userRanting);

        $ownRanting = $userRanting->ranting;
        $otherRanting = Ranting::where('id', '!=', $ownRanting->id)->first();

        $ownRelawan = Relawan::where('ranting_id', $ownRanting->id)->first();
        $otherRelawan = Relawan::where('ranting_id', $otherRanting->id)->first();

        // 1. Tambah donatur di ranting sendiri
        $storeResponse = $this->post('/donators', [
            'box_number'  => 'KLG-TEST-M01R01-999',
            'name'        => 'Donatur Uji Brengkok',
            'nik'         => '3524016600000001',
            'phone'       => '081299990001',
            'ranting_id'  => $ownRanting->id,
            'relawan_id'  => $ownRelawan->id,
            'address'     => 'Jl. Uji No. 12',
            'rt'          => '01',
            'rw'          => '01',
            'status'      => 'active',
            'joined_date' => now()->toDateString(),
        ]);
        $storeResponse->assertRedirect('/donators');
        $this->assertDatabaseHas('donators', [
            'box_number' => 'KLG-TEST-M01R01-999',
            'ranting_id' => $ownRanting->id,
            'relawan_id' => $ownRelawan->id,
        ]);

        $createdDonator = Donator::where('box_number', 'KLG-TEST-M01R01-999')->first();

        // 2. Edit & update donatur sendiri -> Berhasil
        $editResponse = $this->get("/donators/{$createdDonator->id}/edit");
        $editResponse->assertStatus(200);

        $updateResponse = $this->put("/donators/{$createdDonator->id}", [
            'box_number'  => 'KLG-TEST-M01R01-999',
            'name'        => 'Donatur Uji Brengkok Update',
            'phone'       => '081299999999',
            'address'     => 'Jl. Uji No. 12 Baru',
            'status'      => 'active',
            'relawan_id'  => $ownRelawan->id,
        ]);
        $updateResponse->assertRedirect('/donators');
        $this->assertDatabaseHas('donators', [
            'id'   => $createdDonator->id,
            'name' => 'Donatur Uji Brengkok Update',
        ]);

        // 3. Quick reassign petugas ke null atau petugas lain di ranting yang sama
        $assignResponse = $this->post("/donators/{$createdDonator->id}/assign", [
            'relawan_id' => null,
        ]);
        $assignResponse->assertRedirect();
        $this->assertDatabaseHas('donators', [
            'id'         => $createdDonator->id,
            'relawan_id' => null,
        ]);

        // 4. DILARANG menugaskan relawan dari ranting lain
        $forbiddenAssign = $this->post("/donators/{$createdDonator->id}/assign", [
            'relawan_id' => $otherRelawan->id,
        ]);
        // Harus kembali dengan pesan error karena relawan beda ranting
        $this->assertDatabaseMissing('donators', [
            'id'         => $createdDonator->id,
            'relawan_id' => $otherRelawan->id,
        ]);

        // 5. DILARANG mengakses atau mengedit donatur ranting lain
        $foreignDonator = Donator::where('ranting_id', $otherRanting->id)->first();
        if ($foreignDonator) {
            $forbiddenShow = $this->get("/donators/{$foreignDonator->id}");
            $forbiddenShow->assertStatus(403);

            $forbiddenEdit = $this->get("/donators/{$foreignDonator->id}/edit");
            $forbiddenEdit->assertStatus(403);

            $forbiddenDelete = $this->delete("/donators/{$foreignDonator->id}");
            $forbiddenDelete->assertStatus(403);
        }
    }

    public function test_box_number_generator_formula_and_endpoint(): void
    {
        $ranting = Ranting::first();
        $this->assertNotNull($ranting);

        // 1. Model generator test
        $generatedCode = Donator::generateBoxNumber($ranting->id, '1', '2');
        // Harus berformat KLG-{KODE_RANTING}-0102-xxx (Contoh: KLG-M01R10-0102-005)
        $expectedPrefix = "KLG-{$ranting->code}-0102-";
        $this->assertStringStartsWith($expectedPrefix, $generatedCode);

        // 2. AJAX Endpoint test
        $userRanting = User::where('ranting_id', $ranting->id)->first();
        $this->actingAs($userRanting);

        $response = $this->getJson("/donators/get-next-box-number?ranting_id={$ranting->id}&rt=1&rw=2");
        $response->assertStatus(200);
        $response->assertJsonStructure(['box_number', 'status']);
        $this->assertStringStartsWith($expectedPrefix, $response->json('box_number'));
    }

    public function test_donator_bulk_import_via_csv_with_auto_generated_box_number(): void
    {
        $userRanting = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();
        $this->actingAs($userRanting);

        $ranting = $userRanting->ranting;
        $relawan = Relawan::where('ranting_id', $ranting->id)->first();

        // 1. Download Template CSV
        $templateResponse = $this->get('/donators/template');
        $templateResponse->assertStatus(200);
        $templateResponse->assertHeader('Content-Disposition', 'attachment; filename="template_import_donatur_koin_nu.csv"');

        // 2. Bulk import CSV (campuran: kosong di-auto-generate, dan angka urut diformat standar KLG-kode-RTRW-seq)
        $csvContent = "nama,nik,phone,address,rt,rw,box_number,petugas,notes\n"
                    . "Donatur CSV Satu,3524018800000101,081211110001,Dusun Krajan Timur,01,02,,{$relawan->nama},Kotak import satu\n"
                    . "Donatur CSV Dua,3524018800000102,081211110002,Dusun Krajan Barat,01,02,,,Kotak import dua\n"
                    . "Donatur CSV Tiga,3524018800000103,081211110003,Dusun Krajan Tengah,01,02,003,,Kotak import tiga dengan urut 003\n";

        $file = UploadedFile::fake()->createWithContent('donators.csv', $csvContent);

        $response = $this->post('/donators/import', [
            'file' => $file,
        ]);

        $response->assertRedirect('/donators?ranting_id=' . $ranting->id);

        $this->assertDatabaseHas('donators', [
            'name'       => 'Donatur CSV Satu',
            'ranting_id' => $ranting->id,
            'relawan_id' => $relawan->id,
            'rt'         => '01',
            'rw'         => '02',
        ]);

        $this->assertDatabaseHas('donators', [
            'name'       => 'Donatur CSV Dua',
            'ranting_id' => $ranting->id,
            'rt'         => '01',
            'rw'         => '02',
        ]);

        $this->assertDatabaseHas('donators', [
            'name'       => 'Donatur CSV Tiga',
            'ranting_id' => $ranting->id,
            'rt'         => '01',
            'rw'         => '02',
        ]);

        // Pastikan box_number terisi otomatis dengan pola standar KLG-{kodeMWCkoderanting}-0102-xxx
        $imported1 = Donator::where('name', 'Donatur CSV Satu')->first();
        $this->assertStringStartsWith("KLG-{$ranting->code}-0102-", $imported1->box_number);

        // Pastikan baris dengan nomor urut 003 menghasilkan format persis KLG-kodeMWCkoderanting-0102-003
        $imported3 = Donator::where('name', 'Donatur CSV Tiga')->first();
        $this->assertEquals("KLG-{$ranting->code}-0102-003", $imported3->box_number);
    }

    public function test_pc_can_toggle_ranting_program_status_and_control_access(): void
    {
        $userPc = User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();
        $userMwc = User::whereHas('role', fn($q) => $q->where('name', 'mwc'))->first();

        $ranting = Ranting::first();
        $this->assertTrue($ranting->is_active);

        // 1. MWC dilarang mengubah status aktivasi ranting (403)
        $this->actingAs($userMwc);
        $forbiddenToggle = $this->post("/rantings/{$ranting->id}/toggle-status");
        $forbiddenToggle->assertStatus(403);

        // 2. PC Admin sukses menonaktifkan program pada Ranting
        $this->actingAs($userPc);
        $toggleResponse = $this->post("/rantings/{$ranting->id}/toggle-status");
        $toggleResponse->assertRedirect();

        $ranting->refresh();
        $this->assertFalse($ranting->is_active);

        // 3. Ketika Ranting nonaktif, pendaftaran donatur baru pada ranting tersebut ditolak
        $userRanting = User::where('ranting_id', $ranting->id)->first();
        $this->actingAs($userRanting);

        $blockedStore = $this->post('/donators', [
            'name'        => 'Donatur Gagal Karena Nonaktif',
            'address'     => 'Jl. Balai Desa',
            'ranting_id'  => $ranting->id,
            'status'      => 'active',
        ]);

        // Harus gagal/kembali dengan pesan error status program
        $this->assertDatabaseMissing('donators', [
            'name' => 'Donatur Gagal Karena Nonaktif',
        ]);

        // 4. PC Admin mengaktifkan kembali Ranting
        $this->actingAs($userPc);
        $reactivateResponse = $this->post("/rantings/{$ranting->id}/toggle-status");
        $reactivateResponse->assertRedirect();

        $ranting->refresh();
        $this->assertTrue($ranting->is_active);
    }
}
