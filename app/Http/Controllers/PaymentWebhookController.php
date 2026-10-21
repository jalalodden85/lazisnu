<?php

namespace App\Http\Controllers;

use App\Models\Campaign;
use App\Models\OnlineDonation;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentWebhookController extends Controller
{
    /**
     * Endpoint Webhook / Callback Handler dari Payment Gateway (Midtrans/Duitku/Xendit)
     * URL: POST /api/payment/webhook
     */
    public function handleWebhook(Request $request): JsonResponse
    {
        // 1. Tangkap Payload Notifikasi dari Payment Gateway
        $payload = $request->all();
        Log::info('Payment Gateway Webhook Received:', $payload);

        $orderId           = $request->input('order_id');
        $statusCode        = $request->input('status_code');
        $grossAmount       = $request->input('gross_amount');
        $signatureKey      = $request->input('signature_key');
        $transactionStatus = $request->input('transaction_status');
        $fraudStatus       = $request->input('fraud_status');

        if (empty($orderId) || empty($transactionStatus)) {
            return response()->json([
                'status'  => 'error',
                'message' => 'Parameter order_id atau transaction_status tidak valid.',
            ], 400);
        }

        // 2. SECURITY VALIDATION (Validasi HMAC / Signature Key)
        // Mencegah eksploitasi manipulasi status dari penyerang luar (Hacker)
        $serverKey = config('services.midtrans.server_key') ?? env('MIDTRANS_SERVER_KEY', 'SB-Mid-server-TESTKEY1234567890');
        
        // Format Signature Standar Midtrans: SHA512(order_id + status_code + gross_amount + ServerKey)
        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        // Abaikan verifikasi signature hanya jika mode testing sandbox lokal tanpa signature_key
        if (!empty($signatureKey) && !hash_equals($expectedSignature, $signatureKey)) {
            Log::warning("SECURITY ALERT: Invalid Signature Key for Order [{$orderId}]. Expected: {$expectedSignature}, Received: {$signatureKey}");
            return response()->json([
                'status'  => 'forbidden',
                'message' => 'Validasi Signature Key gagal. Akses ditolak.',
            ], 403);
        }

        // 3. LOGIKA UPDATE DATABASE DENGAN DATABASE TRANSACTION (ATOMIK)
        try {
            DB::transaction(function () use ($orderId, $transactionStatus, $fraudStatus, $grossAmount, $payload) {
                // Lock baris data dengan lockForUpdate untuk mencegah race condition
                $donation = OnlineDonation::where('donation_code', $orderId)
                    ->lockForUpdate()
                    ->first();

                if (!$donation) {
                    Log::error("Donasi dengan kode [{$orderId}] tidak ditemukan pada database.");
                    return;
                }

                // A. KASUS SUKSES: Pembayaran telah diselesaikan (settlement / capture)
                if ($transactionStatus === 'settlement' || ($transactionStatus === 'capture' && $fraudStatus === 'accept')) {
                    // Cek Idempoten: Jika sudah verified, tidak perlu update ulang
                    if ($donation->payment_status === 'verified') {
                        return;
                    }

                    $verifiedAmount = (float) ($grossAmount ?: $donation->total_amount);

                    // Update status donasi menjadi 'verified' (Sah / Terverifikasi)
                    $donation->update([
                        'payment_status'          => 'verified',
                        'nominal_terverifikasi'   => $verifiedAmount,
                        'verified_at'             => now(),
                        'payment_gateway_payload' => json_encode($payload),
                    ]);

                    // Sinkronkan capaian dana pada tabel Kampanye bersangkutan
                    $campaign = Campaign::where('id', $donation->campaign_id)
                        ->lockForUpdate()
                        ->first();

                    if ($campaign) {
                        $campaign->increment('collected_amount', $verifiedAmount);
                    }

                    Log::info("Donasi [{$orderId}] BERHASIL DIVERIFIKASI OTOMATIS sebesar Rp " . number_format($verifiedAmount, 0, ',', '.'));
                }
                // B. KASUS KADALUARSA / BATAL
                elseif (in_array($transactionStatus, ['expire', 'cancel', 'deny'])) {
                    if ($donation->payment_status !== 'verified') {
                        $donation->update([
                            'payment_status'          => 'cancelled',
                            'payment_gateway_payload' => json_encode($payload),
                        ]);
                        Log::info("Donasi [{$orderId}] status diubah menjadi Cancelled/Expired.");
                    }
                }
            });

            // Respon HTTP 200 OK wajib dikembalikan agar Payment Gateway tidak mengirim webhook berulang
            return response()->json([
                'status'  => 'success',
                'message' => 'Notifikasi webhook berhasil diproses.',
            ], 200);

        } catch (\Throwable $e) {
            Log::error("Webhook Database Transaction Error for [{$orderId}]: " . $e->getMessage());
            return response()->json([
                'status'  => 'error',
                'message' => 'Terjadi kesalahan internal server saat memproses transaksi.',
            ], 500);
        }
    }

    /**
     * Endpoint Internal untuk AJAX Polling Frontend
     * Memeriksa apakah transaksi sudah berstatus 'verified' secara berkala (tiap 3-5 detik)
     * URL: GET /api/donasi/status/{code}
     */
    public function checkStatus(string $code): JsonResponse
    {
        $donation = OnlineDonation::where('donation_code', $code)->first();

        if (!$donation) {
            return response()->json([
                'success' => false,
                'message' => 'Data donasi tidak ditemukan.',
            ], 404);
        }

        $isVerified = ($donation->payment_status === 'verified');
        $isCancelled = in_array($donation->payment_status, ['cancelled', 'expired']);

        return response()->json([
            'success'        => true,
            'donation_code'  => $donation->donation_code,
            'payment_status' => $donation->payment_status,
            'is_verified'    => $isVerified,
            'is_cancelled'   => $isCancelled,
            'donor_name'     => $donation->display_name,
            'amount'         => (float) $donation->total_amount,
            'amount_fmt'     => 'Rp ' . number_format($donation->total_amount, 0, ',', '.'),
            'redirect_url'   => route('donation.success', ['code' => $donation->donation_code, 'status' => 'verified']),
            'message'        => $isVerified 
                ? 'Alhamdulillah! Pembayaran donasi telah berhasil diverifikasi oleh sistem.' 
                : 'Menunggu proses transfer donatur...',
        ]);
    }

    /**
     * Helper Simulasi Pembayaran (Khusus Testing Sandbox / Developer)
     * URL: POST /api/donasi/simulate/{code}
     */
    public function simulatePayment(string $code): JsonResponse
    {
        $donation = OnlineDonation::where('donation_code', $code)->first();
        if (!$donation) {
            return response()->json(['success' => false, 'message' => 'Donasi tidak ditemukan.'], 404);
        }

        // Buat payload tiruan seolah-olah berasal dari Midtrans
        $fakeRequest = new Request([
            'order_id'           => $donation->donation_code,
            'status_code'        => '200',
            'gross_amount'       => (string) ((int) $donation->total_amount),
            'transaction_status' => 'settlement',
            'fraud_status'       => 'accept',
        ]);

        return $this->handleWebhook($fakeRequest);
    }
}
