<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Ranting extends Model
{
    use HasFactory;

    protected $fillable = [
        'mwc_id',
        'code',
        'name',
        'desa',
        'pj_name',
        'address',
        'is_active',
        // Susunan Pengurus Ranting & WhatsApp
        'nama_ketua_ranting',
        'wa_ketua_ranting',
        'nama_ketua_upzis',
        'wa_ketua_upzis',
        'nama_bendahara',
        'wa_bendahara',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'mwc_id'    => 'integer',
        ];
    }

    /**
     * Scope Ranting yang aktif mengikuti program KOIN NU
     */
    public function scopeActiveProgram(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * Badge status keikutsertaan program untuk UI
     */
    public function getProgramStatusBadgeAttribute(): string
    {
        return $this->is_active
            ? '<span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Aktif</span>'
            : '<span class="badge badge-danger px-2 py-1"><i class="fas fa-ban mr-1"></i>Non-Aktif</span>';
    }

    /**
     * Otomatis susun format kode ranting dan format nomor WhatsApp
     */
    protected static function booted(): void
    {
        static::creating(function (Ranting $ranting) {
            if (empty($ranting->code) && !empty($ranting->mwc_id)) {
                $ranting->code = self::generateCode($ranting->mwc_id);
            }
        });

        static::saving(function (Ranting $ranting) {
            $ranting->wa_ketua_ranting = \App\Services\WhatsAppHelper::formatNumber($ranting->wa_ketua_ranting);
            $ranting->wa_ketua_upzis   = \App\Services\WhatsAppHelper::formatNumber($ranting->wa_ketua_upzis);
            $ranting->wa_bendahara     = \App\Services\WhatsAppHelper::formatNumber($ranting->wa_bendahara);
        });
    }

    /**
     * Struktur daftar pengurus Ranting terformat untuk antarmuka
     */
    public function getPengurusListAttribute(): array
    {
        return [
            [
                'jabatan' => 'Ketua Tanfidziyah Ranting NU',
                'nama'    => $this->nama_ketua_ranting,
                'wa'      => $this->wa_ketua_ranting,
                'link'    => \App\Services\WhatsAppHelper::generateLink($this->wa_ketua_ranting, $this->nama_ketua_ranting, 'Ketua Tanfidziyah', "Ranting {$this->name}"),
                'badge'   => 'bg-success',
                'icon'    => 'fas fa-user-tie',
            ],
            [
                'jabatan' => 'Ketua UPZIS / JPZIS Ranting',
                'nama'    => $this->nama_ketua_upzis,
                'wa'      => $this->wa_ketua_upzis,
                'link'    => \App\Services\WhatsAppHelper::generateLink($this->wa_ketua_upzis, $this->nama_ketua_upzis, 'Ketua UPZIS', "Ranting {$this->name}"),
                'badge'   => 'bg-primary',
                'icon'    => 'fas fa-hand-holding-heart',
            ],
            [
                'jabatan' => 'Bendahara UPZIS Ranting',
                'nama'    => $this->nama_bendahara,
                'wa'      => $this->wa_bendahara,
                'link'    => \App\Services\WhatsAppHelper::generateLink($this->wa_bendahara, $this->nama_bendahara, 'Bendahara UPZIS', "Ranting {$this->name}"),
                'badge'   => 'bg-warning text-dark',
                'icon'    => 'fas fa-coins',
            ],
        ];
    }

    /**
     * Helper untuk mengenerate kode ranting berikutnya berdasarkan MWC
     */
    public static function generateCode(int $mwcId, ?int $number = null): string
    {
        $mwc = Mwc::findOrFail($mwcId);
        
        if ($number === null) {
            // Hitung ranting yang sudah ada di MWC ini
            $count = self::where('mwc_id', $mwcId)->count() + 1;
            $number = $count;
        }

        $formattedNumber = str_pad($number, 2, '0', STR_PAD_LEFT);
        return "{$mwc->code}R{$formattedNumber}";
    }

    /**
     * MWC Induk tempat Ranting bernaung
     */
    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class);
    }

    /**
     * Relawan-relawan koordinator RT/RW pada Ranting ini
     */
    public function relawans(): HasMany
    {
        return $this->hasMany(Relawan::class);
    }

    /**
     * Seluruh transaksi setoran koin infaq di Ranting ini
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(KoinTransaction::class, 'ranting_id');
    }

    /**
     * Seluruh donatur penerima kotak infaq di Ranting ini
     */
    public function donators(): HasMany
    {
        return $this->hasMany(Donator::class, 'ranting_id');
    }

    /**
     * Akun pengurus/operator Ranting
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
