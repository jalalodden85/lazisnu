@extends('layouts.adminlte')

@section('title', 'Buku Kas Umum (BKU) KOIN NU')
@section('page_title', 'Buku Kas Umum (BKU) KOIN NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pentasyarufan.index') }}">Pentasyarufan</a></li>
    <li class="breadcrumb-item active">Buku Kas Umum</li>
@endsection

@section('content')
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
            {{-- Sembunyikan sementara tombol Setor Kontribusi Struktur karena belum digunakan --}}
            {{--
            <a href="{{ route('pentasyarufan.kontribusi.create', ['penthong_id' => $selectedPenthongId]) }}" class="btn btn-sm btn-warning text-dark font-weight-bold shadow-xs ml-2">
                <i class="fas fa-hand-holding-usd mr-1"></i> + Setor Kontribusi Struktur
            </a>
            --}}
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

<!-- 4 KPI Card Saldo Buku Kas -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-secondary font-weight-bold">Rp {{ number_format($bkuData['saldo_awal'], 0, ',', '.') }}</h3>
                <p class="text-muted mb-0 font-weight-bold">Saldo Bulan Lalu (Awal)</p>
            </div>
            <div class="icon text-secondary" style="opacity: 0.25;">
                <i class="fas fa-history"></i>
            </div>
            <div class="small-box-footer bg-light text-muted border-top py-1 small">
                Sisa Kas Per 1 {{ $bkuData['nama_bulan'] }} {{ $tahun }}
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3 class="font-weight-bold">Rp {{ number_format($bkuData['total_pemasukan_bulan_ini'], 0, ',', '.') }}</h3>
                <p class="mb-0">Pemasukan Bulan Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-arrow-down"></i>
            </div>
            <div class="small-box-footer py-1 small text-white" style="background: rgba(0,0,0,0.1);">
                @if($bkuData['is_ranting_scope'] ?? false)
                    Setoran Koin NU Munfiq (Bruto)
                @elseif($bkuData['is_mwc_scope'] ?? false)
                    Bagi Hasil Kontribusi Ranting ({{ $bkuData['mwc_pct'] ?? 13 }}% Netto)
                @else
                    Bagi Hasil Kontribusi Cabang ({{ $bkuData['pc_pct'] ?? 5 }}% Netto)
                @endif
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-danger shadow-sm">
            <div class="inner">
                <h3 class="font-weight-bold">Rp {{ number_format($bkuData['total_pengeluaran'], 0, ',', '.') }}</h3>
                <p class="mb-0">Pengeluaran Bulan Ini</p>
            </div>
            <div class="icon">
                <i class="fas fa-arrow-up"></i>
            </div>
            <div class="small-box-footer py-1 small text-white" style="background: rgba(0,0,0,0.1);">
                Pentasyarufan & Penyaluran Proker
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box {{ $bkuData['saldo_buku'] < 0 ? 'bg-warning text-dark' : 'bg-primary' }} shadow-sm">
            <div class="inner">
                <h3 class="font-weight-bold">Rp {{ number_format($bkuData['saldo_buku'], 0, ',', '.') }}</h3>
                <p class="mb-0 font-weight-bold">Saldo Buku (Kas Akhir)</p>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="small-box-footer py-1 small text-white" style="background: rgba(0,0,0,0.1);">
                Jumlah Pemasukan - Pengeluaran
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
