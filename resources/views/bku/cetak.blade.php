<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>BUKU KAS UMUM (BKU) - {{ $bkuData['periode_title'] }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            padding: 20px;
            font-size: 11pt;
            line-height: 1.3;
        }

        /* KOP SURAT RESMI */
        .kop-surat {
            border-bottom: 3px double #000;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }
        .logo-kop {
            max-height: 80px;
            object-fit: contain;
        }
        .instansi-title {
            font-size: 1.15rem;
            font-weight: bold;
            margin: 0;
            color: #000;
            letter-spacing: 0.5px;
        }
        .instansi-sub {
            font-size: 1.35rem;
            font-weight: 800;
            margin: 0;
            color: #0a6b38;
        }
        .instansi-meta {
            font-size: 0.85rem;
            margin: 0;
            color: #222;
        }

        /* HEADER LAPORAN BKU */
        .judul-bku {
            font-size: 1.3rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2px;
            text-transform: uppercase;
        }
        .periode-bku {
            font-size: 1.05rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 2px;
            text-transform: uppercase;
            color: #111;
        }
        .entitas-bku {
            font-size: 0.95rem;
            font-weight: bold;
            text-align: center;
            margin-bottom: 16px;
            text-transform: uppercase;
            color: #333;
        }

        /* TABEL T-ACCOUNT RESMI */
        .table-bku-cetak {
            width: 100%;
            table-layout: fixed;
            border-collapse: collapse;
            font-size: 8.5pt;
            margin-bottom: 20px;
        }
        .table-bku-cetak th, .table-bku-cetak td {
            border: 1px solid #000 !important;
            padding: 4px 5px;
            vertical-align: top;
        }
        .table-bku-cetak thead th {
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            background-color: #f0f0f0 !important;
            border-top: 2px solid #000 !important;
            border-bottom: 2px solid #000 !important;
            vertical-align: middle;
        }
        .table-bku-cetak tfoot td {
            border-top: 2px solid #000 !important;
            border-bottom: 3px double #000 !important;
            font-weight: bold;
            background-color: #f7f7f7 !important;
            vertical-align: middle;
        }
        .table-bku-cetak .col-uraian {
            word-wrap: break-word;
            overflow-wrap: break-word;
            word-break: break-word;
            white-space: normal;
        }

        /* LEMBAR PENGESAHAN */
        .pengesahan-area {
            margin-top: 24px;
            page-break-inside: avoid;
        }
        .ttd-box {
            text-align: center;
            font-size: 10pt;
        }
        .ttd-space {
            height: 70px;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        /* PRINT STYLES */
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
                margin: 0;
            }
            @page {
                size: A4 landscape;
                margin: 1cm;
            }
            .table-bku-cetak {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>

    <!-- Bar Navigasi Cetak (Non-Printable) -->
    <div class="no-print mb-4 p-3 bg-light border rounded shadow-sm d-flex justify-content-between align-items-center">
        <div>
            <h6 class="fw-bold text-success mb-1">
                <i class="fas fa-file-pdf me-1"></i> Cetak Dokumen Buku Kas Umum (BKU) T-Account
            </h6>
            <span class="small text-muted">
                Periode: <strong>{{ $bkuData['periode_title'] }}</strong> &nbsp;|&nbsp;
                Entitas: <strong>{{ $wilayahLabel }}</strong> &nbsp;|&nbsp;
                Layout: <strong>Format Lanskap (Landscape A4)</strong>
            </span>
        </div>
        <div class="d-flex align-items-center">
            <button type="button" class="btn btn-secondary btn-sm me-2 font-weight-bold" onclick="window.close()">
                <i class="fas fa-times me-1"></i> Tutup
            </button>
            <button type="button" class="btn btn-danger btn-sm font-weight-bold px-3 shadow-sm" onclick="window.print()">
                <i class="fas fa-print me-1"></i> Cetak / Simpan PDF
            </button>
        </div>
    </div>

    <!-- KOP SURAT RESMI LAZISNU LAMONGAN -->
    <div class="kop-surat">
        <div class="row align-items-center">
            <div class="col-2 text-center">
                <img src="{{ asset('logo_nucare_lazisnu.png') }}" 
                     alt="Logo NU CARE - LAZISNU" 
                     class="logo-kop"
                     onerror="this.onerror=null; this.src='{{ asset('logo_lazisnu_lmg.png') }}';">
            </div>
            <div class="col-10 text-center">
                <div class="instansi-title">LEMBAGA AMIL ZAKAT, INFAQ DAN SHADAQAH NAHDLATUL ULAMA</div>
                <div class="instansi-sub">NU CARE - LAZISNU PCNU LAMONGAN</div>
                <div class="instansi-meta">
                    SK Kemenag RI No. 257 Tahun 2022 &bull; Izin Operasional Lembaga Amil Zakat Skala Nasional
                </div>
                <div class="instansi-meta">
                    Sekretariat: Gedung PCNU Lamongan Lt. 2, Jl. K.H. Hasyim Asy'ari No. 17, Lamongan &bull; Telp/WA: 0851-8899-1926
                </div>
            </div>
        </div>
    </div>

    <!-- JUDUL DOKUMEN BKU -->
    <div class="judul-bku">BUKU KAS UMUM (BKU)</div>
    <div class="periode-bku">PERIODE BULAN {{ $bkuData['periode_title'] }}</div>
    <div class="entitas-bku">{{ strtoupper($wilayahLabel) }}</div>

    <!-- TABEL T-ACCOUNT SPLIT SEIMBANG -->
    <table class="table-bku-cetak">
        <colgroup>
            <!-- Sisi Kiri (Pemasukan - 50%) -->
            <col style="width: 4%;">
            <col style="width: 9%;">
            <col style="width: 22%;">
            <col style="width: 15%;">

            <!-- Sisi Kanan (Pengeluaran - 50%) -->
            <col style="width: 4%;">
            <col style="width: 9%;">
            <col style="width: 22%;">
            <col style="width: 15%;">
        </colgroup>
        <thead>
            <tr>
                <th colspan="4" style="width: 50%;">PEMASUKAN</th>
                <th colspan="4" style="width: 50%;">PENGELUARAN</th>
            </tr>
            <tr>
                <!-- Sisi Kiri (Pemasukan) -->
                <th style="width: 4%;">NO</th>
                <th style="width: 9%;">TANGGAL</th>
                <th style="width: 22%;">URAIAN PEMASUKAN</th>
                <th style="width: 15%;">NOMINAL (Rp)</th>

                <!-- Sisi Kanan (Pengeluaran) -->
                <th style="width: 4%;">NO</th>
                <th style="width: 9%;">TANGGAL</th>
                <th style="width: 22%;">URAIAN PENGELUARAN</th>
                <th style="width: 15%;">NOMINAL (Rp)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($bkuData['t_account_rows'] as $index => $row)
                @php
                    $in = $row['debet'];
                    $out = $row['kredit'];
                @endphp
                <tr>
                    <!-- SISI KIRI (PEMASUKAN) -->
                    @if($in)
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">{{ $in['tanggal'] }}</td>
                        <td class="col-uraian">
                            @if($in['is_saldo_awal'] ?? false)
                                <strong>{{ $in['uraian'] }}</strong>
                            @else
                                {{ $in['uraian'] }}
                            @endif
                        </td>
                        <td class="text-end font-weight-bold">{{ number_format($in['jumlah'], 0, ',', '.') }}</td>
                    @else
                        <td></td><td></td><td></td><td></td>
                    @endif

                    <!-- SISI KANAN (PENGELUARAN) -->
                    @if($out)
                        <td class="text-center">{{ $index + 1 }}</td>
                        <td class="text-center">{{ $out['tanggal'] }}</td>
                        <td class="col-uraian">{{ $out['uraian'] }}</td>
                        <td class="text-end font-weight-bold">{{ number_format($out['jumlah'], 0, ',', '.') }}</td>
                    @else
                        <td></td><td></td><td></td><td></td>
                    @endif
                </tr>
            @endforeach
        </tbody>
        <tfoot>
            <!-- Subtotal Bulan Ini -->
            <tr>
                <td colspan="3" class="text-end font-weight-bold">Total Pemasukan Bulan Ini:</td>
                <td class="text-end font-weight-bold">{{ number_format($bkuData['total_pemasukan_bulan_ini'], 0, ',', '.') }}</td>
                <td colspan="3" class="text-end font-weight-bold">Total Pengeluaran Bulan Ini:</td>
                <td class="text-end font-weight-bold">{{ number_format($bkuData['total_pengeluaran'], 0, ',', '.') }}</td>
            </tr>

            <!-- Akumulasi Jumlah Pemasukan & Saldo Buku Kas Akhir -->
            <tr>
                <td colspan="3" class="text-end font-weight-bold">JUMLAH PEMASUKAN:</td>
                <td class="text-end font-weight-bold">{{ number_format($bkuData['jumlah_pemasukan'], 0, ',', '.') }}</td>
                <td colspan="3" class="text-end font-weight-bold">SALDO BUKU KAS AKHIR:</td>
                <td class="text-end font-weight-bold">{{ number_format($bkuData['saldo_buku'], 0, ',', '.') }}</td>
            </tr>

            <!-- Baris Total Seimbang T-Account -->
            <tr style="background-color: #eaeaea !important; font-size: 9.5pt;">
                <td colspan="3" class="text-end font-weight-bold">TOTAL SEIMBANG:</td>
                <td class="text-end font-weight-bold">Rp {{ number_format($bkuData['jumlah_pemasukan'], 0, ',', '.') }}</td>
                <td colspan="3" class="text-end font-weight-bold">TOTAL SEIMBANG:</td>
                <td class="text-end font-weight-bold">Rp {{ number_format($bkuData['total_seimbang_kanan'], 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- LEMBAR PENGESAHAN TIGA PIHAK OTOMATIS SESUAI DATA ENTRI -->
    <div class="pengesahan-area">
        <div class="row align-items-start">
            <!-- Paling Kiri: Mengetahui Ketua Tanfidz -->
            <div class="col-4 text-center ttd-box">
                <div>{{ $signatories['kiri']['label'] ?? 'Mengetahui,' }}</div>
                <div class="fw-bold">{{ $signatories['kiri']['jabatan'] ?? 'Ketua Tanfidziyah' }}</div>
                <div class="ttd-space"></div>
                <div class="ttd-nama">( {{ $signatories['kiri']['nama'] ?? ($namaKetuaTanfidz ?? '....................................') }} )</div>
                <div class="small text-muted">{{ $signatories['kiri']['sub'] ?? 'Pimpinan Nahdlatul Ulama' }}</div>
            </div>

            <!-- Tengah: Ketua LAZISNU atau UPZIS -->
            <div class="col-4 text-center ttd-box">
                <div>&nbsp;</div>
                <div class="fw-bold">{{ $signatories['tengah']['jabatan'] ?? 'Ketua UPZIS / LAZISNU' }}</div>
                <div class="ttd-space"></div>
                <div class="ttd-nama">( {{ $signatories['tengah']['nama'] ?? ($namaKetuaLazisnu ?? $namaKetua) }} )</div>
                <div class="small text-muted">{{ $signatories['tengah']['sub'] ?? 'NU CARE - LAZISNU' }}</div>
            </div>

            <!-- Paling Kanan: Bendahara -->
            <div class="col-4 text-center ttd-box">
                <div>{{ $signatories['place'] ?? 'Lamongan' }}, {{ $tanggalCetak }}</div>
                <div class="fw-bold">{{ $signatories['kanan']['jabatan'] ?? 'Bendahara' }}</div>
                <div class="ttd-space"></div>
                <div class="ttd-nama">( {{ $signatories['kanan']['nama'] ?? $namaBendahara }} )</div>
                <div class="small text-muted">{{ $signatories['kanan']['sub'] ?? 'Pemeriksa / Keuangan' }}</div>
            </div>
        </div>
    </div>

</body>
</html>
