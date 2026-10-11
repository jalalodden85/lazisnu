<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'mwc_id',
        'ranting_id',
        'title',
        'slug',
        'post_type',
        'excerpt',
        'content',
        'thumbnail_image',
        'views_count',
        'author_id',
        'status',
        'published_at',
    ];

    protected function casts(): array
    {
        return [
            'views_count' => 'integer',
            'published_at' => 'datetime',
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

    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class);
    }

    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'author_id');
    }
}
