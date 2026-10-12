<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Mwc extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'kecamatan',
        'address',
        'phone',
        // Susunan Pengurus MWC & WhatsApp
        'nama_ketua_mwc',
        'wa_ketua_mwc',
        'nama_ketua_lazisnu',
        'wa_ketua_lazisnu',
        'nama_manager_upzis',
        'wa_manager_upzis',
        'nama_sekretaris',
        'wa_sekretaris',
        'nama_bendahara',
        'wa_bendahara',
    ];

    /**
     * Otomatis format nomor WhatsApp saat model disimpan
     */
    protected static function booted(): void
    {
        static::saving(function (Mwc $mwc) {
            $mwc->wa_ketua_mwc     = \App\Services\WhatsAppHelper::formatNumber($mwc->wa_ketua_mwc);
            $mwc->wa_ketua_lazisnu = \App\Services\WhatsAppHelper::formatNumber($mwc->wa_ketua_lazisnu);
            $mwc->wa_manager_upzis = \App\Services\WhatsAppHelper::formatNumber($mwc->wa_manager_upzis);
            $mwc->wa_sekretaris    = \App\Services\WhatsAppHelper::formatNumber($mwc->wa_sekretaris);
            $mwc->wa_bendahara     = \App\Services\WhatsAppHelper::formatNumber($mwc->wa_bendahara);
        });
    }

    /**
     * Struktur daftar pengurus MWC terformat untuk antarmuka
     */
    public function getPengurusListAttribute(): array
    {
        return [
            [
                'jabatan' => 'Ketua Tanfidziyah MWC NU',
                'nama'    => $this->nama_ketua_mwc,
                'wa'      => $this->wa_ketua_mwc,
                'link'    => \App\Services\WhatsAppHelper::generateLink($this->wa_ketua_mwc, $this->nama_ketua_mwc, 'Ketua Tanfidziyah', "MWC {$this->name}"),
                'badge'   => 'bg-success',
                'icon'    => 'fas fa-user-tie',
            ],
            [
                'jabatan' => 'Ketua LAZISNU MWC',
                'nama'    => $this->nama_ketua_lazisnu,
                'wa'      => $this->wa_ketua_lazisnu,
                'link'    => \App\Services\WhatsAppHelper::generateLink($this->wa_ketua_lazisnu, $this->nama_ketua_lazisnu, 'Ketua LAZISNU', "MWC {$this->name}"),
                'badge'   => 'bg-primary',
                'icon'    => 'fas fa-hand-holding-heart',
            ],
            [
                'jabatan' => 'Manager UPZIS MWC',
                'nama'    => $this->nama_manager_upzis,
                'wa'      => $this->wa_manager_upzis,
                'link'    => \App\Services\WhatsAppHelper::generateLink($this->wa_manager_upzis, $this->nama_manager_upzis, 'Manager UPZIS', "MWC {$this->name}"),
                'badge'   => 'bg-info',
                'icon'    => 'fas fa-briefcase',
            ],
            [
                'jabatan' => 'Sekretaris UPZIS MWC',
                'nama'    => $this->nama_sekretaris,
                'wa'      => $this->wa_sekretaris,
                'link'    => \App\Services\WhatsAppHelper::generateLink($this->wa_sekretaris, $this->nama_sekretaris, 'Sekretaris UPZIS', "MWC {$this->name}"),
                'badge'   => 'bg-secondary',
                'icon'    => 'fas fa-file-signature',
            ],
            [
                'jabatan' => 'Bendahara UPZIS MWC',
                'nama'    => $this->nama_bendahara,
                'wa'      => $this->wa_bendahara,
                'link'    => \App\Services\WhatsAppHelper::generateLink($this->wa_bendahara, $this->nama_bendahara, 'Bendahara UPZIS', "MWC {$this->name}"),
                'badge'   => 'bg-warning text-dark',
                'icon'    => 'fas fa-coins',
            ],
        ];
    }

    /**
     * Ranting-ranting (desa) yang berada di bawah naungan MWC ini
     */
    public function rantings(): HasMany
    {
        return $this->hasMany(Ranting::class);
    }

    /**
     * Relawan yang bertugas di seluruh ranting di bawah MWC ini (hasManyThrough)
     */
    public function relawans(): HasManyThrough
    {
        return $this->hasManyThrough(Relawan::class, Ranting::class);
    }

    /**
     * Donatur (pemegang kotak koin) di seluruh ranting di bawah MWC ini (hasManyThrough)
     */
    public function donators(): HasManyThrough
    {
        return $this->hasManyThrough(Donator::class, Ranting::class);
    }

    /**
     * Seluruh transaksi koin infaq di wilayah MWC ini
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(KoinTransaction::class, 'mwc_id');
    }

    /**
     * Akun pengurus/admin pada MWC ini
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
