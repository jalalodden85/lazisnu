<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class OnlineDonation extends Model
{
    use HasFactory;

    protected $fillable = [
        'donation_code',
        'campaign_id',
        'kategori',
        'donor_name',
        'donor_phone',
        'donor_email',
        'alamat',
        'is_anonymous',
        'amount',
        'unique_code',
        'total_amount',
        'nominal_terverifikasi',
        'payment_method',
        'qris_url',
        'payment_gateway_payload',
        'payment_status',
        'prayer_message',
        'payment_proof',
        'verified_at',
        'verified_by',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'nominal_terverifikasi' => 'decimal:2',
            'is_anonymous' => 'boolean',
            'verified_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (OnlineDonation $donation) {
            if (empty($donation->donation_code)) {
                $donation->donation_code = 'DON-' . date('Ymd') . '-' . strtoupper(Str::random(5));
            }
        });
    }

    public function campaign(): BelongsTo
    {
        return $this->belongsTo(Campaign::class);
    }

    public function verifier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'verified_by');
    }

    public function getDisplayNameAttribute(): string
    {
        return $this->is_anonymous ? 'Hamba Allah' : $this->donor_name;
    }

    /**
     * Sanitasi Alamat Publik:
     * Menyaring nomor rumah, blok, RT/RW, dan hanya menampilkan Desa/Kecamatan/Kabupaten
     */
    public function getPublicAlamatAttribute(): string
    {
        if (empty($this->alamat)) {
            return 'Lamongan';
        }

        $raw = $this->alamat;

        // Bersihkan pola RT/RW (contoh: RT 01 RW 02, RT.01/02, RT/RW 03)
        $clean = preg_replace('/(rt\s*\.?\s*\d+|rw\s*\.?\s*\d+|rt\s*\/\s*rw\s*\d+(\/\d+)?)/i', '', $raw);

        // Bersihkan no rumah / blok / gang (contoh: No. 12, Gang Mawar, Blok B3, Jl. Veteran No 5)
        $clean = preg_replace('/(no\.\s*\d+[a-zA-Z]?|jl\.\s*[^,]+|jalan\s*[^,]+|gang\s*[^,]+|blok\s*[^,]+)/i', '', $clean);

        // Hapus koma ganda atau spasi berlebih
        $clean = preg_replace('/\s+,/', ',', $clean);
        $clean = preg_replace('/,{2,}/', ',', $clean);
        $clean = preg_replace('/,\s*/', ', ', $clean);
        $clean = preg_replace('/\s{2,}/', ' ', $clean);
        $clean = trim(trim($clean), ', -');

        return !empty($clean) ? $clean : 'Kab. Lamongan';
    }

    /**
     * Label Kategori Donasi (Prioritas: Kategori Judul Kampanye -> Kolom Kategori -> Kategori Pilar)
     */
    public function getKategoriLabelAttribute(): string
    {
        if (!empty($this->campaign?->kategori_judul)) {
            return $this->campaign->kategori_judul;
        }

        if (!empty($this->kategori)) {
            return $this->kategori;
        }

        return $this->campaign?->category?->name ?? 'Infaq';
    }

    /**
     * Alias accessor untuk kode_donasi
     */
    public function getKodeDonasiAttribute(): string
    {
        return $this->donation_code;
    }

    /**
     * Scope query berdasarkan kode donasi
     */
    public function scopeWhereKodeDonasi($query, string $code)
    {
        return $query->where('donation_code', $code);
    }
}
