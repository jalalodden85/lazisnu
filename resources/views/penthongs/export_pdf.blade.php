<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LAPORAN PEROLEHAN PENTONG KOIN NU - {{ $penthong->nama_periode }}</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            padding: 24px;
            line-height: 1.35;
        }

        /* KOP SURAT RESMI */
        .kop-surat {
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 18px;
        }
        .logo-kop {
            max-height: 90px;
            object-fit: contain;
        }
        .instansi-title {
            font-size: 1.2rem;
            font-weight: bold;
            letter-spacing: 0.5px;
            margin: 0;
            color: #000;
        }
        .instansi-sub {
            font-size: 1.45rem;
            font-weight: 800;
            margin: 0;
            color: #0a6b38;
        }
        .instansi-meta {
            font-size: 0.85rem;
            margin: 0;
            color: #333;
        }

        /* HEADER LAPORAN */
        .judul-dokumen {
            font-size: 1.25rem;
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 4px;
            text-align: center;
            text-transform: uppercase;
        }
        .meta-box {
            font-size: 0.88rem;
            margin-bottom: 16px;
            border: 1px solid #ccc;
            padding: 8px 12px;
            background-color: #fcfcfc;
        }

        /* TABEL DATA */
        .table-laporan {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
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
            font-size: 0.82rem;
        }
        .table-laporan tfoot td {
            border-top: 2px solid #000 !important;
            border-bottom: 3px double #000 !important;
            background-color: #f8f9fa !important;
            font-weight: bold;
        }

        /* LEMBAR PENGESAHAN */
        .tanda-tangan-area {
            margin-top: 32px;
            page-break-inside: avoid;
        }
        .ttd-box {
            text-align: center;
            font-size: 0.9rem;
        }
        .ttd-space {
            height: 70px;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }
        .stempel-garis {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 200px;
            padding-bottom: 2px;
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
                size: A4 portrait;
                margin: 1.2cm;
            }
        }
    </style>
</head>
<body>

    <!-- Bar Navigasi Tombol Cetak / PDF (Non-Printable) -->
    <div class="no-print mb-4 d-flex justify-content-between align-items-center bg-light p-3 border rounded shadow-sm">
        <div>
            <h6 class="fw-bold mb-0 text-success">
                <i class="fa-solid fa-file-pdf me-2"></i> Pratinjau Dokumen Laporan Resmi LAZISNU
            </h6>
            <span class="text-muted small">
                Tingkat: <strong class="text-uppercase text-dark">{{ $scope['scope_level'] ?? 'PC' }}</strong> &nbsp;|&nbsp;
                Wilayah: <strong>{{ $wilayahLabel }}</strong> &nbsp;|&nbsp;
                Dokumen telah diformat sesuai standar kop surat resmi & pengesahan tanda tangan.
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

    <!-- 1. KOP SURAT RESMI BERJENJANG (PC / MWC / RANTING) -->
    @php
        $kopData = $kop ?? [
            'instansi_header' => 'PENGURUS CABANG NAHDLATUL ULAMA LAMONGAN',
            'lembaga_title'   => 'NU CARE - LAZISNU PCNU LAMONGAN',
            'sub_afiliasi'    => 'NU CARE - LAZISNU',
            'legalitas'       => 'SK Kemenag RI No. 255 Tahun 2021 | Terdaftar Lembaga Amil Zakat Resmi BAZNAS',
            'alamat'          => 'Alamat Sekretariat: Jl. Kyai Amin No. 9 Lamongan 62213',
            'kontak'          => 'Hotline: 0851 8899 1926 | Email: lazisnulamongan@gmail.com',
        ];
    @endphp

    <div class="kop-surat d-flex align-items-center gap-3">
        <img src="{{ asset('logo_lazisnu_lmg.png') }}" class="logo-kop" alt="Logo LAZISNU" onerror="this.src='{{ asset('img/logo-lazisnu.png') }}'">
        <div class="text-center flex-grow-1">
            <h4 class="instansi-title">{{ $kopData['instansi_header'] }}</h4>
            <h3 class="instansi-sub">{{ $kopData['lembaga_title'] }}</h3>
            @if(!empty($kopData['sub_afiliasi']) && $kopData['sub_afiliasi'] !== $kopData['instansi_header'])
                <p class="instansi-meta fw-bold text-dark">{{ $kopData['sub_afiliasi'] }}</p>
            @endif
            <p class="instansi-meta">{{ $kopData['legalitas'] ?? 'SK Kemenag RI No. 255 Tahun 2021 | Terdaftar Lembaga Amil Zakat Resmi BAZNAS' }}</p>
            <p class="instansi-meta">{{ $kopData['alamat'] }} | {{ $kopData['kontak'] }}</p>
        </div>
    </div>

    <!-- 2. JUDUL DOKUMEN & METADATA RESMI -->
    <div class="text-center mb-3">
        <h5 class="judul-dokumen">LAPORAN PEROLEHAN PENTONG KOIN NU</h5>
    </div>

    <div class="meta-box">
        <div class="row g-2">
            <div class="col-md-6 col-12">
                <table class="table-borderless w-100 small">
                    <tr>
                        <td style="width: 140px;" class="text-muted">Nama Periode:</td>
                        <td class="fw-bold text-dark">: {{ $penthong->nama_periode }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Status Periode:</td>
                        <td>: {!! $penthong->status_badge !!}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Rentang Tanggal:</td>
                        <td class="fw-bold text-dark">: {{ $dateRange }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-md-6 col-12">
                <table class="table-borderless w-100 small">
                    <tr>
                        <td style="width: 140px;" class="text-muted">Wilayah / Teritori:</td>
                        <td class="fw-bold text-dark">: {{ $wilayahLabel }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal Dicetak:</td>
                        <td class="fw-bold text-dark">: {{ $printedAt }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. STRUKTUR TABEL DATA LAPORAN -->
    <table class="table-laporan mb-4">
        <thead>
            <tr>
                <th style="width: 38px;">No</th>
                <th style="width: 95px;">Tanggal Setoran</th>
                <th>Nama Ranting / MWC</th>
                <th>Nama Munfiq / Koordinator</th>
                <th style="width: 160px;">No. Kaleng / Identitas</th>
                <th style="width: 130px;">Petugas Lapangan (PLK)</th>
                <th class="text-end" style="width: 130px;">Nominal Setoran</th>
                <th style="width: 110px;">Keterangan / Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($transactions as $index => $tx)
                <tr>
                    <td class="text-center">{{ $index + 1 }}</td>
                    <td class="text-center">{{ $tx->collection_date ? \Carbon\Carbon::parse($tx->collection_date)->format('d/m/Y') : '-' }}</td>
                    <td>
                        <strong>{{ $tx->ranting->name ?? '-' }}</strong>
                        <span class="text-muted small">({{ $tx->mwc->name ?? '-' }})</span>
                    </td>
                    <td>{{ $tx->relawan->nama ?? '-' }}</td>
                    <td>
                        <span class="font-monospace fw-bold">{{ $tx->invoice_code }}</span>
                    </td>
                    <td>
                        {{ $tx->relawan->nama ?? '-' }}
                        @if($tx->relawan?->area_tugas)
                            <small class="text-muted d-block">{{ $tx->relawan->area_tugas }}</small>
                        @endif
                    </td>
                    <td class="text-end fw-bold">
                        Rp {{ number_format($tx->amount, 0, ',', '.') }}
                    </td>
                    <td class="text-center small">
                        {{ $tx->status === 'confirmed' ? 'Terkonfirmasi' : ($tx->notes ?: '-') }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="text-center py-4 text-muted">
                        <em>Belum ada transaksi setoran koin yang tercatat pada cakupan wilayah dan periode pentong ini.</em>
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" class="text-end text-uppercase pe-3">
                    TOTAL AKUMULASI PENERIMAAN DANA (RP):
                </td>
                <td class="text-end fs-6">
                    Rp {{ number_format($totalNominal, 0, ',', '.') }}
                </td>
                <td class="text-center small text-muted">
                    {{ $totalCount }} Transaksi
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- 4. LEMBAR PENGESAHAN DUA KOLOM RESMI (DARI MODAL FORM) -->
    @php
        $sigData = $signatures ?? [];
        $docPlaceDate = $sigData['doc_place_date'] ?? ('Lamongan, ' . \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $pihak1Title = $sigData['pihak1_title'] ?? 'Mengetahui / Verifikator,';
        $pihak1Jabatan = $sigData['pihak1_jabatan'] ?? 'Direktur Eksekutif / Admin Cabang';
        $pihak1Nama = !empty($sigData['pihak1_nama']) ? $sigData['pihak1_nama'] : 'M. KHOIRUL ANAM, S.Pd.';
        $pihak1Nip = $sigData['pihak1_nip'] ?? 'NIP / ID: 35.24.01.002';

        $pihak2Title = $sigData['pihak2_title'] ?? 'Mengetahui / Penanggung Jawab,';
        $pihak2Jabatan = $sigData['pihak2_jabatan'] ?? 'Pimpinan PC LAZISNU Lamongan (Ketua)';
        $pihak2Nama = !empty($sigData['pihak2_nama']) ? $sigData['pihak2_nama'] : 'H. M. SYAMSUDDIN, M.Pd.I';
        $pihak2Nip = $sigData['pihak2_nip'] ?? 'NIP / ID: 35.24.01.001';
    @endphp

    <div class="tanda-tangan-area">
        <div class="row">
            <!-- Kolom Kiri: Pihak 1 (Pemeriksa / Pembuat Laporan) -->
            <div class="col-6 ttd-box">
                <p class="mb-1">{{ $pihak1Title }}</p>
                <p class="fw-bold mb-0">{{ $pihak1Jabatan }}</p>
                <div class="ttd-space"></div>
                <p class="ttd-nama stempel-garis">( {{ $pihak1Nama }} )</p>
                <div class="small text-muted">{{ $pihak1Nip }}</div>
            </div>

            <!-- Kolom Kanan: Tempat & Tanggal Dokumen + Pihak 2 (Penanggung Jawab) -->
            <div class="col-6 ttd-box">
                <p class="mb-1">{{ $docPlaceDate }}</p>
                <p class="fw-bold mb-0">{{ $pihak2Jabatan }}</p>
                <div class="ttd-space"></div>
                <p class="ttd-nama stempel-garis">( {{ $pihak2Nama }} )</p>
                <div class="small text-muted">{{ $pihak2Nip }}</div>
            </div>
        </div>
    </div>

</body>
</html>
