@extends('layouts.adminlte')

@section('title', 'Buku Kas Umum (BKU) KOIN NU')
@section('page_title', 'Buku Kas Umum (BKU) KOIN NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pentasyarufan.index') }}">Pentasyarufan</a></li>
    <li class="breadcrumb-item active">Buku Kas Umum</li>
@endsection

@section('content')
{{-- Notifikasi Flash Message --}}
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle mr-2"></i> <strong>Berhasil!</strong> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i> <strong>Peringatan!</strong> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-times-circle mr-2"></i> <strong>Terdapat Kesalahan Input:</strong>
        <ul class="mb-0 mt-1 pl-3">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<!-- Filter Parameter Periode BKU & Data Scoping -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h3 class="card-title font-weight-bold text-dark mb-0">
                <i class="fas fa-book-open text-success mr-2"></i>Buku Kas Umum (BKU) — {{ $wilayahLabel }}
            </h3>
        </div>
        <div class="d-flex align-items-center mt-2 mt-md-0">
            <span class="badge badge-success px-3 py-2 font-weight-bold">
                <i class="fas fa-box-open mr-1"></i> Periode: {{ $bkuData['periode_title'] }}
            </span>
            <button type="button" class="btn btn-sm btn-danger font-weight-bold shadow-xs ml-2" data-toggle="modal" data-target="#modalTambahPengeluaran">
                <i class="fas fa-plus-circle mr-1"></i> + Tambah Pengeluaran
            </button>
        </div>
    </div>
    <div class="card-body bg-light py-3">
        <form action="{{ route('bku.index') }}" method="GET" class="row align-items-end">
            <!-- Filter Utama: Periode Pentong (Default Aktif/Open) -->
            <div class="col-md-4 form-group mb-2">
                <label class="small font-weight-bold text-dark">
                    <i class="fas fa-calendar-alt text-success mr-1"></i> Pilih Periode Pentong (Sumber Data)
                </label>
                <select name="penthong_id" class="form-control form-control-sm font-weight-bold" onchange="this.form.submit()">
                    @foreach($penthongs as $pt)
                        <option value="{{ $pt->id }}" {{ $selectedPenthongId == $pt->id ? 'selected' : '' }}>
                            {{ $pt->nama_periode }} ({{ $pt->bulan_nama }} {{ $pt->tahun }}) 
                            — [{{ $pt->isOpen() ? 'AKTIF / OPEN' : 'TUTUP' }}]
                        </option>
                    @endforeach
                </select>
                <small class="text-muted">Default otomatis mengunci pada Periode Pentong yang sedang Aktif/Buka.</small>
            </div>

            <!-- Filter MWC (Jika PC) -->
            @if(auth()->user() && auth()->user()->isPc())
            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Filter MWC NU</label>
                <select name="mwc_id" class="form-control form-control-sm" onchange="this.form.submit()">
                    <option value="">-- Seluruh MWC (Konsolidasi PC) --</option>
                    @foreach($mwcs as $mwc)
                        <option value="{{ $mwc->id }}" {{ request('mwc_id') == $mwc->id ? 'selected' : '' }}>
                            [{{ $mwc->code }}] {{ $mwc->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <!-- Filter Ranting (Jika PC atau MWC) -->
            @if(auth()->user() && (auth()->user()->isPc() || auth()->user()->isMwc()))
                @if($rantings->isNotEmpty())
                <div class="col-md-3 form-group mb-2">
                    <label class="small font-weight-bold text-muted">Filter Ranting Desa</label>
                    <select name="ranting_id" class="form-control form-control-sm" onchange="this.form.submit()">
                        <option value="">-- Seluruh Ranting --</option>
                        @foreach($rantings as $r)
                            <option value="{{ $r->id }}" {{ request('ranting_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->name }} ({{ $r->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
                @endif
            @else
                <!-- Admin Ranting: Data Scoping Terkunci -->
                <div class="col-md-4 form-group mb-2">
                    <label class="small font-weight-bold text-muted">Entitas Pemilik Kas</label>
                    <input type="text" class="form-control form-control-sm bg-white font-weight-bold text-success" 
                           value="{{ $wilayahLabel }}" readonly>
                    <small class="text-muted"><i class="fas fa-lock text-success mr-1"></i> Data Scoping: Hanya kas Ranting Anda</small>
                </div>
            @endif

            <!-- Tombol Aksi -->
            <div class="col-md form-group mb-2 d-flex">
                <button type="submit" class="btn btn-sm btn-success font-weight-bold mr-2 flex-grow-1">
                    <i class="fas fa-sync-alt mr-1"></i> Muat
                </button>
                <a href="{{ route('bku.cetak', request()->all()) }}" target="_blank" class="btn btn-sm btn-danger font-weight-bold mr-1" title="Cetak Format PDF Standar BKU">
                    <i class="fas fa-print mr-1"></i> Cetak
                </a>
                <a href="{{ route('bku.excel', request()->all()) }}" class="btn btn-sm btn-outline-success font-weight-bold" title="Ekspor Spreadsheet Excel">
                    <i class="fas fa-file-excel mr-1"></i> Excel
                </a>
            </div>
        </form>
    </div>
</div>

@if(($bkuData['is_ranting_scope'] ?? false))
<!-- Keterhubungan Akuntansi BKU Ranting & 5 Pilar -->
<div class="alert alert-light border-left-success py-2 px-3 small mb-4 text-dark shadow-xs" style="border-left: 4px solid #28a745; background-color: #f8fbf9;">
    <div class="d-flex align-items-center">
        <i class="fas fa-link text-success fa-2x mr-3"></i>
        <div>
            <strong class="text-success"><i class="fas fa-check-double mr-1"></i> Sinkronisasi Otomatis BKU Ranting & Pagu 5 Pilar:</strong>
            <span class="text-muted d-block mt-1">
                Pada sisi Pengeluaran, sistem secara otomatis mengalokasikan bagian struktur ke <strong>MWC NU ({{ $bkuData['mwc_pct'] ?? 13 }}%)</strong>, <strong>PC LAZISNU ({{ $bkuData['pc_pct'] ?? 5 }}%)</strong>, <strong>PW Jatim ({{ $bkuData['pw_pct'] ?? 1.5 }}%)</strong>, <strong>PBNU Pusat ({{ $bkuData['pb_pct'] ?? 0.5 }}%)</strong>, dan <strong>Hak Amil/Operasional ({{ $bkuData['operasional_pct'] ?? 20 }}%)</strong>. 
                Dengan demikian, <strong>Saldo Buku Kas Ranting (Rp {{ number_format($bkuData['saldo_buku'], 0, ',', '.') }})</strong> selalu menyambung utuh dengan <strong>Sisa Pagu Anggaran 5 Pilar Program Pentasyarufan</strong>.
            </span>
        </div>
    </div>
</div>
@elseif(($bkuData['is_mwc_scope'] ?? false))
<!-- Keterhubungan Akuntansi BKU MWC & 5 Pilar -->
<div class="alert alert-light border-left-info py-2 px-3 small mb-4 text-dark shadow-xs" style="border-left: 4px solid #17a2b8; background-color: #f7fafc;">
    <div class="d-flex align-items-center">
        <i class="fas fa-hand-holding-usd text-info fa-2x mr-3"></i>
        <div>
            <strong class="text-info"><i class="fas fa-check-double mr-1"></i> Pemasukan BKU MWC Berbasis Bagi Hasil Kontribusi Ranting ({{ $bkuData['mwc_pct'] ?? 13 }}% Netto):</strong>
            <span class="text-muted d-block mt-1">
                Pemasukan kas MWC HANYA berasal dari dana kontribusi yang disetorkan oleh Ranting-Ranting di wilayah MWC ini. 
                <strong>Saldo Buku Kas MWC (Rp {{ number_format($bkuData['saldo_buku'], 0, ',', '.') }})</strong> menjadi basis dana untuk <strong>Pagu Anggaran 5 Pilar Program Pentasyarufan Tingkat MWC</strong>.
            </span>
        </div>
    </div>
</div>
@else
<!-- Keterhubungan Akuntansi BKU PC & 5 Pilar -->
<div class="alert alert-light border-left-primary py-2 px-3 small mb-4 text-dark shadow-xs" style="border-left: 4px solid #007bff; background-color: #f8faff;">
    <div class="d-flex align-items-center">
        <i class="fas fa-university text-primary fa-2x mr-3"></i>
        <div>
            <strong class="text-primary"><i class="fas fa-check-double mr-1"></i> Pemasukan BKU PC Cabang Berbasis Bagi Hasil Kontribusi ({{ $bkuData['pc_pct'] ?? 5 }}% Netto):</strong>
            <span class="text-muted d-block mt-1">
                Pemasukan kas Cabang PC LAZISNU HANYA berasal dari dana bagi hasil kontribusi struktur koin NU dari seluruh tingkatan di bawahnya. 
                <strong>Saldo Buku Kas PC (Rp {{ number_format($bkuData['saldo_buku'], 0, ',', '.') }})</strong> menjadi basis pagu penyaluran program 5 Pilar di tingkat Cabang.
            </span>
        </div>
    </div>
</div>
@endif

<!-- 3 WIDGET CARD KALKULASI DASHBOARD BKU (TERHUBUNG SECARA MATEMATIS) -->
<div class="row mb-4">
    <!-- Card 1: Total Pemasukan -->
    <div class="col-lg-4 col-12 mb-3 mb-lg-0">
        <div class="card card-outline card-success shadow-sm h-100 mb-0 border-success">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <span class="badge badge-success px-2 py-1 font-weight-bold">
                    <i class="fas fa-sign-in-alt mr-1"></i> TOTAL PEMASUKAN
                </span>
                <span class="text-muted small font-weight-bold">DEBET [ + ]</span>
            </div>
            <div class="card-body py-3 d-flex flex-column justify-content-between">
                <div>
                    <span class="text-muted text-uppercase small font-weight-bold">Akumulasi Kas Masuk</span>
                    <h2 class="font-weight-bold text-success my-2">
                        Rp {{ number_format($bkuData['jumlah_pemasukan'], 0, ',', '.') }}
                    </h2>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-coins text-warning mr-1"></i> SUM(nominal) transaksi berjenis pemasukan kas.
                    </p>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between small text-muted">
                    <span>Saldo Awal: <strong>Rp {{ number_format($bkuData['saldo_awal'], 0, ',', '.') }}</strong></span>
                    <span>Masuk Periode Ini: <strong>Rp {{ number_format($bkuData['total_pemasukan_bulan_ini'], 0, ',', '.') }}</strong></span>
                </div>
            </div>
            <div class="card-footer bg-light py-2 text-center small text-success font-weight-bold border-top">
                <i class="fas fa-arrow-circle-down mr-1"></i> Total Kas Debet Tersedia
            </div>
        </div>
    </div>

    <!-- Card 2: Total Pengeluaran -->
    <div class="col-lg-4 col-12 mb-3 mb-lg-0">
        <div class="card card-outline card-danger shadow-sm h-100 mb-0 border-danger">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <span class="badge badge-danger px-2 py-1 font-weight-bold">
                    <i class="fas fa-sign-out-alt mr-1"></i> TOTAL PENGELUARAN
                </span>
                <span class="text-muted small font-weight-bold">KREDIT [ &minus; ]</span>
            </div>
            <div class="card-body py-3 d-flex flex-column justify-content-between">
                <div>
                    <span class="text-muted text-uppercase small font-weight-bold">Akumulasi Kas Keluar</span>
                    <h2 class="font-weight-bold text-danger my-2">
                        Rp {{ number_format($bkuData['total_pengeluaran'], 0, ',', '.') }}
                    </h2>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-receipt text-danger mr-1"></i> SUM(nominal) transaksi berjenis pengeluaran kas.
                    </p>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between small text-muted">
                    <span>Pentasyarufan & Operasional</span>
                    <button type="button" class="btn btn-xs btn-outline-danger font-weight-bold" data-toggle="modal" data-target="#modalTambahPengeluaran">
                        <i class="fas fa-plus mr-1"></i> Tambah Pengeluaran
                    </button>
                </div>
            </div>
            <div class="card-footer bg-light py-2 text-center small text-danger font-weight-bold border-top">
                <i class="fas fa-arrow-circle-up mr-1"></i> Total Kas Kredit Dikeluarkan
            </div>
        </div>
    </div>

    <!-- Card 3: Sisa Saldo Akhir -->
    <div class="col-lg-4 col-12 mb-3 mb-lg-0">
        <div class="card card-outline {{ $bkuData['saldo_buku'] < 0 ? 'card-warning border-warning' : 'card-primary border-primary' }} shadow-sm h-100 mb-0">
            <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                <span class="badge {{ $bkuData['saldo_buku'] < 0 ? 'badge-warning text-dark' : 'badge-primary' }} px-2 py-1 font-weight-bold">
                    <i class="fas fa-wallet mr-1"></i> SISA SALDO AKHIR
                </span>
                <span class="text-muted small font-weight-bold">HASIL [ = ]</span>
            </div>
            <div class="card-body py-3 d-flex flex-column justify-content-between">
                <div>
                    <span class="text-muted text-uppercase small font-weight-bold">Saldo Kas Tersedia</span>
                    <h2 class="font-weight-bold {{ $bkuData['saldo_buku'] < 0 ? 'text-warning' : 'text-primary' }} my-2">
                        Rp {{ number_format($bkuData['saldo_buku'], 0, ',', '.') }}
                    </h2>
                    <p class="text-muted small mb-0">
                        <i class="fas fa-calculator text-primary mr-1"></i> <strong>(Total Pemasukan &minus; Total Pengeluaran)</strong>
                    </p>
                </div>
                <div class="mt-3 pt-2 border-top d-flex justify-content-between small text-muted">
                    <span>Status Kas:</span>
                    @if($bkuData['saldo_buku'] < 0)
                        <strong class="text-danger"><i class="fas fa-exclamation-triangle"></i> Defisit Kas!</strong>
                    @else
                        <strong class="text-success"><i class="fas fa-check-circle"></i> Kas Sehat & Seimbang</strong>
                    @endif
                </div>
            </div>
            <div class="card-footer bg-light py-2 text-center small {{ $bkuData['saldo_buku'] < 0 ? 'text-danger font-weight-bold' : 'text-primary font-weight-bold' }} border-top">
                <i class="fas fa-shield-alt text-success mr-1"></i> Sisa Dana Riil Kas BKU
            </div>
        </div>
    </div>
</div>

<!-- Standarisasi Chart of Accounts (COA) Banner -->
<div class="alert alert-light border shadow-xs py-2 px-3 mb-3 small d-flex flex-wrap justify-content-between align-items-center">
    <div>
        <strong><i class="fas fa-tags text-success mr-1"></i> Standarisasi Kode Transaksi BKU:</strong>
        <span class="badge badge-primary ml-1">NU Cerdas</span> (Pendidikan) &nbsp;
        <span class="badge badge-warning text-dark ml-1">NU Berdaya</span> (Ekonomi) &nbsp;
        <span class="badge badge-success ml-1">NU Sehat</span> (Kesehatan) &nbsp;
        <span class="badge badge-danger ml-1">NU Damai</span> (Sosial) &nbsp;
        <span class="badge badge-info ml-1">NU Hijau</span> (Dakwah) &nbsp;
        <span class="badge badge-secondary ml-1">NU-O</span> (Hak Amil) &nbsp;
        <span class="badge badge-dark ml-1">NU-PB</span> (Pindah Buku)
    </div>
    <div class="text-muted font-weight-bold">
        Entitas: <span class="text-success">{{ $wilayahLabel }}</span>
    </div>
</div>

<!-- TABEL UTAMA: T-ACCOUNT BUKU KAS UMUM (DUA SISI SEIMBANG) -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header bg-white py-3 text-center border-bottom">
        <h4 class="font-weight-bold text-dark mb-1 text-uppercase" style="letter-spacing: 0.5px;">
            BUKU KAS UMUM (BKU)
        </h4>
        <h6 class="font-weight-bold text-success mb-0">
            PERIODE BULAN {{ $bkuData['periode_title'] }}
        </h6>
        <div class="small text-muted mt-1">{{ strtoupper($wilayahLabel) }}</div>
    </div>

    <div class="card-body table-responsive p-0">
        <table class="table table-bordered table-bku mb-0">
            <thead>
                <!-- Baris Kategori Utama: Pemasukan (Kiri) vs Pengeluaran (Kanan) -->
                <tr>
                    <th colspan="4" class="text-center bg-success text-white py-2 font-weight-bold" style="width: 50%; font-size: 1rem;">
                        <i class="fas fa-sign-in-alt mr-1"></i> PEMASUKAN (DEBET)
                    </th>
                    <th colspan="4" class="text-center bg-danger text-white py-2 font-weight-bold" style="width: 50%; font-size: 1rem;">
                        <i class="fas fa-sign-out-alt mr-1"></i> PENGELUARAN (KREDIT)
                    </th>
                </tr>
                <!-- Sub Header Kolom Sesuai Standar BKU Excel -->
                <tr class="bg-light text-dark small text-center font-weight-bold">
                    <!-- Sisi Kiri -->
                    <th style="width: 95px;">TANGGAL</th>
                    <th style="width: 90px;">KODE</th>
                    <th>URAIAN</th>
                    <th style="width: 140px;">JUMLAH (Rp)</th>

                    <!-- Sisi Kanan -->
                    <th style="width: 95px;">TANGGAL</th>
                    <th style="width: 90px;">KODE</th>
                    <th>URAIAN</th>
                    <th style="width: 140px;">JUMLAH (Rp)</th>
                </tr>
            </thead>
            <tbody>
                @foreach($bkuData['t_account_rows'] as $index => $row)
                    @php
                        $in = $row['debet'];
                        $out = $row['kredit'];
                    @endphp
                    <tr>
                        <!-- ================= SISI KIRI (PEMASUKAN) ================= -->
                        @if($in)
                            <td class="text-center small {{ ($in['is_saldo_awal'] ?? false) ? 'bg-light font-weight-bold text-dark' : '' }}">
                                {{ $in['tanggal'] }}
                            </td>
                            <td class="text-center small">
                                @if(($in['is_saldo_awal'] ?? false))
                                    <span class="text-muted font-weight-bold">-</span>
                                @else
                                    <span class="badge badge-dark">{{ $in['kode'] }}</span>
                                @endif
                            </td>
                            <td class="small {{ ($in['is_saldo_awal'] ?? false) ? 'bg-light font-weight-bold text-success' : '' }}">
                                @if(($in['is_saldo_awal'] ?? false))
                                    <i class="fas fa-arrow-circle-right text-success mr-1"></i><strong>{{ $in['uraian'] }}</strong>
                                @else
                                    {{ $in['uraian'] }}
                                @endif
                            </td>
                            <td class="text-right font-weight-bold {{ ($in['is_saldo_awal'] ?? false) ? 'bg-light text-primary' : 'text-dark' }}">
                                {{ number_format($in['jumlah'], 0, ',', '.') }}
                            </td>
                        @else
                            <!-- Blank Space agar Seimbang -->
                            <td class="bg-light-blank"></td>
                            <td class="bg-light-blank"></td>
                            <td class="bg-light-blank"></td>
                            <td class="bg-light-blank"></td>
                        @endif

                        <!-- ================= SISI KANAN (PENGELUARAN) ================= -->
                        @if($out)
                            <td class="text-center small">
                                {{ $out['tanggal'] }}
                            </td>
                            <td class="text-center small">
                                @php
                                    $badgeClass = match($out['kode']) {
                                        'NU Cerdas'       => 'badge-primary',
                                        'NU Berdaya'      => 'badge-warning text-dark',
                                        'NU Sehat'        => 'badge-success',
                                        'NU Damai'        => 'badge-danger',
                                        'NU Hijau'        => 'badge-info',
                                        'NU-O'            => 'badge-secondary',
                                        'NU-KONTRIBUSI'   => 'badge-warning font-weight-bold text-dark border border-warning shadow-xs',
                                        default           => 'badge-dark',
                                    };
                                @endphp
                                <span class="badge {{ $badgeClass }}">{{ $out['kode'] }}</span>
                            </td>
                            <td class="small">
                                {{ $out['uraian'] }}
                                @if(!empty($out['is_auto']))
                                    <span class="badge badge-light border text-info ml-1" title="Dihitung otomatis oleh sistem sesuai ketentuan persentase resmi">
                                        <i class="fas fa-magic text-info mr-1"></i>Otomatis
                                    </span>
                                @endif
                                @if(!empty($out['bukti_kwitansi']))
                                    <div class="mt-1">
                                        <a href="{{ asset('storage/' . $out['bukti_kwitansi']) }}" target="_blank" class="btn btn-xs btn-outline-info font-weight-bold shadow-xs" title="Lihat Bukti Kwitansi / Nota Struk">
                                            <i class="fas fa-file-invoice mr-1"></i>Lihat Bukti
                                        </a>
                                    </div>
                                @endif
                            </td>
                            <td class="text-right text-danger font-weight-bold">
                                {{ number_format($out['jumlah'], 0, ',', '.') }}
                            </td>
                        @else
                            <!-- Blank Space agar Seimbang -->
                            <td class="bg-light-blank"></td>
                            <td class="bg-light-blank"></td>
                            <td class="bg-light-blank"></td>
                            <td class="bg-light-blank"></td>
                        @endif
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="font-weight-bold">
                <!-- Baris 1: Total Bulan Ini -->
                <tr class="bg-light">
                    <td colspan="3" class="text-right small font-weight-bold">Total Pemasukan Bulan Ini:</td>
                    <td class="text-right text-success font-weight-bold">
                        {{ number_format($bkuData['total_pemasukan_bulan_ini'], 0, ',', '.') }}
                    </td>
                    <td colspan="3" class="text-right small font-weight-bold">Total Pengeluaran Bulan Ini:</td>
                    <td class="text-right text-danger font-weight-bold">
                        {{ number_format($bkuData['total_pengeluaran'], 0, ',', '.') }}
                    </td>
                </tr>

                <!-- Baris 2: Akumulasi Jumlah Pemasukan & Saldo Buku Kas Akhir -->
                <tr class="bg-light">
                    <td colspan="3" class="text-right font-weight-bold text-success">
                        JUMLAH PEMASUKAN (Saldo Awal + Masuk):
                    </td>
                    <td class="text-right text-success font-weight-bold" style="font-size: 1.05rem;">
                        {{ number_format($bkuData['jumlah_pemasukan'], 0, ',', '.') }}
                    </td>
                    <td colspan="3" class="text-right font-weight-bold text-primary">
                        SALDO BUKU KAS AKHIR BULAN:
                    </td>
                    <td class="text-right text-primary font-weight-bold" style="font-size: 1.05rem;">
                        {{ number_format($bkuData['saldo_buku'], 0, ',', '.') }}
                    </td>
                </tr>

                <!-- Baris 3: Total Seimbang T-Account (Debet = Kredit + Saldo Buku) -->
                <tr class="bg-dark text-white text-uppercase" style="font-size: 1.05rem;">
                    <td colspan="3" class="text-right">TOTAL SEIMBANG:</td>
                    <td class="text-right font-weight-bold text-warning">
                        Rp {{ number_format($bkuData['jumlah_pemasukan'], 0, ',', '.') }}
                    </td>
                    <td colspan="3" class="text-right">TOTAL SEIMBANG (Pengeluaran + Saldo):</td>
                    <td class="text-right font-weight-bold text-warning">
                        Rp {{ number_format($bkuData['total_seimbang_kanan'], 0, ',', '.') }}
                    </td>
                </tr>
            </tfoot>
        </table>
    </div>

    <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
        <small class="text-muted">
            <i class="fas fa-check-double text-success mr-1"></i> Seluruh angka pada buku kas umum telah terverifikasi secara matematis dengan status <strong>Seimbang (Zero Discrepancy)</strong>.
        </small>
        <div>
            <a href="{{ route('bku.cetak', request()->all()) }}" target="_blank" class="btn btn-danger btn-sm font-weight-bold px-3">
                <i class="fas fa-print mr-1"></i> Pratinjau Cetak Lembar Pengesahan
            </a>
        </div>
    </div>
</div>

{{-- MODAL POP-UP FORM INPUT PENGELUARAN BKU --}}
<div class="modal fade" id="modalTambahPengeluaran" tabindex="-1" role="dialog" aria-labelledby="modalTambahPengeluaranLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('bku.pengeluaran.store') }}" method="POST" enctype="multipart/form-data" id="formTambahPengeluaran">
                @csrf
                <input type="hidden" name="penthong_id" value="{{ $selectedPenthongId }}">
                @if(!empty($filters['mwc_id']))
                    <input type="hidden" name="mwc_id" value="{{ $filters['mwc_id'] }}">
                @endif
                @if(!empty($filters['ranting_id']))
                    <input type="hidden" name="ranting_id" value="{{ $filters['ranting_id'] }}">
                @endif

                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title font-weight-bold" id="modalTambahPengeluaranLabel">
                        <i class="fas fa-plus-circle mr-2"></i> Tambah Pengeluaran Kas BKU
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body p-4">
                    {{-- Alert Info Sisa Saldo Kas Kas Tersedia --}}
                    <div class="alert alert-light border py-2 px-3 mb-3 d-flex justify-content-between align-items-center" style="border-left: 4px solid #007bff !important; background-color: #f7faff;">
                        <div>
                            <span class="text-muted small text-uppercase font-weight-bold d-block">Sisa Saldo Kas BKU Tersedia:</span>
                            <span class="font-weight-bold text-primary" style="font-size: 1.25rem;">
                                Rp {{ number_format($bkuData['saldo_buku'], 0, ',', '.') }}
                            </span>
                        </div>
                        <div class="text-right small text-muted">
                            Entitas: <strong class="text-dark">{{ $wilayahLabel }}</strong><br>
                            Periode: <strong class="text-dark">{{ $bkuData['periode_title'] }}</strong>
                        </div>
                    </div>

                    {{-- Dynamic Warning Alert if Nominal Exceeds Balance --}}
                    <div id="alertNominalDefisit" class="alert alert-danger py-2 px-3 mb-3 small d-none shadow-xs">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Peringatan Defisit:</strong> Nominal yang Anda masukkan (<span id="txtNominalInput">0</span>) melebihi sisa saldo kas (Rp {{ number_format($bkuData['saldo_buku'], 0, ',', '.') }}). Sistem akan menolak transaksi ini karena saldo kas tidak mencukupi!
                    </div>

                    <div class="row">
                        <!-- Kategori Pengeluaran (*) -->
                        <div class="col-md-6 form-group mb-3">
                            <label for="kategori" class="font-weight-bold small text-dark">
                                Kategori Pengeluaran <span class="text-danger">*</span>
                            </label>
                            <select name="kategori" id="kategori" class="form-control font-weight-bold" required>
                                <option value="">-- Pilih Kategori Pengeluaran --</option>
                                <option value="Pendidikan" {{ old('kategori') == 'Pendidikan' ? 'selected' : '' }}>Pendidikan (NU Cerdas)</option>
                                <option value="Kesehatan" {{ old('kategori') == 'Kesehatan' ? 'selected' : '' }}>Kesehatan (NU Sehat)</option>
                                <option value="Ekonomi" {{ old('kategori') == 'Ekonomi' ? 'selected' : '' }}>Ekonomi (NU Berdaya)</option>
                                <option value="Sosial" {{ old('kategori') == 'Sosial' ? 'selected' : '' }}>Sosial (NU Damai)</option>
                                <option value="Dakwah" {{ old('kategori') == 'Dakwah' ? 'selected' : '' }}>Dakwah (NU Hijau)</option>
                                <option value="Biaya Operasional" {{ old('kategori') == 'Biaya Operasional' ? 'selected' : '' }}>Biaya Operasional (NU-O)</option>
                            </select>
                            <small class="text-muted">Pilih bidang program pentasyarufan atau biaya operasional.</small>
                        </div>

                        <!-- Tanggal Pengeluaran (*) -->
                        <div class="col-md-6 form-group mb-3">
                            <label for="tanggal" class="font-weight-bold small text-dark">
                                Tanggal Pengeluaran <span class="text-danger">*</span>
                            </label>
                            <input type="date" name="tanggal" id="tanggal" class="form-control font-weight-bold" 
                                   value="{{ old('tanggal', date('Y-m-d')) }}" required>
                            <small class="text-muted">Default tanggal hari ini.</small>
                        </div>
                    </div>

                    <div class="row">
                        <!-- Nominal Pengeluaran (*) dengan Format Ribuan Otomatis -->
                        <div class="col-md-6 form-group mb-3">
                            <label for="nominal_mask" class="font-weight-bold small text-dark">
                                Nominal Pengeluaran (Rp) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold bg-light">Rp</span>
                                </div>
                                <input type="text" name="nominal" id="nominal_mask" class="form-control font-weight-bold text-dark text-right" 
                                       placeholder="0" value="{{ old('nominal') }}" required autocomplete="off">
                            </div>
                            <small class="text-muted">Format ribuan otomatis saat mengetik angka.</small>
                        </div>

                        <!-- Penerima / Nama Toko / Vendor (Opsional) -->
                        <div class="col-md-6 form-group mb-3">
                            <label for="penerima_vendor" class="font-weight-bold small text-dark">
                                Penerima / Nama Toko / Vendor <span class="text-muted font-weight-normal">(Opsional)</span>
                            </label>
                            <input type="text" name="penerima_vendor" id="penerima_vendor" class="form-control" 
                                   placeholder="Contoh: Toko Barokah / Klinik Sehat / Bpk. Fulan" 
                                   value="{{ old('penerima_vendor') }}">
                            <small class="text-muted">Pihak ketiga penyedia barang/jasa atau penerima manfaat.</small>
                        </div>
                    </div>

                    <!-- Uraian Keperluan Belanja / Pentasyarufan (*) -->
                    <div class="form-group mb-3">
                        <label for="uraian" class="font-weight-bold small text-dark">
                            Uraian Keperluan Belanja / Pentasyarufan <span class="text-danger">*</span>
                        </label>
                        <textarea name="uraian" id="uraian" class="form-control" rows="3" 
                                  placeholder="Contoh: Bisyaroh petugas atau Bantuan biaya sekolah anak yatim" required>{{ old('uraian') }}</textarea>
                        <small class="text-muted">Jelaskan keperluan dan rincian belanja secara jelas, transparan, dan akuntabel.</small>
                    </div>

                    <!-- Bukti Kwitansi / Nota Struk (Opsional, max 2MB) -->
                    <div class="form-group mb-0">
                        <label for="bukti_kwitansi" class="font-weight-bold small text-dark">
                            Bukti Kwitansi / Nota Struk <span class="text-muted font-weight-normal">(Opsional, Maks 2MB)</span>
                        </label>
                        <div class="custom-file">
                            <input type="file" name="bukti_kwitansi" id="bukti_kwitansi" class="custom-file-input" 
                                   accept="image/jpeg,image/png,image/jpg,image/webp,application/pdf">
                            <label class="custom-file-label" for="bukti_kwitansi">Pilih file foto nota atau kwitansi...</label>
                        </div>
                        <small class="text-muted d-block mt-1">
                            <i class="fas fa-file-image text-info mr-1"></i> Format file didukung: Gambar (JPG, PNG) atau PDF (Maksimal 2 MB).
                        </small>
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" id="btnSimpanPengeluaran" class="btn btn-danger font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Pengeluaran
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
.table-bku {
    border: 2px solid #333 !important;
}
.table-bku th, .table-bku td {
    border: 1px solid #ddd !important;
    vertical-align: middle !important;
    padding: 6px 8px;
}
.table-bku thead th {
    border-bottom: 2px solid #333 !important;
}
.table-bku tfoot td {
    border-top: 2px solid #333 !important;
}
.bg-light-blank {
    background-color: #fafbfc;
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const nominalInput = document.getElementById('nominal_mask');
    const alertDefisit = document.getElementById('alertNominalDefisit');
    const txtNominalInput = document.getElementById('txtNominalInput');
    const btnSimpan = document.getElementById('btnSimpanPengeluaran');
    const saldoKasTersedia = {{ (float) ($bkuData['saldo_buku'] ?? 0) }};

    function formatRibuan(angka) {
        return angka.replace(/\D/g, "").replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    if (nominalInput) {
        nominalInput.addEventListener('input', function () {
            let rawValue = this.value.replace(/\D/g, '');

            if (rawValue === '') {
                this.value = '';
                if (alertDefisit) alertDefisit.classList.add('d-none');
                this.classList.remove('is-invalid');
                return;
            }

            this.value = formatRibuan(rawValue);

            let numValue = parseFloat(rawValue) || 0;
            if (numValue > saldoKasTersedia) {
                if (alertDefisit) {
                    alertDefisit.classList.remove('d-none');
                    if (txtNominalInput) txtNominalInput.innerText = 'Rp ' + formatRibuan(rawValue);
                }
                this.classList.add('is-invalid');
            } else {
                if (alertDefisit) alertDefisit.classList.add('d-none');
                this.classList.remove('is-invalid');
            }
        });
    }

    // Update label custom-file-input saat file dipilih
    const fileInput = document.getElementById('bukti_kwitansi');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            let fileName = e.target.files[0] ? e.target.files[0].name : 'Pilih file foto nota atau kwitansi...';
            let nextSibling = e.target.nextElementSibling;
            if (nextSibling && nextSibling.classList.contains('custom-file-label')) {
                nextSibling.innerText = fileName;
            }
        });
    }

    // Validasi saat submit form
    const formPengeluaran = document.getElementById('formTambahPengeluaran');
    if (formPengeluaran) {
        formPengeluaran.addEventListener('submit', function (e) {
            let rawValue = nominalInput.value.replace(/\D/g, '');
            let numValue = parseFloat(rawValue) || 0;

            if (numValue <= 0) {
                e.preventDefault();
                alert('Nominal pengeluaran wajib diisi dan harus lebih besar dari Rp 0.');
                nominalInput.focus();
                return false;
            }

            if (numValue > saldoKasTersedia) {
                e.preventDefault();
                alert('Saldo Kas tidak mencukupi! Sisa saldo saat ini Rp ' + formatRibuan(saldoKasTersedia.toString()) + '. Pengeluaran sebesar Rp ' + formatRibuan(rawValue) + ' tidak dapat diproses.');
                nominalInput.focus();
                return false;
            }
        });
    }
});
</script>
@endpush
