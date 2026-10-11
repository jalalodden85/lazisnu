@extends('layouts.adminlte')

@section('title', 'Pembagian Petugas di Wilayah Masing-Masing')
@section('page_title', 'Pembagian Petugas Wilayah KOIN NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('donators.index') }}">Penerima Kotak</a></li>
    <li class="breadcrumb-item active">Pembagian Petugas Wilayah</li>
@endsection

@section('content')
<!-- Header Banner Deskripsi -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-body">
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-md-center">
            <div>
                <h4 class="font-weight-bold text-success mb-1">
                    <i class="fas fa-map-marked-alt mr-2"></i> Pembagian Petugas di Wilayah Masing-Masing
                </h4>
                <p class="text-muted mb-0">
                    Pemetaan petugas relawan lapangan (Koordinator RT/RW) dan alokasi kotak infaq KOIN NU di setiap ranting binaan.
                </p>
            </div>
            <div class="mt-3 mt-md-0">
                <a href="{{ route('donators.index') }}" class="btn btn-outline-secondary btn-sm mr-2">
                    <i class="fas fa-list mr-1"></i> Daftar Semua Kotak
                </a>
                <a href="{{ route('relawans.create') }}" class="btn btn-success btn-sm font-weight-bold">
                    <i class="fas fa-user-plus mr-1"></i> Tambah Petugas Relawan
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Filter Ranting (jika ada lebih dari 1 ranting dalam scope) -->
@if($rantings->count() > 1)
<div class="card card-default shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="GET" action="{{ route('donators.distribution') }}" class="row align-items-center">
            <div class="col-md-4">
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text bg-success text-white font-weight-bold">
                            <i class="fas fa-mosque mr-1"></i> Pilih Ranting:
                        </span>
                    </div>
                    <select name="ranting_id" class="form-control" onchange="this.form.submit()">
                        <option value="">-- Semua Ranting --</option>
                        @foreach($rantings as $ranting)
                            <option value="{{ $ranting->id }}" {{ request('ranting_id') == $ranting->id ? 'selected' : '' }}>
                                {{ $ranting->code }} - {{ $ranting->name }} ({{ $ranting->mwc?->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
            @if(request('ranting_id'))
                <div class="col-md-2">
                    <a href="{{ route('donators.distribution') }}" class="btn btn-outline-secondary btn-sm">
                        <i class="fas fa-undo mr-1"></i> Reset
                    </a>
                </div>
            @endif
        </form>
    </div>
</div>
@endif

<!-- Kotak Infaq yang Belum Ada Petugas Pengampu (Peringatan & Tindakan Cepat) -->
@if($unassignedDonators->count() > 0)
<div class="card card-warning card-outline shadow-sm mb-4">
    <div class="card-header bg-warning py-2 text-dark font-weight-bold d-flex justify-content-between align-items-center">
        <span>
            <i class="fas fa-exclamation-circle mr-2"></i> 
            Terdapat {{ $unassignedDonators->count() }} Kotak Infaq Belum Memiliki Petugas Pengampu
        </span>
        <small class="badge badge-light font-weight-bold">Perlu Segera Ditugaskan</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-sm table-striped m-0 align-middle">
                <thead class="thead-light">
                    <tr>
                        <th>No. Kotak</th>
                        <th>Nama Donatur</th>
                        <th>Ranting / Desa</th>
                        <th>Alamat Lengkap</th>
                        <th style="width: 250px;">Tugaskan Petugas Wilayah</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($unassignedDonators as $unassigned)
                        <tr>
                            <td>
                                <span class="badge badge-warning text-dark font-weight-bold">
                                    <i class="fas fa-box mr-1"></i>{{ $unassigned->box_number }}
                                </span>
                            </td>
                            <td class="font-weight-bold">{{ $unassigned->name }}</td>
                            <td>
                                <span class="badge badge-light border">
                                    {{ $unassigned->ranting?->name }} ({{ $unassigned->ranting?->code }})
                                </span>
                            </td>
                            <td><small class="text-muted">{{ $unassigned->full_address }}</small></td>
                            <td>
                                <form action="{{ route('donators.assign', $unassigned->id) }}" method="POST" class="d-flex align-items-center">
                                    @csrf
                                    <select name="relawan_id" class="form-control form-control-sm mr-2" required>
                                        <option value="">-- Pilih Petugas --</option>
                                        @foreach($relawans->where('ranting_id', $unassigned->ranting_id) as $rel)
                                            <option value="{{ $rel->id }}">{{ $rel->nama }} ({{ $rel->area_tugas }})</option>
                                        @endforeach
                                    </select>
                                    <button type="submit" class="btn btn-sm btn-success font-weight-bold px-3">
                                        <i class="fas fa-check mr-1"></i> Tetapkan
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

<!-- Daftar Petugas & Pembagian Binaan Wilayah -->
<div class="card shadow-sm">
    <div class="card-header bg-white">
        <h3 class="card-title font-weight-bold text-success">
            <i class="fas fa-users-cog mr-2"></i> Daftar Petugas Relawan & Alokasi Kotak Binaan ({{ $relawans->count() }} Petugas)
        </h3>
    </div>
    <div class="card-body">
        <div class="row">
            @forelse($relawans as $relawan)
                <div class="col-lg-6 col-12 mb-4">
                    <div class="card card-outline card-success shadow-sm h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center py-2">
                            <div>
                                <h5 class="card-title font-weight-bold mb-0 text-dark">
                                    <i class="fas fa-user-tie text-success mr-2"></i>{{ $relawan->nama }}
                                </h5>
                                <div class="mt-1">
                                    <span class="badge badge-success font-weight-bold">
                                        <i class="fas fa-map-pin mr-1"></i>Wilayah: {{ $relawan->area_tugas }}
                                    </span>
                                    <span class="badge badge-light border">
                                        <i class="fas fa-mosque mr-1"></i>{{ $relawan->ranting?->name }} ({{ $relawan->ranting?->code }})
                                    </span>
                                </div>
                            </div>
                            <div class="text-right">
                                <span class="badge badge-primary px-2 py-1" style="font-size: 0.9rem;">
                                    <i class="fas fa-boxes mr-1"></i>{{ $relawan->donators->count() }} Kotak Binaan
                                </span>
                                @if($relawan->no_hp)
                                    <div class="mt-1">
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $relawan->no_hp) }}" target="_blank" class="badge badge-success text-white">
                                            <i class="fab fa-whatsapp mr-1"></i>{{ $relawan->no_hp }}
                                        </a>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="card-body p-0">
                            @if($relawan->donators->count() > 0)
                                <div class="table-responsive" style="max-height: 250px; overflow-y: auto;">
                                    <table class="table table-sm table-hover m-0" style="font-size: 0.88rem;">
                                        <thead class="thead-light sticky-top">
                                            <tr>
                                                <th style="width: 110px;">No. Kotak</th>
                                                <th>Nama Donatur</th>
                                                <th>Alamat & RT/RW</th>
                                                <th style="width: 80px;" class="text-center">Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @foreach($relawan->donators as $donator)
                                                <tr>
                                                    <td>
                                                        <span class="badge badge-success font-weight-bold">
                                                            {{ $donator->box_number }}
                                                        </span>
                                                    </td>
                                                    <td class="font-weight-bold text-dark">{{ $donator->name }}</td>
                                                    <td>
                                                        <small class="text-muted">
                                                            {{ $donator->address }}
                                                            @if($donator->rt || $donator->rw)
                                                                (RT {{ $donator->rt ?? '-' }}/RW {{ $donator->rw ?? '-' }})
                                                            @endif
                                                        </small>
                                                    </td>
                                                    <td class="text-center">
                                                        <form action="{{ route('donators.assign', $donator->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Lepaskan kotak ini dari petugas {{ $relawan->nama }}?')">
                                                            @csrf
                                                            <input type="hidden" name="relawan_id" value="">
                                                            <button type="submit" class="btn btn-xs btn-outline-danger" title="Lepaskan Petugas">
                                                                <i class="fas fa-user-minus"></i>
                                                            </button>
                                                        </form>
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            @else
                                <div class="p-4 text-center text-muted">
                                    <i class="fas fa-box-open fa-2x mb-2 text-muted"></i>
                                    <p class="mb-0">Belum ada kotak infaq yang ditugaskan kepada petugas ini.</p>
                                </div>
                            @endif
                        </div>

                        <div class="card-footer bg-white text-right py-2">
                            <a href="{{ route('donators.create') }}" class="btn btn-xs btn-outline-success font-weight-bold">
                                <i class="fas fa-plus mr-1"></i> Tambah Kotak ke Petugas Ini
                            </a>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 py-5 text-center text-muted">
                    <i class="fas fa-users-slash fa-3x mb-3 text-muted"></i>
                    <p class="mb-2">Belum ada petugas relawan yang terdaftar pada wilayah ini.</p>
                    <a href="{{ route('relawans.create') }}" class="btn btn-sm btn-success">
                        <i class="fas fa-plus mr-1"></i> Daftarkan Petugas Relawan Sekarang
                    </a>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
