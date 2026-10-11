<?php

namespace Tests\Feature;

use App\Models\Mustahiq;
use App\Models\Mwc;
use App\Models\Pentasyarufan;
use App\Models\Penthong;
use App\Models\PilarProgram;
use App\Models\ProgramKerja;
use App\Models\Ranting;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class MustahiqManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_data_scoping_and_multi_tenancy_for_mustahiq(): void
    {
        $roleRanting = Role::where('name', 'ranting')->first();
        $roleMwc = Role::where('name', 'mwc')->first();
        $rolePc = Role::where('name', 'pc')->first();

        $mwcs = Mwc::take(2)->get();
        $mwc1 = $mwcs[0];
        $mwc2 = $mwcs[1];

        $ranting1 = Ranting::where('mwc_id', $mwc1->id)->first();
        $ranting2 = Ranting::where('mwc_id', $mwc1->id)->where('id', '!=', $ranting1->id)->first();
        $ranting3 = Ranting::where('mwc_id', $mwc2->id)->first();

        // Buat data mustahiq di masing-masing ranting
        $mustahiq1 = Mustahiq::create([
            'nik' => '3524010101010001',
            'nama' => 'Mustahiq Ranting 1',
            'jenis_kelamin' => 'L',
            'alamat' => 'Dusun 1, RT 01/RW 01',
            'ranting_id' => $ranting1->id,
            'mwc_id' => $mwc1->id,
            'asnaf' => 'fakir',
            'status' => 'aktif',
        ]);

        $mustahiq2 = Mustahiq::create([
            'nik' => '3524010101010002',
            'nama' => 'Mustahiq Ranting 2',
            'jenis_kelamin' => 'P',
            'alamat' => 'Dusun 2, RT 02/RW 02',
            'ranting_id' => $ranting2->id,
            'mwc_id' => $mwc1->id,
            'asnaf' => 'miskin',
            'status' => 'aktif',
        ]);

        $mustahiq3 = Mustahiq::create([
            'nik' => '3524020202020003',
            'nama' => 'Mustahiq Ranting 3',
            'jenis_kelamin' => 'L',
            'alamat' => 'Dusun 3, RT 03/RW 03',
            'ranting_id' => $ranting3->id,
            'mwc_id' => $mwc2->id,
            'asnaf' => 'gharim',
            'status' => 'aktif',
        ]);

        // 1. User Ranting 1 hanya boleh melihat data Mustahiq 1
        $userRanting1 = User::factory()->create([
            'role_id' => $roleRanting->id,
            'ranting_id' => $ranting1->id,
            'mwc_id' => $mwc1->id,
        ]);

        $scopedRanting1 = Mustahiq::forUser($userRanting1)->pluck('id')->all();
        $this->assertContains($mustahiq1->id, $scopedRanting1);
        $this->assertNotContains($mustahiq2->id, $scopedRanting1);
        $this->assertNotContains($mustahiq3->id, $scopedRanting1);

        // 2. User MWC 1 bisa melihat Mustahiq 1 & Mustahiq 2 (dalam 1 kecamatan), tetapi tidak bisa melihat Mustahiq 3
        $userMwc1 = User::factory()->create([
            'role_id' => $roleMwc->id,
            'mwc_id' => $mwc1->id,
        ]);

        $scopedMwc1 = Mustahiq::forUser($userMwc1)->pluck('id')->all();
        $this->assertContains($mustahiq1->id, $scopedMwc1);
        $this->assertContains($mustahiq2->id, $scopedMwc1);
        $this->assertNotContains($mustahiq3->id, $scopedMwc1);

        // 3. User PC / Cabang bisa melihat seluruh mustahiq se-kabupaten
        $userPc = User::factory()->create([
            'role_id' => $rolePc->id,
        ]);

        $scopedPc = Mustahiq::forUser($userPc)->pluck('id')->all();
        $this->assertContains($mustahiq1->id, $scopedPc);
        $this->assertContains($mustahiq2->id, $scopedPc);
        $this->assertContains($mustahiq3->id, $scopedPc);
    }

    public function test_select2_autocomplete_search_endpoint(): void
    {
        $roleRanting = Role::where('name', 'ranting')->first();
        $ranting = Ranting::first();

        $mustahiqAktif = Mustahiq::create([
            'nik' => '3524111111110001',
            'nama' => 'Bapak Joko Santoso',
            'jenis_kelamin' => 'L',
            'alamat' => 'RT 01 RW 02 Dusun Timur',
            'ranting_id' => $ranting->id,
            'mwc_id' => $ranting->mwc_id,
            'asnaf' => 'fakir',
            'status' => 'aktif',
        ]);

        $mustahiqNonAktif = Mustahiq::create([
            'nik' => '3524111111110002',
            'nama' => 'Ibu Siti Aminah',
            'jenis_kelamin' => 'P',
            'alamat' => 'RT 03 RW 01 Dusun Barat',
            'ranting_id' => $ranting->id,
            'mwc_id' => $ranting->mwc_id,
            'asnaf' => 'miskin',
            'status' => 'tidak_aktif',
        ]);

        $userRanting = User::factory()->create([
            'role_id' => $roleRanting->id,
            'ranting_id' => $ranting->id,
            'mwc_id' => $ranting->mwc_id,
        ]);

        // Panggil endpoint autocomplete /mustahiqs/search
        $response = $this->actingAs($userRanting)
            ->getJson(route('mustahiqs.search', ['q' => 'Santoso']));

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'results' => [
                '*' => [
                    'id',
                    'text',
                    'nama',
                    'nik',
                    'asnaf',
                    'asnaf_label',
                    'alamat',
                    'ranting_id',
                ]
            ],
            'pagination' => ['more'],
        ]);

        // Cek bahwa hanya yang aktif dan sesuai query yang muncul
        $results = $response->json('results');
        $this->assertCount(1, $results);
        $this->assertEquals($mustahiqAktif->id, $results[0]['id']);
        $this->assertEquals('Bapak Joko Santoso', $results[0]['nama']);

        // Data non-aktif tidak boleh muncul dalam pencarian penyaluran
        $responseNonAktif = $this->actingAs($userRanting)
            ->getJson(route('mustahiqs.search', ['q' => 'Aminah']));
        $this->assertEmpty($responseNonAktif->json('results'));
    }

    public function test_photo_privacy_and_unauthorized_access_protection(): void
    {
        Storage::fake('local');

        $roleRanting = Role::where('name', 'ranting')->first();
        $rantings = Ranting::take(2)->get();
        $rantingA = $rantings[0];
        $rantingB = $rantings[1];

        // Buat file foto kondisi privat
        $fakePhotoPath = 'mustahiqs/foto/sample_kondisi_rumah.jpg';
        Storage::disk('local')->put($fakePhotoPath, 'fake-image-content');

        $mustahiqA = Mustahiq::create([
            'nik' => '3524999999990001',
            'nama' => 'Mustahiq Ranting A',
            'jenis_kelamin' => 'L',
            'alamat' => 'Dusun Krajan',
            'ranting_id' => $rantingA->id,
            'mwc_id' => $rantingA->mwc_id,
            'asnaf' => 'fakir',
            'status' => 'aktif',
            'foto_kondisi' => $fakePhotoPath,
        ]);

        // 1. Guest (tidak login) mengakses URL foto harus diarahkan login atau 401/403
        $guestRes = $this->get(route('mustahiqs.foto', $mustahiqA->id));
        $guestRes->assertRedirect(route('login'));

        // 2. User Ranting B (bukan ranting pemilik) mencoba mengakses foto -> 403 Forbidden
        $userRantingB = User::factory()->create([
            'role_id' => $roleRanting->id,
            'ranting_id' => $rantingB->id,
            'mwc_id' => $rantingB->mwc_id,
        ]);

        $resUnauthorized = $this->actingAs($userRantingB)->get(route('mustahiqs.foto', $mustahiqA->id));
        $resUnauthorized->assertStatus(403);

        // 3. User Ranting A (pemilik wilayah) -> Diizinkan mengunduh/melihat stream foto
        $userRantingA = User::factory()->create([
            'role_id' => $roleRanting->id,
            'ranting_id' => $rantingA->id,
            'mwc_id' => $rantingA->mwc_id,
        ]);

        $resAuthorized = $this->actingAs($userRantingA)->get(route('mustahiqs.foto', $mustahiqA->id));
        $resAuthorized->assertStatus(200);
    }

    public function test_pentasyarufan_and_mustahiq_pivot_history_tracking(): void
    {
        $ranting = Ranting::first();
        $penthong = Penthong::first();
        $pilar = PilarProgram::first();
        $user = User::first();

        $proker = ProgramKerja::create([
            'nomor_proker' => 'PRK-TEST-001',
            'nama_kegiatan' => 'Santunan Dhuafa Rutin',
            'tingkat' => 'ranting',
            'ranting_id' => $ranting->id,
            'mwc_id' => $ranting->mwc_id,
            'pilar_program_id' => $pilar->id,
            'tahun_anggaran' => 2026,
            'status' => 'disetujui',
            'rencana_anggaran' => 10000000,
            'realisasi_anggaran' => 1000000,
            'sisa_anggaran' => 9000000,
            'user_id' => $user->id,
        ]);

        $mustahiq = Mustahiq::create([
            'nik' => '3524888888880001',
            'nama' => 'Mbah Kasmi',
            'jenis_kelamin' => 'P',
            'alamat' => 'RT 04 Dusun Melati',
            'ranting_id' => $ranting->id,
            'mwc_id' => $ranting->mwc_id,
            'asnaf' => 'fakir',
            'status' => 'aktif',
        ]);

        $pentasyarufan = Pentasyarufan::create([
            'nomor_transaksi' => 'TSF-202610-9999',
            'penthong_id' => $penthong->id,
            'pilar_program_id' => $pilar->id,
            'program_kerja_id' => $proker->id,
            'tingkat' => 'ranting',
            'ranting_id' => $ranting->id,
            'mwc_id' => $ranting->mwc_id,
            'tanggal_penyaluran' => '2026-10-08',
            'nominal_pencairan' => 500000,
            'penerima_manfaat' => 'Mbah Kasmi',
            'jumlah_penerima' => 1,
            'spj_status' => 'belum_spj',
            'user_id' => $user->id,
            'mustahiq_id' => $mustahiq->id,
        ]);

        // Hubungkan ke pivot pentasyarufan_mustahiq
        $mustahiq->pentasyarufans()->attach($pentasyarufan->id, [
            'nominal_bantuan' => 500000,
            'keterangan' => 'Santunan Paket Sembako & Uang Tunai',
        ]);

        // Verifikasi relasi Many-to-Many
        $this->assertCount(1, $mustahiq->fresh()->pentasyarufans);
        $this->assertEquals(500000, $mustahiq->fresh()->pentasyarufans->first()->pivot->nominal_bantuan);
        $this->assertEquals('Santunan Paket Sembako & Uang Tunai', $mustahiq->fresh()->pentasyarufans->first()->pivot->keterangan);
        $this->assertEquals(500000, $mustahiq->fresh()->total_bantuan);

        // Verifikasi relasi terbalik dari Pentasyarufan
        $this->assertCount(1, $pentasyarufan->fresh()->mustahiqs);
        $this->assertEquals($mustahiq->id, $pentasyarufan->fresh()->mustahiqs->first()->id);
    }

    public function test_sidebar_displays_data_mustahiq_menu_and_views_render_successfully(): void
    {
        $userSuperAdmin = User::where('username', 'adminpc')->first();
        if (!$userSuperAdmin) {
            $rolePc = Role::where('name', 'pc')->first();
            $userSuperAdmin = User::factory()->create([
                'role_id' => $rolePc->id,
            ]);
        }

        // 1. Dashboard memuat menu 'Data Mustahiq' di sidebar
        $responseDashboard = $this->actingAs($userSuperAdmin)->get(route('dashboard'));
        $responseDashboard->assertStatus(200);
        $responseDashboard->assertSee('Data Mustahiq');

        // 2. Halaman Index Mustahiq memuat tabel & KPI
        $responseIndex = $this->actingAs($userSuperAdmin)->get(route('mustahiqs.index'));
        $responseIndex->assertStatus(200);
        $responseIndex->assertSee('Data Mustahiq (Penerima Manfaat)');

        // 3. Halaman Form Tambah Mustahiq
        $responseCreate = $this->actingAs($userSuperAdmin)->get(route('mustahiqs.create'));
        $responseCreate->assertStatus(200);
        $responseCreate->assertSee('Formulir Pendataan Mustahiq');
    }
}
