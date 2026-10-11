<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;

class Mwc extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'name',
        'kecamatan',
        'address',
        'phone',
    ];

    /**
     * Ranting-ranting (desa) yang berada di bawah naungan MWC ini
     */
    public function rantings(): HasMany
    {
        return $this->hasMany(Ranting::class);
    }

    /**
     * Relawan yang bertugas di seluruh ranting di bawah MWC ini (hasManyThrough)
     */
    public function relawans(): HasManyThrough
    {
        return $this->hasManyThrough(Relawan::class, Ranting::class);
    }

    /**
     * Donatur (pemegang kotak koin) di seluruh ranting di bawah MWC ini (hasManyThrough)
     */
    public function donators(): HasManyThrough
    {
        return $this->hasManyThrough(Donator::class, Ranting::class);
    }

    /**
     * Seluruh transaksi koin infaq di wilayah MWC ini
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(KoinTransaction::class, 'mwc_id');
    }

    /**
     * Akun pengurus/admin pada MWC ini
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
