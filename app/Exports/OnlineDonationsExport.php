<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class OnlineDonationsExport implements FromCollection, WithHeadings, ShouldAutoSize, WithStyles, WithTitle
{
    protected Collection $donations;
    protected array $filterInfo;

    public function __construct(Collection $donations, array $filterInfo = [])
    {
        $this->donations = $donations;
        $this->filterInfo = $filterInfo;
    }

    public function collection(): Collection
    {
        $rows = $this->donations->values()->map(function ($don, $index) {
            $statusLabel = match ($don->payment_status) {
                'verified' => 'Sah / Terverifikasi',
                'pending' => 'Pending (Menunggu Transfer)',
                'cancelled' => 'Dibatalkan',
                default => ucfirst((string) $don->payment_status),
            };

            $nominal = (float) $don->amount;
            $kodeUnik = (int) $don->unique_code;
            $totalAmount = (float) $don->total_amount;
            $nominalVerif = $don->nominal_terverifikasi !== null ? (float) $don->nominal_terverifikasi : ($don->payment_status === 'verified' ? $totalAmount : 0);

            return [
                'no'                    => $index + 1,
                'kode_donasi'           => $don->donation_code,
                'tanggal'               => $don->created_at ? $don->created_at->format('d/m/Y H:i') : '-',
                'nama_donatur'          => $don->display_name,
                'telepon'               => $don->donor_phone ? "'" . $don->donor_phone : '-',
                'alamat'                => $don->alamat ?: '-',
                'kategori'              => $don->kategori_label,
                'program'               => $don->campaign?->title ?? '-',
                'nominal'               => $nominal,
                'kode_unik'             => $kodeUnik,
                'total_transfer'        => $totalAmount,
                'nominal_terverifikasi' => $nominalVerif,
                'metode_bayar'          => strtoupper(str_replace('_', ' ', (string) $don->payment_method)),
                'status'                => $statusLabel,
                'tanggal_verifikasi'    => $don->verified_at ? $don->verified_at->format('d/m/Y H:i') : '-',
                'verifikator'           => $don->verifier?->name ?? ($don->payment_status === 'verified' ? 'Admin / Sistem' : '-'),
                'doa'                   => $don->prayer_message ?: '-',
            ];
        });

        // Baris Total Akumulasi di bagian akhir
        $totalNominal = (float) $this->donations->sum('amount');
        $totalKodeUnik = (int) $this->donations->sum('unique_code');
        $totalTransfer = (float) $this->donations->sum('total_amount');
        $totalVerif = (float) $this->donations->reduce(function ($carry, $don) {
            $verif = $don->nominal_terverifikasi !== null ? (float) $don->nominal_terverifikasi : ($don->payment_status === 'verified' ? (float) $don->total_amount : 0);
            return $carry + $verif;
        }, 0);

        $rows->push([
            'no'                    => '',
            'kode_donasi'           => 'TOTAL AKUMULASI',
            'tanggal'               => '',
            'nama_donatur'          => count($this->donations) . ' Transaksi',
            'telepon'               => '',
            'alamat'                => '',
            'kategori'              => '',
            'program'               => '',
            'nominal'               => $totalNominal,
            'kode_unik'             => $totalKodeUnik,
            'total_transfer'        => $totalTransfer,
            'nominal_terverifikasi' => $totalVerif,
            'metode_bayar'          => '',
            'status'                => '',
            'tanggal_verifikasi'    => '',
            'verifikator'           => '',
            'doa'                   => '',
        ]);

        return $rows;
    }

    public function headings(): array
    {
        return [
            'NO',
            'KODE DONASI',
            'TANGGAL MASUK',
            'NAMA DONATUR',
            'NO. WHATSAPP / HP',
            'ALAMAT DONATUR',
            'JENIS / KATEGORI',
            'PROGRAM KAMPANYE',
            'NOMINAL DONASI (RP)',
            'KODE UNIK (RP)',
            'TOTAL TRANSFER (RP)',
            'NOMINAL VERIFIKASI (RP)',
            'METODE PEMBAYARAN',
            'STATUS PEMBAYARAN',
            'TANGGAL VERIFIKASI',
            'VERIFIKATOR',
            'PESAN / DOA DONATUR',
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        $lastRow = count($this->donations) + 2; // +1 heading, +1 total row

        // Format Heading Row
        $sheet->getRowDimension(1)->setRowHeight(28);
        $sheet->getStyle('A1:Q1')->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => 'FFFFFF'],
                'size'  => 10,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '0A6B38'], // Hijau NU LAZISNU
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
                'vertical'   => Alignment::VERTICAL_CENTER,
                'wrapText'   => true,
            ],
        ]);

        // Border seluruh tabel
        $sheet->getStyle("A1:Q{$lastRow}")->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Format Angka / Ribuan untuk kolom I, J, K, L (Nominal, Kode Unik, Total, Terverifikasi)
        $sheet->getStyle("I2:L{$lastRow}")->getNumberFormat()->setFormatCode('#,##0');
        $sheet->getStyle("I2:L{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_RIGHT);

        // Format Kolom No, Kode, Tanggal, Telepon, Metode, Status ke Center
        $sheet->getStyle("A2:C{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("E2:E{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $sheet->getStyle("M2:P{$lastRow}")->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Baris Total Akumulasi di baris terakhir
        $sheet->getRowDimension($lastRow)->setRowHeight(24);
        $sheet->getStyle("A{$lastRow}:Q{$lastRow}")->applyFromArray([
            'font' => [
                'bold'  => true,
                'color' => ['rgb' => '0A6B38'],
                'size'  => 10,
            ],
            'fill' => [
                'fillType'   => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E8F5E9'],
            ],
            'alignment' => [
                'vertical' => Alignment::VERTICAL_CENTER,
            ],
        ]);

        return [];
    }

    public function title(): string
    {
        return 'Donasi Online LAZISNU';
    }
}
