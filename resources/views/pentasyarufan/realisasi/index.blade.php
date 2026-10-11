@extends('layouts.adminlte')

@section('title', 'Realisasi Pentasyarufan & SPJ')
@section('page_title', 'Realisasi Pentasyarufan & SPJ')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item">Pentasyarufan</li>
    <li class="breadcrumb-item active">Penyaluran & SPJ</li>
@endsection

@section('content')
<!-- Ringkasan Kartu Statistik -->
<div class="row">
    <div class="col-lg-4 col-6">
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3>Rp {{ number_format($totalNominalDisalurkan, 0, ',', '.') }}</h3>
                <p>Total Dana Terealisasi</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <span class="small-box-footer">Akumulasi Pencairan Dana</span>
        </div>
    </div>

    <div class="col-lg-4 col-6">
        <div class="small-box bg-primary shadow-sm">
            <div class="inner">
                <h3>{{ number_format($totalPenerimaManfaat, 0, ',', '.') }}</h3>
                <p>Jiwa Penerima Manfaat</p>
            </div>
            <div class="icon">
                <i class="fas fa-users"></i>
            </div>
            <span class="small-box-footer">Warga Mustahiq / Binaan</span>
        </div>
    </div>

    <div class="col-lg-4 col-12">
        <div class="small-box bg-warning shadow-sm">
            <div class="inner">
                <h3>{{ $countBelumSpj }}</h3>
                <p>Pencairan Belum Upload SPJ</p>
            </div>
            <div class="icon">
                <i class="fas fa-file-invoice"></i>
            </div>
            <a href="{{ route('pentasyarufan.index', ['spj_status' => 'belum_spj']) }}" class="small-box-footer">
                Lihat yang Perlu SPJ <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<!-- Tabel Daftar Pentasyarufan -->
<div class="card card-outline card-success shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-dark pt-1">
            <i class="fas fa-hand-holding-heart text-success mr-2"></i>Daftar Realisasi Penyaluran Dana & Pertanggungjawaban (SPJ)
        </h3>
        <div class="card-tools">
            <a href="{{ route('pentasyarufan.create') }}" class="btn btn-sm btn-success font-weight-bold shadow-sm">
                <i class="fas fa-plus-circle mr-1"></i> Cairkan Pentasyarufan Baru
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card-body border-bottom bg-light py-3">
        <form action="{{ route('pentasyarufan.index') }}" method="GET" class="row">
            <div class="col-md-2 form-group mb-2">
                <label class="small font-weight-bold text-muted">Periode Penthong</label>
                <select name="penthong_id" class="form-control form-control-sm">
                    <option value="">-- Semua Periode --</option>
                    @foreach($penthongs as $pt)
                        <option value="{{ $pt->id }}" {{ request('penthong_id') == $pt->id ? 'selected' : '' }}>
                            {{ $pt->nama_periode }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 form-group mb-2">
                <label class="small font-weight-bold text-muted">Pilar Program</label>
                <select name="pilar_id" class="form-control form-control-sm">
                    <option value="">-- Semua Pilar --</option>
                    @foreach($pilars as $p)
                        <option value="{{ $p->id }}" {{ request('pilar_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->nama_pilar }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 form-group mb-2">
                <label class="small font-weight-bold text-muted">Status SPJ</label>
                <select name="spj_status" class="form-control form-control-sm">
                    <option value="">-- Semua Status SPJ --</option>
                    <option value="belum_spj" {{ request('spj_status') == 'belum_spj' ? 'selected' : '' }}>Belum SPJ</option>
                    <option value="menunggu_verifikasi" {{ request('spj_status') == 'menunggu_verifikasi' ? 'selected' : '' }}>Menunggu Verifikasi</option>
                    <option value="diterima" {{ request('spj_status') == 'diterima' ? 'selected' : '' }}>SPJ Sah / Diterima</option>
                    <option value="ditolak" {{ request('spj_status') == 'ditolak' ? 'selected' : '' }}>SPJ Ditolak</option>
                </select>
            </div>

            <div class="col-md-2 form-group mb-2">
                <label class="small font-weight-bold text-muted">Tingkat</label>
                <select name="tingkat" class="form-control form-control-sm">
                    <option value="">-- Semua Tingkat --</option>
                    <option value="pc" {{ request('tingkat') == 'pc' ? 'selected' : '' }}>PC Lamongan</option>
                    <option value="mwc" {{ request('tingkat') == 'mwc' ? 'selected' : '' }}>MWC (Kecamatan)</option>
                    <option value="ranting" {{ request('tingkat') == 'ranting' ? 'selected' : '' }}>Ranting (Desa)</option>
                </select>
            </div>

            <div class="col-md-2 form-group mb-2">
                <label class="small font-weight-bold text-muted">Pencarian</label>
                <input type="text" name="search" class="form-control form-control-sm" 
                       placeholder="No. Transaksi, penerima..." value="{{ request('search') }}">
            </div>

            <div class="col-md-2 form-group mb-2 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-success btn-block mr-1">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                @if(request()->anyFilled(['penthong_id', 'pilar_id', 'spj_status', 'tingkat', 'search']))
                    <a href="{{ route('pentasyarufan.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>No. Transaksi & Periode</th>
                    <th>Program Kerja & Pilar</th>
                    <th>Wilayah Pelaksana</th>
                    <th>Penerima Manfaat</th>
                    <th>Nominal Pencairan</th>
                    <th class="text-center" style="width: 140px;">Status SPJ</th>
                    <th class="text-center" style="width: 110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pentasyarufans as $index => $item)
                    <tr>
                        <td>{{ $pentasyarufans->firstItem() + $index }}</td>
                        <td>
                            <code class="text-primary font-weight-bold">{{ $item->nomor_transaksi }}</code>
                            <div class="small text-muted mt-1">
                                <i class="fas fa-calendar-alt mr-1"></i>{{ $item->tanggal_penyaluran->format('d M Y') }}
                            </div>
                            <span class="badge badge-light border text-muted mt-1">
                                <i class="fas fa-coins text-warning mr-1"></i>{{ $item->penthong?->nama_periode ?? 'Global' }}
                            </span>
                        </td>
                        <td>
                            <a href="{{ route('proker.show', $item->program_kerja_id) }}" class="font-weight-bold text-dark d-block">
                                {{ $item->programKerja?->nama_kegiatan }}
                            </a>
                            <span class="badge text-white mt-1" style="background: {{ $item->pilarProgram?->warna ?? '#0a6b38' }};">
                                <i class="{{ $item->pilarProgram?->icon ?? 'fas fa-circle' }} mr-1"></i>
                                {{ $item->pilarProgram?->nama_pilar }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-light border font-weight-bold">{{ strtoupper($item->tingkat) }}</span>
                            <div class="small font-weight-bold text-muted mt-1">{{ $item->wilayah_label }}</div>
                        </td>
                        <td>
                            <strong>{{ $item->penerima_manfaat }}</strong>
                            <small class="text-muted d-block">{{ $item->jumlah_penerima }} Jiwa</small>
                        </td>
                        <td>
                            <strong class="text-danger" style="font-size: 1rem;">
                                Rp {{ number_format($item->nominal_pencairan, 0, ',', '.') }}
                            </strong>
                        </td>
                        <td class="text-center">
                            {!! $item->spj_badge !!}
                        </td>
                        <td class="text-center">
                            <a href="{{ route('pentasyarufan.show', $item->id) }}" class="btn btn-outline-primary btn-xs" title="Lihat Bukti & SPJ">
                                <i class="fas fa-eye mr-1"></i>Detail SPJ
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-inbox fa-2x mb-2 d-block text-secondary"></i>
                            Belum ada riwayat realisasi pentasyarufan yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($pentasyarufans->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <small class="text-muted">
                Halaman {{ $pentasyarufans->currentPage() }} dari {{ $pentasyarufans->lastPage() }}
            </small>
            <div>
                {{ $pentasyarufans->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>
@endsection
