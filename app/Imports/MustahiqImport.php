<?php

namespace App\Imports;

use App\Models\Mustahiq;
use App\Models\Ranting;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;

class MustahiqImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected int $targetRantingId;
    protected int $targetMwcId;
    protected ?int $createdBy;

    // Pelacak metrik hasil import
    public int $importedCount = 0;
    public int $skippedDuplicateCount = 0;
    public array $errors = [];

    // Hash map cache NIK untuk lookup instan O(1) & eliminasi N+1 query
    protected array $existingNiks = [];
    protected array $processedInFileNiks = [];

    /**
     * @param int|null $targetRantingId ID Ranting target import (opsional jika user adalah Ranting)
     * @param int|null $targetMwcId ID MWC target
     */
    public function __construct(?int $targetRantingId = null, ?int $targetMwcId = null)
    {
        $user = Auth::user();
        $this->createdBy = $user?->id;

        if ($user && $user->isRanting()) {
            $this->targetRantingId = (int) $user->ranting_id;
            $this->targetMwcId     = (int) ($user->mwc_id ?? $user->ranting?->mwc_id ?? 1);
        } else {
            $this->targetRantingId = (int) ($targetRantingId ?? ($user?->ranting_id ?? 1));
            $ranting = Ranting::find($this->targetRantingId);
            $this->targetMwcId     = (int) ($targetMwcId ?? ($ranting?->mwc_id ?? 1));
        }

        // Pre-fetch seluruh NIK yang sudah ada di database ke dalam hash map memori O(1)
        $this->existingNiks = Mustahiq::pluck('nik')
            ->flip()
            ->toArray();
    }

    /**
     * Pemrosesan baris koleksi per chunk
     */
    public function collection(Collection $rows)
    {
        $validAsnafList = [
            'fakir', 'miskin', 'amil', 'muallaf', 
            'riqab', 'gharim', 'fisabilillah', 'ibnu_sabil'
        ];

        $insertBatch = [];

        foreach ($rows as $index => $row) {
            $rowNumber = $index + 2; // +2 memperhitungkan 1 baris header Excel

            // 1. Validasi & Sanitasi NIK (Nomor Induk Kependudukan)
            $rawNik = $row['nik'] ?? $row['nomor_induk_kependudukan'] ?? null;
            $cleanNik = preg_replace('/[^0-9]/', '', (string) $rawNik);

            // NIK harus berupa angka dan wajib 16 digit sesuai standar Dukcapil RI
            if (empty($cleanNik) || strlen($cleanNik) !== 16) {
                $this->errors[] = "Baris {$rowNumber}: NIK [{$rawNik}] tidak valid (harus 16 digit angka).";
                continue;
            }

            // Validasi Unik: Cek apakah NIK sudah ada di Database
            if (isset($this->existingNiks[$cleanNik])) {
                $this->skippedDuplicateCount++;
                continue; // Skip baris duplikat agar tidak terjadi error SQL Duplicate Entry
            }

            // Validasi Unik Internal: Cek apakah NIK duplikat di baris sebelumnya dalam file yang sama
            if (isset($this->processedInFileNiks[$cleanNik])) {
                $this->skippedDuplicateCount++;
                continue;
            }

            // 2. Validasi Kolom Wajib: Nama Lengkap
            $nama = trim((string) ($row['nama_lengkap'] ?? $row['nama'] ?? ''));
            if (empty($nama)) {
                $this->errors[] = "Baris {$rowNumber}: Kolom Nama Lengkap tidak boleh kosong.";
                continue;
            }

            // 3. Validasi Kolom Wajib & Normalisasi: Kategori 8 Asnaf
            $rawAsnaf = strtolower(trim((string) ($row['kategori_asnaf'] ?? $row['asnaf'] ?? 'miskin')));
            $cleanAsnaf = match ($rawAsnaf) {
                'fakir miskin', 'fakir_miskin' => 'fakir',
                'sabilillah', 'fisabililah'     => 'fisabilillah',
                'ibnusabil', 'ibnu sabil'      => 'ibnu_sabil',
                default                        => $rawAsnaf,
            };

            if (!in_array($cleanAsnaf, $validAsnafList, true)) {
                $this->errors[] = "Baris {$rowNumber}: Kategori Asnaf [{$rawAsnaf}] tidak valid. Pilihan: " . implode(', ', $validAsnafList);
                continue;
            }

            // 4. Kolom Opsional: Jenis Kelamin (L/P)
            $jk = strtoupper(trim((string) ($row['jenis_kelamin'] ?? $row['jk'] ?? 'L')));
            $jenisKelamin = in_array($jk, ['L', 'P'], true) ? $jk : 'L';

            // 5. Kolom Opsional: Alamat & Pekerjaan/Keterangan
            $alamat = trim((string) ($row['alamat'] ?? $row['alamat_lengkap'] ?? '-'));
            $pekerjaan = trim((string) (
                $row['pekerjaan_keterangan'] ?? 
                $row['pekerjaanketerangan'] ?? 
                $row['pekerjaan'] ?? 
                $row['keterangan'] ?? 
                null
            ));

            // Tandai NIK sebagai telah diproses untuk baris-baris berikutnya
            $this->processedInFileNiks[$cleanNik] = true;
            $this->existingNiks[$cleanNik] = true;

            $insertBatch[] = [
                'nik'                   => $cleanNik,
                'nama'                  => $nama,
                'jenis_kelamin'         => $jenisKelamin,
                'telepon'               => null,
                'alamat'                => $alamat ?: '-',
                'ranting_id'            => $this->targetRantingId,
                'mwc_id'                => $this->targetMwcId,
                'asnaf'                 => $cleanAsnaf,
                'kondisi_spesifik'      => $pekerjaan,
                'pekerjaan'             => $pekerjaan,
                'penghasilan_rata_rata' => 0,
                'jumlah_tanggungan'     => 0,
                'status'                => 'aktif',
                'foto_kondisi'          => null,
                'catatan_verifikasi'    => 'Diimpor massal via Excel pada ' . now()->format('d/m/Y H:i'),
                'created_by'            => $this->createdBy,
                'created_at'            => now(),
                'updated_at'            => now(),
            ];

            $this->importedCount++;
        }

        // Eksekusi bulk insert untuk performa optimal
        if (!empty($insertBatch)) {
            Mustahiq::insert($insertBatch);
        }
    }

    public function chunkSize(): int
    {
        return 500;
    }
}
