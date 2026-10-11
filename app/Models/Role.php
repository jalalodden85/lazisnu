<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Role extends Model
{
    use HasFactory;

    public const PC_SUPERADMIN = 'pc_superadmin';
    public const PC_KETUA = 'pc_ketua';
    public const PC_SEKRETARIS = 'pc_sekretaris';
    public const PC_BENDAHARA = 'pc_bendahara';
    public const PC_ADMIN_KOIN = 'pc_admin_koin';
    public const PC_ADMIN_ONLINE = 'pc_admin_online';
    public const PC = 'pc';
    public const MWC = 'mwc';
    public const RANTING = 'ranting';

    protected $fillable = [
        'name',
        'label',
        'description',
    ];

    /**
     * Relasi ke seluruh user dengan role ini
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
