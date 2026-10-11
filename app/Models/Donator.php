<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Donator extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'box_number',
        'name',
        'nik',
        'phone',
        'ranting_id',
        'relawan_id',
        'address',
        'rt',
        'rw',
        'status',
        'joined_date',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'joined_date' => 'date',
            'ranting_id'  => 'integer',
            'relawan_id'  => 'integer',
        ];
    }

    /**
     * Otomatis generate nomor kotak jika tidak diisi saat pembuatan donatur
     */
    protected static function booted(): void
    {
        static::creating(function (Donator $donator) {
            if (empty($donator->box_number) && !empty($donator->ranting_id)) {
                $donator->box_number = self::generateBoxNumber($donator->ranting_id, $donator->rt, $donator->rw);
            }
        });
    }

    /**
     * Cari nomor urut kotak tertinggi yang sudah digunakan pada ranting ini
     */
    public static function getNextSequenceNumber(int $rantingId): int
    {
        $boxes = self::where('ranting_id', $rantingId)
            ->whereNotNull('box_number')
            ->pluck('box_number');

        $maxSeq = 0;
        foreach ($boxes as $box) {
            $trimmed = trim((string) $box);
            if (empty($trimmed)) {
                continue;
            }

            // Pola format: KLG-kode-RTRW-XXX atau KLG-kode-XXX
            if (preg_match('/-(\d+)$/', $trimmed, $matches)) {
                $num = (int) $matches[1];
                if ($num > $maxSeq) {
                    $maxSeq = $num;
                }
            } elseif (ctype_digit($trimmed)) {
                $num = (int) $trimmed;
                if ($num > $maxSeq) {
                    $maxSeq = $num;
                }
            }
        }

        return $maxSeq + 1;
    }

    /**
     * Standarisasi dan susun format kode kotak infaq KOIN NU:
     * KLG-{kodeMWCkoderanting}-{RTRW}-{NO_URUT}
     * Contoh: KLG-M01R01-0102-003 atau KLG-M01R10-0102-005
     *
     * @param int $rantingId ID Ranting domisili kotak
     * @param string|null $rt Nomor RT
     * @param string|null $rw Nomor RW
     * @param mixed $seqInput Nomor urut spesifik atau teks kode utuh jika ada
     * @param int|null $excludeDonatorId Abaikan donatur ini saat cek keunikan (untuk edit)
     */
    public static function formatBoxNumber(int $rantingId, ?string $rt = null, ?string $rw = null, $seqInput = null, ?int $excludeDonatorId = null): string
    {
        $ranting = Ranting::with('mwc')->find($rantingId);
        if (!$ranting) {
            $ranting = Ranting::findOrFail($rantingId);
        }

        // Ambil kode MWC (contoh: M01)
        $mwc = $ranting->mwc;
        $mwcCode = $mwc ? strtoupper(trim($mwc->code)) : 'M01';
        if (!preg_match('/^M\d+/i', $mwcCode)) {
            $cleanMwc = preg_replace('/[^0-9]/', '', $mwcCode);
            $mwcCode = 'M' . str_pad($cleanMwc ?: '01', 2, '0', STR_PAD_LEFT);
        }

        // Susun kode MWC + Ranting (contoh: M01R01, M01R10, M09R17)
        $rawRantingCode = strtoupper(trim((string) $ranting->code));
        if (preg_match('/^' . preg_quote($mwcCode, '/') . 'R\d+/i', $rawRantingCode)) {
            $kodeMwcRanting = $rawRantingCode;
        } elseif (preg_match('/^R\d+/i', $rawRantingCode)) {
            $kodeMwcRanting = $mwcCode . $rawRantingCode;
        } elseif (is_numeric($rawRantingCode)) {
            $kodeMwcRanting = $mwcCode . 'R' . str_pad($rawRantingCode, 2, '0', STR_PAD_LEFT);
        } elseif (!empty($rawRantingCode)) {
            $kodeMwcRanting = str_starts_with($rawRantingCode, $mwcCode) ? $rawRantingCode : ($mwcCode . $rawRantingCode);
        } else {
            $kodeMwcRanting = $mwcCode . 'R' . str_pad((string)$ranting->id, 2, '0', STR_PAD_LEFT);
        }

        // Format RT & RW masing-masing 2 digit numerik (contoh: RT 1 RW 2 => 0102, RT 004 RW 002 => 0402)
        $cleanRt = preg_replace('/[^0-9]/', '', (string) $rt);
        $formattedRt = !empty($cleanRt) ? str_pad(substr($cleanRt, -2), 2, '0', STR_PAD_LEFT) : '00';

        $cleanRw = preg_replace('/[^0-9]/', '', (string) $rw);
        $formattedRw = !empty($cleanRw) ? str_pad(substr($cleanRw, -2), 2, '0', STR_PAD_LEFT) : '00';

        $rtrw = "{$formattedRt}{$formattedRw}";

        // Analisis nomor urut atau input yang diberikan
        $trimmedInput = trim((string) $seqInput);
        if (!empty($trimmedInput)) {
            // Jika sudah berformat lengkap KLG-{kodeMWCkoderanting}-{RTRW}-{NO_URUT}
            if (preg_match('/^KLG-[A-Z0-9]+-\d{4}-\d{3,}$/i', $trimmedInput)) {
                return strtoupper($trimmedInput);
            }

            // Jika berformat tanpa prefix KLG, misal M01R01-0102-003
            if (preg_match('/^[A-Z0-9]+-\d{4}-\d{3,}$/i', $trimmedInput)) {
                return 'KLG-' . strtoupper($trimmedInput);
            }

            // Jika berupa angka murni (seperti 112, 003, 3) atau teks seperti KLG-112, Kotak 3
            if (preg_match('/(\d+)$/', $trimmedInput, $matches)) {
                $cleanSeq = (int) $matches[1];
            } else {
                $cleanSeq = null;
            }
        } else {
            $cleanSeq = null;
        }

        if ($cleanSeq !== null && $cleanSeq > 0) {
            $seqNumber = $cleanSeq;
        } else {
            $seqNumber = self::getNextSequenceNumber($rantingId);
        }

        $formattedSeq = str_pad((string) $seqNumber, 3, '0', STR_PAD_LEFT);
        $boxNumber = "KLG-{$kodeMwcRanting}-{$rtrw}-{$formattedSeq}";

        // Pastikan kode unik jika ada collision nomor kotak
        while (
            self::where('box_number', $boxNumber)
                ->when($excludeDonatorId, fn($q) => $q->where('id', '!=', $excludeDonatorId))
                ->exists()
        ) {
            $seqNumber++;
            $formattedSeq = str_pad((string) $seqNumber, 3, '0', STR_PAD_LEFT);
            $boxNumber = "KLG-{$kodeMwcRanting}-{$rtrw}-{$formattedSeq}";
        }

        return $boxNumber;
    }

    /**
     * Generator nomor kotak infaq standar KOIN NU:
     * Format: KLG-{KODE_MWC}{KODE_RANTING}-{RT}{RW}-{NOMOR_URUT_DI_RANTING}
     * Contoh: KLG-M01R01-0102-003 atau KLG-M01R10-0102-005
     */
    public static function generateBoxNumber(int $rantingId, ?string $rt = null, ?string $rw = null): string
    {
        return self::formatBoxNumber($rantingId, $rt, $rw, null);
    }

    /**
     * Relasi ke Ranting / Desa domisili kotak infaq
     */
    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class, 'ranting_id');
    }

    /**
     * Relasi ke Petugas Relawan Lapangan Pengampu di wilayah tersebut
     */
    public function relawan(): BelongsTo
    {
        return $this->belongsTo(Relawan::class, 'relawan_id');
    }

    /**
     * Alias relasi petugas (kompatibilitas dengan relawan pengampu)
     */
    public function petugas(): BelongsTo
    {
        return $this->relawan();
    }

    /**
     * Accessor alamat lengkap format rapi
     */
    public function getFullAddressAttribute(): string
    {
        $parts = [$this->address];
        if ($this->rt || $this->rw) {
            $parts[] = "RT " . ($this->rt ?? '-') . " / RW " . ($this->rw ?? '-');
        }
        if ($this->ranting) {
            $parts[] = "Desa " . $this->ranting->name;
        }
        return implode(', ', $parts);
    }

    /**
     * Accessor Badge status untuk UI AdminLTE
     */
    public function getStatusBadgeAttribute(): string
    {
        return $this->status === self::STATUS_ACTIVE
            ? '<span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i>Aktif</span>'
            : '<span class="badge badge-secondary"><i class="fas fa-times-circle mr-1"></i>Nonaktif</span>';
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    public function scopeByRanting(Builder $query, ?int $rantingId): Builder
    {
        return $rantingId ? $query->where('ranting_id', $rantingId) : $query;
    }

    public function scopeByRelawan(Builder $query, ?int $relawanId): Builder
    {
        return $relawanId ? $query->where('relawan_id', $relawanId) : $query;
    }

    public function scopeByMwc(Builder $query, ?int $mwcId): Builder
    {
        if (!$mwcId) {
            return $query;
        }

        return $query->whereHas('ranting', function ($q) use ($mwcId) {
            $q->where('mwc_id', $mwcId);
        });
    }

    public function scopeSearch(Builder $query, ?string $keyword): Builder
    {
        if (empty($keyword)) {
            return $query;
        }

        return $query->where(function ($q) use ($keyword) {
            $q->where('name', 'like', "%{$keyword}%")
              ->orWhere('box_number', 'like', "%{$keyword}%")
              ->orWhere('nik', 'like', "%{$keyword}%")
              ->orWhere('phone', 'like', "%{$keyword}%")
              ->orWhere('address', 'like', "%{$keyword}%");
        });
    }
}
