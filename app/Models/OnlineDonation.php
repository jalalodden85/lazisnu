<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OnlineDonation extends Model
{
    use HasFactory;

    protected $fillable = [
        'donation_code',
        'campaign_id',
        'donor_name',
        'donor_phone',
        'donor_email',
        'is_anonymous',
        'amount',
        'unique_code',
        'total_amount',
        'nominal_terverifikasi',
        'payment_method',
        'payment_status',
        'prayer_message',
        'payment_proof',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'nominal_terverifikasi' => 'decimal:2',
            'is_anonymous' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OnlineDonation $donation) {
            if (empty($donation->donation_code)) {
                $donation->donation_code = 'DON-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->is_anonymous ? 'Hamba Allah' : $this->donor_name;
    }

    /**
     * Alias accessor untuk kode_donasi
     */
    public function getKodeDonasiAttribute(): string
    {
        return $this->donation_code;
    }

    /**
     * Scope query berdasarkan kode donasi
     */
    public function scopeWhereKodeDonasi($query, string $code)
    {
        return $query->where('donation_code', $code);
    }
}
