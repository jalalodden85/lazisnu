<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Relawan extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'ranting_id',
        'nama',
        'nik',
        'no_hp',
        'area_tugas',
        'status',
        'joined_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joined_date' => 'date',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Relawan $relawan) {
            if (!empty($relawan->no_hp)) {
                $relawan->no_hp = \App\Services\WhatsAppHelper::formatNumber($relawan->no_hp);
            }
        });
    }

    /**
     * Mutator untuk memastikan nomor kontak relawan selalu berformat standar 62...
     */
    public function setNoHpAttribute($value): void
    {
        $this->attributes['no_hp'] = \App\Services\WhatsAppHelper::formatNumber($value);
    }

    /**
     * Ranting tempat relawan bertugas
     */
    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class);
    }

    /**
     * Riwayat transaksi setoran koin yang diserahkan oleh relawan ini
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(KoinTransaction::class);
    }

    /**
     * Donatur / Penerima Kotak Infaq yang diampu oleh relawan di wilayah tugasnya
     */
    public function donators(): HasMany
    {
        return $this->hasMany(Donator::class, 'relawan_id');
    }

    /**
     * Jumlah donatur aktif yang diampu relawan
     */
    public function getActiveDonatorsCountAttribute(): int
    {
        return $this->donators()->where('status', Donator::STATUS_ACTIVE)->count();
    }

    /**
     * Badge status relawan untuk AdminLTE
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->status === self::STATUS_ACTIVE
            ? '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Aktif</span>'
            : '<span class="badge badge-secondary"><i class="fas fa-times-circle mr-1"></i>Nonaktif</span>';
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByRanting(Builder $query, ?int $rantingId): Builder
    {
        return $rantingId ? $query->where('ranting_id', $rantingId) : $query;
    }

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (empty($term)) {
            return $query;
        }

        return $query->where(function ($q) use ($term) {
            $q->where('nama', 'like', "%{$term}%")
              ->orWhere('nik', 'like', "%{$term}%")
              ->orWhere('area_tugas', 'like', "%{$term}%")
              ->orWhere('no_hp', 'like', "%{$term}%");
        });
    }
}
