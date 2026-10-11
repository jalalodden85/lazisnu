<?php

namespace App\Services;

use App\Models\Campaign;
use App\Models\OnlineDonation;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;
use RuntimeException;

class DonationVerificationService
{
    /**
     * Verifikasi donasi secara atomik dengan Database Transaction.
     * Menjamin sinkronisasi antara tabel online_donations dan campaigns.
     *
     * @param string $kodeDonasi Kode transaksi donasi unik (donation_code / kode_donasi)
     * @param float|null $nominalTerverifikasi Nominal mutasi riil yang disetujui (default: nominal pokok/total transaksi)
     * @param int|null $adminId ID admin yang melakukan verifikasi
     * @return array
     * @throws InvalidArgumentException|RuntimeException
     */
    public function verifyByCode(string $kodeDonasi, ?float $nominalTerverifikasi = null, ?int $adminId = null): array
    {
        return DB::transaction(function () use ($kodeDonasi, $nominalTerverifikasi, $adminId) {
            // 1. Lock record untuk mencegah race condition (double approval secara konkuren)
            $donation = OnlineDonation::where('donation_code', $kodeDonasi)
                ->lockForUpdate()
                ->first();

            if (!$donation) {
                throw new InvalidArgumentException("Donasi dengan kode [{$kodeDonasi}] tidak ditemukan di database.");
            }

            if ($donation->payment_status === 'verified') {
                throw new RuntimeException("Donasi dengan kode [{$kodeDonasi}] sudah berstatus Verified sebelumnya.");
            }

            // 2. Hitung nominal final yang diverifikasi
            // Jika ada selisih mutasi / kode unik, gunakan nominal custom yang dimasukkan admin
            $finalNominal = $nominalTerverifikasi !== null && $nominalTerverifikasi > 0
                ? $nominalTerverifikasi
                : (float) $donation->amount;

            $verifierId = $adminId ?? Auth::id();
            $now = now();

            // 3. Update status & binding payload pada tabel online_donations
            $donation->update([
                'payment_status'        => 'verified',
                'verified_at'           => $now,
                'verified_by'           => $verifierId,
                'nominal_terverifikasi' => $finalNominal,
            ]);

            // 4. Update dan sinkronkan akumulasi dana pada tabel kampanye bersangkutan
            $campaign = Campaign::where('id', $donation->campaign_id)
                ->lockForUpdate()
                ->firstOrFail();

            $campaign->increment('collected_amount', $finalNominal);

            return [
                'status'                => 'success',
                'kode_donasi'           => $donation->donation_code,
                'donation'              => $donation->fresh(['campaign', 'verifier']),
                'campaign'              => $campaign->fresh(),
                'nominal_terverifikasi' => $finalNominal,
                'message'               => "Donasi dengan kode [{$donation->donation_code}] berhasil diverifikasi dan disinkronkan ke riwayat donasi online.",
            ];
        });
    }

    /**
     * Batalkan verifikasi donasi secara atomik.
     * Mengurangi kembali dana kampanye dan mereset status donasi online.
     *
     * @param string $kodeDonasi
     * @return array
     */
    public function cancelByCode(string $kodeDonasi): array
    {
        return DB::transaction(function () use ($kodeDonasi) {
            $donation = OnlineDonation::where('donation_code', $kodeDonasi)
                ->lockForUpdate()
                ->first();

            if (!$donation) {
                throw new InvalidArgumentException("Donasi dengan kode [{$kodeDonasi}] tidak ditemukan.");
            }

            if ($donation->payment_status !== 'verified') {
                throw new RuntimeException("Donasi dengan kode [{$kodeDonasi}] belum diverifikasi.");
            }

            $deductedAmount = (float) ($donation->nominal_terverifikasi ?? $donation->amount);

            // 1. Reset status donasi online
            $donation->update([
                'payment_status'        => 'cancelled',
                'verified_at'           => null,
                'verified_by'           => null,
                'nominal_terverifikasi' => null,
            ]);

            // 2. Kurangi dana terkumpul kampanye
            $campaign = Campaign::where('id', $donation->campaign_id)
                ->lockForUpdate()
                ->firstOrFail();

            $campaign->decrement('collected_amount', $deductedAmount);

            return [
                'status'      => 'cancelled',
                'kode_donasi' => $donation->donation_code,
                'donation'    => $donation->fresh(['campaign']),
                'campaign'    => $campaign->fresh(),
                'message'     => "Verifikasi donasi dengan kode [{$donation->donation_code}] berhasil dibatalkan.",
            ];
        });
    }
}
