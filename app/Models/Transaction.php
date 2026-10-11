<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Transaction extends Model
{
    use HasFactory;

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_code',
        'donator_id',
        'user_id',
        'amount',
        'collection_date',
        'period',
        'notes',
        'proof_image',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'collection_date' => 'date',
            'amount' => 'decimal:2',
        ];
    }

    /**
     * Auto generate Invoice Code & Period saat transaksi dibuat
     */
    protected static function booted(): void
    {
        static::creating(function (Transaction $transaction) {
            if (empty($transaction->collection_date)) {
                $transaction->collection_date = now()->toDateString();
            }

            if (empty($transaction->period)) {
                $transaction->period = Carbon::parse($transaction->collection_date)->format('Y-m');
            }

            if (empty($transaction->invoice_code)) {
                $datePrefix = Carbon::parse($transaction->collection_date)->format('Ymd');
                $randomSuffix = strtoupper(Str::random(4));
                $transaction->invoice_code = "TRX-{$datePrefix}-{$randomSuffix}";
            }
        });
    }

    /**
     * Relasi ke Donatur / Kaleng KOIN NU.
     */
    public function donator(): BelongsTo
    {
        return $this->belongsTo(Donator::class, 'donator_id');
    }

    /**
     * Relasi ke Petugas Lapangan (JPZIS) yang mengambil koin.
     */
    public function petugas(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Accessor format rupiah
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Accessor status badge
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_CONFIRMED => '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Terkonfirmasi</span>',
            self::STATUS_PENDING => '<span class="badge badge-warning"><i class="fas fa-clock mr-1"></i>Menunggu</span>',
            self::STATUS_CANCELLED => '<span class="badge badge-danger"><i class="fas fa-ban mr-1"></i>Dibatalkan</span>',
            default => '<span class="badge badge-secondary">' . $this->status . '</span>',
        };
    }

    /**
     * Scopes
     */
    public function scopeConfirmed(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_CONFIRMED);
    }

    public function scopePeriod(Builder $query, ?string $period): Builder
    {
        return $period ? $query->where('period', $period) : $query;
    }

    public function scopeDateRange(Builder $query, ?string $startDate, ?string $endDate): Builder
    {
        if ($startDate && $endDate) {
            return $query->whereBetween('collection_date', [$startDate, $endDate]);
        }
        if ($startDate) {
            return $query->where('collection_date', '>=', $startDate);
        }
        if ($endDate) {
            return $query->where('collection_date', '<=', $endDate);
        }
        return $query;
    }

    public function scopeByPetugas(Builder $query, ?int $userId): Builder
    {
        return $userId ? $query->where('user_id', $userId) : $query;
    }

    public function scopeByRanting(Builder $query, ?string $ranting): Builder
    {
        if (empty($ranting)) {
            return $query;
        }

        return $query->whereHas('donator', function ($q) use ($ranting) {
            $q->where('ranting', $ranting);
        });
    }
}
