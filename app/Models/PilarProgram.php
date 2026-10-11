<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PilarProgram extends Model
{
    use HasFactory;

    protected $table = 'pilar_programs';

    protected $fillable = [
        'nama_pilar',
        'kode',
        'target_persentase',
        'deskripsi',
        'warna',
        'icon',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'target_persentase' => 'float',
        'is_active'         => 'boolean',
        'sort_order'        => 'integer',
    ];

    public function programKerjas(): HasMany
    {
        return $this->hasMany(ProgramKerja::class, 'pilar_program_id');
    }

    public function pentasyarufans(): HasMany
    {
        return $this->hasMany(Pentasyarufan::class, 'pilar_program_id');
    }

    /**
     * Hitung Pagu Anggaran (Nominal Rupiah) dari persentase terhadap total pemasukan koin
     */
    public function getPaguAnggaran(float $totalPemasukanKoin): float
    {
        return ($totalPemasukanKoin * $this->target_persentase) / 100;
    }

    /**
     * Hitung total pentasyarufan aktual yang telah disalurkan pada pilar ini
     */
    public function getTotalPenyaluran(): float
    {
        return (float) $this->pentasyarufans()->sum('nominal_pencairan');
    }

    /**
     * Hitung Sisa Pagu Anggaran per Program
     * Rumus: (Total Pemasukan KOIN × Persentase Program) - Total Pentasyarufan pada program tersebut
     */
    public function getSisaPagu(float $totalPemasukanKoin): float
    {
        $pagu = $this->getPaguAnggaran($totalPemasukanKoin);
        $terpakai = $this->getTotalPenyaluran();
        return $pagu - $terpakai;
    }

    /**
     * Hitung Persentase Serapan Aktual terhadap Pagu Anggaran Pilar
     */
    public function getPersentaseSerapan(float $totalPemasukanKoin): float
    {
        $pagu = $this->getPaguAnggaran($totalPemasukanKoin);
        if ($pagu <= 0) {
            return 0.00;
        }
        $terpakai = $this->getTotalPenyaluran();
        return round(($terpakai / $pagu) * 100, 2);
    }
}
