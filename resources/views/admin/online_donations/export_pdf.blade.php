<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LAPORAN PENERIMAAN DONASI ONLINE - LAZISNU LAMONGAN</title>
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
            font-size: 1.40rem;
            font-weight: 800;
            margin: 0;
            color: #0a6b38;
            letter-spacing: 0.3px;
        }
        .instansi-meta {
            font-size: 0.82rem;
            margin: 0;
            color: #333;
        }

        /* HEADER DOKUMEN */
        .judul-dokumen {
            font-size: 1.20rem;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
            text-align: center;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .meta-box {
            font-size: 0.85rem;
            margin-bottom: 16px;
            border: 1px solid #999;
            padding: 8px 12px;
            background-color: #fcfcfc;
        }

        /* TABEL DATA LAPORAN */
        .table-laporan {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.82rem;
        }
        .table-laporan th, .table-laporan td {
            border: 1px solid #000 !important;
            padding: 6px 8px;
            vertical-align: middle;
        }
        .table-laporan thead th {
            background-color: #e9ecef !important;
            color: #000;
            text-align: center;
            font-weight: bold;
            text-transform: uppercase;
            font-size: 0.78rem;
        }
        .table-laporan tfoot td {
            border-top: 2px solid #000 !important;
            border-bottom: 3px double #000 !important;
            background-color: #f8f9fa !important;
            font-weight: bold;
        }
        .col-kode {
            font-family: 'Consolas', 'Courier New', monospace;
            font-weight: bold;
            font-size: 0.82rem;
            text-align: center;
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
                margin: 1cm 0.8cm;
            }
        }
    </style>
</head>
<body>

    <!-- Bar Navigasi Tombol Cetak / PDF (Non-Printable) -->
    <div class="no-print mb-4 d-flex justify-content-between align-items-center bg-light p-3 border rounded shadow-sm">
        <div>
            <h6 class="fw-bold mb-1 text-success">
                <i class="fa-solid fa-file-pdf me-2"></i> Pratinjau Dokumen: Laporan Penerimaan Donasi Online & ZISWAF
            </h6>
            <span class="text-muted small">
                Total Transaksi: <strong>{{ number_format($totalCount, 0, ',', '.') }}</strong> | 
                Dana Terverifikasi: <strong class="text-success">Rp {{ number_format($totalVerifiedNominal, 0, ',', '.') }}</strong> | 
                Filter: <strong>{{ $filterInfo['summary_text'] }}</strong>
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
    @php
        $kopData = [
            'instansi_header' => 'PENGURUS CABANG NAHDLATUL ULAMA LAMONGAN',
            'lembaga_title'   => 'NU CARE - LAZISNU PCNU LAMONGAN',
            'sub_afiliasi'    => 'LEMBAGA AMIL ZAKAT INFAQ DAN SHADAQAH NAHDLATUL ULAMA',
            'legalitas'       => 'SK Kemenag RI No. 255 Tahun 2021 | Terdaftar Lembaga Amil Zakat Resmi BAZNAS',
            'alamat'          => 'Alamat Sekretariat: Jl. Kyai Amin No. 9 Lamongan 62213',
            'kontak'          => 'Hotline: 0851 8899 1926 | Email: lazisnulamongan@gmail.com | Website: lazisnulamongan.org',
        ];
    @endphp

    <div class="kop-surat d-flex align-items-center gap-3">
        <img src="{{ asset('logo_lazisnu_lmg.png') }}" class="logo-kop" alt="Logo LAZISNU" onerror="this.src='{{ asset('img/logo-lazisnu.png') }}'">
        <div class="text-center flex-grow-1">
            <h4 class="instansi-title">{{ $kopData['instansi_header'] }}</h4>
            <h3 class="instansi-sub">{{ $kopData['lembaga_title'] }}</h3>
            <p class="instansi-meta fw-bold text-dark">{{ $kopData['sub_afiliasi'] }}</p>
            <p class="instansi-meta">{{ $kopData['legalitas'] }}</p>
            <p class="instansi-meta">{{ $kopData['alamat'] }}</p>
            <p class="instansi-meta">{{ $kopData['kontak'] }}</p>
        </div>
    </div>

    <!-- 2. JUDUL DOKUMEN & METADATA RESMI -->
    <div class="text-center mb-3">
        <h5 class="judul-dokumen">LAPORAN DATA PENERIMAAN DONASI ONLINE</h5>
        <div class="small text-muted">Sistem Informasi Pengelolaan Zakat, Infaq, Shadaqah & Crowdfunding Digital</div>
    </div>

    <div class="meta-box">
        <div class="row g-2">
            <div class="col-6">
                <table class="table-borderless w-100 small">
                    <tr>
                        <td style="width: 140px;" class="text-muted">Periode Bulan:</td>
                        <td><strong>{{ $filterInfo['bulan'] }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Jenis / Kategori:</td>
                        <td><strong class="text-success">{{ $filterInfo['jenis_donasi'] }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status Transaksi:</td>
                        <td><strong>{{ $filterInfo['status'] }}</strong></td>
                    </tr>
                </table>
            </div>
            <div class="col-6">
                <table class="table-borderless w-100 small">
                    <tr>
                        <td style="width: 150px;" class="text-muted">Total Donasi Tercatat:</td>
                        <td><strong>{{ number_format($totalCount, 0, ',', '.') }} Transaksi</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Nominal Masuk:</td>
                        <td><strong>Rp {{ number_format($totalNominal, 0, ',', '.') }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Total Dana Terverifikasi:</td>
                        <td><strong class="text-success">Rp {{ number_format($totalVerifiedNominal, 0, ',', '.') }}</strong></td>
                    </tr>
                    <tr>
                        <td class="text-muted">Dicetak Pada:</td>
                        <td>{{ $printedAt }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. TABEL DATA LAPORAN -->
    <table class="table-laporan mb-3">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th style="width: 125px;">Kode Donasi</th>
                <th style="width: 95px;">Tanggal</th>
                <th>Nama Donatur</th>
                <th>No. Telepon / WA</th>
                <th>Alamat Donatur</th>
                <th>Jenis / Kategori</th>
                <th>Program Kampanye</th>
                <th style="width: 105px;">Nominal (Rp)</th>
                <th style="width: 75px;">Metode</th>
                <th style="width: 85px;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($donations as $index => $don)
                @php
                    $nominal = (float) $don->total_amount;
                @endphp
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="col-kode">{{ $don->donation_code }}</td>
                    <td class="text-center">{{ $don->created_at ? $don->created_at->format('d/m/Y H:i') : '-' }}</td>
                    <td>
                        <strong>{{ $don->display_name }}</strong>
                        @if($don->is_anonymous)
                            <small class="text-muted d-block fst-italic">(Anonim)</small>
                        @endif
                    </td>
                    <td class="text-center">{{ $don->donor_phone ?: '-' }}</td>
                    <td>{{ $don->alamat ?: '-' }}</td>
                    <td>
                        <span class="badge {{ strtolower($don->kategori ?? '') === 'zakat' ? 'bg-primary' : 'bg-success' }} text-white">
                            {{ $don->kategori_label }}
                        </span>
                    </td>
                    <td><small>{{ $don->campaign?->title ?? '-' }}</small></td>
                    <td class="text-end fw-bold">Rp {{ number_format($nominal, 0, ',', '.') }}</td>
                    <td class="text-center text-uppercase"><small>{{ str_replace('_', ' ', $don->payment_method) }}</small></td>
                    <td class="text-center">
                        @if($don->payment_status === 'verified')
                            <span class="text-success fw-bold">Sah / Lunas</span>
                        @elseif($don->payment_status === 'pending')
                            <span class="text-warning fw-bold">Pending</span>
                        @else
                            <span class="text-muted">{{ ucfirst($don->payment_status) }}</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center py-4 text-muted">
                        Tidak ada data donasi online yang sesuai dengan filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
        @if($donations->count() > 0)
            <tfoot>
                <tr>
                    <td colspan="8" class="text-end text-uppercase">TOTAL AKUMULASI ({{ $donations->count() }} Donasi):</td>
                    <td class="text-end text-success fw-bold">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
                    <td colspan="2" class="text-center small text-muted">
                        Sah: Rp {{ number_format($totalVerifiedNominal, 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        @endif
    </table>

    <!-- 4. LEMBAR PENGESAHAN TANDA TANGAN RESMI -->
    <div class="tanda-tangan-area d-flex justify-content-end">
        <div class="ttd-box">
            <div>Lamongan, {{ now()->translatedFormat('d F Y') }}</div>
            <div class="fw-bold">Pengurus / Petugas Pencetak,</div>
            <div class="ttd-space"></div>
            <div class="ttd-nama">{{ $signerName }}</div>
            <div class="text-muted small">PC LAZISNU Kabupaten Lamongan</div>
        </div>
    </div>

</body>
</html>
