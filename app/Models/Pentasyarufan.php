<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Pentasyarufan extends Model
{
    use HasFactory;

    protected $table = 'pentasyarufans';

    protected $fillable = [
        'nomor_transaksi',
        'penthong_id',
        'periode_pentong_id',
        'program_kerja_id',
        'pilar_program_id',
        'mustahiq_id',
        'user_id',
        'tingkat',
        'mwc_id',
        'ranting_id',
        'nominal_pencairan',
        'potongan_operasional_pct',
        'tanggal_penyaluran',
        'penerima_manfaat',
        'jumlah_penerima',
        'keterangan',
        'bukti_penyaluran',
        'berkas_spj',
        'spj_status',
        'spj_verified_by',
        'spj_verified_at',
        'spj_notes',
    ];

    protected $casts = [
        'nominal_pencairan'        => 'float',
        'potongan_operasional_pct' => 'float',
        'tanggal_penyaluran'       => 'date',
        'spj_verified_at'          => 'datetime',
        'jumlah_penerima'          => 'integer',
    ];

    public function penthong(): BelongsTo
    {
        return $this->belongsTo(Penthong::class, 'penthong_id');
    }

    public function periodePentong(): BelongsTo
    {
        return $this->belongsTo(Penthong::class, 'penthong_id');
    }

    public function programKerja(): BelongsTo
    {
        return $this->belongsTo(ProgramKerja::class, 'program_kerja_id');
    }

    public function pilarProgram(): BelongsTo
    {
        return $this->belongsTo(PilarProgram::class, 'pilar_program_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class, 'mwc_id');
    }

    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class, 'ranting_id');
    }

    public function spjVerifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'spj_verified_by');
    }

    /**
     * Relasi ke penerima manfaat / Mustahiq (Banyak Mustahiq per transaksi)
     */
    public function mustahiqs(): BelongsToMany
    {
        return $this->belongsToMany(Mustahiq::class, 'pentasyarufan_mustahiq')
            ->withPivot('nominal_bantuan', 'keterangan')
            ->withTimestamps();
    }

    /**
     * Relasi ke Mustahiq tunggal 1-on-1
     */
    public function mustahiq(): BelongsTo
    {
        return $this->belongsTo(Mustahiq::class, 'mustahiq_id');
    }

    /**
     * Label Tingkatan Wilayah
     */
    public function getWilayahLabelAttribute(): string
    {
        if ($this->tingkat === 'pc') {
            return 'PC LAZISNU Lamongan';
        }
        if ($this->tingkat === 'mwc') {
            return 'MWC ' . ($this->mwc?->name ?? 'Kecamatan');
        }
        return 'Ranting ' . ($this->ranting?->name ?? 'Desa');
    }

    /**
     * Badge status SPJ
     */
    public function getSpjBadgeAttribute(): string
    {
        return match ($this->spj_status) {
            'belum_spj' => '<span class="badge badge-secondary"><i class="fas fa-file-upload mr-1"></i>Belum Upload SPJ</span>',
            'menunggu_verifikasi' => '<span class="badge badge-warning text-dark"><i class="fas fa-hourglass-half mr-1"></i>SPJ Menunggu Verifikasi</span>',
            'diterima' => '<span class="badge badge-success"><i class="fas fa-check-double mr-1"></i>SPJ Terverifikasi / Sah</span>',
            'ditolak' => '<span class="badge badge-danger"><i class="fas fa-exclamation-circle mr-1"></i>SPJ Ditolak / Perlu Revisi</span>',
            default => '<span class="badge badge-light">-</span>',
        };
    }
}
