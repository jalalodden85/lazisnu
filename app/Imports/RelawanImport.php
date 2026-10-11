<?php

namespace App\Imports;

use App\Models\Relawan;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithBatchInserts;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class RelawanImport implements 
    ToModel, 
    WithHeadingRow, 
    WithValidation, 
    SkipsOnFailure, 
    SkipsOnError,
    WithBatchInserts, 
    WithChunkReading
{
    use Importable, SkipsFailures, SkipsErrors;

    protected int $rantingId;

    /**
     * Inisialisasi dengan mengunci ranting_id berdasarkan Admin Ranting yang sedang login
     */
    public function __construct(int $rantingId)
    {
        $this->rantingId = $rantingId;
    }

    /**
     * Mapping setiap baris dari file Excel/CSV ke Model Relawan
     */
    public function model(array $row)
    {
        // Bersihkan NIK jika ada karakter non-angka
        $cleanNik = preg_replace('/[^0-9]/', '', (string) ($row['nik'] ?? ''));

        return new Relawan([
            'ranting_id'  => $this->rantingId, // Diambil murni dari auth user Ranting (Aman dari manipulasi client)
            'nama'        => trim($row['nama']),
            'nik'         => $cleanNik,
            'no_hp'       => !empty($row['no_hp']) ? trim((string) $row['no_hp']) : null,
            'area_tugas'  => trim($row['area_tugas']),
            'status'      => Relawan::STATUS_ACTIVE,
            'joined_date' => now()->toDateString(),
        ]);
    }

    /**
     * Aturan validasi per baris
     */
    public function rules(): array
    {
        return [
            'nama'       => ['required', 'string', 'max:150'],
            'area_tugas' => ['required', 'string', 'max:100'],
            'nik'        => ['required', 'digits:16', 'unique:relawans,nik'],
            'no_hp'      => ['nullable', 'string', 'max:25'],
        ];
    }

    /**
     * Pesan validasi kustom dalam Bahasa Indonesia
     */
    public function customValidationMessages(): array
    {
        return [
            'nama.required'       => 'Kolom nama wajib diisi pada baris :attribute.',
            'area_tugas.required' => 'Kolom area_tugas (RT/RW) wajib diisi.',
            'nik.required'        => 'NIK wajib diisi.',
            'nik.digits'          => 'NIK harus berupa 16 digit angka.',
            'nik.unique'          => 'NIK sudah terdaftar sebelumnya di sistem.',
        ];
    }

    /**
     * Ukuran batch insert untuk efisiensi memori
     */
    public function batchSize(): int
    {
        return 200;
    }

    /**
     * Ukuran pembacaan chunk data
     */
    public function chunkSize(): int
    {
        return 200;
    }
}
