<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DistributionSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'operasional_pct',
        'ranting_pct',
        'mwc_pct',
        'pc_pct',
        'pw_pct',
        'pb_pct',
        'is_active',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'operasional_pct' => 'float',
            'ranting_pct'     => 'float',
            'mwc_pct'         => 'float',
            'pc_pct'          => 'float',
            'pw_pct'          => 'float',
            'pb_pct'          => 'float',
            'is_active'       => 'boolean',
        ];
    }

    /**
     * Ambil pengaturan distribusi yang aktif saat ini (MWC 13%, PC 5%), atau fallback ke config/default
     */
    public static function getActive(): self
    {
        $activeConfig = config('lazisnu.distribution.active', []);

        return static::where('is_active', true)->latest('id')->first() ?? new static([
            'name'            => $activeConfig['name'] ?? 'Standar Tasharruf KOIN NU Lamongan (Revisi MWC 13% PC 5%)',
            'operasional_pct' => (float) ($activeConfig['operasional_pct'] ?? 20.00),
            'ranting_pct'     => (float) ($activeConfig['ranting_pct'] ?? 80.00),
            'mwc_pct'         => (float) ($activeConfig['mwc_pct'] ?? 13.00),
            'pc_pct'          => (float) ($activeConfig['pc_pct'] ?? 5.00),
            'pw_pct'          => (float) ($activeConfig['pw_pct'] ?? 1.50),
            'pb_pct'          => (float) ($activeConfig['pb_pct'] ?? 0.50),
            'is_active'       => true,
        ]);
    }

    /**
     * Ambil pengaturan distribusi legacy masa lalu (MWC 12%, PC 6%) untuk proteksi data lama (Grandfathering)
     */
    public static function getLegacy(): self
    {
        $legacyConfig = config('lazisnu.distribution.legacy', []);

        return static::where('mwc_pct', 12.00)
            ->where('pc_pct', 6.00)
            ->first() ?? new static([
            'name'            => $legacyConfig['name'] ?? 'Standar Tasharruf KOIN NU Lamongan (Legacy s/d September 2026)',
            'operasional_pct' => (float) ($legacyConfig['operasional_pct'] ?? 20.00),
            'ranting_pct'     => (float) ($legacyConfig['ranting_pct'] ?? 80.00),
            'mwc_pct'         => (float) ($legacyConfig['mwc_pct'] ?? 12.00),
            'pc_pct'          => (float) ($legacyConfig['pc_pct'] ?? 6.00),
            'pw_pct'          => (float) ($legacyConfig['pw_pct'] ?? 1.50),
            'pb_pct'          => (float) ($legacyConfig['pb_pct'] ?? 0.50),
            'is_active'       => false,
        ]);
    }

    /**
     * Dapatkan skema persentase yang tepat untuk sebuah Periode Penthong.
     * Aturan Grandfathering:
     * - Jika penthong terikat ke distribution_setting_id, gunakan setting tersebut.
     * - Jika penthong sudah tutup ('tutup'/'closed'), gunakan skema lama (MWC 12%, PC 6%).
     * - Jika penthong aktif atau null (periode baru), gunakan skema baru (MWC 13%, PC 5%).
     */
    public static function getForPenthong(?Penthong $penthong = null): self
    {
        if ($penthong) {
            if ($penthong->distribution_setting_id) {
                $setting = static::find($penthong->distribution_setting_id);
                if ($setting) {
                    return $setting;
                }
            }

            if ($penthong->isClosed()) {
                return static::getLegacy();
            }
        }

        return static::getActive();
    }
}
