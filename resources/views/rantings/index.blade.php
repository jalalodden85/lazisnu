@extends('layouts.adminlte')

@section('title', 'Master Data Ranting NU')
@section('page_title', 'Master Data Ranting Nahdlatul Ulama (Tingkat Desa / Kelurahan)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Master Ranting</li>
@endsection

@section('content')
<!-- Penjelasan Format Kode Ranting -->
<div class="callout callout-success bg-white shadow-sm mb-3">
    <h6 class="font-weight-bold text-success mb-1">
        <i class="fas fa-barcode mr-1"></i> Standarisasi Kode Ranting NU & Kontrol Program:
    </h6>
    <p class="text-xs mb-0 text-muted">
        Format Kode Ranting adalah <strong>[KODE MWC]</strong> ditambah huruf <strong>R</strong> dan nomor urut 2 digit. 
        Contoh: <strong>MWC NU Brondong (M01)</strong> dengan Ranting No. 10 &rarr; <code class="text-dark font-weight-bold">M01R10</code>.
        <span class="text-success font-weight-bold ml-1">Admin PC Lazisnu dapat mengontrol aktivasi keikutsertaan ranting dalam program kotak KOIN NU.</span>
    </p>
</div>

<!-- KPI Ringkasan Status Program Ranting -->
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-primary">{{ number_format($totalRanting) }}</h3>
                <p class="text-muted font-weight-bold mb-0">Total Ranting Terdaftar</p>
            </div>
            <div class="icon">
                <i class="fas fa-mosque text-primary" style="opacity: 0.25;"></i>
            </div>
            <a href="{{ route('rantings.index') }}" class="small-box-footer text-primary font-weight-bold">
                Semua Ranting <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-success">{{ number_format($activeRanting) }}</h3>
                <p class="text-muted font-weight-bold mb-0">Ranting Aktif Program</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle text-success" style="opacity: 0.25;"></i>
            </div>
            <a href="{{ route('rantings.index', ['status' => 'active']) }}" class="small-box-footer text-success font-weight-bold">
                Filter Aktif Program <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-4 col-12">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="{{ $inactiveRanting > 0 ? 'text-danger font-weight-bold' : 'text-muted' }}">{{ number_format($inactiveRanting) }}</h3>
                <p class="text-muted font-weight-bold mb-0">Ranting Nonaktif Program</p>
            </div>
            <div class="icon">
                <i class="fas fa-ban text-danger" style="opacity: 0.25;"></i>
            </div>
            <a href="{{ route('rantings.index', ['status' => 'inactive']) }}" class="small-box-footer text-danger font-weight-bold">
                Filter Nonaktif <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>
</div>

<!-- Card Filter & Pencarian -->
<div class="card card-default shadow-sm mb-4">
    <div class="card-header bg-light d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-filter mr-1 text-success"></i> Filter & Cari Ranting
        </h3>
        @if(Auth::user()?->isPc() || !Auth::user())
            <a href="{{ route('rantings.create') }}" class="btn btn-sm btn-success shadow-sm font-weight-bold">
                <i class="fas fa-plus-circle mr-1"></i> Tambah Ranting Baru
            </a>
        @endif
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('rantings.index') }}" class="row">
            @if(Auth::user()?->isPc() || !Auth::user())
                <div class="col-md-4 form-group">
                    <label>Filter Berdasarkan MWC:</label>
                    <select name="mwc_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Semua 15 MWC --</option>
                        @foreach($mwcs as $m)
                            <option value="{{ $m->id }}" {{ request('mwc_id') == $m->id ? 'selected' : '' }}>
                                [{{ $m->code }}] {{ $m->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
            @endif

            <div class="col-md-3 form-group">
                <label>Status Program KOIN NU:</label>
                <select name="status" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Status Program --</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif Mengikuti Program</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif Program</option>
                </select>
            </div>

            <div class="col-md-4 form-group">
                <label>Kata Kunci Pencarian:</label>
                <input type="text" name="search" class="form-control" placeholder="Cari Kode (M01R01), Nama Ranting, atau Desa..." value="{{ request('search') }}">
            </div>

            <div class="col-md-1 form-group d-flex align-items-end">
                <button type="submit" class="btn btn-success btn-block" title="Terapkan Filter">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Master Data Ranting -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-list mr-1"></i> Daftar Ranting & Status Program KOIN NU
        </h3>
        <span class="badge badge-success px-2 py-1">Menampilkan {{ $rantings->total() }} Ranting</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover m-0 align-middle">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 120px;">Kode Ranting</th>
                        <th>Nama Ranting NU</th>
                        <th>Nama PJ / Petugas</th>
                        <th>MWC Induk</th>
                        <th class="text-center" style="width: 170px;">Status Program</th>
                        <th class="text-center">Jumlah Relawan</th>
                        <th class="text-right">Koin Terhimpun</th>
                        <th class="text-center" style="width: 150px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rantings as $r)
                        <tr>
                            <td>
                                <span class="badge badge-primary font-weight-bold" style="font-size: 0.95rem; letter-spacing: 0.5px;">
                                    {{ $r->code }}
                                </span>
                            </td>
                            <td>
                                <span class="font-weight-bold text-dark d-block">{{ $r->name }}</span>
                                <small class="text-muted"><i class="fas fa-map-marker-alt mr-1 text-danger"></i>Desa: {{ $r->desa ?? '-' }}</small>
                            </td>
                            <td><small class="text-muted"><i class="fas fa-user-check mr-1 text-success"></i>{{ $r->pj_name ?? '-' }}</small></td>
                            <td>
                                <span class="badge badge-light border">{{ $r->mwc->code ?? '' }}</span>
                                <small class="text-muted ml-1">{{ $r->mwc->name ?? '-' }}</small>
                            </td>
                            <td class="text-center">
                                {!! $r->program_status_badge !!}
                                @if(Auth::user()?->isPc() || !Auth::user())
                                    <form id="toggle-form-{{ $r->id }}" action="{{ route('rantings.toggle-status', $r->id) }}" method="POST" class="d-inline ml-1">
                                        @csrf
                                        @if($r->is_active)
                                            <button type="button" 
                                                    class="btn btn-xs btn-outline-danger btn-toggle-status" 
                                                    data-id="{{ $r->id }}" 
                                                    data-name="{{ $r->name }} ({{ $r->code }})"
                                                    data-action="NONAKTIFKAN"
                                                    title="Nonaktifkan dari Program KOIN NU">
                                                <i class="fas fa-toggle-on"></i> Nonaktifkan
                                            </button>
                                        @else
                                            <button type="button" 
                                                    class="btn btn-xs btn-outline-success btn-toggle-status" 
                                                    data-id="{{ $r->id }}" 
                                                    data-name="{{ $r->name }} ({{ $r->code }})"
                                                    data-action="AKTIFKAN"
                                                    title="Aktifkan ke dalam Program KOIN NU">
                                                <i class="fas fa-toggle-off"></i> Aktifkan
                                            </button>
                                        @endif
                                    </form>
                                @endif
                            </td>
                            <td class="text-center">
                                <a href="{{ route('relawans.index', ['ranting_id' => $r->id]) }}" class="badge badge-warning text-dark font-weight-bold" title="Lihat Relawan">
                                    {{ $r->relawans_count }} Relawan
                                </a>
                            </td>
                            <td class="text-right font-weight-bold text-success">
                                Rp {{ number_format($r->total_collected ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <a href="{{ route('rantings.show', $r->id) }}" class="btn btn-xs btn-info mr-1" title="Detail">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if(Auth::user()?->isPc() || !Auth::user())
                                    <a href="{{ route('rantings.edit', $r->id) }}" class="btn btn-xs btn-warning text-white" title="Edit">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="text-center py-4 text-muted">
                                Tidak ada data Ranting yang sesuai kriteria filter.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white clearfix">
        <div class="float-right">
            {{ $rantings->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const toggleButtons = document.querySelectorAll('.btn-toggle-status');
        toggleButtons.forEach(button => {
            button.addEventListener('click', function () {
                const rantingId = this.getAttribute('data-id');
                const rantingName = this.getAttribute('data-name');
                const actionText = this.getAttribute('data-action');
                const isDeactivating = actionText === 'NONAKTIFKAN';

                Swal.fire({
                    title: `${actionText} Ranting?`,
                    text: isDeactivating 
                        ? `Apakah Anda yakin ingin menonaktifkan "${rantingName}" dari program KOIN NU? Ranting yang nonaktif tidak dapat mendaftarkan kotak infaq baru.`
                        : `Apakah Anda ingin mengaktifkan kembali "${rantingName}" ke dalam program KOIN NU?`,
                    icon: isDeactivating ? 'warning' : 'question',
                    showCancelButton: true,
                    confirmButtonColor: isDeactivating ? '#d33' : '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: `<i class="fas fa-check mr-1"></i> Ya, ${actionText}!`,
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('toggle-form-' + rantingId).submit();
                    }
                });
            });
        });
    });
</script>
@endpush
