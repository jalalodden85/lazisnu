@extends('layouts.adminlte')

@section('title', 'Manajemen Program Kerja (Proker)')
@section('page_title', 'Manajemen Program Kerja (Proker)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item">Pentasyarufan</li>
    <li class="breadcrumb-item active">Program Kerja</li>
@endsection

@section('content')
<!-- KPI Ringkasan Proker -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary shadow-sm">
            <div class="inner">
                <h3>{{ $countDraft }}</h3>
                <p>Proker Status Draft</p>
            </div>
            <div class="icon">
                <i class="fas fa-edit"></i>
            </div>
            <a href="{{ route('proker.index', ['status' => 'draft']) }}" class="small-box-footer">
                Filter Draft <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning shadow-sm">
            <div class="inner">
                <h3>{{ $countMenunggu }}</h3>
                <p>Menunggu Persetujuan</p>
            </div>
            <div class="icon">
                <i class="fas fa-hourglass-half"></i>
            </div>
            <a href="{{ route('proker.index', ['status' => 'menunggu_persetujuan']) }}" class="small-box-footer">
                Perlu Approval <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3>{{ $countDisetujui }}</h3>
                <p>Proker Disetujui (Siap Salur)</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <a href="{{ route('proker.index', ['status' => 'disetujui']) }}" class="small-box-footer">
                Siap Dicairkan <i class="fas fa-arrow-circle-right"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-primary shadow-sm">
            <div class="inner">
                <h3>Rp {{ number_format($totalRencanaAnggaran, 0, ',', '.') }}</h3>
                <p>Total Rencana Anggaran</p>
            </div>
            <div class="icon">
                <i class="fas fa-money-check-alt"></i>
            </div>
            <span class="small-box-footer">Rencana Anggaran Biaya (RAB)</span>
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@if(session('warning'))
    <div class="alert alert-warning alert-dismissible fade show text-dark" role="alert">
        <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('warning') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<!-- Tabel Program Kerja -->
<div class="card card-outline card-success shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-dark pt-1">
            <i class="fas fa-clipboard-list text-success mr-2"></i>Daftar Program Kerja (RAB) Berjenjang
        </h3>
        <div class="card-tools">
            <a href="{{ route('proker.create') }}" class="btn btn-sm btn-success font-weight-bold shadow-sm">
                <i class="fas fa-plus-circle mr-1"></i> Ajukan Proker Baru
            </a>
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card-body border-bottom bg-light py-3">
        <form action="{{ route('proker.index') }}" method="GET" class="row">
            <div class="col-md-2 form-group mb-2">
                <label class="small font-weight-bold text-muted">Tingkatan</label>
                <select name="tingkat" class="form-control form-control-sm">
                    <option value="">-- Semua Tingkat --</option>
                    <option value="pc" {{ request('tingkat') == 'pc' ? 'selected' : '' }}>PC Lamongan</option>
                    <option value="mwc" {{ request('tingkat') == 'mwc' ? 'selected' : '' }}>MWC (Kecamatan)</option>
                    <option value="ranting" {{ request('tingkat') == 'ranting' ? 'selected' : '' }}>Ranting (Desa)</option>
                </select>
            </div>

            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Pilar Program</label>
                <select name="pilar_id" class="form-control form-control-sm">
                    <option value="">-- Semua 5 Pilar --</option>
                    @foreach($pilars as $p)
                        <option value="{{ $p->id }}" {{ request('pilar_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->nama_pilar }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-2 form-group mb-2">
                <label class="small font-weight-bold text-muted">Status</label>
                <select name="status" class="form-control form-control-sm">
                    <option value="">-- Semua Status --</option>
                    <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                    <option value="menunggu_persetujuan" {{ request('status') == 'menunggu_persetujuan' ? 'selected' : '' }}>Menunggu Persetujuan</option>
                    <option value="disetujui" {{ request('status') == 'disetujui' ? 'selected' : '' }}>Disetujui</option>
                    <option value="ditolak" {{ request('status') == 'ditolak' ? 'selected' : '' }}>Ditolak</option>
                    <option value="selesai" {{ request('status') == 'selesai' ? 'selected' : '' }}>Selesai</option>
                </select>
            </div>

            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Pencarian</label>
                <input type="text" name="search" class="form-control form-control-sm" 
                       placeholder="Nama kegiatan, kode, penerima..." value="{{ request('search') }}">
            </div>

            <div class="col-md-2 form-group mb-2 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-success btn-block mr-1">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                @if(request()->anyFilled(['tingkat', 'pilar_id', 'status', 'search']))
                    <a href="{{ route('proker.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table Content -->
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nomor & Kegiatan</th>
                    <th>Pilar Program</th>
                    <th>Tingkat / Wilayah</th>
                    <th>Rencana Anggaran (RAB)</th>
                    <th>Terserap / Sisa</th>
                    <th class="text-center" style="width: 140px;">Status</th>
                    <th class="text-center" style="width: 130px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($prokers as $index => $proker)
                    @php
                        $persenSerap = $proker->rencana_anggaran > 0 ? round(($proker->total_pencairan / $proker->rencana_anggaran) * 100, 1) : 0;
                    @endphp
                    <tr>
                        <td>{{ $prokers->firstItem() + $index }}</td>
                        <td>
                            <code class="text-primary font-weight-bold">{{ $proker->nomor_proker }}</code>
                            <div class="font-weight-bold text-dark mt-1">
                                <a href="{{ route('proker.show', $proker->id) }}" class="text-dark">
                                    {{ $proker->nama_kegiatan }}
                                </a>
                            </div>
                            <small class="text-muted"><i class="fas fa-users mr-1"></i>{{ $proker->target_penerima_manfaat ?? 'Penerima manfaat' }}</small>
                        </td>
                        <td>
                            <span class="badge text-white" style="background: {{ $proker->pilarProgram?->warna ?? '#0a6b38' }};">
                                <i class="{{ $proker->pilarProgram?->icon ?? 'fas fa-circle' }} mr-1"></i>
                                {{ $proker->pilarProgram?->kode ?? 'PILAR' }}
                            </span>
                        </td>
                        <td>
                            <span class="badge badge-light border font-weight-bold">{{ strtoupper($proker->tingkat) }}</span>
                            <div class="small font-weight-bold text-muted mt-1">{{ $proker->wilayah_label }}</div>
                        </td>
                        <td>
                            <strong>Rp {{ number_format($proker->rencana_anggaran, 0, ',', '.') }}</strong>
                            <br><small class="text-muted">Tahun {{ $proker->tahun_anggaran }}</small>
                        </td>
                        <td>
                            <div class="small">
                                <span class="text-danger font-weight-bold">Rp {{ number_format($proker->total_pencairan, 0, ',', '.') }}</span>
                                <span class="text-muted">/ Rp {{ number_format($proker->sisa_anggaran, 0, ',', '.') }}</span>
                            </div>
                            <div class="progress progress-xs mt-1">
                                <div class="progress-bar bg-success" style="width: {{ min(100, $persenSerap) }}%;"></div>
                            </div>
                        </td>
                        <td class="text-center">
                            {!! $proker->status_badge !!}
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('proker.show', $proker->id) }}" class="btn btn-outline-primary btn-xs" title="Lihat Detail & Tracking">
                                    <i class="fas fa-eye mr-1"></i>Detail
                                </a>
                                @if($proker->status === 'disetujui' && $proker->sisa_anggaran > 0)
                                    <a href="{{ route('pentasyarufan.create', ['proker_id' => $proker->id]) }}" class="btn btn-outline-success btn-xs" title="Cairkan Dana">
                                        <i class="fas fa-hand-holding-usd"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            <i class="fas fa-clipboard-check fa-2x mb-2 d-block text-secondary"></i>
                            Belum ada usulan Program Kerja yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($prokers->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <small class="text-muted">
                Halaman {{ $prokers->currentPage() }} dari {{ $prokers->lastPage() }}
            </small>
            <div>
                {{ $prokers->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>
@endsection
