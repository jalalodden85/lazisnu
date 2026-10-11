@extends('layouts.adminlte')

@section('title', 'Master Data MWC')
@section('page_title', 'Master Data Majelis Wakil Cabang (MWC)')

@section('breadcrumb')
    <li class="breadcrumb-item active">Master MWC</li>
@endsection

@section('content')
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
        <p class="text-muted mb-0">
            <i class="fas fa-landmark mr-1 text-success"></i> Terdapat <strong>15 MWC</strong> di bawah naungan Pimpinan Cabang (PC) Lazisnu.
        </p>
        <a href="{{ route('mwcs.create') }}" class="btn btn-success shadow-sm mt-2 mt-sm-0">
            <i class="fas fa-plus-circle mr-1"></i> Tambah MWC Baru
        </a>
    </div>
</div>

<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-list mr-1"></i> Daftar 15 MWC Resmi
        </h3>
        <form method="GET" action="{{ route('mwcs.index') }}" class="form-inline">
            <div class="input-group input-group-sm">
                <input type="text" name="search" class="form-control" placeholder="Cari nama atau kode MWC..." value="{{ request('search') }}">
                <div class="input-group-append">
                    <button class="btn btn-default" type="submit"><i class="fas fa-search"></i></button>
                </div>
            </div>
        </form>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover m-0 align-middle">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 80px;">Kode</th>
                        <th>Nama MWC</th>
                        <th>Kecamatan</th>
                        <th class="text-center">Jumlah Ranting</th>
                        <th class="text-center">Jumlah Relawan</th>
                        <th class="text-right">Total Koin Terkumpul</th>
                        <th class="text-center" style="width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($mwcs as $m)
                        <tr>
                            <td><span class="badge badge-success font-weight-bold" style="font-size: 0.9rem;">{{ $m->code }}</span></td>
                            <td class="font-weight-bold text-dark">{{ $m->name }}</td>
                            <td>{{ $m->kecamatan }}</td>
                            <td class="text-center">
                                <a href="{{ route('rantings.index', ['mwc_id' => $m->id]) }}" class="badge badge-info" title="Lihat Ranting">
                                    {{ $m->rantings_count }} Ranting
                                </a>
                            </td>
                            <td class="text-center">
                                <span class="badge badge-warning text-dark">{{ $m->relawans_count }} Relawan</span>
                            </td>
                            <td class="text-right font-weight-bold text-success">
                                Rp {{ number_format($m->total_collected ?? 0, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <a href="{{ route('mwcs.show', $m->id) }}" class="btn btn-xs btn-info mr-1" title="Lihat Detail & Ranting">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('mwcs.edit', $m->id) }}" class="btn btn-xs btn-warning text-white" title="Edit">
                                    <i class="fas fa-edit"></i>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center py-4 text-muted">Belum ada data MWC.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white clearfix">
        <div class="float-right">
            {{ $mwcs->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection
