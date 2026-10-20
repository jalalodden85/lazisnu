<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Campaign extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'kategori_judul',
        'mwc_id',
        'title',
        'slug',
        'short_description',
        'story_description',
        'target_amount',
        'collected_amount',
        'banner_image',
        'start_date',
        'end_date',
        'status',
        'is_featured',
        'is_emergency',
        'created_by',
    ];

    protected function casts(): array
    {
        return [
            'target_amount' => 'decimal:2',
            'collected_amount' => 'decimal:2',
            'start_date' => 'date',
            'end_date' => 'date',
            'is_featured' => 'boolean',
            'is_emergency' => 'boolean',
        ];
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(ProgramCategory::class, 'category_id');
    }

    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function donations(): HasMany
    {
        return $this->hasMany(OnlineDonation::class);
    }

    /**
     * Tampilan Kategori Judul (Fallback ke Kategori Pilar jika kosong)
     */
    public function getKategoriJudulDisplayAttribute(): string
    {
        return $this->kategori_judul ?: ($this->category?->name ?: $this->title);
    }

    /**
     * Hitung persentase capaian donasi
     */
    public function getProgressPercentageAttribute(): int
    {
        if ($this->target_amount <= 0) {
            return 100;
        }

        $percentage = ($this->collected_amount / $this->target_amount) * 100;
        return (int) min(100, round($percentage));
    }

    /**
     * Hitung sisa hari kampanye
     */
    public function getDaysLeftAttribute(): ?int
    {
        if (!$this->end_date) {
            return null; // Program Abadi / Berkelanjutan
        }

        $now = Carbon::now()->startOfDay();
        $end = Carbon::parse($this->end_date)->startOfDay();

        if ($now->greaterThan($end)) {
            return 0;
        }

        return (int) $now->diffInDays($end);
    }
}
