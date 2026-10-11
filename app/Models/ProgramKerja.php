<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProgramKerja extends Model
{
    use HasFactory;

    protected $table = 'program_kerjas';

    protected $fillable = [
        'nomor_proker',
        'nama_kegiatan',
        'pilar_program_id',
        'tingkat',
        'mwc_id',
        'ranting_id',
        'user_id',
        'tahun_anggaran',
        'rencana_anggaran',
        'deskripsi_kegiatan',
        'target_penerima_manfaat',
        'tanggal_mulai',
        'tanggal_selesai',
        'status',
        'approved_by',
        'approved_at',
        'catatan_approval',
    ];

    protected $casts = [
        'rencana_anggaran' => 'float',
        'tanggal_mulai'    => 'date',
        'tanggal_selesai'  => 'date',
        'approved_at'      => 'datetime',
    ];

    public function pilarProgram(): BelongsTo
    {
        return $this->belongsTo(PilarProgram::class, 'pilar_program_id');
    }

    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class, 'mwc_id');
    }

    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class, 'ranting_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function pentasyarufans(): HasMany
    {
        return $this->hasMany(Pentasyarufan::class, 'program_kerja_id');
    }

    /**
     * Total dana yang telah dicairkan untuk program kerja ini
     */
    public function getTotalPencairanAttribute(): float
    {
        return (float) $this->pentasyarufans()->sum('nominal_pencairan');
    }

    /**
     * Sisa anggaran rencana kerja (RAB) yang belum dicairkan
     */
    public function getSisaAnggaranAttribute(): float
    {
        return max(0, $this->rencana_anggaran - $this->total_pencairan);
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
     * Badge status Proker
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            'draft' => '<span class="badge badge-secondary"><i class="fas fa-pencil-alt mr-1"></i>Draft</span>',
            'menunggu_persetujuan' => '<span class="badge badge-warning text-dark"><i class="fas fa-clock mr-1"></i>Menunggu Persetujuan</span>',
            'disetujui' => '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Disetujui</span>',
            'ditolak' => '<span class="badge badge-danger"><i class="fas fa-times-circle mr-1"></i>Ditolak</span>',
            'selesai' => '<span class="badge badge-info"><i class="fas fa-flag-checkered mr-1"></i>Selesai</span>',
            default => '<span class="badge badge-light">Unknown</span>',
        };
    }
}
