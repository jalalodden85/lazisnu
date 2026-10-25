<?php

namespace App\Services;

class WhatsAppHelper
{
    /**
     * Standarisasi format nomor WhatsApp agar kompatibel dengan protokol wa.me (diawali 62).
     * Contoh: '0812-3456-7890' -> '6281234567890'
     */
    public static function formatNumber(?string $phone): ?string
    {
        if (empty($phone)) {
            return null;
        }

        // Hapus seluruh karakter non-angka
        $clean = preg_replace('/[^0-9]/', '', $phone);

        if (empty($clean)) {
            return null;
        }

        // Jika diawali '0', ubah menjadi '62' (misal: 0812... -> 62812...)
        if (str_starts_with($clean, '0')) {
            $clean = '62' . substr($clean, 1);
        }
        // Jika diawali '8', tambahkan '62' di depannya (misal: 812... -> 62812...)
        elseif (str_starts_with($clean, '8')) {
            $clean = '62' . $clean;
        }
        // Jika diawali '+62' (sudah terhapus plusnya menjadi '62'), tetap '62...'

        // Validasi panjang nomor Indonesia wajar (10 - 15 digit)
        if (strlen($clean) < 9) {
            return null;
        }

        return $clean;
    }

    /**
     * Cek apakah nomor WhatsApp valid setelah dinormalisasi
     */
    public static function isValidNumber(?string $phone): bool
    {
        return self::formatNumber($phone) !== null;
    }

    /**
     * Susun Hyperlink wa.me dengan pesan template salam resmi NU Care - LAZISNU.
     */
    public static function generateLink(?string $phone, ?string $recipientName = null, ?string $jabatan = null, ?string $wilayah = null): ?string
    {
        $formatted = self::formatNumber($phone);
        if (!$formatted) {
            return null;
        }

        $greeting = "Assalamu'alaikum Wr. Wb.";
        if ($recipientName && $jabatan) {
            $greeting .= " Yth. Sahabat {$recipientName} ({$jabatan}";
            if ($wilayah) {
                $greeting .= " - {$wilayah}";
            }
            $greeting .= "),";
        }

        $greeting .= " Salam silaturahmi dari Tim Manajemen PC Lazisnu Kabupaten Lamongan.";

        $params = http_build_query([
            'text' => $greeting,
        ]);

        return "https://wa.me/{$formatted}?{$params}";
    }

    /**
     * Susun link wa.me ucapan terima kasih untuk donasi online
     */
    public static function generateDonationThankYouLink(?string $phone, ?string $donorName = null, ?string $donationCode = null, ?float $amount = null): ?string
    {
        $formatted = self::formatNumber($phone);
        if (!$formatted) {
            return null;
        }

        $name = !empty($donorName) ? $donorName : 'Bapak/Ibu Donatur';
        $text = "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\n";
        $text .= "Yth. {$name},\n";
        $text .= "Terima kasih banyak atas donasi/infaq yang telah disalurkan melalui PC NU Care - LAZISNU Lamongan";

        if ($donationCode) {
            $text .= " (Kode: {$donationCode})";
        }

        if ($amount && $amount > 0) {
            $text .= " sebesar Rp " . number_format($amount, 0, ',', '.');
        }

        $text .= ".\n\nSemoga donasi Bapak/Ibu menjadi amal jariyah yang membawa keberkahan, kemuliaan, serta dibalas oleh Allah SWT dengan pahala berlipat ganda. Aamiin ya Rabbal 'Alamin.\n\n";
        $text .= "- Manajemen PC NU Care - LAZISNU Lamongan";

        return "https://wa.me/{$formatted}?text=" . rawurlencode($text);
    }

    /**
     * Susun link wa.me ucapan terima kasih untuk donatur kaleng Koin NU
     */
    public static function generateKoinNuThankYouLink(?string $phone, ?string $donatorName = null, ?string $boxNumber = null): ?string
    {
        $formatted = self::formatNumber($phone);
        if (!$formatted) {
            return null;
        }

        $name = !empty($donatorName) ? $donatorName : 'Sahabat Donatur';
        $text = "Assalamu'alaikum Warahmatullahi Wabarakatuh.\n\n";
        $text .= "Yth. {$name},\n";
        $text .= "Terima kasih atas keistiqomahan Bapak/Ibu dalam program Kaleng Koin NU";

        if ($boxNumber) {
            $text .= " (No. Kotak: #{$boxNumber})";
        }

        $text .= " LAZISNU PCNU Lamongan.\n\n";
        $text .= "Semoga Allah SWT senantiasa memberikan keberkahan hidup, kesehatan, rezeki yang berlimpah, dan pahala berlipat ganda. Aamiin ya Rabbal 'Alamin.\n\n";
        $text .= "- PC NU Care - LAZISNU Lamongan";

        return "https://wa.me/{$formatted}?text=" . rawurlencode($text);
    }
}
