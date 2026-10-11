<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KontribusiStruktur extends Model
{
    use HasFactory;

    protected $table = 'kontribusi_strukturs';

    protected $fillable = [
        'nomor_transaksi',
        'periode_pentong_id',
        'penthong_id',
        'ranting_id',
        'mwc_id',
        'user_id',
        'tanggal_setor',
        'nominal_bruto',
        'persentase_operasional',
        'nominal_operasional',
        'nominal_netto',
        'nominal_mwc',
        'nominal_pc',
        'nominal_pw',
        'nominal_pb',
        'total_disetor',
        'persentase_setting_json',
        'tujuan_struktur',
        'tujuan_setoran',
        'metode_penyaluran',
        'nama_bank',
        'nomor_rekening',
        'bukti_transfer',
        'catatan',
        'status',
        'verified_by',
        'verified_at',
        'catatan_verifikasi',
    ];

    protected function casts(): array
    {
        return [
            'tanggal_setor'          => 'date',
            'verified_at'            => 'datetime',
            'nominal_bruto'          => 'decimal:2',
            'persentase_operasional' => 'decimal:2',
            'nominal_operasional'    => 'decimal:2',
            'nominal_netto'          => 'decimal:2',
            'nominal_mwc'            => 'decimal:2',
            'nominal_pc'             => 'decimal:2',
            'nominal_pw'             => 'decimal:2',
            'nominal_pb'             => 'decimal:2',
            'total_disetor'          => 'decimal:2',
            'persentase_setting_json'=> 'array',
        ];
    }

    public function penthong(): BelongsTo
    {
        return $this->belongsTo(Penthong::class, 'periode_pentong_id');
    }

    public function periodePentong(): BelongsTo
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

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    /**
     * Scope filter per Ranting (Data Scoping)
     */
    public function scopeForRanting(Builder $query, int $rantingId): Builder
    {
        return $query->where('ranting_id', $rantingId);
    }

    /**
     * Scope filter per MWC
     */
    public function scopeForMwc(Builder $query, int $mwcId): Builder
    {
        return $query->where('mwc_id', $mwcId);
    }

    /**
     * Scope filter per Periode Pentong
     */
    public function scopeForPeriode(Builder $query, int $periodePentongId): Builder
    {
        return $query->where(function ($q) use ($periodePentongId) {
            $q->where('periode_pentong_id', $periodePentongId)
              ->orWhere('penthong_id', $periodePentongId);
        });
    }

    /**
     * Scope hanya transaksi sah/disetor
     */
    public function scopeValidDisetor(Builder $query): Builder
    {
        return $query->whereIn('status', ['disetor', 'diterima']);
    }

    /**
     * Scope filter tujuan setoran (MWC, PC, atau SEMUA)
     */
    public function scopeForTujuan(Builder $query, string $tujuan): Builder
    {
        $upper = strtoupper($tujuan);
        $lower = strtolower($tujuan);
        return $query->where(function ($q) use ($upper, $lower) {
            $q->where('tujuan_setoran', $upper)
              ->orWhere('tujuan_setoran', 'SEMUA')
              ->orWhere('tujuan_struktur', $lower)
              ->orWhere('tujuan_struktur', 'semua');
        });
    }

    /**
     * Accessor tujuan setoran
     */
    public function getTujuanSetoranAttribute(?string $value): string
    {
        return $value ?: strtoupper($this->attributes['tujuan_struktur'] ?? 'SEMUA');
    }

    /**
     * Generate Nomor Transaksi Otomatis
     */
    public static function generateNomorTransaksi(?int $rantingId = null): string
    {
        $prefix = 'KTR-' . date('Ym') . '-';
        $count = static::whereYear('created_at', date('Y'))
            ->whereMonth('created_at', date('m'))
            ->count() + 1;

        return $prefix . str_pad((string) $count, 4, '0', STR_PAD_LEFT);
    }
}
