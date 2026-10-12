<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DonatorController;
use App\Http\Controllers\MwcController;
use App\Http\Controllers\RantingController;
use App\Http\Controllers\RelawanController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\FrontendController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\PenthongController;
use App\Http\Controllers\PilarProgramController;
use App\Http\Controllers\ProgramKerjaController;
use App\Http\Controllers\PentasyarufanController;
use App\Http\Controllers\MustahiqController;
use App\Http\Controllers\KontribusiStrukturController;
use App\Http\Controllers\BkuController;
use App\Http\Controllers\BkuOperasionalController;
use App\Http\Controllers\Admin\ProgramCategoryController;
use App\Http\Controllers\Admin\CampaignController;
use App\Http\Controllers\Admin\OnlineDonationAdminController;
use App\Http\Controllers\Admin\ArticleController;
use App\Http\Controllers\Admin\SliderController;
use App\Http\Controllers\Admin\FinancialReportController;
use App\Http\Controllers\Admin\SiteSettingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes - Portal Publik NU Care - LAZISNU & Manajemen KOIN NU
|--------------------------------------------------------------------------
*/

// Portal Publik (Halaman Depan Informasi & Crowdfunding ala nucare.id)
Route::get('/', [FrontendController::class, 'index'])->name('home');
Route::get('/program', [FrontendController::class, 'programs'])->name('programs.index');
Route::get('/program/{slug}', [FrontendController::class, 'programDetail'])->name('programs.detail');
Route::get('/berita', [FrontendController::class, 'articles'])->name('articles.index');
Route::get('/berita/{slug}', [FrontendController::class, 'articleDetail'])->name('articles.detail');
Route::get('/kalkulator-zakat', [FrontendController::class, 'calculator'])->name('zakat.calculator');
Route::get('/laporan-publik', [FrontendController::class, 'reports'])->name('reports.index');
Route::post('/donasi/proses', [FrontendController::class, 'storeDonation'])->name('donation.store');
Route::get('/donasi/sukses/{code}', [FrontendController::class, 'donationSuccess'])->name('donation.success');

// =========================================================================
// Autentikasi Login (Hanya untuk Tamu / Belum Login)
// =========================================================================
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.post');
});
Route::get('/quick-login/{id}', [AuthController::class, 'quickLogin'])->name('quick-login');
// =========================================================================
// RUTE TERPROTEKSI (WAJIB LOGIN / AUTHENTICATION MIDDLEWARE)
// Seluruh rute internal, operasional koin, manajemen admin, dan pelaporan
// =========================================================================
Route::middleware(['auth'])->group(function () {
    Route::match(['get', 'post'], '/logout', [AuthController::class, 'logout'])->name('logout');

    // Dashboard Utama (Level PC / Global)
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Profil Mandiri & Ubah Password (Dapat diakses seluruh pengguna: PC, MWC, Ranting)
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::put('/profile/password', [ProfileController::class, 'updatePassword'])->name('profile.password.update');

    // Master Data MWC (15 MWC)
    Route::resource('mwcs', MwcController::class);

    // Master Data Ranting NU (Kode: {MWC}R01, {MWC}R02, dst)
    Route::get('/rantings/get-next-code', [RantingController::class, 'getNextCode'])->name('rantings.get-next-code');
    Route::post('/rantings/{ranting}/toggle-status', [RantingController::class, 'toggleStatus'])->name('rantings.toggle-status');
    Route::resource('rantings', RantingController::class);

    // =========================================================================
    // Rute Mutasi Data yang Dilindungi Status Operasional Ranting (CheckRantingStatus)
    // Admin Ranting yang berstatus non-aktif HANYA dapat melihat riwayat (Read-Only)
    // =========================================================================
    Route::middleware('ranting.active')->group(function () {
        // 1. Transaksi Setoran Koin (Create, Store, Edit, Update, Destroy)
        Route::get('/transactions/create', [TransactionController::class, 'create'])->name('transactions.create');
        Route::post('/transactions', [TransactionController::class, 'store'])->name('transactions.store');
        Route::get('/transactions/{transaction}/edit', [TransactionController::class, 'edit'])->name('transactions.edit');
        Route::put('/transactions/{transaction}', [TransactionController::class, 'update'])->name('transactions.update');
        Route::patch('/transactions/{transaction}', [TransactionController::class, 'update']);
        Route::delete('/transactions/{transaction}', [TransactionController::class, 'destroy'])->name('transactions.destroy');

        // 2. Manajemen Relawan (Create, Store, Import, Edit, Update, Destroy)
        Route::get('/relawans/create', [RelawanController::class, 'create'])->name('relawans.create');
        Route::post('/relawans', [RelawanController::class, 'store'])->name('relawans.store');
        Route::post('/relawans/import', [RelawanController::class, 'import'])->name('relawans.import');
        Route::get('/relawans/{relawan}/edit', [RelawanController::class, 'edit'])->name('relawans.edit');
        Route::put('/relawans/{relawan}', [RelawanController::class, 'update'])->name('relawans.update');
        Route::patch('/relawans/{relawan}', [RelawanController::class, 'update']);
        Route::delete('/relawans/{relawan}', [RelawanController::class, 'destroy'])->name('relawans.destroy');

        // 3. Donatur & Pembagian Petugas (Create, Store, Import, Edit, Update, Destroy, Assign)
        Route::get('/donators/create', [DonatorController::class, 'create'])->name('donators.create');
        Route::post('/donators', [DonatorController::class, 'store'])->name('donators.store');
        Route::post('/donators/import', [DonatorController::class, 'import'])->name('donators.import');
        Route::get('/donators/{donator}/edit', [DonatorController::class, 'edit'])->name('donators.edit');
        Route::put('/donators/{donator}', [DonatorController::class, 'update'])->name('donators.update');
        Route::patch('/donators/{donator}', [DonatorController::class, 'update']);
        Route::delete('/donators/{donator}', [DonatorController::class, 'destroy'])->name('donators.destroy');
        Route::post('/donators/{donator}/assign', [DonatorController::class, 'assignPetugas'])->name('donators.assign');

        // 4. Data Mustahiq (Create, Store, Import, Edit, Update, Destroy)
        Route::get('/mustahiqs/create', [MustahiqController::class, 'create'])->name('mustahiqs.create');
        Route::post('/mustahiqs', [MustahiqController::class, 'store'])->name('mustahiqs.store');
        Route::post('/mustahiqs/import', [MustahiqController::class, 'import'])->name('mustahiqs.import');
        Route::get('/mustahiqs/{mustahiq}/edit', [MustahiqController::class, 'edit'])->name('mustahiqs.edit');
        Route::put('/mustahiqs/{mustahiq}', [MustahiqController::class, 'update'])->name('mustahiqs.update');
        Route::delete('/mustahiqs/{mustahiq}', [MustahiqController::class, 'destroy'])->name('mustahiqs.destroy');
    });

    // Rute Data Mustahiq (Template, Export PDF/Excel, Autocomplete Search, Secure Photo Stream, Index, Show)
    Route::get('/mustahiqs/template', [MustahiqController::class, 'downloadTemplate'])->name('mustahiqs.template');
    Route::get('/mustahiqs/export/pdf', [MustahiqController::class, 'exportPdf'])->name('mustahiqs.export.pdf');
    Route::get('/mustahiqs/export/excel', [MustahiqController::class, 'exportExcel'])->name('mustahiqs.export.excel');
    Route::get('/mustahiqs/search', [MustahiqController::class, 'search'])->name('mustahiqs.search');
    Route::get('/mustahiqs/{mustahiq}/foto', [MustahiqController::class, 'serveFoto'])->name('mustahiqs.foto');
    Route::resource('mustahiqs', MustahiqController::class)->only(['index', 'show']);

    // Rute Read-Only & Riwayat (Tetap dapat diakses oleh Admin Ranting meskipun nonaktif)
    Route::resource('transactions', TransactionController::class)->only(['index', 'show']);
    Route::get('/relawans/template', [RelawanController::class, 'downloadTemplate'])->name('relawans.template');
    Route::resource('relawans', RelawanController::class)->only(['index', 'show']);
    Route::get('/donators/get-next-box-number', [DonatorController::class, 'getNextBoxNumber'])->name('donators.get-next-box-number');
    Route::get('/donators/template', [DonatorController::class, 'downloadTemplate'])->name('donators.template');
    Route::get('/donators/distribution', [DonatorController::class, 'distribution'])->name('donators.distribution');
    Route::get('/donators/export-pdf', [DonatorController::class, 'exportPdf'])->name('donators.export-pdf');
    Route::resource('donators', DonatorController::class)->only(['index', 'show']);

    // Laporan Khusus KOIN NU (Rekap Koin, Wilayah MWC/Ranting, Kinerja Relawan, Donatur, Penthong, Cetak)
    Route::prefix('laporan')->name('reports.')->group(function () {
        Route::get('/koin', [ReportController::class, 'koin'])->name('koin');
        Route::get('/wilayah', [ReportController::class, 'wilayah'])->name('wilayah');
        Route::get('/relawan', [ReportController::class, 'relawan'])->name('relawan');
        Route::get('/donatur', [ReportController::class, 'donatur'])->name('donatur');
        Route::get('/donatur/export-pdf', [DonatorController::class, 'exportPdf'])->name('donatur.export-pdf');
        Route::get('/penthong', [PenthongController::class, 'report'])->name('penthong');
        Route::match(['get', 'post'], '/cetak', [ReportController::class, 'print'])->name('print');
    });

    // Modul Periodisasi Buka Kotak (Penthong) & Tasharruf Bagi Hasil
    // 1. Rute Mutasi Periode Pentong (HANYA Admin PC / Cabang)
    Route::middleware(['admin.pc'])->group(function () {
        Route::get('/penthongs/create', [PenthongController::class, 'create'])->name('penthongs.create');
        Route::post('/penthongs', [PenthongController::class, 'store'])->name('penthongs.store');
        Route::post('/penthongs/{penthong}/close', [PenthongController::class, 'close'])->name('penthongs.close')->whereNumber('penthong');
        Route::post('/penthongs/{penthong}/toggle-status', [PenthongController::class, 'toggleStatus'])->name('penthongs.toggle-status')->whereNumber('penthong');
    });

    // 2. Rute Read-Only & Ekspor (Dapat diakses seluruh role pengguna untuk melihat informasi & filter)
    Route::get('/penthongs', [PenthongController::class, 'index'])->name('penthongs.index');
    Route::get('/penthongs/{penthong}', [PenthongController::class, 'show'])->name('penthongs.show')->whereNumber('penthong');
    Route::get('/penthongs/{penthong}/export/pdf', [PenthongController::class, 'exportPdf'])->name('penthongs.export.pdf')->whereNumber('penthong');
    Route::get('/penthongs/{penthong}/export/excel', [PenthongController::class, 'exportExcel'])->name('penthongs.export.excel')->whereNumber('penthong');
    Route::get('/laporan-penthong', [PenthongController::class, 'report'])->name('penthongs.report');

    // Manajemen Akun Pengguna (PC, MWC, Ranting) & Hak Akses Menu
    Route::resource('users', UserController::class);

    // =========================================================================
    // Manajemen Portal NU Care - LAZISNU (Admin PC)
    // =========================================================================
    Route::prefix('admin')->name('admin.')->group(function () {
        // 1. 5 Pilar Program NU Care
        Route::resource('categories', ProgramCategoryController::class);

        // 2. Kampanye Donasi & Crowdfunding
        Route::post('campaigns/verify-donation', [CampaignController::class, 'verifyDonation'])->name('campaigns.verify-donation');
        Route::post('campaigns/cancel-donation', [CampaignController::class, 'cancelDonation'])->name('campaigns.cancel-donation');
        Route::resource('campaigns', CampaignController::class);

        // 3. Donasi Online Masuk & Verifikasi
        Route::get('online-donations', [OnlineDonationAdminController::class, 'index'])->name('online-donations.index');
        Route::get('online-donations/{donation}', [OnlineDonationAdminController::class, 'show'])->name('online-donations.show');
        Route::post('online-donations/{donation}/verify', [OnlineDonationAdminController::class, 'verify'])->name('online-donations.verify');
        Route::post('online-donations/{donation}/cancel', [OnlineDonationAdminController::class, 'cancel'])->name('online-donations.cancel');
        Route::delete('online-donations/{donation}', [OnlineDonationAdminController::class, 'destroy'])->name('online-donations.destroy');

        // 4. Portal Berita & Kabar Pentasyarufan
        Route::resource('articles', ArticleController::class);

        // 5. Hero Banner Slider
        Route::resource('sliders', SliderController::class);

        // 6. Laporan Keuangan & Akuntabilitas Publik
        Route::resource('financial-reports', FinancialReportController::class);

        // 7. Pengaturan Portal & Rekening Donasi
        Route::get('site-settings', [SiteSettingController::class, 'index'])->name('site-settings.index');
        Route::post('site-settings', [SiteSettingController::class, 'update'])->name('site-settings.update');
    });

    // =========================================================================
    // Modul Pentasyarufan & Penyaluran Dana (5 Pilar & Proker Berjenjang)
    // =========================================================================
    Route::prefix('pentasyarufan')->group(function () {
        // 1. 5 Pilar Program & Pagu Anggaran
        Route::get('pilar', [PilarProgramController::class, 'index'])->name('pilar-program.index');
        Route::post('pilar/update', [PilarProgramController::class, 'update'])->name('pilar-program.update');
        Route::get('pilar/chart-data', [PilarProgramController::class, 'chartData'])->name('pilar-program.chart-data');

        // 2. Program Kerja (Proker) Berjenjang (Ranting, MWC, PC)
        Route::get('proker', [ProgramKerjaController::class, 'index'])->name('proker.index');
        Route::get('proker/create', [ProgramKerjaController::class, 'create'])->name('proker.create');
        Route::post('proker', [ProgramKerjaController::class, 'store'])->name('proker.store');
        Route::get('proker/{proker}', [ProgramKerjaController::class, 'show'])->name('proker.show')->whereNumber('proker');
        Route::get('proker/{proker}/edit', [ProgramKerjaController::class, 'edit'])->name('proker.edit')->whereNumber('proker');
        Route::put('proker/{proker}', [ProgramKerjaController::class, 'update'])->name('proker.update')->whereNumber('proker');
        Route::post('proker/{proker}/submit', [ProgramKerjaController::class, 'submit'])->name('proker.submit')->whereNumber('proker');
        Route::post('proker/{proker}/approve', [ProgramKerjaController::class, 'approve'])->name('proker.approve')->whereNumber('proker');
        Route::post('proker/{proker}/reject', [ProgramKerjaController::class, 'reject'])->name('proker.reject')->whereNumber('proker');
        Route::delete('proker/{proker}', [ProgramKerjaController::class, 'destroy'])->name('proker.destroy')->whereNumber('proker');

        // 3. Realisasi Pencairan Dana & SPJ
        Route::get('realisasi', [PentasyarufanController::class, 'index'])->name('pentasyarufan.index');
        Route::get('realisasi/create', [PentasyarufanController::class, 'create'])->name('pentasyarufan.create');
        Route::get('realisasi/periode-balance/{penthong}', [PentasyarufanController::class, 'getPeriodBalance'])->name('pentasyarufan.periode-balance')->whereNumber('penthong');
        Route::post('realisasi', [PentasyarufanController::class, 'store'])->name('pentasyarufan.store');
        Route::get('realisasi/{pentasyarufan}', [PentasyarufanController::class, 'show'])->name('pentasyarufan.show')->whereNumber('pentasyarufan');
        Route::post('realisasi/{pentasyarufan}/upload-spj', [PentasyarufanController::class, 'uploadSpj'])->name('pentasyarufan.upload-spj')->whereNumber('pentasyarufan');
        Route::post('realisasi/{pentasyarufan}/verify-spj', [PentasyarufanController::class, 'verifySpj'])->name('pentasyarufan.verify-spj')->whereNumber('pentasyarufan');

        // 4. Setoran Kontribusi / Pembagian Hasil Struktur ke MWC, PC, PW, PB
        Route::get('kontribusi', [KontribusiStrukturController::class, 'index'])->name('pentasyarufan.kontribusi.index');
        Route::get('kontribusi/create', [KontribusiStrukturController::class, 'create'])->name('pentasyarufan.kontribusi.create');
        Route::post('kontribusi', [KontribusiStrukturController::class, 'store'])->name('pentasyarufan.kontribusi.store');
        Route::get('kontribusi/{kontribusi}', [KontribusiStrukturController::class, 'show'])->name('pentasyarufan.kontribusi.show')->whereNumber('kontribusi');

        // 5. Laporan & Cetak Pentasyarufan
        Route::get('laporan', [PentasyarufanController::class, 'laporan'])->name('pentasyarufan.laporan');
    });

    // 9. Buku Kas Umum (BKU) T-Account KOIN NU
    Route::prefix('bku')->group(function () {
        Route::get('/', [BkuController::class, 'index'])->name('bku.index');
        Route::get('/cetak', [BkuController::class, 'cetak'])->name('bku.cetak');
        Route::get('/excel', [BkuController::class, 'exportExcel'])->name('bku.excel');
    });

    // 10. Modul Buku Kas Umum (BKU) Operasional / Hak Amil
    Route::prefix('bku-operasional')->name('bku-operasional.')->group(function () {
        Route::get('/', [BkuOperasionalController::class, 'index'])->name('index');
        Route::post('/expense', [BkuOperasionalController::class, 'storeExpense'])->name('expense');
        Route::post('/income', [BkuOperasionalController::class, 'storeIncome'])->name('income');
        Route::post('/sync-penthong', [BkuOperasionalController::class, 'syncPenthong'])->name('sync-penthong');
        Route::get('/export/pdf', [BkuOperasionalController::class, 'exportPdf'])->name('export.pdf');
        Route::get('/export/excel', [BkuOperasionalController::class, 'exportExcel'])->name('export.excel');
    });

    // 8. Buku Panduan Operasional Sistem (User Manual & PDF Ready)
    Route::get('/buku-panduan', function () {
        return response()->file(public_path('buku-panduan.html'));
    })->name('buku-panduan');
});


