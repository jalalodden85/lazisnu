<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KoinTransaction extends Model
{
    use HasFactory;

    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'invoice_code',
        'penthong_id',
        'periode_pentong_id',
        'relawan_id',
        'ranting_id',
        'mwc_id',
        'user_id',
        'amount',
        'period',
        'collection_date',
        'notes',
        'proof_image',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'collection_date'    => 'date',
            'amount'             => 'decimal:2',
            'penthong_id'        => 'integer',
            'periode_pentong_id' => 'integer',
        ];
    }

    /**
     * Auto generate invoice_code, period, dan kaitkan ke Penthong aktif
     */
    protected static function booted(): void
    {
        static::creating(function (KoinTransaction $tx) {
            if (empty($tx->collection_date)) {
                $tx->collection_date = now()->toDateString();
            }

            if (empty($tx->period)) {
                $tx->period = Carbon::parse($tx->collection_date)->format('Y-m');
            }

            // Sinkronisasi Periode Pentong Wajib
            $pId = $tx->penthong_id ?: $tx->periode_pentong_id;
            if (empty($pId)) {
                $activePenthong = Penthong::getActive($tx->mwc_id, $tx->ranting_id);
                if ($activePenthong) {
                    $pId = $activePenthong->id;
                }
            }

            if ($pId) {
                $tx->penthong_id = $pId;
                $tx->periode_pentong_id = $pId;
            }

            if (empty($tx->invoice_code)) {
                $datePrefix = Carbon::parse($tx->collection_date)->format('Ymd');
                do {
                    $randomSuffix = strtoupper(Str::random(6)) . mt_rand(10, 99);
                    $code = "TRX-{$datePrefix}-{$randomSuffix}";
                } while (static::where('invoice_code', $code)->exists());
                $tx->invoice_code = $code;
            }

            if (empty($tx->user_id)) {
                $tx->user_id = auth()->id() ?? User::first()?->id ?? 1;
            }
        });
    }

    /**
     * Periode Penthong tempat setoran koin tercatat
     */
    public function penthong(): BelongsTo
    {
        return $this->belongsTo(Penthong::class, 'penthong_id');
    }

    /**
     * Relasi resmi periode pentong (alias penthong)
     */
    public function periodePentong(): BelongsTo
    {
        return $this->belongsTo(Penthong::class, 'penthong_id');
    }

    public function getPeriodePentongIdAttribute()
    {
        return $this->attributes['periode_pentong_id'] ?? $this->attributes['penthong_id'] ?? null;
    }

    public function setPeriodePentongIdAttribute($value)
    {
        $this->attributes['periode_pentong_id'] = $value;
        $this->attributes['penthong_id'] = $value;
    }

    /**
     * Relawan koordinator yang menyerahkan koin
     */
    public function relawan(): BelongsTo
    {
        return $this->belongsTo(Relawan::class);
    }

    /**
     * Ranting asal setoran koin
     */
    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class);
    }

    /**
     * MWC tempat Ranting bernaung
     */
    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class);
    }

    /**
     * Admin/Operator yang menginput data setoran
     */
    public function operator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Format Rupiah
     */
    public function getFormattedAmountAttribute(): string
    {
        return 'Rp ' . number_format($this->amount, 0, ',', '.');
    }

    /**
     * Status badge AdminLTE
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

    public function scopeByMwc(Builder $query, ?int $mwcId): Builder
    {
        return $mwcId ? $query->where('mwc_id', $mwcId) : $query;
    }

    public function scopeByRanting(Builder $query, ?int $rantingId): Builder
    {
        return $rantingId ? $query->where('ranting_id', $rantingId) : $query;
    }

    public function scopeByRelawan(Builder $query, ?int $relawanId): Builder
    {
        return $relawanId ? $query->where('relawan_id', $relawanId) : $query;
    }
}
