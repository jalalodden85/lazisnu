<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class KasMutasi extends Model
{
    use HasFactory;

    protected $table = 'kas_mutasis';

    protected $fillable = [
        'nomor_bukti',
        'periode_pentong_id',
        'penthong_id',
        'jenis',
        'kode_transaksi',
        'uraian',
        'nominal',
        'tanggal_transaksi',
        'tingkat',
        'ranting_id',
        'mwc_id',
        'user_id',
        'source_type',
        'source_id',
        'keterangan',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_transaksi' => 'date',
            'nominal'           => 'decimal:2',
        ];
    }

    public function penthong(): BelongsTo
    {
        return $this->belongsTo(Penthong::class, 'periode_pentong_id');
    }

    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class);
    }

    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function source(): MorphTo
    {
        return $this->morphTo();
    }

    public function scopeDebet(Builder $query): Builder
    {
        return $query->where('jenis', 'debet');
    }

    public function scopeKredit(Builder $query): Builder
    {
        return $query->where('jenis', 'kredit');
    }
}
