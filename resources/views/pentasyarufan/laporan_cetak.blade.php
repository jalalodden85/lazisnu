<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Laporan Realisasi Pentasyarufan - LAZISNU PCNU Lamongan</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 20px;
        }
        .header {
            text-align: center;
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 15px;
        }
        .header h2 {
            margin: 0;
            font-size: 16px;
            color: #064d27;
            text-transform: uppercase;
        }
        .header h3 {
            margin: 2px 0;
            font-size: 14px;
            color: #0a6b38;
        }
        .header p {
            margin: 2px 0;
            font-size: 10px;
            color: #666;
        }
        .title-box {
            text-align: center;
            margin-bottom: 15px;
        }
        .title-box h4 {
            margin: 0;
            font-size: 13px;
            text-decoration: underline;
            text-transform: uppercase;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 15px;
        }
        table, th, td {
            border: 1px solid #777;
        }
        th {
            background-color: #f2f2f2;
            padding: 6px;
            font-size: 10px;
            text-align: center;
        }
        td {
            padding: 5px;
            font-size: 10px;
        }
        .text-center { text-align: center; }
        .text-right { text-align: right; }
        .font-weight-bold { font-weight: bold; }
        .recap-box {
            display: flex;
            justify-content: space-between;
            margin-bottom: 15px;
        }
        .recap-card {
            border: 1px solid #ccc;
            padding: 8px;
            width: 18%;
            background: #fafafa;
            border-radius: 4px;
            text-align: center;
        }
        .signatures {
            margin-top: 30px;
            width: 100%;
            border: none;
        }
        .signatures td {
            border: none;
            text-align: center;
            vertical-align: top;
            width: 33.33%;
        }
        .sign-space {
            height: 65px;
        }
        @media print {
            .no-print { display: none; }
            body { margin: 0; }
        }
    </style>
</head>
<body onload="window.print()">
    <div class="no-print" style="margin-bottom: 15px; text-align: right;">
        <button onclick="window.print()" style="padding: 6px 12px; background: #0a6b38; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">
            Cetak / Simpan PDF
        </button>
        <button onclick="window.close()" style="padding: 6px 12px; background: #6c757d; color: #fff; border: none; border-radius: 4px; cursor: pointer;">
            Tutup
        </button>
    </div>

    <!-- Kop Surat Resmi -->
    <div class="header">
        <h2>PENGURUS CABANG NAHDLATUL ULAMA LAMONGAN</h2>
        <h3>LEMBAGA AMIL ZAKAT, INFAQ, DAN SHADAQAH (LAZISNU)</h3>
        <p>Gedung PCNU Lamongan, Jl. Kyai Amin No. 9 Lamongan, Jawa Timur | Telp/WA: 0851-8899-1926 | Email: pcnu@lazisnulamongan.org</p>
    </div>

    <div class="title-box">
        <h4>LAPORAN RESMI REALISASI PENTASYARUFAN DANA KOIN NU</h4>
        <small>Berdasarkan 5 Pilar Program Kerja & Peraturan Pagu Keuangan LAZISNU</small>
    </div>

    <!-- Rekapitulasi per 5 Pilar Program -->
    <table style="margin-bottom: 15px;">
        <thead>
            <tr style="background-color: #0a6b38; color: #fff;">
                <th colspan="5">REKAPITULASI SERAPAN PENTASYARUFAN BERDASARKAN 5 PILAR PROGRAM NU CARE</th>
            </tr>
            <tr>
                @foreach($pilars as $p)
                    <th>{{ $p->nama_pilar }} ({{ $p->target_persentase }}%)</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            <tr>
                @foreach($pilars as $p)
                    <td class="text-center font-weight-bold" style="color: #064d27;">
                        Rp {{ number_format($rekapPilar[$p->id] ?? 0, 0, ',', '.') }}
                    </td>
                @endforeach
            </tr>
        </tbody>
    </table>

    <!-- Rincian Transaksi Penyaluran -->
    <table>
        <thead>
            <tr>
                <th style="width: 25px;">No</th>
                <th style="width: 95px;">No. Transaksi</th>
                <th style="width: 70px;">Tanggal</th>
                <th>Program Kerja (Kegiatan)</th>
                <th style="width: 110px;">Pilar Program</th>
                <th style="width: 100px;">Wilayah Pelaksana</th>
                <th>Penerima Manfaat</th>
                <th style="width: 90px;">Nominal (Rp)</th>
                <th style="width: 70px;">Status SPJ</th>
            </tr>
        </thead>
        <tbody>
            @forelse($pentasyarufans as $idx => $t)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center"><code>{{ $t->nomor_transaksi }}</code></td>
                    <td class="text-center">{{ $t->tanggal_penyaluran->format('d/m/Y') }}</td>
                    <td>{{ $t->programKerja?->nama_kegiatan }}</td>
                    <td>{{ $t->pilarProgram?->kode }}</td>
                    <td>{{ $t->wilayah_label }}</td>
                    <td>{{ $t->penerima_manfaat }} ({{ $t->jumlah_penerima }} Jiwa)</td>
                    <td class="text-right font-weight-bold">{{ number_format($t->nominal_pencairan, 0, ',', '.') }}</td>
                    <td class="text-center">{{ strtoupper(str_replace('_', ' ', $t->spj_status)) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">Tidak ada transaksi pentasyarufan pada periode ini.</td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr style="background-color: #f2f2f2; font-weight: bold;">
                <td colspan="7" class="text-right">TOTAL PENTASYARUFAN TEREALISASI:</td>
                <td class="text-right">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                <td class="text-center">-</td>
            </tr>
        </tfoot>
    </table>

    <!-- Form Tanda Tangan Pengesahan 3 Pejabat PC LAZISNU -->
    <table class="signatures">
        <tr>
            <td colspan="3" style="text-align: right; padding-bottom: 10px;">
                Lamongan, {{ date('d F Y') }}
            </td>
        </tr>
        <tr>
            <td>
                Mengetahui,<br>
                <strong>Ketua LAZISNU PCNU Lamongan</strong>
                <div class="sign-space"></div>
                <strong><u>Drs. H. Suharjuddin, M.Pd.I</u></strong><br>
                <small>NIP/ID: PC-01-001</small>
            </td>
            <td>
                Menyetujui,<br>
                <strong>Sekretaris PC LAZISNU</strong>
                <div class="sign-space"></div>
                <strong><u>H. Mochammad Baihaqi, S.Ag</u></strong><br>
                <small>NIP/ID: PC-01-002</small>
            </td>
            <td>
                Penanggung Jawab Keuangan,<br>
                <strong>Bendahara PC LAZISNU</strong>
                <div class="sign-space"></div>
                <strong><u>H. Ahmad Fathoni, S.E.</u></strong><br>
                <small>NIP/ID: PC-01-003</small>
            </td>
        </tr>
    </table>
</body>
</html>
