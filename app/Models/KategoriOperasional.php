<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KategoriOperasional extends Model
{
    use HasFactory;

    protected $table = 'kategori_operasionals';

    protected $fillable = [
        'kode',
        'nama_kategori',
        'deskripsi',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function mutasis(): HasMany
    {
        return $this->hasMany(BkuOperasional::class, 'kategori_operasional_id');
    }
}
