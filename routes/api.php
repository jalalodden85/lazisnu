<?php

use App\Http\Controllers\PenthongController;
use App\Http\Controllers\TransactionController;
use App\Models\Penthong;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// =========================================================================
// RUTE API TERPROTEKSI (AUTHENTICATION MIDDLEWARE)
// Seluruh endpoint data internal, setoran koin, periode pentong, dan laporan
// Menolak request tanpa session/token valid dengan HTTP 401 (Unauthorized)
// =========================================================================
Route::middleware(['web', 'auth'])->group(function () {
    // ---------------------------------------------------------------------
    // API ENDPOINTS SETORAN KOIN INFAQ (TINGKAT RANTING / MWC / CABANG)
    // ---------------------------------------------------------------------
    // Input setoran baru
    Route::post('/setoran', [TransactionController::class, 'store'])->name('api.setoran.store');

    // Edit / Update data setoran koin (Hanya jika periode pentong masih OPEN)
    Route::put('/setoran/{transaction}', [TransactionController::class, 'update'])->name('api.setoran.update');
    Route::patch('/setoran/{transaction}', [TransactionController::class, 'update']);

    // Hapus transaksi setoran koin (Hanya jika periode pentong masih OPEN)
    Route::delete('/setoran/{transaction}', [TransactionController::class, 'destroy'])->name('api.setoran.destroy');

    // ---------------------------------------------------------------------
    // API ENDPOINTS PERIODE PENTONG (READ-ONLY INFORMASI & FILTER)
    // ---------------------------------------------------------------------
    // Cek status periode pentong aktif
    Route::get('/periode-pentong/aktif', function (Request $request) {
        $mwcId = $request->query('mwc_id');
        $rantingId = $request->query('ranting_id');
        $active = Penthong::getActive($mwcId ? (int) $mwcId : null, $rantingId ? (int) $rantingId : null);

        if (!$active || !$active->isOpen()) {
            return response()->json([
                'success'   => false,
                'is_open'   => false,
                'message'   => 'Tidak ada periode pentong yang aktif saat ini.',
                'penthong'  => null,
            ]);
        }

        return response()->json([
            'success'   => true,
            'is_open'   => true,
            'message'   => 'Periode pentong aktif ditemukan.',
            'penthong'  => [
                'id'           => $active->id,
                'nama_periode' => $active->nama_periode,
                'bulan'        => $active->bulan,
                'bulan_nama'   => $active->bulan_nama,
                'tahun'        => $active->tahun,
                'status'       => $active->status,
                'is_active'    => (bool) $active->is_active,
            ],
        ]);
    })->name('api.periode-pentong.aktif');

    // Daftar riwayat periode pentong
    Route::get('/periode-pentong', function () {
        $penthongs = Penthong::orderByDesc('tahun')->orderByDesc('bulan')->get()->map(function ($p) {
            return [
                'id'           => $p->id,
                'nama_periode' => $p->nama_periode,
                'bulan'        => $p->bulan,
                'bulan_nama'   => $p->bulan_nama,
                'tahun'        => $p->tahun,
                'status'       => $p->status,
                'is_active'    => (bool) $p->is_active,
                'is_open'      => $p->isOpen(),
            ];
        });

        return response()->json([
            'success' => true,
            'data'    => $penthongs,
        ]);
    })->name('api.periode-pentong.index');

    // ---------------------------------------------------------------------
    // API ENDPOINTS MUTASI PERIODE PENTONG (ADMIN PC ONLY - HTTP 403 IF NOT PC)
    // ---------------------------------------------------------------------
    Route::middleware('admin.pc')->group(function () {
        // Buat / buka periode pentong baru
        Route::post('/periode-pentong', [PenthongController::class, 'store'])->name('api.periode-pentong.store');

        // Update / Switch Status Periode Pentong (Buka/Tutup)
        Route::match(['put', 'patch', 'post'], '/periode-pentong/{penthong}/toggle-status', [PenthongController::class, 'toggleStatus'])->name('api.periode-pentong.toggle-status');
        Route::match(['put', 'patch'], '/periode-pentong/{penthong}', [PenthongController::class, 'toggleStatus']);

        // Kunci / tutup periode pentong
        Route::post('/periode-pentong/{penthong}/close', [PenthongController::class, 'close'])->name('api.periode-pentong.close');
    });

    // ---------------------------------------------------------------------
    // API ENDPOINTS CETAK LAPORAN SETORAN KOIN DENGAN HIERARCHICAL DATA SCOPING
    // ---------------------------------------------------------------------
    Route::match(['get', 'post'], '/laporan/cetak', [\App\Http\Controllers\LaporanCetakController::class, 'cetak'])->name('api.laporan.cetak');
    Route::get('/laporan/scope-options', [\App\Http\Controllers\LaporanCetakController::class, 'scopeOptions'])->name('api.laporan.scope-options');
});

// =========================================================================
// RUTE PUBLIK PAYMENT GATEWAY (WEBHOOK & AUTO-VERIFIKASI REAL-TIME)
// Bebas autentikasi session & bebas CSRF untuk menerima callback eksternal
// =========================================================================
Route::post('/payment/webhook', [\App\Http\Controllers\PaymentWebhookController::class, 'handleWebhook'])->name('api.payment.webhook');
Route::get('/donasi/status/{code}', [\App\Http\Controllers\PaymentWebhookController::class, 'checkStatus'])->name('api.donation.check-status');
Route::post('/donasi/simulate/{code}', [\App\Http\Controllers\PaymentWebhookController::class, 'simulatePayment'])->name('api.donation.simulate');


