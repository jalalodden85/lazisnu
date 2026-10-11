<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LAPORAN DATA DONATUR (BERDASARKAN KODE KALENG) - LAZISNU LAMONGAN</title>
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
            max-height: 88px;
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

        /* HEADER DOKUMEN */
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
        .table-laporan tfoot td {
            border-top: 2px solid #000 !important;
            border-bottom: 3px double #000 !important;
            background-color: #f8f9fa !important;
            font-weight: bold;
        }
        .col-kode-kaleng {
            font-family: 'Consolas', 'Courier New', monospace;
            font-weight: bold;
            font-size: 0.88rem;
            text-align: center;
            background-color: #fdfdfd;
        }

        /* LEMBAR PENGESAHAN */
        .tanda-tangan-area {
            margin-top: 36px;
            page-break-inside: avoid;
        }
        .ttd-box {
            text-align: center;
            font-size: 0.88rem;
        }
        .ttd-space {
            height: 65px;
        }
        .ttd-nama {
            font-weight: bold;
            text-decoration: underline;
            margin-bottom: 2px;
        }
        .stempel-garis {
            border-bottom: 1px solid #000;
            display: inline-block;
            min-width: 210px;
            padding-bottom: 2px;
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
                size: A4 portrait;
                margin: 1.2cm 1cm;
            }
        }
    </style>
</head>
<body>

    <!-- Bar Navigasi Tombol Cetak / PDF (Non-Printable) -->
    <div class="no-print mb-4 d-flex justify-content-between align-items-center bg-light p-3 border rounded shadow-sm">
        <div>
            <h6 class="fw-bold mb-0 text-success">
                <i class="fa-solid fa-file-pdf me-2"></i> Pratinjau Dokumen: Laporan Data Donatur (Berdasarkan Kode Kaleng)
            </h6>
            <span class="text-muted small">
                Total Donatur: <strong>{{ number_format($totalCount, 0, ',', '.') }}</strong> orang &nbsp;|&nbsp;
                Urutan Data: <strong class="text-success"><i class="fas fa-sort-numeric-down me-1"></i>Natural Sort Ascending</strong> &nbsp;|&nbsp;
                Wilayah: <strong>{{ $wilayahLabel }}</strong>
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
        $kopData = $kop ?? [
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
            @if(!empty($kopData['sub_afiliasi']))
                <p class="instansi-meta fw-bold text-dark">{{ $kopData['sub_afiliasi'] }}</p>
            @endif
            <p class="instansi-meta">{{ $kopData['legalitas'] }}</p>
            <p class="instansi-meta">{{ $kopData['alamat'] }}</p>
            <p class="instansi-meta">{{ $kopData['kontak'] }}</p>
        </div>
    </div>

    <!-- 2. JUDUL DOKUMEN & METADATA RESMI -->
    <div class="text-center mb-3">
        <h5 class="judul-dokumen">LAPORAN DATA DONATUR (BERDASARKAN KODE KALENG)</h5>
    </div>

    <div class="meta-box">
        <div class="row g-2">
            <div class="col-6">
                <table class="table-borderless w-100 small">
                    <tr>
                        <td style="width: 140px;" class="text-muted">Rentang Periode:</td>
                        <td class="fw-bold text-dark">: {{ $dateRange }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Wilayah / Ranting:</td>
                        <td class="fw-bold text-dark">: {{ $wilayahLabel }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Kategori Program:</td>
                        <td class="fw-bold text-dark">: {{ $kategoriLabel }}</td>
                    </tr>
                </table>
            </div>
            <div class="col-6">
                <table class="table-borderless w-100 small">
                    <tr>
                        <td style="width: 140px;" class="text-muted">Status Transaksi:</td>
                        <td class="fw-bold text-dark">: {{ $statusLabel }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Tanggal Dicetak:</td>
                        <td class="fw-bold text-dark">: {{ $printedAt }}</td>
                    </tr>
                    <tr>
                        <td class="text-muted">Petugas Pengunduh:</td>
                        <td class="fw-bold text-dark">: {{ Auth::user()?->name ?? 'Admin Sistem' }}</td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- 3. STRUKTUR TABEL DATA DONATUR (NATURAL SORT ASCENDING KODE KALENG) -->
    <table class="table-laporan mb-3">
        <thead>
            <tr>
                <th style="width: 5%;">No</th>
                <th style="width: 22%;">Kode Kaleng</th>
                <th style="width: 25%;">Nama Donatur</th>
                <th style="width: 26%;">Alamat / Dusun</th>
                <th style="width: 12%;">Nominal (Rp)</th>
                <th style="width: 10%;">Keterangan</th>
            </tr>
        </thead>
        <tbody>
            @forelse($donators as $idx => $d)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="col-kode-kaleng">{{ $d->kode_kaleng ?? $d->box_number }}</td>
                    <td class="fw-bold">{{ $d->name }}</td>
                    <td>
                        {{ $d->address ?: 'Dusun Setempat' }}
                        @if($d->rt || $d->rw)
                            <span class="text-muted">, RT {{ $d->rt ?? '-' }} / RW {{ $d->rw ?? '-' }}</span>
                        @endif
                        @if($d->ranting)
                            <span class="text-muted">, Desa {{ $d->ranting->name }}</span>
                        @endif
                    </td>
                    <td class="text-end fw-bold">
                        {{ number_format($d->nominal ?? 0, 0, ',', '.') }}
                    </td>
                    <td class="text-center small">
                        @if(($d->status ?? 'active') === 'active')
                            <span class="text-success fw-bold">Aktif</span>
                        @else
                            <span class="text-muted">Nonaktif</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted fst-italic">
                        Tidak ada data donatur yang sesuai dengan kriteria filter yang dipilih.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="4" class="text-end text-uppercase pe-3 fs-6">
                    <strong>TOTAL PEROLEHAN DANA :</strong>
                </td>
                <td class="text-end fs-6 text-success fw-bold">
                    {{ number_format($totalNominal, 0, ',', '.') }}
                </td>
                <td class="text-center small text-muted">
                    {{ number_format($totalCount, 0, ',', '.') }} Donatur
                </td>
            </tr>
        </tfoot>
    </table>

    <!-- 4. LEMBAR PENGESAHAN DUA KOLOM RESMI -->
    @php
        $sigData = $signatures ?? [];
        $docPlaceDate = $sigData['doc_place_date'] ?? ('Lamongan, ' . \Carbon\Carbon::now()->translatedFormat('d F Y'));
        $pihak1Jabatan = $sigData['pihak1_jabatan'] ?? 'Petugas / Admin Keuangan';
        $pihak1Nama = !empty($sigData['pihak1_nama']) ? $sigData['pihak1_nama'] : (Auth::user()?->name ?? 'M. KHOIRUL ANAM, S.Pd.');
        $pihak1Nip = $sigData['pihak1_nip'] ?? 'NIP / ID: 35.24.01.002';

        $pihak2Jabatan = $sigData['pihak2_jabatan'] ?? 'Pimpinan PC LAZISNU Lamongan (Ketua)';
        $pihak2Nama = !empty($sigData['pihak2_nama']) ? $sigData['pihak2_nama'] : 'H. M. SYAMSUDDIN, M.Pd.I';
        $pihak2Nip = $sigData['pihak2_nip'] ?? 'NIP / ID: 35.24.01.001';
    @endphp

    <div class="tanda-tangan-area">
        <div class="row">
            <!-- Kolom Kiri: Petugas / Admin Keuangan -->
            <div class="col-6 ttd-box">
                <p class="mb-1 text-muted">Mengetahui / Pemeriksa,</p>
                <p class="fw-bold mb-0">{{ $pihak1Jabatan }}</p>
                <div class="ttd-space"></div>
                <p class="ttd-nama stempel-garis">( {{ $pihak1Nama }} )</p>
                <div class="small text-muted">{{ $pihak1Nip }}</div>
            </div>

            <!-- Kolom Kanan: Tempat & Tanggal Dokumen + Pimpinan / Ketua -->
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
