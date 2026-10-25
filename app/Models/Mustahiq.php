<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Mustahiq extends Model
{
    use HasFactory;

    protected $table = 'mustahiqs';

    protected $fillable = [
        'nik',
        'nama',
        'jenis_kelamin',
        'telepon',
        'alamat',
        'ranting_id',
        'mwc_id',
        'asnaf',
        'kondisi_spesifik',
        'pekerjaan',
        'penghasilan_rata_rata',
        'jumlah_tanggungan',
        'status',
        'alasan_tidak_aktif',
        'foto_kondisi',
        'catatan_verifikasi',
        'created_by',
    ];

    protected $casts = [
        'penghasilan_rata_rata' => 'float',
        'jumlah_tanggungan'     => 'integer',
        'created_at'            => 'datetime',
        'updated_at'            => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (Mustahiq $mustahiq) {
            if (!empty($mustahiq->telepon)) {
                $mustahiq->telepon = \App\Services\WhatsAppHelper::formatNumber($mustahiq->telepon);
            }
        });
    }

    /**
     * Mutator untuk memastikan nomor telepon/WA mustahiq berformat standar 62...
     */
    public function setTeleponAttribute($value): void
    {
        $this->attributes['telepon'] = \App\Services\WhatsAppHelper::formatNumber($value);
    }

    /**
     * Relasi ke Ranting NU (Tingkat Desa)
     */
    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class, 'ranting_id');
    }

    /**
     * Relasi ke MWC NU (Tingkat Kecamatan)
     */
    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class, 'mwc_id');
    }

    /**
     * Petugas Lapangan / User yang mendaftarkan data mustahiq
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * Relasi Riwayat Bantuan Pentasyarufan (Many-to-Many via pivot pentasyarufan_mustahiq)
     */
    public function pentasyarufans(): BelongsToMany
    {
        return $this->belongsToMany(Pentasyarufan::class, 'pentasyarufan_mustahiq')
            ->withPivot('nominal_bantuan', 'keterangan')
            ->withTimestamps()
            ->orderByPivot('created_at', 'desc');
    }

    /**
     * Relasi Pentasyarufan 1-on-1 (Tunggal)
     */
    public function directPentasyarufans(): HasMany
    {
        return $this->hasMany(Pentasyarufan::class, 'mustahiq_id')
            ->orderBy('tanggal_penyaluran', 'desc');
    }

    /**
     * Scope Multi-tenancy / Isolasi Data Berdasarkan Hierarki Pengguna
     * - Admin Ranting: Hanya melihat mustahiq di rantingnya
     * - Admin MWC: Melihat mustahiq di seluruh ranting kecamatannya
     * - Admin PC / Super Admin: Melihat seluruh mustahiq se-kabupaten
     */
    public function scopeForUser(Builder $query, ?User $user = null): Builder
    {
        $user = $user ?? auth()->user();

        if (!$user) {
            return $query->whereRaw('1 = 0'); // Tolak jika unauthenticated
        }

        // 1. Level PC / Super Admin -> Akses Global (Seluruh Kabupaten)
        if ($user->isSuperAdmin() || $user->isPc()) {
            return $query;
        }

        // 2. Level MWC -> Dibatasi pada 1 Kecamatan (Seluruh Ranting bernaung)
        if ($user->isMwc() && $user->mwc_id) {
            return $query->where('mwc_id', $user->mwc_id);
        }

        // 3. Level Ranting -> Isolasi Ketat Hanya di Desanya Sendiri
        if ($user->isRanting() && $user->ranting_id) {
            return $query->where('ranting_id', $user->ranting_id);
        }

        return $query->whereRaw('1 = 0');
    }

    /**
     * Scope Mustahiq Aktif (Masih berhak menerima bantuan)
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'aktif');
    }

    /**
     * Scope Pencarian Teks (Nama, NIK, Dusun / Alamat)
     */
    public function scopeSearch(Builder $query, string $term): Builder
    {
        $term = trim($term);
        return $query->where(function (Builder $q) use ($term) {
            $q->where('nama', 'LIKE', "%{$term}%")
              ->orWhere('nik', 'LIKE', "%{$term}%")
              ->orWhere('alamat', 'LIKE', "%{$term}%")
              ->orWhere('kondisi_spesifik', 'LIKE', "%{$term}%");
        });
    }

    /**
     * Label Asnaf Resmi
     */
    public function getAsnafLabelAttribute(): string
    {
        return match ($this->asnaf) {
            'fakir'        => 'Fakir',
            'miskin'       => 'Miskin',
            'amil'         => 'Amil',
            'muallaf'      => 'Muallaf',
            'riqab'        => 'Riqab (Memerdekakan Budak)',
            'gharim'       => 'Gharim (Terlilit Hutang)',
            'fisabilillah' => 'Fisabilillah',
            'ibnu_sabil'   => 'Ibnu Sabil (Musafir Kehabisan Bekal)',
            default        => ucfirst($this->asnaf),
        };
    }

    /**
     * Badge UI Kategori Asnaf
     */
    public function getAsnafBadgeAttribute(): string
    {
        $color = match ($this->asnaf) {
            'fakir'        => 'danger',
            'miskin'       => 'warning',
            'muallaf'      => 'info',
            'gharim'       => 'secondary',
            'fisabilillah' => 'success',
            'ibnu_sabil'   => 'primary',
            default        => 'dark',
        };

        return "<span class=\"badge badge-{$color} px-2 py-1\"><i class=\"fas fa-hand-holding-heart mr-1\"></i>{$this->asnaf_label}</span>";
    }

    /**
     * Badge Status Kelayakan
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->status === 'aktif'
            ? '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Aktif Layak Bantu</span>'
            : '<span class="badge badge-secondary"><i class="fas fa-times-circle mr-1"></i>Tidak Aktif / Mandiri</span>';
    }

    /**
     * Menghitung total akumulasi bantuan yang pernah disalurkan ke Mustahiq ini
     */
    public function getTotalBantuanAttribute(): float
    {
        $fromPivot = (float) $this->pentasyarufans()->sum('pentasyarufan_mustahiq.nominal_bantuan');
        $fromDirect = (float) $this->directPentasyarufans()->sum('nominal_pencairan');

        return max($fromPivot, $fromDirect);
    }
}
