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
}
