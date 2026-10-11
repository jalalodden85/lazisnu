<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Skema Persentase Distribusi Hasil KOIN NU (Tasharruf)
    |--------------------------------------------------------------------------
    | Mengatur rasio pembagian hasil penghimpunan Koin NU Lamongan.
    |
    | Skema Aktif (Revisi Resmi 2026):
    | - Operasional (Hak Amil): 20.00% dari Bruto
    | - Ranting               : 80.00% dari Netto (Tetap)
    | - MWC                   : 13.00% dari Netto (Naik dari 12%)
    | - PC                    : 5.00% dari Netto  (Turun dari 6%)
    | - PW                    : 1.50% dari Netto  (Tetap)
    | - PB                    : 0.50% dari Netto  (Tetap)
    | Total Netto = 100.00%
    |
    | Skema Legacy (Grandfathering):
    | - MWC                   : 12.00% dari Netto
    | - PC                    : 6.00% dari Netto
    */
    'distribution' => [
        'active' => [
            'name'            => 'Standar Tasharruf KOIN NU Lamongan (Revisi MWC 13% PC 5%)',
            'operasional_pct' => 20.00,
            'ranting_pct'     => 80.00,
            'mwc_pct'         => 13.00,
            'pc_pct'          => 5.00,
            'pw_pct'          => 1.50,
            'pb_pct'          => 0.50,
        ],
        'legacy' => [
            'name'            => 'Standar Tasharruf KOIN NU Lamongan (Legacy s/d September 2026)',
            'operasional_pct' => 20.00,
            'ranting_pct'     => 80.00,
            'mwc_pct'         => 12.00,
            'pc_pct'          => 6.00,
            'pw_pct'          => 1.50,
            'pb_pct'          => 0.50,
        ],
    ],
];
