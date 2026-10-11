<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PenthongDistribution extends Model
{
    use HasFactory;

    protected $fillable = [
        'penthong_id',
        'ranting_id',
        'mwc_id',
        'gross_amount',
        'operasional_amount',
        'net_amount',
        'ranting_amount',
        'mwc_amount',
        'pc_amount',
        'pw_amount',
        'pb_amount',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount'       => 'decimal:2',
            'operasional_amount' => 'decimal:2',
            'net_amount'         => 'decimal:2',
            'ranting_amount'     => 'decimal:2',
            'mwc_amount'         => 'decimal:2',
            'pc_amount'          => 'decimal:2',
            'pw_amount'          => 'decimal:2',
            'pb_amount'          => 'decimal:2',
        ];
    }

    public function getHakRantingAttribute()
    {
        return $this->ranting_amount;
    }

    public function getHakMwcAttribute()
    {
        return $this->mwc_amount;
    }

    public function getHakPcAttribute()
    {
        return $this->pc_amount;
    }

    public function getHakPwAttribute()
    {
        return $this->pw_amount;
    }

    public function getHakPbAttribute()
    {
        return $this->pb_amount;
    }

    public function getOperasionalAttribute()
    {
        return $this->operasional_amount;
    }

    public function getNetAttribute()
    {
        return $this->net_amount;
    }

    public function getGrossAttribute()
    {
        return $this->gross_amount;
    }

    public function penthong(): BelongsTo
    {
        return $this->belongsTo(Penthong::class);
    }

    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class);
    }

    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class);
    }
}
