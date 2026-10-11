<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>Cetak Laporan Resmi KOIN NU - LAZISNU PCNU Lamongan</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <style>
        body {
            font-family: 'Times New Roman', Times, serif;
            color: #000;
            background: #fff;
            padding: 20px;
        }
        .kop-surat {
            border-bottom: 3px double #000;
            padding-bottom: 12px;
            margin-bottom: 24px;
        }
        .logo-kop {
            max-height: 95px;
            object-fit: contain;
        }
        .table-bordered th, .table-bordered td {
            border: 1px solid #000 !important;
            padding: 6px 10px;
            font-size: 0.9rem;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                padding: 0;
            }
        }
    </style>
</head>
<body>

    <!-- Tombol Cetak / Download -->
    <div class="no-print mb-4 d-flex justify-content-between align-items-center bg-light p-3 border rounded">
        <div>
            <strong>Pratinjau Dokumen Cetak Laporan Resmi</strong>
            <div class="text-muted small">Silakan klik tombol Cetak di sebelah kanan untuk mencetak atau simpan sebagai PDF.</div>
        </div>
        <div class="d-flex gap-2">
            <button onclick="window.print()" class="btn btn-success fw-bold px-4">
                <i class="fa-solid fa-print me-1"></i> Cetak / Simpan PDF
            </button>
            <button onclick="window.close()" class="btn btn-outline-secondary">
                Tutup
            </button>
        </div>
    </div>

    <!-- KOP SURAT RESMI LAZISNU -->
    <div class="kop-surat d-flex align-items-center gap-4">
        <img src="{{ asset('logo_lazisnu_lmg.png') }}" class="logo-kop" alt="Logo LAZISNU Lamongan">
        <div class="text-center flex-grow-1">
            <h4 class="fw-bold mb-0 text-uppercase tracking-wide">LEMBAGA AMIL ZAKAT INFAQ & SHADAQAH NAHDLATUL ULAMA</h4>
            <h3 class="fw-bold mb-0 text-uppercase">NU CARE - LAZISNU PCNU LAMONGAN</h3>
            <p class="mb-0 small">SK Kemenag RI No. 255 Tahun 2021 | Terdaftar Resmi BAZNAS</p>
            <p class="mb-0 small">Sekretariat: Jl. Kyai Amin No 9 Lamongan 62213</p>
            <p class="mb-0 small">Hotline Call Center: 0851 8899 1926 | Email: lazisnulamongan@gmail.com</p>
        </div>
    </div>

    <!-- JUDUL LAPORAN -->
    <div class="text-center mb-4">
        <h5 class="fw-bold text-uppercase mb-1" style="text-decoration: underline;">
            @if($type === 'rekap_mwc')
                LAPORAN REKAPITULASI PEROLEHAN KOIN TINGKAT MWC
            @else
                LAPORAN REKAPITULASI PENGHIMPUNAN KOIN NU
            @endif
        </h5>
        <div class="small">
            Wilayah: <strong>{{ $mwcName }}</strong> &nbsp;|&nbsp; Periode Bulan: <strong>{{ $period }}</strong>
        </div>
    </div>

    <!-- TABEL DATA -->
    <table class="table table-bordered mb-4">
        <thead class="table-light text-center">
            @if($type === 'rekap_mwc')
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Kode MWC</th>
                    <th>Nama MWC</th>
                    <th>Jumlah Ranting</th>
                    <th>Relawan Aktif</th>
                    <th>Kotak Beredar</th>
                    <th class="text-end">Total Perolehan (Rp)</th>
                </tr>
            @else
                <tr>
                    <th style="width: 40px;">No</th>
                    <th>Kode Invoice</th>
                    <th>Tanggal Setor</th>
                    <th>Nama Relawan</th>
                    <th>Ranting & MWC</th>
                    <th>Area Tugas</th>
                    <th class="text-end">Nominal (Rp)</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @if($type === 'rekap_mwc')
                @foreach($data as $i => $item)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td class="text-center fw-bold">{{ $item->code }}</td>
                        <td>{{ $item->name }} ({{ $item->kecamatan }})</td>
                        <td class="text-center">{{ $item->rantings_count }} Rtg</td>
                        <td class="text-center">{{ $item->relawan_count ?? '-' }}</td>
                        <td class="text-center">{{ $item->donators_count }}</td>
                        <td class="text-end fw-bold">Rp {{ number_format($item->nominal, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @else
                @foreach($data as $i => $item)
                    <tr>
                        <td class="text-center">{{ $i + 1 }}</td>
                        <td>{{ $item->invoice_code }}</td>
                        <td class="text-center">{{ $item->collection_date ? $item->collection_date->format('d/m/Y') : '-' }}</td>
                        <td>{{ $item->relawan->nama ?? '-' }}</td>
                        <td>{{ $item->ranting->name ?? '-' }} ({{ $item->ranting->mwc->code ?? '' }})</td>
                        <td>{{ $item->relawan->area_tugas ?? '-' }}</td>
                        <td class="text-end fw-bold">Rp {{ number_format($item->amount, 0, ',', '.') }}</td>
                    </tr>
                @endforeach
            @endif
        </tbody>
        <tfoot class="table-light fw-bold">
            <tr>
                <td colspan="{{ $type === 'rekap_mwc' ? 6 : 6 }}" class="text-end text-uppercase">TOTAL KESELURUHAN:</td>
                <td class="text-end fs-6">Rp {{ number_format($totalNominal, 0, ',', '.') }}</td>
            </tr>
        </tfoot>
    </table>

    <!-- LEMBAR PENGESAHAN & TANDA TANGAN -->
    <div class="row mt-5">
        <div class="col-4 text-center">
            <p class="mb-5">Mengetahui,<br><strong>Ketua NU CARE - LAZISNU</strong></p>
            <br><br>
            <p class="fw-bold mb-0"><u>H. M. SYAMSUDDIN, M.Pd.I</u></p>
            <small>NIP / ID: 35.24.01.001</small>
        </div>
        <div class="col-4 text-center">
            <p class="mb-5"><br><strong>Bendahara Cabang</strong></p>
            <br><br>
            <p class="fw-bold mb-0"><u>H. AHMAD FAUZI, S.E.</u></p>
            <small>NIP / ID: 35.24.01.002</small>
        </div>
        <div class="col-4 text-center">
            <p class="mb-5">Lamongan, {{ date('d F Y') }}<br><strong>Manajer Program KOIN NU</strong></p>
            <br><br>
            <p class="fw-bold mb-0"><u>M. KHOIRUL ANAM</u></p>
            <small>Petugas Pelaksana Administrasi</small>
        </div>
    </div>

</body>
</html>
