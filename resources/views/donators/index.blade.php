@extends('layouts.adminlte')

@section('title', 'Data Donatur & Penerima Kotak Infaq KOIN NU')
@section('page_title', 'Data Donatur & Penerima Kotak Infaq')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Penerima Kotak Infaq</li>
@endsection

@section('content')
<!-- KPI Ringkasan Data -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-success">{{ number_format($totalDonators) }}</h3>
                <p class="text-muted font-weight-bold mb-0">Total Penerima Kotak</p>
            </div>
            <div class="icon">
                <i class="fas fa-box-open text-success" style="opacity: 0.25;"></i>
            </div>
            <a href="{{ route('donators.index') }}" class="small-box-footer text-success font-weight-bold">
                Semua Kotak <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-primary">{{ number_format($activeDonators) }}</h3>
                <p class="text-muted font-weight-bold mb-0">Kotak Infaq Aktif</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle text-primary" style="opacity: 0.25;"></i>
            </div>
            <a href="{{ route('donators.index', ['status' => 'active']) }}" class="small-box-footer text-primary font-weight-bold">
                Filter Aktif <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-success">{{ number_format($assignedDonators) }}</h3>
                <p class="text-muted font-weight-bold mb-0">Sudah Ada Petugas</p>
            </div>
            <div class="icon">
                <i class="fas fa-user-check text-success" style="opacity: 0.25;"></i>
            </div>
            <a href="{{ route('donators.index', ['assignment' => 'assigned']) }}" class="small-box-footer text-success font-weight-bold">
                Terkordinasi <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="{{ $unassignedDonators > 0 ? 'text-warning font-weight-bold' : 'text-muted' }}">
                    {{ number_format($unassignedDonators) }}
                </h3>
                <p class="text-muted font-weight-bold mb-0">Belum Ada Petugas</p>
            </div>
            <div class="icon">
                <i class="fas fa-exclamation-triangle text-warning" style="opacity: 0.25;"></i>
            </div>
            <a href="{{ route('donators.index', ['assignment' => 'unassigned']) }}" class="small-box-footer text-warning font-weight-bold">
                Perlu Penugasan <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Filter & Pencarian -->
<div class="card card-default shadow-sm mb-4">
    <div class="card-header bg-light">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-search mr-1 text-success"></i> Cari & Filter Penerima Kotak Infaq
        </h3>
        <div class="card-tools">
            <a href="{{ route('donators.export-pdf', request()->query()) }}" target="_blank" class="btn btn-sm btn-danger font-weight-bold mr-1 shadow-sm" title="Langsung Cetak PDF dengan filter yang sedang aktif">
                <i class="fas fa-file-pdf mr-1"></i> Cetak / Export PDF
            </a>
            <button type="button" class="btn btn-sm btn-outline-danger font-weight-bold mr-1 shadow-sm" data-toggle="modal" data-target="#exportPdfDonaturModal" data-bs-toggle="modal" data-bs-target="#exportPdfDonaturModal" title="Atur kustom filter & tanda tangan PDF">
                <i class="fas fa-sliders-h mr-1"></i> Filter Cetak...
            </button>
            <a href="{{ route('donators.distribution') }}" class="btn btn-sm btn-outline-success font-weight-bold mr-1">
                <i class="fas fa-map-marked-alt mr-1"></i> Pembagian Petugas Wilayah
            </a>
            <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold mr-1" data-toggle="modal" data-target="#importDonatorModal" data-bs-toggle="modal" data-bs-target="#importDonatorModal">
                <i class="fas fa-file-excel mr-1"></i> Bulk Import Excel / CSV
            </button>
            <a href="{{ route('donators.create') }}" class="btn btn-sm btn-success font-weight-bold">
                <i class="fas fa-user-plus mr-1"></i> Tambah Donatur Baru
            </a>
        </div>
    </div>
    <div class="card-body">
        @if(session('import_failures'))
            <div class="alert alert-warning alert-dismissible fade show shadow-sm mb-3" role="alert">
                <h5><i class="icon fas fa-exclamation-triangle"></i> Catatan Import (Terdapat {{ session('total_failures') }} baris):</h5>
                <ul class="mb-0 pl-3">
                    @foreach(session('import_failures') as $failure)
                        <li>{{ $failure }}</li>
                    @endforeach
                </ul>
                <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
        @endif
        <form method="GET" action="{{ route('donators.index') }}" class="row">
            <div class="col-md-3 form-group">
                <label>Kata Kunci Pencarian:</label>
                <input type="text" name="search" class="form-control" placeholder="Nama, No. Kotak, NIK, Alamat..." value="{{ request('search') }}">
            </div>

            @if(Auth::user()?->isPc() || !Auth::user())
                <div class="col-md-2 form-group">
                    <label>MWC Cabang:</label>
                    <select name="mwc_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Semua MWC (15) --</option>
                        @foreach($mwcs as $mwc)
                            <option value="{{ $mwc->id }}" {{ request('mwc_id') == $mwc->id ? 'selected' : '' }}>
                                {{ $mwc->code }} - {{ $mwc->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            @if(!Auth::user()?->isRanting())
                <div class="col-md-2 form-group">
                    <label>Ranting / Desa:</label>
                    <select name="ranting_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Semua Ranting --</option>
                        @foreach($rantings as $r)
                            <option value="{{ $r->id }}" {{ request('ranting_id') == $r->id ? 'selected' : '' }}>
                                {{ $r->code }} - {{ $r->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-2 form-group">
                <label>Petugas Lapangan:</label>
                <select name="relawan_id" class="form-control">
                    <option value="">-- Semua Petugas --</option>
                    @foreach($relawans as $relawan)
                        <option value="{{ $relawan->id }}" {{ request('relawan_id') == $relawan->id ? 'selected' : '' }}>
                            {{ $relawan->nama }} ({{ $relawan->area_tugas }})
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 form-group">
                <label>Status Penugasan:</label>
                <select name="assignment" class="form-control">
                    <option value="">-- Status Petugas --</option>
                    <option value="assigned" {{ request('assignment') == 'assigned' ? 'selected' : '' }}>Sudah Ada Petugas</option>
                    <option value="unassigned" {{ request('assignment') == 'unassigned' ? 'selected' : '' }}>Belum Ada Petugas</option>
                </select>
            </div>

            <div class="col-md-1 form-group d-flex align-items-end">
                <button type="submit" class="btn btn-success flex-grow-1 mr-1" title="Terapkan Filter">
                    <i class="fas fa-filter"></i>
                </button>
                <a href="{{ route('donators.export-pdf', request()->query()) }}" target="_blank" class="btn btn-danger" title="Cetak PDF Hasil Filter Ini">
                    <i class="fas fa-file-pdf"></i>
                </a>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Daftar Penerima Kotak Infaq -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-boxes text-success mr-2"></i> Daftar Donatur & Petugas Pengampu Wilayah
        </h3>
        <span class="badge badge-light border">Menampilkan {{ $donators->total() }} data kotak</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover m-0 align-middle">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 130px;">No. Kotak</th>
                        <th>Donatur / Penerima</th>
                        <th>Wilayah & Alamat</th>
                        <th>Petugas Pengampu (Relawan)</th>
                        <th style="width: 100px;">Status</th>
                        <th style="width: 150px;" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($donators as $d)
                        <tr>
                            <td>
                                <span class="badge badge-success font-weight-bold px-2 py-1" style="font-size: 0.9rem;">
                                    <i class="fas fa-box mr-1"></i>{{ $d->box_number }}
                                </span>
                            </td>
                            <td>
                                <a href="{{ route('donators.show', $d->id) }}" class="font-weight-bold text-dark d-block">
                                    {{ $d->name }}
                                </a>
                                @php
                                    $koinWaLink = \App\Services\WhatsAppHelper::generateKoinNuThankYouLink($d->phone, $d->name, $d->box_number);
                                @endphp
                                @if($koinWaLink)
                                    <small class="text-success font-weight-bold d-inline-block">
                                        <a href="{{ $koinWaLink }}" target="_blank" class="text-success" title="Kirim WA Ucapan Terima Kasih">
                                            <i class="fab fa-whatsapp mr-1"></i>{{ $d->phone }}
                                        </a>
                                    </small>
                                @elseif($d->phone)
                                    <small class="text-muted"><i class="fas fa-phone-alt mr-1"></i>{{ $d->phone }}</small>
                                @endif
                                @if($d->nik)
                                    <small class="text-muted ml-2"><i class="fas fa-id-card mr-1"></i>{{ $d->nik }}</small>
                                @endif
                            </td>
                            <td>
                                <div>
                                    <span class="badge badge-light border font-weight-bold">
                                        <i class="fas fa-mosque mr-1 text-success"></i>{{ $d->ranting?->name ?? '-' }}
                                    </span>
                                    <small class="text-muted">({{ $d->ranting?->code ?? '-' }})</small>
                                </div>
                                <small class="text-muted d-block mt-1">
                                    <i class="fas fa-map-marker-alt mr-1 text-danger"></i>
                                    {{ $d->address }}
                                    @if($d->rt || $d->rw)
                                        (RT {{ $d->rt ?? '-' }} / RW {{ $d->rw ?? '-' }})
                                    @endif
                                </small>
                            </td>
                            <td>
                                @if($d->relawan)
                                    <div class="font-weight-bold text-success">
                                        <i class="fas fa-user-check mr-1"></i>{{ $d->relawan->nama }}
                                    </div>
                                    <small class="badge badge-info mr-1">
                                        <i class="fas fa-map-pin mr-1"></i>{{ $d->relawan->area_tugas }}
                                    </small>
                                    @if($d->relawan->no_hp && ($relWaNum = \App\Services\WhatsAppHelper::formatNumber($d->relawan->no_hp)))
                                        <a href="https://wa.me/{{ $relWaNum }}" target="_blank" class="badge badge-light border text-success">
                                            <i class="fab fa-whatsapp"></i> WA
                                        </a>
                                    @endif
                                @else
                                    <div class="mb-1">
                                        <span class="badge badge-warning text-dark font-weight-bold">
                                            <i class="fas fa-exclamation-triangle mr-1"></i>Belum Ada Petugas
                                        </span>
                                    </div>
                                    <!-- Quick Assign Dropdown Form -->
                                    <form action="{{ route('donators.assign', $d->id) }}" method="POST" class="d-inline-flex align-items-center">
                                        @csrf
                                        <select name="relawan_id" class="form-control form-control-sm mr-1" style="max-width: 140px; font-size: 0.8rem;" required>
                                            <option value="">-- Tugaskan --</option>
                                            @foreach($relawans->where('ranting_id', $d->ranting_id) as $rel)
                                                <option value="{{ $rel->id }}">{{ $rel->nama }}</option>
                                            @endforeach
                                        </select>
                                        <button type="submit" class="btn btn-xs btn-outline-success" title="Tetapkan Petugas">
                                            <i class="fas fa-check"></i>
                                        </button>
                                    </form>
                                @endif
                            </td>
                            <td>
                                {!! $d->status_badge !!}
                            </td>
                            <td class="text-center">
                                <div class="btn-group">
                                    @if($koinWaLink)
                                        <a href="{{ $koinWaLink }}" target="_blank" class="btn btn-xs btn-success" title="Kirim WA Ucapan Terima Kasih (Kotak #{{ $d->box_number }})">
                                            <i class="fab fa-whatsapp"></i>
                                        </a>
                                    @else
                                        <button type="button" class="btn btn-xs btn-secondary" disabled title="Nomor WhatsApp donatur tidak tersedia">
                                            <i class="fab fa-whatsapp"></i>
                                        </button>
                                    @endif
                                    <a href="{{ route('donators.show', $d->id) }}" class="btn btn-xs btn-info" title="Detail Kotak">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="{{ route('donators.edit', $d->id) }}" class="btn btn-xs btn-warning text-white" title="Edit Data & Petugas">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <button type="button" 
                                            class="btn btn-xs btn-danger btn-delete" 
                                            data-id="{{ $d->id }}" 
                                            data-name="{{ $d->name }} (Kotak #{{ $d->box_number }})"
                                            title="Hapus Donatur">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </div>
                                <form id="delete-form-{{ $d->id }}" action="{{ route('donators.destroy', $d->id) }}" method="POST" style="display: none;">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="fas fa-box-open fa-3x mb-3 text-muted"></i>
                                <p class="mb-2">Belum ada data penerima kotak infaq yang sesuai kriteria.</p>
                                <a href="{{ route('donators.create') }}" class="btn btn-sm btn-success">
                                    <i class="fas fa-plus mr-1"></i> Daftarkan Donatur Sekarang
                                </a>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white clearfix">
        <div class="float-right">
            {{ $donators->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>

<!-- Modal Bulk Import Donatur dari Excel / CSV -->
<div class="modal fade" id="importDonatorModal" tabindex="-1" role="dialog" aria-labelledby="importDonatorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold" id="importDonatorModalLabel">
                    <i class="fas fa-file-excel mr-2"></i> Bulk Import Donatur & Kotak Infaq
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('donators.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <!-- Target Ranting -->
                    <div class="form-group">
                        <label class="font-weight-bold">Ranting / Desa Tujuan:</label>
                        @if(Auth::user()?->isRanting())
                            <input type="text" class="form-control bg-light font-weight-bold text-success" value="{{ Auth::user()->ranting?->code }} - {{ Auth::user()->ranting?->name }}" readonly>
                            <input type="hidden" name="ranting_id" value="{{ Auth::user()->ranting_id }}">
                        @else
                            <select name="ranting_id" class="form-control" required>
                                <option value="">-- Pilih Ranting Tujuan Import --</option>
                                @foreach($rantings as $ranting)
                                    <option value="{{ $ranting->id }}" {{ !$ranting->is_active ? 'disabled' : '' }}>
                                        {{ $ranting->code }} - {{ $ranting->name }} {{ !$ranting->is_active ? '(Nonaktif Program)' : '' }}
                                    </option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- File Input -->
                    <div class="form-group">
                        <label for="import_file" class="font-weight-bold">File Excel / CSV (.xlsx, .xls, .csv):</label>
                        <div class="custom-file">
                            <input type="file" name="file" class="custom-file-input" id="import_file" accept=".xlsx,.xls,.csv,.txt" required>
                            <label class="custom-file-label" for="import_file">Pilih file spreadsheet...</label>
                        </div>
                        <small class="form-text text-muted">Format file yang didukung: XLSX, XLS, CSV. Ukuran maksimum 5 MB.</small>
                    </div>

                    <!-- Notice Auto Generate Box Number -->
                    <div class="callout callout-info py-2 px-3 bg-light mb-2">
                        <h6 class="font-weight-bold text-info mb-1"><i class="fas fa-magic mr-1"></i> Standarisasi & Auto-Generate Kode Kotak:</h6>
                        <small class="text-muted d-block">
                            Kolom <code>box_number</code> pada file boleh dikosongkan (atau diisi nomor urut). Sistem otomatis menyusun format resmi:
                            <br><strong>KLG-[KODEMWC][KODERANTING]-[RT][RW]-[NO_URUT]</strong>
                            <br>Contoh: <code class="font-weight-bold text-success">KLG-M01R01-0102-003</code>
                        </small>
                    </div>

                    <!-- Template Download Link -->
                    <div class="d-flex justify-content-between align-items-center mt-3 pt-2 border-top">
                        <span class="small text-muted">Belum punya template CSV?</span>
                        <a href="{{ route('donators.template') }}" class="btn btn-xs btn-outline-success font-weight-bold">
                            <i class="fas fa-download mr-1"></i> Download Template CSV
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-upload mr-1"></i> Unggah & Proses Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Export PDF Laporan Donatur (Natural Sort Kode Kaleng) -->
<div class="modal fade" id="exportPdfDonaturModal" tabindex="-1" role="dialog" aria-labelledby="exportPdfDonaturModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold" id="exportPdfDonaturModalLabel">
                    <i class="fas fa-file-pdf mr-2"></i> Export / Cetak Laporan Data Donatur (PDF)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" data-bs-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('donators.export-pdf') }}" method="GET" target="_blank">
                <!-- Parameter Aktif Dari Halaman Utama -->
                <input type="hidden" name="search" value="{{ request('search') }}">
                <input type="hidden" name="relawan_id" value="{{ request('relawan_id') }}">
                <input type="hidden" name="assignment" value="{{ request('assignment') }}">

                <div class="modal-body">
                    <!-- Callout Info Natural Sort & Spesifikasi -->
                    <div class="callout callout-info py-2 px-3 bg-light mb-3">
                        <h6 class="font-weight-bold text-info mb-1">
                            <i class="fas fa-info-circle mr-1"></i> Format Resmi & Natural Sorting Kode Kaleng:
                        </h6>
                        <small class="text-muted d-block">
                            Laporan akan di-generate lengkap dengan <strong>Kop Surat Resmi NU Care-LAZISNU Lamongan</strong>, kolom <strong>Kode Kaleng</strong> di urutan terdepan untuk memudahkan verifikasi fisik PLK di lapangan, serta <strong>Natural Sorting Ascending (1, 2, 3, 10, 11)</strong>.
                        </small>
                    </div>

                    <!-- 1. FILTER RENTANG TANGGAL -->
                    <div class="card card-outline card-secondary mb-3 shadow-none">
                        <div class="card-header py-2 bg-light">
                            <h6 class="card-title font-weight-bold text-dark mb-0">
                                <i class="fas fa-calendar-alt mr-1 text-danger"></i> 1. Filter Rentang Tanggal
                            </h6>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6 form-group mb-2">
                                    <label class="small font-weight-bold">Tanggal Mulai (Start Date):</label>
                                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                                    <small class="text-muted">Kosongkan jika ingin mencakup dari awal.</small>
                                </div>
                                <div class="col-md-6 form-group mb-2">
                                    <label class="small font-weight-bold">Tanggal Sampai (End Date):</label>
                                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                                    <small class="text-muted">Kosongkan jika ingin mencakup hingga sekarang.</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. FILTER WILAYAH / RANTING & MWC -->
                    <div class="card card-outline card-secondary mb-3 shadow-none">
                        <div class="card-header py-2 bg-light">
                            <h6 class="card-title font-weight-bold text-dark mb-0">
                                <i class="fas fa-map-marker-alt mr-1 text-danger"></i> 2. Filter Wilayah / Ranting
                            </h6>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                @if(Auth::user()?->isPc() || !Auth::user())
                                    <div class="col-md-6 form-group mb-2">
                                        <label class="small font-weight-bold">MWC Cabang:</label>
                                        <select name="mwc_id" id="pdf_filter_mwc_id" class="form-control form-control-sm">
                                            <option value="">-- Seluruh MWC (15 Wilayah) --</option>
                                            @foreach($mwcs as $mwc)
                                                <option value="{{ $mwc->id }}" {{ request('mwc_id') == $mwc->id ? 'selected' : '' }}>
                                                    {{ $mwc->code }} - {{ $mwc->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @endif

                                @if(!Auth::user()?->isRanting())
                                    <div class="col-md-{{ Auth::user()?->isPc() || !Auth::user() ? '6' : '12' }} form-group mb-2">
                                        <label class="small font-weight-bold">Ranting / Desa:</label>
                                        <select name="ranting_id" id="pdf_filter_ranting_id" class="form-control form-control-sm">
                                            <option value="">-- Seluruh Ranting --</option>
                                            @foreach($rantings as $r)
                                                <option value="{{ $r->id }}" data-mwc-id="{{ $r->mwc_id }}" {{ request('ranting_id') == $r->id ? 'selected' : '' }}>
                                                    {{ $r->code }} - {{ $r->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                @else
                                    <div class="col-md-12 form-group mb-2">
                                        <label class="small font-weight-bold">Wilayah Ranting Terkunci:</label>
                                        <input type="text" class="form-control form-control-sm bg-light" value="{{ Auth::user()->ranting?->code }} - {{ Auth::user()->ranting?->name }}" readonly>
                                        <input type="hidden" name="ranting_id" value="{{ Auth::user()->ranting_id }}">
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- 3. FILTER PROGRAM & STATUS TRANSAKSI -->
                    <div class="card card-outline card-secondary mb-3 shadow-none">
                        <div class="card-header py-2 bg-light">
                            <h6 class="card-title font-weight-bold text-dark mb-0">
                                <i class="fas fa-hand-holding-heart mr-1 text-danger"></i> 3. Kategori Program & Status Transaksi
                            </h6>
                        </div>
                        <div class="card-body py-2">
                            <div class="row">
                                <div class="col-md-6 form-group mb-2">
                                    <label class="small font-weight-bold">Kategori Program:</label>
                                    <select name="kategori_program" class="form-control form-control-sm">
                                        <option value="koin_nu" selected>KOIN NU (Kotak Infaq Nahdlatul Ulama)</option>
                                        <option value="infaq">Infaq & Shadaqah Terikat</option>
                                        <option value="zakat">Zakat (Fitrah & Maal)</option>
                                        <option value="kampanye">Kampanye Tematik / Khusus</option>
                                        <option value="all">Semua Kategori Program</option>
                                    </select>
                                </div>
                                <div class="col-md-6 form-group mb-2">
                                    <label class="small font-weight-bold">Status Transaksi / Donatur:</label>
                                    <select name="status" class="form-control form-control-sm">
                                        <option value="terverifikasi" selected>Terverifikasi / Aktif (Default)</option>
                                        <option value="all">Semua Status (Aktif & Nonaktif)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. PENGESAHAN TANDA TANGAN (OPSIONAL) -->
                    <div class="card card-outline card-light shadow-none mb-0">
                        <div class="card-header py-2 bg-light d-flex justify-content-between align-items-center" style="cursor: pointer;" data-toggle="collapse" data-target="#collapseSignatures">
                            <h6 class="card-title font-weight-bold text-dark mb-0 small">
                                <i class="fas fa-signature mr-1 text-secondary"></i> Pengaturan Lembar Tanda Tangan (Opsional)
                            </h6>
                            <i class="fas fa-chevron-down text-muted"></i>
                        </div>
                        <div id="collapseSignatures" class="collapse">
                            <div class="card-body py-2">
                                <div class="row">
                                    <div class="col-md-6 form-group mb-2">
                                        <label class="small font-weight-bold">Nama Petugas / Admin Keuangan (Kiri):</label>
                                        <input type="text" name="pihak1_nama" class="form-control form-control-sm" value="{{ Auth::user()?->name ?? 'M. KHOIRUL ANAM, S.Pd.' }}">
                                    </div>
                                    <div class="col-md-6 form-group mb-2">
                                        <label class="small font-weight-bold">Nama Pimpinan / Ketua Cabang (Kanan):</label>
                                        <input type="text" name="pihak2_nama" class="form-control form-control-sm" value="H. M. SYAMSUDDIN, M.Pd.I">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger font-weight-bold px-4 shadow-sm">
                        <i class="fas fa-print mr-1"></i> Buka Pratinjau & Cetak PDF
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const deleteButtons = document.querySelectorAll('.btn-delete');
        deleteButtons.forEach(button => {
            button.addEventListener('click', function () {
                const donatorId = this.getAttribute('data-id');
                const donatorName = this.getAttribute('data-name');

                Swal.fire({
                    title: 'Hapus Penerima Kotak?',
                    text: `Apakah Anda yakin ingin menghapus data "${donatorName}"?`,
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#3085d6',
                    confirmButtonText: '<i class="fas fa-trash mr-1"></i> Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('delete-form-' + donatorId).submit();
                    }
                });
            });
        });

        // Dynamic filter Ranting saat MWC berubah pada Modal PDF
        const mwcSelect = document.getElementById('pdf_filter_mwc_id');
        const rantingSelect = document.getElementById('pdf_filter_ranting_id');

        if (mwcSelect && rantingSelect) {
            mwcSelect.addEventListener('change', function () {
                const selectedMwc = this.value;
                const options = rantingSelect.querySelectorAll('option');

                options.forEach(opt => {
                    const optMwc = opt.getAttribute('data-mwc-id');
                    if (!selectedMwc || !optMwc || optMwc === selectedMwc) {
                        opt.style.display = '';
                    } else {
                        opt.style.display = 'none';
                    }
                });

                // Reset jika ranting yang dipilih tersembunyi
                const currentOpt = rantingSelect.selectedOptions[0];
                if (currentOpt && currentOpt.getAttribute('data-mwc-id') && currentOpt.getAttribute('data-mwc-id') !== selectedMwc) {
                    rantingSelect.value = '';
                }
            });
        }
    });
</script>
@endpush
