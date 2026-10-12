<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>DATA MUSTAHIQ / PENERIMA MANFAAT - LAZISNU LAMONGAN</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #111;
            background: #fff;
            padding: 24px;
            line-height: 1.35;
        }

        /* KOP SURAT RESMI LAZISNU */
        .kop-surat {
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .logo-kop {
            max-height: 84px;
            object-fit: contain;
        }
        .instansi-title {
            font-size: 1.15rem;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 0;
            color: #000;
            text-transform: uppercase;
        }
        .instansi-sub {
            font-size: 1.45rem;
            font-weight: 800;
            margin: 0;
            color: #0a6b38;
            letter-spacing: 0.3px;
        }
        .instansi-meta {
            font-size: 0.85rem;
            margin: 0;
            color: #333;
        }

        /* JUDUL DOKUMEN */
        .judul-dokumen {
            font-size: 1.25rem;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-box {
            font-size: 0.86rem;
            margin-bottom: 16px;
            border: 1px solid #999;
            padding: 8px 12px;
            background-color: #fcfcfc;
        }

        /* TABEL DATA LAPORAN */
        .table-laporan {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.84rem;
        }
        .table-laporan th, .table-laporan td {
            border: 1px solid #000 !important;
            padding: 5px 7px;
            vertical-align: middle;
        }
        .table-laporan thead th {
            background-color: #e9ecef !important;
            color: #000;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.80rem;
        }

        /* LEMBAR PENGESAHAN */
        .tanda-tangan-area {
            margin-top: 36px;
            page-break-inside: avoid;
        }
        .ttd-box {
            text-align: center;
            font-size: 0.88rem;
            min-width: 240px;
        }
        .ttd-space {
            height: 65px;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }

        /* PENGATURAN CETAK (PRINT STYLES) */
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
                margin: 1.2cm 1cm;
            }
        }
    </style>
</head>
<body>

    <!-- Bar Navigasi Tombol Cetak (Non-Printable) -->
    <div class="no-print mb-4 d-flex justify-content-between align-items-center bg-light p-3 border rounded shadow-sm">
        <div>
            <h6 class="fw-bold mb-0 text-success">
                <i class="fa-solid fa-file-pdf me-2"></i> Pratinjau Dokumen: Data Mustahiq / Penerima Manfaat
            </h6>
            <span class="text-muted small">
                Total Data: <strong>{{ number_format($totalCount, 0, ',', '.') }}</strong> Jiwa &nbsp;|&nbsp;
                Filter: <strong>{{ $subHeaderFilter }}</strong> &nbsp;|&nbsp;
                Waktu Cetak: {{ $printedAt }}
            </span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-success fw-bold px-4 shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary">
                Tutup Jendela
            </button>
        </div>
    </div>

    <!-- 1. KOP SURAT RESMI LAZISNU LAMONGAN -->
    <div class="kop-surat d-flex align-items-center gap-3">
        <img src="{{ asset('logo_lazisnu_lmg.png') }}" class="logo-kop" alt="Logo LAZISNU" onerror="this.src='{{ asset('img/logo-lazisnu.png') }}'">
        <div class="text-center flex-grow-1">
            <h4 class="instansi-title">PENGURUS CABANG NAHDLATUL ULAMA LAMONGAN</h4>
            <h3 class="instansi-sub">NU CARE - LAZISNU PCNU LAMONGAN</h3>
            <p class="instansi-meta fw-bold text-dark">LEMBAGA AMIL ZAKAT INFAQ DAN SHADAQAH NAHDLATUL ULAMA</p>
            <p class="instansi-meta">SK Kemenag RI No. 255 Tahun 2021 | Terdaftar Lembaga Amil Zakat Resmi BAZNAS</p>
            <p class="instansi-meta">Alamat Sekretariat: Jl. Kyai Amin No. 9 Lamongan 62213</p>
            <p class="instansi-meta">Hotline: 0851 8899 1926 | Email: lazisnulamongan@gmail.com | Website: lazisnulamongan.org</p>
        </div>
    </div>

    <!-- 2. JUDUL DOKUMEN & SUB-HEADER -->
    <div class="text-center mb-3">
        <h5 class="judul-dokumen">DATA MUSTAHIQ / PENERIMA MANFAAT</h5>
    </div>

    <div class="meta-box">
        <div class="d-flex justify-content-between align-items-center small">
            <div>
                <strong>Parameter Filter:</strong> {{ $subHeaderFilter }}
            </div>
            <div>
                <strong>Total Penerima:</strong> {{ number_format($totalCount, 0, ',', '.') }} Jiwa &nbsp;|&nbsp;
                <strong>Tanggal Cetak:</strong> {{ $printedAt }}
            </div>
        </div>
    </div>

    <!-- 3. TABEL DATA MUSTAHIQ -->
    <table class="table-laporan">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th style="width: 140px;">NIK</th>
                <th>Nama Lengkap</th>
                <th style="width: 40px;">L/P</th>
                <th>Alamat Lengkap</th>
                <th style="width: 120px;">Wilayah Ranting</th>
                <th style="width: 100px;">Kategori Asnaf</th>
                <th>Keterangan / Kerentanan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mustahiqs as $index => $item)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td style="font-family: Consolas, monospace; font-size: 0.88rem;">{{ $item->nik }}</td>
                    <td><strong>{{ $item->nama }}</strong></td>
                    <td class="text-center">{{ $item->jenis_kelamin }}</td>
                    <td>{{ $item->alamat }}</td>
                    <td>
                        {{ $item->ranting?->name ?? '-' }}
                        @if($item->mwc)
                            <small class="text-muted d-block">Kec. {{ $item->mwc->name }}</small>
                        @endif
                    </td>
                    <td class="text-center">
                        <span class="badge bg-light text-dark border">{{ $item->asnaf_label }}</span>
                    </td>
                    <td>{{ $item->kondisi_spesifik ?: ($item->pekerjaan ?: '-') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        Tidak ada data mustahiq yang memenuhi kriteria filter ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <!-- 4. LEMBAR PENGESAHAN TANDA TANGAN -->
    <div class="tanda-tangan-area d-flex justify-content-end">
        <div class="ttd-box">
            <div>Lamongan, {{ now()->translatedFormat('d F Y') }}</div>
            <div class="fw-bold">Pengurus / Petugas Pencetak,</div>
            <div class="ttd-space"></div>
            <div class="ttd-nama">{{ $signerName }}</div>
            <div class="text-muted small">PC LAZISNU Lamongan</div>
        </div>
    </div>

</body>
</html>
