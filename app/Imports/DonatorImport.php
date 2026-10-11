<?php

namespace App\Imports;

use App\Models\Donator;
use App\Models\Relawan;
use Maatwebsite\Excel\Concerns\Importable;
use Maatwebsite\Excel\Concerns\SkipsErrors;
use Maatwebsite\Excel\Concerns\SkipsFailures;
use Maatwebsite\Excel\Concerns\SkipsOnError;
use Maatwebsite\Excel\Concerns\SkipsOnFailure;
use Maatwebsite\Excel\Concerns\ToModel;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithValidation;

class DonatorImport implements 
    ToModel, 
    WithHeadingRow, 
    WithValidation, 
    SkipsOnFailure, 
    SkipsOnError
{
    use Importable, SkipsFailures, SkipsErrors;

    protected int $rantingId;
    protected $relawanMap;
    protected array $sequenceMap = [];
    protected array $rantingCache = [];

    /**
     * Inisialisasi dengan mengunci ranting_id target import
     */
    public function __construct(int $rantingId)
    {
        $this->rantingId = $rantingId;
        // Cache relawan yang ada pada ranting ini untuk penugasan cepat
        $this->relawanMap = Relawan::where('ranting_id', $rantingId)->get();
    }

    /**
     * Mapping setiap baris dari file Excel/CSV ke Model Donator
     */
    public function model(array $row)
    {
        $name = trim((string)($row['nama'] ?? $row['name'] ?? $row['nama_donatur'] ?? $row['nama_lengkap'] ?? ''));
        if (empty($name)) {
            return null;
        }

        // Tentukan Target Ranting
        $targetRantingId = $this->rantingId;
        $rantingRef = trim((string)($row['kode_ranting'] ?? $row['ranting'] ?? $row['kode_mwc_ranting'] ?? ''));
        if (!empty($rantingRef)) {
            if (!isset($this->rantingCache[$rantingRef])) {
                $foundRanting = \App\Models\Ranting::where('code', $rantingRef)
                    ->orWhere('name', $rantingRef)
                    ->orWhere('desa', 'like', "%{$rantingRef}%")
                    ->first();
                $this->rantingCache[$rantingRef] = $foundRanting ? $foundRanting->id : null;
            }
            if (!empty($this->rantingCache[$rantingRef])) {
                $targetRantingId = $this->rantingCache[$rantingRef];
            }
        }

        // Bersihkan NIK jika ada karakter non-angka
        $rawNik = $row['nik'] ?? null;
        $cleanNik = !empty($rawNik) ? preg_replace('/[^0-9]/', '', (string) $rawNik) : null;
        if (empty($cleanNik)) {
            $cleanNik = null;
        }

        // Parse RT dan RW
        $rawRt = $row['rt'] ?? $row['no_rt'] ?? null;
        $rawRw = $row['rw'] ?? $row['no_rw'] ?? null;

        // Cek jika RT/RW digabung di satu kolom (misal: 01/02, 01-02, atau 0102)
        $rtRwCombined = $row['rt_rw'] ?? $row['rtrw'] ?? $row['rt_dan_rw'] ?? null;
        if ($rtRwCombined && (empty($rawRt) || empty($rawRw))) {
            if (preg_match('/(\d+)\s*[\/\-]\s*(\d+)/', (string) $rtRwCombined, $matches)) {
                $rawRt = $matches[1];
                $rawRw = $matches[2];
            } elseif (preg_match('/^(\d{2})(\d{2})$/', (string) $rtRwCombined, $matches)) {
                $rawRt = $matches[1];
                $rawRw = $matches[2];
            }
        }

        $cleanRt = !empty($rawRt) ? str_pad(substr(preg_replace('/[^0-9]/', '', (string) $rawRt), -2), 2, '0', STR_PAD_LEFT) : '00';
        $cleanRw = !empty($rawRw) ? str_pad(substr(preg_replace('/[^0-9]/', '', (string) $rawRw), -2), 2, '0', STR_PAD_LEFT) : '00';

        // Tentukan Nomor Kotak / Kaleng KOIN NU:
        // Format standar persis: KLG-{kodeMWCkoderanting}-{RTRW}-{NO_URUT}
        // Contoh: KLG-M01R01-0102-003 atau KLG-M09R17-0402-112
        $rawBox = trim((string)($row['box_number'] ?? $row['nomor_kotak'] ?? $row['no_kotak'] ?? $row['kode_kotak'] ?? $row['kaleng'] ?? $row['no_kaleng'] ?? $row['kode_kaleng'] ?? $row['no_urut'] ?? $row['nomor_urut'] ?? $row['urut'] ?? ''));

        if (!empty($rawBox)) {
            // Jika sudah lengkap sesuai format KLG-kode-RTRW-NO_URUT
            if (preg_match('/^KLG-[A-Z0-9]+-\d{4}-\d{3,}$/i', $rawBox)) {
                $boxNumber = strtoupper($rawBox);
                if (preg_match('/-(\d+)$/', $boxNumber, $m)) {
                    $this->sequenceMap[$targetRantingId] = max($this->sequenceMap[$targetRantingId] ?? 0, (int) $m[1]);
                }
            } elseif (preg_match('/^[A-Z0-9]+-\d{4}-\d{3,}$/i', $rawBox)) {
                // Tanpa prefix KLG
                $boxNumber = 'KLG-' . strtoupper($rawBox);
                if (preg_match('/-(\d+)$/', $boxNumber, $m)) {
                    $this->sequenceMap[$targetRantingId] = max($this->sequenceMap[$targetRantingId] ?? 0, (int) $m[1]);
                }
            } elseif (preg_match('/(\d+)$/', $rawBox, $matches)) {
                // Berupa nomor urut (contoh: 112, 003, 3, KLG-112)
                $seqInt = (int) $matches[1];
                $this->sequenceMap[$targetRantingId] = max($this->sequenceMap[$targetRantingId] ?? 0, $seqInt);
                $boxNumber = Donator::formatBoxNumber($targetRantingId, $cleanRt, $cleanRw, $seqInt);
            } else {
                // Teks non-angka, gunakan auto-increment
                if (!isset($this->sequenceMap[$targetRantingId])) {
                    $this->sequenceMap[$targetRantingId] = Donator::getNextSequenceNumber($targetRantingId);
                } else {
                    $this->sequenceMap[$targetRantingId]++;
                }
                $boxNumber = Donator::formatBoxNumber($targetRantingId, $cleanRt, $cleanRw, $this->sequenceMap[$targetRantingId]);
            }
        } else {
            // Jika kosong di file, generate nomor urut otomatis
            if (!isset($this->sequenceMap[$targetRantingId])) {
                $this->sequenceMap[$targetRantingId] = Donator::getNextSequenceNumber($targetRantingId);
            } else {
                $this->sequenceMap[$targetRantingId]++;
            }
            $boxNumber = Donator::formatBoxNumber($targetRantingId, $cleanRt, $cleanRw, $this->sequenceMap[$targetRantingId]);
        }

        // Pastikan tidak ada benturan nomor kotak yang sama
        while (Donator::where('box_number', $boxNumber)->exists()) {
            if (!isset($this->sequenceMap[$targetRantingId])) {
                $this->sequenceMap[$targetRantingId] = Donator::getNextSequenceNumber($targetRantingId);
            } else {
                $this->sequenceMap[$targetRantingId]++;
            }
            $boxNumber = Donator::formatBoxNumber($targetRantingId, $cleanRt, $cleanRw, $this->sequenceMap[$targetRantingId]);
        }

        // Cari petugas relawan pengampu jika diisi di file
        $relawanId = null;
        $petugasRef = trim((string)($row['petugas'] ?? $row['relawan'] ?? $row['nik_relawan'] ?? ''));
        if (!empty($petugasRef)) {
            $relawan = $this->relawanMap->first(function ($item) use ($petugasRef) {
                return $item->nik === $petugasRef || stripos($item->nama, $petugasRef) !== false;
            });
            if ($relawan) {
                $relawanId = $relawan->id;
            }
        }

        $address = trim((string)($row['address'] ?? $row['alamat'] ?? 'Dusun Setempat'));

        return new Donator([
            'ranting_id'  => $targetRantingId,
            'relawan_id'  => $relawanId,
            'box_number'  => $boxNumber,
            'name'        => $name,
            'nik'         => $cleanNik,
            'phone'       => !empty($row['phone'] ?? $row['no_hp'] ?? null) ? trim((string)($row['phone'] ?? $row['no_hp'])) : null,
            'address'     => $address,
            'rt'          => $cleanRt,
            'rw'          => $cleanRw,
            'status'      => Donator::STATUS_ACTIVE,
            'joined_date' => now()->toDateString(),
            'notes'       => !empty($row['notes'] ?? $row['catatan'] ?? null) ? trim((string)($row['notes'] ?? $row['catatan'])) : 'Import data Excel/CSV',
        ]);
    }

    /**
     * Aturan validasi per baris file
     */
    public function rules(): array
    {
        return [];
    }
}
