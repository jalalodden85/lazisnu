<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class BkuOperasional extends Model
{
    use HasFactory;

    protected $table = 'bku_operasionals';

    protected $fillable = [
        'nomor_transaksi',
        'periode_pentong_id',
        'kategori_operasional_id',
        'jenis',
        'sumber_dana',
        'nominal',
        'tanggal_transaksi',
        'uraian',
        'penerima_atau_vendor',
        'bukti_transaksi',
        'tingkat',
        'mwc_id',
        'ranting_id',
        'created_by',
    ];

    protected $casts = [
        'nominal'           => 'float',
        'tanggal_transaksi' => 'date',
    ];

    public function penthong(): BelongsTo
    {
        return $this->belongsTo(Penthong::class, 'periode_pentong_id');
    }

    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriOperasional::class, 'kategori_operasional_id');
    }

    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class, 'ranting_id');
    }

    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class, 'mwc_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Scope Multi-Tenancy Sesuai Hak Akses Pengguna Login (Terisolasi per Tingkat Secara Mandiri)
     */
    public function scopeForUser(Builder $query, ?User $user = null, ?string $tingkat = null): Builder
    {
        $user = $user ?? auth()->user();
        if (!$user) {
            return $query->whereRaw('1 = 0');
        }

        // 1. Admin Ranting: Hanya melihat BKU Operasional milik Ranting desanya sendiri
        if ($user->isRanting() && $user->ranting_id) {
            return $query->where('tingkat', 'ranting')->where('ranting_id', $user->ranting_id);
        }

        // 2. Admin MWC (Kecamatan): Default ke tingkat 'mwc' agar tidak tercampur dengan BKU Ranting
        if ($user->isMwc() && $user->mwc_id) {
            $query->where('mwc_id', $user->mwc_id);
            if ($tingkat) {
                return $tingkat === 'semua' ? $query : $query->where('tingkat', $tingkat);
            }
            return $query->where('tingkat', 'mwc');
        }

        // 3. Admin PC (Cabang): Default ke tingkat 'pc' agar dana operasional cabang mandiri
        if ($user->isSuperAdmin() || $user->isPc()) {
            if ($tingkat) {
                return $tingkat === 'semua' ? $query : $query->where('tingkat', $tingkat);
            }
            return $query->where('tingkat', 'pc');
        }

        return $query->whereRaw('1 = 0');
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
