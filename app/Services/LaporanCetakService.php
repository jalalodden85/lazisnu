<?php

namespace App\Services;

use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LaporanCetakService
{
    /**
     * Memvalidasi wewenang role user dan menyelesaikan batasan cakupan data (Data Scoping)
     *
     * @param User $user
     * @param array $filters
     * @return array
     * @throws HttpException
     */
    public function validateAndResolveScope(User $user, array $filters = []): array
    {
        $requestedRantingId = !empty($filters['ranting_id']) ? (int) $filters['ranting_id'] : null;
        $requestedMwcId = !empty($filters['mwc_id']) ? (int) $filters['mwc_id'] : null;

        $mwcId = null;
        $rantingId = null;
        $scopeLevel = 'pc';

        // 1. Role "Admin Ranting"
        // Cakupan Data: HANYA data setoran milik rantingnya sendiri.
        // Opsi Wilayah: Terkunci (disabled/read-only) pada ranting yang bersangkutan.
        if ($user->isRanting()) {
            $userRantingId = (int) $user->ranting_id;
            $userMwcId = (int) ($user->ranting?->mwc_id ?? $user->mwc_id);

            // Validasi Keamanan: Tolak jika memanipulasi parameter ke ranting lain
            if ($requestedRantingId && $requestedRantingId !== $userRantingId) {
                throw new HttpException(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk meminta data Ranting lain.');
            }

            // Validasi Keamanan: Tolak jika memanipulasi parameter ke MWC lain
            if ($requestedMwcId && $requestedMwcId !== $userMwcId) {
                throw new HttpException(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk meminta data MWC lain.');
            }

            $mwcId = $userMwcId;
            $rantingId = $userRantingId;
            $scopeLevel = 'ranting';
        }
        // 2. Role "Admin MWC"
        // Cakupan Data: Seluruh ranting yang berada di bawah naungan MWC (kecamatan) kerjanya.
        // Opsi Filter: Dapat memilih cetak "Semua Ranting di MWC Ini" atau memilih satu ranting spesifik dalam wilayahnya.
        elseif ($user->isMwc()) {
            $userMwcId = (int) $user->mwc_id;

            // Validasi Keamanan: Tolak jika memanipulasi parameter ke MWC lain
            if ($requestedMwcId && $requestedMwcId !== $userMwcId) {
                throw new HttpException(403, 'Akses ditolak: Anda tidak memiliki wewenang untuk meminta data MWC lain.');
            }

            $mwcId = $userMwcId;

            if ($requestedRantingId) {
                $isValidRanting = Ranting::where('id', $requestedRantingId)
                    ->where('mwc_id', $userMwcId)
                    ->exists();

                if (!$isValidRanting) {
                    throw new HttpException(403, 'Akses ditolak: Ranting yang dipilih berada di luar wilayah naungan MWC Anda.');
                }

                $rantingId = $requestedRantingId;
                $scopeLevel = 'ranting';
            } else {
                $rantingId = null;
                $scopeLevel = 'mwc';
            }
        }
        // 3. Role "Admin PC" (Pengurus Cabang)
        // Cakupan Data: Seluruh data se-Kabupaten Lamongan.
        // Opsi Filter: Memiliki akses dropdown penuh untuk memilih cetak "Seluruh Wilayah PC", per MWC, atau per Ranting spesifik.
        else {
            if ($requestedRantingId) {
                $targetRanting = Ranting::find($requestedRantingId);
                if ($targetRanting) {
                    $rantingId = $targetRanting->id;
                    $mwcId = $targetRanting->mwc_id;
                    $scopeLevel = 'ranting';
                }
            } elseif ($requestedMwcId) {
                $targetMwc = Mwc::find($requestedMwcId);
                if ($targetMwc) {
                    $mwcId = $targetMwc->id;
                    $rantingId = null;
                    $scopeLevel = 'mwc';
                }
            } else {
                $mwcId = null;
                $rantingId = null;
                $scopeLevel = 'pc';
            }
        }

        // Resolusi Model Wilayah
        $ranting = $rantingId ? Ranting::with('mwc')->find($rantingId) : null;
        $mwc = $mwcId ? Mwc::find($mwcId) : ($ranting?->mwc ?? null);

        // Label Teritori / Wilayah
        $wilayahLabel = 'Seluruh Wilayah PCNU Lamongan';
        if ($scopeLevel === 'ranting' && $ranting) {
            $mwcName = $ranting->mwc->name ?? '';
            $wilayahLabel = "Ranting {$ranting->name}" . ($mwcName ? " ({$mwcName})" : '');
        } elseif ($scopeLevel === 'mwc' && $mwc) {
            $wilayahLabel = "MWC {$mwc->name} ({$mwc->code})";
        }

        // Konfigurasi Kop Surat Resmi Berjenjang
        $kop = $this->resolveKopSurat($scopeLevel, $mwc, $ranting);

        // Konfigurasi Standar Pejabat Penandatangan Default Berdasarkan Level Scope
        $defaultSignatories = $this->resolveDefaultSignatories($scopeLevel, $mwc, $ranting);

        return [
            'scope_level'         => $scopeLevel,
            'mwc_id'              => $mwcId,
            'ranting_id'          => $rantingId,
            'mwc'                 => $mwc,
            'ranting'             => $ranting,
            'wilayah_label'       => $wilayahLabel,
            'kop'                 => $kop,
            'default_signatories' => $defaultSignatories,
        ];
    }

    /**
     * Resolusi Kop Surat Berjenjang Sesuai Tingkatan (PC, MWC, Ranting)
     */
    public function resolveKopSurat(string $level, ?Mwc $mwc = null, ?Ranting $ranting = null): array
    {
        $today = Carbon::now();

        if ($level === 'ranting' && $ranting) {
            $desaName = $ranting->desa ?: $ranting->name;
            $mwcName = $ranting->mwc?->name ?? 'MWC';
            return [
                'level'            => 'ranting',
                'instansi_header'  => 'PENGURUS RANTING NAHDLATUL ULAMA DESA ' . strtoupper($desaName),
                'lembaga_title'    => 'UPZISPR NU CARE - LAZISNU RANTING ' . strtoupper($ranting->name),
                'sub_afiliasi'     => "MWC NU {$mwcName} - PCNU KABUPATEN LAMONGAN",
                'legalitas'        => "Naungan MWC NU {$mwcName} | Terdaftar Resmi BAZNAS & LAZISNU PCNU Lamongan",
                'alamat'           => "Alamat: " . ($ranting->address ?: "Desa {$desaName}, Kec. {$mwcName}") . ", Kab. Lamongan",
                'kontak'           => "Hotline UPZISPR: " . ($ranting->mwc?->phone ?: '0851 8899 1926'),
                'logo'             => asset('logo_lazisnu_lmg.png'),
            ];
        }

        if ($level === 'mwc' && $mwc) {
            $kecamatanName = $mwc->kecamatan ?: $mwc->name;
            return [
                'level'            => 'mwc',
                'instansi_header'  => 'PENGURUS MAJELIS WAKIL CABANG NAHDLATUL ULAMA',
                'lembaga_title'    => 'UPZIS NU CARE - LAZISNU MWC ' . strtoupper($mwc->name),
                'sub_afiliasi'     => 'KECAMATAN ' . strtoupper($kecamatanName) . ' - KABUPATEN LAMONGAN',
                'legalitas'        => 'SK Kemenag RI No. 255 Tahun 2021 | Terakreditasi LAZISNU PCNU Lamongan',
                'alamat'           => 'Sekretariat: ' . ($mwc->address ?: "Kantor MWC NU {$mwc->name}, Jl. Raya {$kecamatanName}") . ', Lamongan',
                'kontak'           => 'Hotline Call Center: ' . ($mwc->phone ?: '0851 8899 1926') . ' | Email: lazisnu.' . strtolower($mwc->code) . '@gmail.com',
                'logo'             => asset('logo_lazisnu_lmg.png'),
            ];
        }

        // Tingkat Pengurus Cabang (PCNU Lamongan)
        return [
            'level'            => 'pc',
            'instansi_header'  => 'PENGURUS CABANG NAHDLATUL ULAMA LAMONGAN',
            'lembaga_title'    => 'NU CARE - LAZISNU PCNU LAMONGAN',
            'sub_afiliasi'     => 'NU CARE - LAZISNU',
            'legalitas'        => 'SK Kemenag RI No. 255 Tahun 2021 | Terdaftar Lembaga Amil Zakat Resmi BAZNAS',
            'alamat'           => 'Alamat Sekretariat: Jl. Kyai Amin No. 9 Lamongan 62213',
            'kontak'           => 'Hotline: 0851 8899 1926 | Email: lazisnulamongan@gmail.com',
            'logo'             => asset('logo_lazisnu_lmg.png'),
        ];
    }

    /**
     * Resolusi Pejabat Penandatangan Default Berdasarkan Tingkatan
     */
    public function resolveDefaultSignatories(string $level, ?Mwc $mwc = null, ?Ranting $ranting = null): array
    {
        $todayFormatted = Carbon::now()->translatedFormat('d F Y');

        if ($level === 'ranting' && $ranting) {
            $desaName = $ranting->desa ?: $ranting->name;
            return [
                'doc_place'        => $desaName,
                'doc_date'         => $todayFormatted,
                'doc_place_date'   => "{$desaName}, {$todayFormatted}",
                // Pihak 1 (Kiri - Pemeriksa / Pembuat Laporan)
                'pihak1_title'     => 'Pemeriksa / Pembuat Laporan,',
                'pihak1_jabatan'   => 'Bendahara UPZIS',
                'pihak1_nama'      => '',
                'pihak1_nip'       => 'Petugas Administrasi Ranting',
                // Pihak 2 (Kanan - Mengetahui / Pejabat Penanggung Jawab)
                'pihak2_title'     => 'Mengetahui / Penanggung Jawab,',
                'pihak2_jabatan'   => 'Ketua Pengurus Ranting NU',
                'pihak2_nama'      => $ranting->pj_name ?: '',
                'pihak2_nip'       => 'Pimpinan Ranting NU',
            ];
        }

        if ($level === 'mwc' && $mwc) {
            $kecamatanName = $mwc->kecamatan ?: $mwc->name;
            return [
                'doc_place'        => $kecamatanName,
                'doc_date'         => $todayFormatted,
                'doc_place_date'   => "{$kecamatanName}, {$todayFormatted}",
                // Pihak 1
                'pihak1_title'     => 'Pemeriksa / Divisi Keuangan,',
                'pihak1_jabatan'   => 'Divisi Keuangan MWC',
                'pihak1_nama'      => '',
                'pihak1_nip'       => 'UPZIS MWC NU Lamongan',
                // Pihak 2
                'pihak2_title'     => 'Mengetahui / Penanggung Jawab,',
                'pihak2_jabatan'   => 'Ketua UPZIS MWC NU',
                'pihak2_nama'      => '',
                'pihak2_nip'       => 'Pimpinan MWC NU',
            ];
        }

        // Tingkat PC (Pengurus Cabang)
        return [
            'doc_place'        => 'Lamongan',
            'doc_date'         => $todayFormatted,
            'doc_place_date'   => "Lamongan, {$todayFormatted}",
            // Pihak 1
            'pihak1_title'     => 'Mengetahui / Verifikator,',
            'pihak1_jabatan'   => 'Direktur Eksekutif / Admin Cabang',
            'pihak1_nama'      => 'M. KHOIRUL ANAM, S.Pd.',
            'pihak1_nip'       => 'NIP / ID: 35.24.01.002',
            // Pihak 2
            'pihak2_title'     => 'Mengetahui / Penanggung Jawab,',
            'pihak2_jabatan'   => 'Pimpinan PC LAZISNU Lamongan (Ketua)',
            'pihak2_nama'      => 'H. M. SYAMSUDDIN, M.Pd.I',
            'pihak2_nip'       => 'NIP / ID: 35.24.01.001',
        ];
    }

    /**
     * Menggabungkan input form modal penandatangan dengan default
     */
    public function resolveSignatures(array $scope, array $inputs = []): array
    {
        $defaults = $scope['default_signatories'] ?? $this->resolveDefaultSignatories($scope['scope_level']);

        $docPlace = !empty($inputs['doc_place']) ? trim($inputs['doc_place']) : ($defaults['doc_place'] ?? 'Lamongan');
        $docDate = !empty($inputs['doc_date']) ? trim($inputs['doc_date']) : ($defaults['doc_date'] ?? Carbon::now()->translatedFormat('d F Y'));
        
        $docPlaceDate = !empty($inputs['doc_place_date']) 
            ? trim($inputs['doc_place_date']) 
            : (!empty($inputs['tempat_tanggal']) ? trim($inputs['tempat_tanggal']) : "{$docPlace}, {$docDate}");

        $pihak1Jabatan = !empty($inputs['pihak1_jabatan']) ? trim($inputs['pihak1_jabatan']) : ($defaults['pihak1_jabatan'] ?? 'Pemeriksa');
        $pihak1Nama = !empty($inputs['pihak1_nama']) ? trim($inputs['pihak1_nama']) : ($defaults['pihak1_nama'] ?? '');
        $pihak1Nip = isset($inputs['pihak1_nip']) ? trim($inputs['pihak1_nip']) : (!empty($inputs['pihak1_keterangan']) ? trim($inputs['pihak1_keterangan']) : ($defaults['pihak1_nip'] ?? ''));

        $pihak2Jabatan = !empty($inputs['pihak2_jabatan']) ? trim($inputs['pihak2_jabatan']) : ($defaults['pihak2_jabatan'] ?? 'Penanggung Jawab');
        $pihak2Nama = !empty($inputs['pihak2_nama']) ? trim($inputs['pihak2_nama']) : ($defaults['pihak2_nama'] ?? '');
        $pihak2Nip = isset($inputs['pihak2_nip']) ? trim($inputs['pihak2_nip']) : (!empty($inputs['pihak2_keterangan']) ? trim($inputs['pihak2_keterangan']) : ($defaults['pihak2_nip'] ?? ''));

        return [
            'doc_place'      => $docPlace,
            'doc_date'       => $docDate,
            'doc_place_date' => $docPlaceDate,
            'pihak1_title'   => $defaults['pihak1_title'] ?? 'Mengetahui / Verifikator,',
            'pihak1_jabatan' => $pihak1Jabatan,
            'pihak1_nama'    => $pihak1Nama,
            'pihak1_nip'     => $pihak1Nip,
            'pihak2_title'   => $defaults['pihak2_title'] ?? 'Mengetahui / Penanggung Jawab,',
            'pihak2_jabatan' => $pihak2Jabatan,
            'pihak2_nama'    => $pihak2Nama,
            'pihak2_nip'     => $pihak2Nip,
        ];
    }

    /**
     * Query transaksi setoran KOIN NU yang dibatasi sesuai cakupan hirarki
     *
     * @param Penthong $penthong
     * @param array $scope
     * @return Collection
     */
    public function queryTransactions(Penthong $penthong, array $scope): Collection
    {
        $query = $penthong->transactions()
            ->with(['relawan', 'ranting.mwc', 'mwc'])
            ->where('status', KoinTransaction::STATUS_CONFIRMED);

        if (!empty($scope['ranting_id'])) {
            $query->where('ranting_id', (int) $scope['ranting_id']);
        } elseif (!empty($scope['mwc_id'])) {
            $query->where('mwc_id', (int) $scope['mwc_id']);
        }

        return $query->orderBy('collection_date')->orderBy('id')->get();
    }

    /**
     * Ekspor Laporan Resmi Perolehan Pentong ke Format Excel (.xlsx) dengan Kop & Tanda Tangan Sesuai Form Modal
     */
    public function exportExcel(Penthong $penthong, Collection $transactions, array $scope, array $signatures): StreamedResponse
    {
        $totalNominal = $transactions->sum('amount');
        $wilayahLabel = $scope['wilayah_label'] ?? 'Seluruh Wilayah PCNU Lamongan';
        $kop = $scope['kop'];

        $dateRange = $penthong->start_date && $penthong->end_date
            ? Carbon::parse($penthong->start_date)->format('d/m/Y') . ' - ' . Carbon::parse($penthong->end_date)->format('d/m/Y')
            : "Bulan {$penthong->bulan_nama} {$penthong->tahun}";

        $printedAt = Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB';

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Laporan Pentong KOIN NU');

        // 1. KOP SURAT RESMI BERJENJANG (A1:G1, A2:G2, A3:G3)
        $sheet->mergeCells('A1:G1');
        $sheet->setCellValue('A1', $kop['instansi_header'] ?? 'PENGURUS CABANG NAHDLATUL ULAMA LAMONGAN');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(13);
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A2:G2');
        $sheet->setCellValue('A2', $kop['lembaga_title'] ?? 'NU CARE - LAZISNU PCNU LAMONGAN');
        $sheet->getStyle('A2')->getFont()->setBold(true)->setSize(12)->getColor()->setRGB('0A6B38');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->mergeCells('A3:G3');
        $sheet->setCellValue('A3', ($kop['alamat'] ?? '') . ' | ' . ($kop['kontak'] ?? ''));
        $sheet->getStyle('A3')->getFont()->setSize(9)->setItalic(true);
        $sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Border pemisah kop surat
        $sheet->getStyle('A3:G3')->getBorders()->getBottom()->setBorderStyle(Border::BORDER_DOUBLE);

        // JUDUL DOKUMEN RESMI
        $sheet->mergeCells('A5:G5');
        $sheet->setCellValue('A5', 'LAPORAN PEROLEHAN PENTONG KOIN NU');
        $sheet->getStyle('A5')->getFont()->setBold(true)->setSize(12)->setUnderline(true);
        $sheet->getStyle('A5')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header Metadata
        $statusText = $penthong->isOpen() ? 'AKTIF (OPEN)' : 'DITUTUP (CLOSED)';
        $sheet->mergeCells('A6:G6');
        $sheet->setCellValue('A6', "Periode: {$penthong->nama_periode} ({$statusText})  |  Rentang: {$dateRange}  |  Wilayah: {$wilayahLabel}  |  Dicetak: {$printedAt}");
        $sheet->getStyle('A6')->getFont()->setSize(9);
        $sheet->getStyle('A6')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // 2. HEADER TABEL DATA
        $headers = [
            'A8' => 'No',
            'B8' => 'Tanggal Setoran',
            'C8' => 'Nama Ranting / MWC',
            'D8' => 'Nama Munfiq / No. Kaleng',
            'E8' => 'Petugas Lapangan (PLK)',
            'F8' => 'Nominal Setoran (Rp)',
            'G8' => 'Keterangan / Status',
        ];

        foreach ($headers as $cell => $value) {
            $sheet->setCellValue($cell, $value);
        }

        $headerStyle = [
            'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '1E7E34'],
            ],
            'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        ];
        $sheet->getStyle('A8:G8')->applyFromArray($headerStyle);
        $sheet->getRowDimension(8)->setRowHeight(26);

        // 3. DATA ROWS
        $row = 9;
        foreach ($transactions as $idx => $tx) {
            $rantingMwc = ($tx->ranting->name ?? '-') . ' / ' . ($tx->mwc->name ?? '-');
            $identitas = $tx->invoice_code . ($tx->relawan?->area_tugas ? ' (' . $tx->relawan->area_tugas . ')' : '');

            $sheet->setCellValue('A' . $row, $idx + 1);
            $sheet->setCellValue('B' . $row, $tx->collection_date ? Carbon::parse($tx->collection_date)->format('d/m/Y') : '-');
            $sheet->setCellValue('C' . $row, $rantingMwc);
            $sheet->setCellValue('D' . $row, $identitas);
            $sheet->setCellValue('E' . $row, $tx->relawan->nama ?? '-');
            $sheet->setCellValue('F' . $row, (float) $tx->amount);
            $sheet->setCellValue('G' . $row, $tx->status === 'confirmed' ? 'Terkonfirmasi' : ($tx->notes ?: '-'));

            $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('B' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
            $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');
            $sheet->getStyle('A' . $row . ':G' . $row)->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

            $row++;
        }

        // 4. BARIS TOTAL AKUMULASI (DOUBLE BOTTOM BORDER)
        $sheet->mergeCells('A' . $row . ':E' . $row);
        $sheet->setCellValue('A' . $row, 'TOTAL AKUMULASI PENERIMAAN DANA (RP)');
        $sheet->setCellValue('F' . $row, (float) $totalNominal);
        $sheet->setCellValue('G' . $row, "{$transactions->count()} Transaksi");

        $totalStyle = [
            'font' => ['bold' => true],
            'alignment' => ['horizontal' => Alignment::HORIZONTAL_RIGHT],
            'borders' => [
                'top'        => ['borderStyle' => Border::BORDER_DOUBLE],
                'bottom'     => ['borderStyle' => Border::BORDER_DOUBLE],
                'allBorders' => ['borderStyle' => Border::BORDER_THIN],
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F5E9'],
            ],
        ];
        $sheet->getStyle('A' . $row . ':G' . $row)->applyFromArray($totalStyle);
        $sheet->getStyle('F' . $row)->getNumberFormat()->setFormatCode('#,##0');

        // 5. LEMBAR PENGESAHAN DUA KOLOM RESMI (DARI MODAL FORM)
        $signRow = $row + 3;

        // Kolom Kiri: Pihak 1 (Pemeriksa / Pembuat Laporan)
        $sheet->setCellValue('B' . $signRow, $signatures['pihak1_title'] ?? 'Pemeriksa / Pembuat Laporan,');
        $sheet->setCellValue('B' . ($signRow + 1), $signatures['pihak1_jabatan'] ?? 'Bendahara');
        $sheet->setCellValue('B' . ($signRow + 5), '( ' . ($signatures['pihak1_nama'] ?: '_________________________') . ' )');
        if (!empty($signatures['pihak1_nip'])) {
            $sheet->setCellValue('B' . ($signRow + 6), $signatures['pihak1_nip']);
            $sheet->getStyle('B' . ($signRow + 6))->getFont()->setSize(9)->setItalic(true);
        }
        $sheet->getStyle('B' . ($signRow + 5))->getFont()->setBold(true);

        // Kolom Kanan: Tempat & Tanggal Dokumen di sisi kanan atas tanda tangan
        $sheet->setCellValue('F' . $signRow, $signatures['doc_place_date'] ?? ('Lamongan, ' . Carbon::now()->translatedFormat('d F Y')));
        $sheet->setCellValue('F' . ($signRow + 1), $signatures['pihak2_jabatan'] ?? 'Penanggung Jawab');
        $sheet->setCellValue('F' . ($signRow + 5), '( ' . ($signatures['pihak2_nama'] ?: '_________________________') . ' )');
        if (!empty($signatures['pihak2_nip'])) {
            $sheet->setCellValue('F' . ($signRow + 6), $signatures['pihak2_nip']);
            $sheet->getStyle('F' . ($signRow + 6))->getFont()->setSize(9)->setItalic(true);
        }
        $sheet->getStyle('F' . ($signRow + 5))->getFont()->setBold(true);

        // Auto size columns
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        $cleanPeriodName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $penthong->nama_periode);
        $cleanWilayah = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $wilayahLabel);
        $filename = "Laporan_Perolehan_Pentong_{$cleanPeriodName}_{$cleanWilayah}_" . date('Ymd_His') . ".xlsx";

        $writer = new Xlsx($spreadsheet);

        return response()->streamDownload(function () use ($writer) {
            $writer->save('php://output');
        }, $filename, [
            'Content-Type'  => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Cache-Control' => 'max-age=0',
        ]);
    }
}
