<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Penthong extends Model
{
    use HasFactory;

    public const STATUS_AKTIF = 'aktif';
    public const STATUS_TUTUP = 'tutup';
    public const STATUS_OPEN = 'aktif';
    public const STATUS_CLOSED = 'tutup';

    protected $fillable = [
        'nama_periode',
        'bulan',
        'tahun',
        'status',
        'is_active',
        'mwc_id',
        'ranting_id',
        'start_date',
        'end_date',
        'total_collected',
        'operasional_amount',
        'net_amount',
        'ranting_amount',
        'mwc_amount',
        'pc_amount',
        'pw_amount',
        'pb_amount',
        'distribution_setting_id',
        'closed_at',
        'closed_by',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'bulan'              => 'integer',
            'tahun'              => 'integer',
            'is_active'          => 'boolean',
            'start_date'         => 'date',
            'end_date'           => 'date',
            'closed_at'          => 'datetime',
            'total_collected'    => 'decimal:2',
            'operasional_amount' => 'decimal:2',
            'net_amount'         => 'decimal:2',
            'ranting_amount'     => 'decimal:2',
            'mwc_amount'         => 'decimal:2',
            'pc_amount'          => 'decimal:2',
            'pw_amount'          => 'decimal:2',
            'pb_amount'          => 'decimal:2',
        ];
    }

    /**
     * Nama Bulan Bahasa Indonesia
     */
    public function getBulanNamaAttribute(): string
    {
        $bulanMap = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];

        return $bulanMap[$this->bulan] ?? Carbon::create()->month($this->bulan)->translatedFormat('F');
    }

    protected static function booted(): void
    {
        static::saving(function (Penthong $penthong) {
            if ($penthong->isDirty('status')) {
                if (in_array($penthong->status, [self::STATUS_TUTUP, 'closed'])) {
                    $penthong->status = self::STATUS_TUTUP;
                    $penthong->is_active = false;
                    return;
                }
                if (in_array($penthong->status, [self::STATUS_AKTIF, 'open'])) {
                    $penthong->status = self::STATUS_AKTIF;
                    $penthong->is_active = true;
                    return;
                }
            }

            if ($penthong->is_active) {
                $penthong->status = self::STATUS_AKTIF;
            } else {
                $penthong->status = self::STATUS_TUTUP;
            }
        });
    }

    /**
     * Badge Status AdminLTE
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->isOpen()
            ? '<span class="badge badge-success px-2 py-1"><i class="fas fa-box-open mr-1"></i>Periode Aktif (Open)</span>'
            : '<span class="badge badge-secondary px-2 py-1"><i class="fas fa-lock mr-1"></i>Tutup (Closed)</span>';
    }

    public function isOpen(): bool
    {
        return (bool) $this->is_active || in_array($this->status, [self::STATUS_AKTIF, self::STATUS_OPEN]);
    }

    public function isClosed(): bool
    {
        return !$this->isOpen();
    }

    public function isAktif(): bool
    {
        return $this->isOpen();
    }

    public function isTutup(): bool
    {
        return $this->isClosed();
    }

    /**
     * Transaksi yang terikat pada Penthong ini
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(KoinTransaction::class, 'penthong_id');
    }

    /**
     * Alias untuk setoran koin
     */
    public function setoranKoin(): HasMany
    {
        return $this->hasMany(KoinTransaction::class, 'penthong_id');
    }

    /**
     * Rincian alokasi bagi hasil per Ranting
     */
    public function distributions(): HasMany
    {
        return $this->hasMany(PenthongDistribution::class, 'penthong_id');
    }

    /**
     * Seluruh realisasi pentasyarufan yang bersumber dari periode penthong ini
     */
    public function pentasyarufans(): HasMany
    {
        return $this->hasMany(Pentasyarufan::class, 'penthong_id');
    }

    /**
     * MWC Induk (jika scope wilayah)
     */
    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class);
    }

    /**
     * Ranting (jika scope wilayah ranting spesifik)
     */
    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class);
    }

    /**
     * Petugas/Admin yang menutup penthong
     */
    public function closer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'closed_by');
    }

    /**
     * Skema pembagian persentase (Tasharruf) yang mengikat periode penthong ini
     */
    public function distributionSetting(): BelongsTo
    {
        return $this->belongsTo(DistributionSetting::class, 'distribution_setting_id');
    }

    /**
     * Dapatkan objek DistributionSetting untuk penthong ini (dengan logika proteksi Grandfathering)
     */
    public function getDistributionSetting(): DistributionSetting
    {
        return DistributionSetting::getForPenthong($this);
    }

    /**
     * Scope Penthong Aktif / Open
     */
    public function scopeAktif(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_active', true)
              ->orWhereIn('status', [self::STATUS_AKTIF, self::STATUS_OPEN]);
        });
    }

    public function scopeOpen(Builder $query): Builder
    {
        return $this->scopeAktif($query);
    }

    /**
     * Scope Penthong Tutup / Closed
     */
    public function scopeTutup(Builder $query): Builder
    {
        return $query->where(function ($q) {
            $q->where('is_active', false)
              ->orWhereIn('status', [self::STATUS_TUTUP, self::STATUS_CLOSED]);
        });
    }

    public function scopeClosed(Builder $query): Builder
    {
        return $this->scopeTutup($query);
    }

    /**
     * Dapatkan Penthong aktif saat ini untuk scope tertentu
     */
    public static function getActive(?int $mwcId = null, ?int $rantingId = null): ?self
    {
        $query = static::aktif();

        if ($rantingId) {
            $query->where(function ($q) use ($rantingId) {
                $q->where('ranting_id', $rantingId)->orWhereNull('ranting_id');
            });
        } elseif ($mwcId) {
            $query->where(function ($q) use ($mwcId) {
                $q->where('mwc_id', $mwcId)->orWhereNull('mwc_id');
            });
        }

        return $query->latest('id')->first();
    }
}
