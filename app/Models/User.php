<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'phone',
        'role_id',
        'mwc_id',
        'ranting_id',
        'is_active',
        'password',
        'avatar',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
            'mwc_id' => 'integer',
            'ranting_id' => 'integer',
        ];
    }

    /**
     * Role user (PC, MWC, Ranting)
     */
    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    /**
     * MWC tempat user bernaung (jika user MWC atau Ranting)
     */
    public function mwc(): BelongsTo
    {
        return $this->belongsTo(Mwc::class);
    }

    /**
     * Ranting tempat user bernaung (jika user Admin Ranting)
     */
    public function ranting(): BelongsTo
    {
        return $this->belongsTo(Ranting::class);
    }

    /**
     * Transaksi yang diinput oleh user ini
     */
    public function transactions(): HasMany
    {
        return $this->hasMany(KoinTransaction::class, 'user_id');
    }

    /**
     * Cache memori daftar menu user saat lifecycle request berjalan
     */
    protected ?\Illuminate\Support\Collection $cachedMenusCollection = null;

    /**
     * Hak akses menu yang diizinkan untuk user ini
     */
    public function menus(): BelongsToMany
    {
        return $this->belongsToMany(Menu::class, 'user_has_menus')->withTimestamps();
    }

    /**
     * Mendapatkan collection menu yang dimiliki user (dengan in-memory caching)
     */
    public function getAssignedMenus(): \Illuminate\Support\Collection
    {
        if ($this->cachedMenusCollection === null) {
            $this->cachedMenusCollection = $this->menus()->get();
        }
        return $this->cachedMenusCollection;
    }

    /**
     * Memeriksa apakah user memiliki hak akses ke menu tertentu
     */
    public function hasMenuAccess(string $routeNameOrPattern): bool
    {
        // 1. Super Admin selalu memiliki akses absolut ke seluruh menu
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Modul Hasil Koin dan BKU dapat diakses oleh semua tingkatan (PC, MWC, Ranting)
        if (in_array($routeNameOrPattern, ['pentasyarufan.hasil-koin.index', 'bku.index', 'bku-operasional.index'], true)) {
            return true;
        }

        // 2. Cek apakah route_name atau url_pattern cocok dengan menu yang di-assign
        return $this->getAssignedMenus()->contains(function ($menu) use ($routeNameOrPattern) {
            if ($menu->route_name === $routeNameOrPattern) {
                return true;
            }
            if ($menu->url_pattern) {
                $pattern = str_replace('*', '.*', preg_quote($menu->url_pattern, '/'));
                if (preg_match('/^' . $pattern . '$/i', $routeNameOrPattern)) {
                    return true;
                }
            }
            return false;
        });
    }

    /**
     * Memeriksa apakah user memiliki hak akses ke minimal 1 menu dalam kategori tertentu
     */
    public function hasCategoryAccess(string $category): bool
    {
        if ($this->isSuperAdmin()) {
            return true;
        }

        // Kategori Pentasyarufan & Penyaluran selalu terbuka untuk seluruh tingkatan (PC, MWC, Ranting)
        if ($category === 'Pentasyarufan & Penyaluran') {
            return true;
        }

        return $this->getAssignedMenus()->contains('category', $category);
    }

    /**
     * Helper Roles
     */
    public function isPc(): bool
    {
        return in_array($this->role?->name, [
            Role::PC, 
            Role::PC_SUPERADMIN, 
            Role::PC_KETUA, 
            Role::PC_SEKRETARIS,
            Role::PC_BENDAHARA, 
            Role::PC_ADMIN_KOIN, 
            Role::PC_ADMIN_ONLINE, 
            'admin_pc', 
            'superadmin'
        ], true);
    }

    public function isSuperAdmin(): bool
    {
        return in_array($this->role?->name, [Role::PC_SUPERADMIN, Role::PC, 'superadmin'], true);
    }

    public function isKetua(): bool
    {
        return $this->role?->name === Role::PC_KETUA;
    }

    public function isSekretaris(): bool
    {
        return $this->role?->name === Role::PC_SEKRETARIS;
    }

    public function isBendahara(): bool
    {
        return $this->role?->name === Role::PC_BENDAHARA;
    }

    public function isAdminKoin(): bool
    {
        return $this->role?->name === Role::PC_ADMIN_KOIN;
    }

    public function isAdminOnline(): bool
    {
        return $this->role?->name === Role::PC_ADMIN_ONLINE;
    }

    public function isMwc(): bool
    {
        return in_array($this->role?->name, [Role::MWC, 'admin_mwc'], true);
    }

    public function isRanting(): bool
    {
        return in_array($this->role?->name, [Role::RANTING, 'admin_ranting'], true);
    }

    public function getWilayahNameAttribute(): string
    {
        if ($this->isPc()) {
            return 'Pimpinan Cabang (Seluruh Wilayah)';
        }
        if ($this->isMwc()) {
            return $this->mwc ? "MWC {$this->mwc->name} ({$this->mwc->code})" : '-';
        }
        if ($this->isRanting()) {
            return $this->ranting ? "{$this->ranting->name} ({$this->ranting->code})" : '-';
        }
        return '-';
    }

    public function getRoleDisplayNameAttribute(): string
    {
        return match ($this->role?->name) {
            Role::PC_SUPERADMIN, Role::PC => 'Super Admin PC',
            Role::PC_KETUA => 'Ketua LAZISNU (Eksekutif)',
            Role::PC_SEKRETARIS => 'Sekretaris PC (Administrasi & Pelaporan)',
            Role::PC_BENDAHARA => 'Bendahara / Keuangan PC',
            Role::PC_ADMIN_KOIN => 'Admin Program KOIN NU',
            Role::PC_ADMIN_ONLINE => 'Admin Donasi Online & Website',
            Role::MWC => 'MWC Lazisnu (Kecamatan)',
            Role::RANTING => 'Ranting (Desa)',
            default => $this->role?->label ?? 'Pengguna',
        };
    }

    public function getRoleBadgeAttribute(): string
    {
        return match ($this->role?->name) {
            Role::PC_SUPERADMIN, Role::PC => '<span class="badge badge-danger"><i class="fas fa-user-shield mr-1"></i>Super Admin PC</span>',
            Role::PC_KETUA => '<span class="badge text-white" style="background:#6f42c1;"><i class="fas fa-user-tie mr-1"></i>Ketua LAZISNU</span>',
            Role::PC_SEKRETARIS => '<span class="badge text-white" style="background:#20c997;"><i class="fas fa-file-signature mr-1"></i>Sekretaris PC</span>',
            Role::PC_BENDAHARA => '<span class="badge badge-warning text-dark"><i class="fas fa-calculator mr-1"></i>Bendahara PC</span>',
            Role::PC_ADMIN_KOIN => '<span class="badge badge-success"><i class="fas fa-coins mr-1"></i>Admin KOIN NU</span>',
            Role::PC_ADMIN_ONLINE => '<span class="badge badge-info"><i class="fas fa-globe mr-1"></i>Admin Donasi Online</span>',
            Role::MWC => '<span class="badge badge-primary"><i class="fas fa-building mr-1"></i>MWC Lazisnu</span>',
            Role::RANTING => '<span class="badge badge-secondary"><i class="fas fa-mosque mr-1"></i>Ranting</span>',
            default => '<span class="badge badge-light border">User</span>',
        };
    }

    /**
     * URL Foto Avatar Profil (dari storage lokal atau generator UI-Avatars)
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->avatar && \Illuminate\Support\Facades\Storage::disk('public')->exists($this->avatar)) {
            return asset('storage/' . $this->avatar);
        }

        return 'https://ui-avatars.com/api/?name=' . urlencode($this->name) . '&background=0a6b38&color=ffffff&size=180&bold=true';
    }

    /**
     * Memeriksa apakah user berhak mengakses data MWC tertentu
     */
    public function canAccessMwc(int|Mwc $mwc): bool
    {
        if ($this->isPc()) {
            return true;
        }

        $mwcId = is_int($mwc) ? $mwc : $mwc->id;

        if ($this->isMwc()) {
            return (int) $this->mwc_id === $mwcId;
        }

        if ($this->isRanting()) {
            return (int) $this->ranting?->mwc_id === $mwcId;
        }

        return false;
    }

    /**
     * Memeriksa apakah user berhak mengakses data Ranting tertentu
     */
    public function canAccessRanting(int|Ranting $ranting): bool
    {
        if ($this->isPc()) {
            return true;
        }

        $rantingModel = is_int($ranting) ? Ranting::find($ranting) : $ranting;
        if (!$rantingModel) {
            return false;
        }

        if ($this->isMwc()) {
            return (int) $rantingModel->mwc_id === (int) $this->mwc_id;
        }

        if ($this->isRanting()) {
            return (int) $rantingModel->id === (int) $this->ranting_id;
        }

        return false;
    }

    /**
     * Memeriksa apakah user berhak mengontrol/mengelola relawan tertentu
     */
    public function canManageRelawan(Relawan $relawan): bool
    {
        if ($this->isPc()) {
            return true;
        }

        if ($this->isMwc()) {
            return (int) ($relawan->ranting?->mwc_id ?? 0) === (int) $this->mwc_id;
        }

        if ($this->isRanting()) {
            return (int) $relawan->ranting_id === (int) $this->ranting_id;
        }

        return false;
    }

    /**
     * Scopes
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    public function scopeByRanting(Builder $query, ?int $rantingId): Builder
    {
        return $rantingId ? $query->where('ranting_id', $rantingId) : $query;
    }

    public function scopeByMwc(Builder $query, ?int $mwcId): Builder
    {
        return $mwcId ? $query->where('mwc_id', $mwcId) : $query;
    }
}
