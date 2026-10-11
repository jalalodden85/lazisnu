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
     * Otomatis susun format kode ranting: {KODE_MWC}R{No_2_digit}
     * Contoh: M01 + No 1 -> M01R01
     */
    protected static function booted(): void
    {
        static::creating(function (Ranting $ranting) {
            if (empty($ranting->code) && !empty($ranting->mwc_id)) {
                $ranting->code = self::generateCode($ranting->mwc_id);
            }
        });
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
