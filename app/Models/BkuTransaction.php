<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BkuTransaction extends Model
{
    use HasFactory;

    protected $table = 'bku_transactions';

    protected $fillable = [
        'nomor_transaksi',
        'periode_pentong_id',
        'jenis',
        'kategori',
        'kode_transaksi',
        'tanggal',
        'nominal',
        'penerima_vendor',
        'uraian',
        'bukti_kwitansi',
        'tingkat',
        'mwc_id',
        'ranting_id',
        'user_id',
    ];

    protected function casts(): array
    {
        return [
            'tanggal' => 'date',
            'nominal' => 'decimal:2',
        ];
    }

    public function penthong(): BelongsTo
    {
        return $this->belongsTo(Penthong::class, 'periode_pentong_id');
    }

    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class);
    }

    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopePengeluaran(Builder $query): Builder
    {
        return $query->where('jenis', 'pengeluaran');
    }

    public function scopePemasukan(Builder $query): Builder
    {
        return $query->where('jenis', 'pemasukan');
    }
}
