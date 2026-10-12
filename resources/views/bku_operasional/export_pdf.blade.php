<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>LAPORAN PERTANGGUNGJAWABAN DANA OPERASIONAL (HAK AMIL) - LAZISNU LAMONGAN</title>
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
        .table-laporan tfoot td {
            background-color: #f8f9fa !important;
            font-weight: bold;
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
            <h6 class="fw-bold mb-0 text-danger">
                <i class="fa-solid fa-file-pdf me-2"></i> Pratinjau Dokumen: Laporan Pertanggungjawaban Dana Operasional (Hak Amil)
            </h6>
            <span class="text-muted small">
                Wilayah: <strong>{{ $namaWilayah }}</strong> &nbsp;|&nbsp;
                Periode: <strong>{{ $periodeLabel }}</strong> &nbsp;|&nbsp;
                Sisa Kas: <strong class="text-primary">Rp {{ number_format($sisaSaldo, 0, ',', '.') }}</strong>
            </span>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-danger fw-bold px-4 shadow-sm">
                <i class="fa-solid fa-print me-1"></i> Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary">
                Tutup Jendela
            </button>
        </div>
    </div>

    <!-- 1. KOP SURAT RESMI LAZISNU LAMONGAN -->
    <div class="kop-surat d-flex align-items-center gap-3">
        <img src="{{ asset('logo_nucare_lazisnu.png') }}" class="logo-kop" alt="Logo NU CARE - LAZISNU" onerror="this.src='{{ asset('logo_lazisnu_lmg.png') }}'">
        <div class="text-center flex-grow-1">
            <h4 class="instansi-title">PENGURUS CABANG NAHDLATUL ULAMA LAMONGAN</h4>
            <h3 class="instansi-sub">NU CARE - LAZISNU PCNU LAMONGAN</h3>
            <p class="instansi-meta fw-bold text-dark">LEMBAGA AMIL ZAKAT INFAQ DAN SHADAQAH NAHDLATUL ULAMA</p>
            <p class="instansi-meta">SK Kemenag RI No. 255 Tahun 2021 | Terdaftar Lembaga Amil Zakat Resmi BAZNAS</p>
            <p class="instansi-meta">Alamat Sekretariat: Jl. Kyai Amin No. 9 Lamongan 62213</p>
            <p class="instansi-meta">Hotline: 0851 8899 1926 | Email: lazisnulamongan@gmail.com | Website: lazisnulamongan.org</p>
        </div>
    </div>

    <!-- 2. JUDUL DOKUMEN RESMI EKSPLISIT HAK AMIL -->
    <div class="text-center mb-3">
        <h5 class="judul-dokumen">LAPORAN PERTANGGUNGJAWABAN DANA OPERASIONAL (HAK AMIL)</h5>
        <div class="text-muted small">Buku Kas Umum (BKU) Operasional Kelembagaan Tingkat {{ strtoupper($tingkat) }}</div>
    </div>

    <!-- Metadata Sub-Header -->
    <div class="meta-box">
        <div class="d-flex justify-content-between align-items-center small">
            <div>
                <strong>Entitas Pelapor:</strong> {{ $namaWilayah }}<br>
                <strong>Periode Pembukuan:</strong> {{ $periodeLabel }}
            </div>
            <div class="text-end">
                <strong>Sisa Saldo Kas Amil:</strong> <span class="fw-bold text-primary">Rp {{ number_format($sisaSaldo, 0, ',', '.') }}</span><br>
                <strong>Waktu Cetak Dokumen:</strong> {{ $printedAt }}
            </div>
        </div>
    </div>

    <!-- 3. TABEL TRANSAKSI MUTASI BERIMBANG -->
    <table class="table-laporan">
        <thead>
            <tr>
                <th style="width: 35px;">No</th>
                <th style="width: 85px;">Tanggal</th>
                <th style="width: 140px;">No. Transaksi / COA</th>
                <th>Uraian Pengeluaran / Pemasukan</th>
                <th style="width: 130px;">Penerima / Vendor</th>
                <th style="width: 110px;" class="text-end">Debet (Masuk)</th>
                <th style="width: 110px;" class="text-end">Kredit (Keluar)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($mutasis as $idx => $m)
                <tr>
                    <td class="text-center">{{ $idx + 1 }}</td>
                    <td class="text-center">{{ $m->tanggal_transaksi ? $m->tanggal_transaksi->format('d/m/Y') : '-' }}</td>
                    <td>
                        <span class="fw-bold">{{ $m->nomor_transaksi }}</span><br>
                        <small class="text-muted">{{ $m->kategori?->nama_kategori ?? ($m->sumber_dana === 'alokasi_pentong' ? 'Alokasi KOIN' : 'Pemasukan Kas') }}</small>
                    </td>
                    <td>{{ $m->uraian }}</td>
                    <td>{{ $m->penerima_atau_vendor ?: '-' }}</td>
                    <td class="text-end text-success fw-bold">
                        {{ $m->jenis === 'debet' ? 'Rp ' . number_format($m->nominal, 0, ',', '.') : '-' }}
                    </td>
                    <td class="text-end text-danger fw-bold">
                        {{ $m->jenis === 'kredit' ? 'Rp ' . number_format($m->nominal, 0, ',', '.') : '-' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">
                        Tidak ada transaksi kas operasional yang tercatat pada periode ini.
                    </td>
                </tr>
            @endforelse
        </tbody>
        <tfoot>
            <tr>
                <td colspan="5" class="text-end">TOTAL MUTASI PERIODE INI:</td>
                <td class="text-end text-success fw-bold">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</td>
                <td class="text-end text-danger fw-bold">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</td>
            </tr>
            <tr>
                <td colspan="5" class="text-end">SISA SALDO KAS OPERASIONAL AKHIR:</td>
                <td colspan="2" class="text-center text-primary fw-bold fs-6">Rp {{ number_format($sisaSaldo, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- 4. LEMBAR PENGESAHAN TANDA TANGAN BERJENJANG OTOMATIS SESUAI DATA ENTRI -->
    <div class="tanda-tangan-area">
        <div class="d-flex justify-content-between align-items-start px-3">
            <!-- Paling Kiri: Mengetahui Ketua Tanfidz -->
            <div class="ttd-box">
                <div class="text-muted small mb-1">{{ $signatories['kiri']['label'] ?? 'Mengetahui,' }}</div>
                <div class="fw-bold">{{ $signatories['kiri']['jabatan'] ?? 'Ketua Tanfidziyah' }}</div>
                <div class="ttd-space"></div>
                <div class="ttd-nama">( {{ $signatories['kiri']['nama'] ?? '....................................' }} )</div>
                <div class="text-muted small">{{ $signatories['kiri']['sub'] ?? 'Pimpinan Nahdlatul Ulama' }}</div>
            </div>

            <!-- Tengah: Ketua LAZISNU atau UPZIS -->
            <div class="ttd-box">
                <div class="text-muted small mb-1">{{ $signatories['tengah']['label'] ?? '&nbsp;' }}</div>
                <div class="fw-bold">{{ $signatories['tengah']['jabatan'] ?? 'Ketua LAZISNU / UPZIS' }}</div>
                <div class="ttd-space"></div>
                <div class="ttd-nama">( {{ $signatories['tengah']['nama'] ?? '....................................' }} )</div>
                <div class="text-muted small">{{ $signatories['tengah']['sub'] ?? 'NU CARE - LAZISNU' }}</div>
            </div>

            <!-- Paling Kanan: Bendahara -->
            <div class="ttd-box">
                <div class="text-muted small mb-1">{{ $signatories['place'] ?? 'Lamongan' }}, {{ now()->translatedFormat('d F Y') }}</div>
                <div class="fw-bold">{{ $signatories['kanan']['jabatan'] ?? 'Bendahara' }}</div>
                <div class="ttd-space"></div>
                <div class="ttd-nama">( {{ $signatories['kanan']['nama'] ?? '....................................' }} )</div>
                <div class="text-muted small">{{ $signatories['kanan']['sub'] ?? 'Pemeriksa / Keuangan' }}</div>
            </div>
        </div>
    </div>

</body>
</html>
