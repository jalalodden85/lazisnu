@extends('layouts.adminlte')

@section('title', 'Detail MWC - ' . $mwc->name)
@section('page_title', 'Detail MWC: ' . $mwc->name)

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('mwcs.index') }}">Master MWC</a></li>
    <li class="breadcrumb-item active">{{ $mwc->code }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Profil MWC -->
    <div class="col-md-4 col-12">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-body box-profile">
                <div class="text-center mb-3">
                    <span class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 1.2rem;">
                        {{ $mwc->code }}
                    </span>
                </div>
                <h3 class="profile-username text-center font-weight-bold text-dark">{{ $mwc->name }}</h3>
                <p class="text-muted text-center"><i class="fas fa-map-marker-alt mr-1"></i> Kecamatan {{ $mwc->kecamatan }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Jumlah Ranting NU</b> <a class="float-right font-weight-bold text-success">{{ $mwc->rantings->count() }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>Total Koin Terhimpun</b> <a class="float-right font-weight-bold text-primary">Rp {{ number_format($totalCollected, 0, ',', '.') }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>No. Telepon / WA</b> <span class="float-right text-muted">{{ $mwc->phone ?? '-' }}</span>
                    </li>
                </ul>

                <a href="{{ route('rantings.create', ['mwc_id' => $mwc->id]) }}" class="btn btn-success btn-block font-weight-bold shadow-sm mb-2">
                    <i class="fas fa-plus mr-1"></i> Tambah Ranting di MWC Ini
                </a>
                <a href="{{ route('mwcs.index') }}" class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar MWC
                </a>
            </div>
        </div>
    </div>

    <!-- Daftar Ranting di Bawah Naungan MWC Ini -->
    <div class="col-md-8 col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-mosque mr-2 text-success"></i> Ranting NU Binaan {{ $mwc->name }}
                </h3>
                <span class="badge badge-light border">{{ $mwc->rantings->count() }} Ranting Terdaftar</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover m-0 align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th style="width: 110px;">Kode Ranting</th>
                                <th>Nama Ranting (Desa)</th>
                                <th>Relawan Aktif</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($mwc->rantings as $ranting)
                                <tr>
                                    <td>
                                        <span class="badge badge-primary font-weight-bold" style="font-size: 0.85rem;">
                                            {{ $ranting->code }}
                                        </span>
                                    </td>
                                    <td class="font-weight-bold text-dark">{{ $ranting->name }}</td>
                                    <td><span class="badge badge-warning text-dark">{{ $ranting->relawans->count() }} Relawan</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('rantings.show', $ranting->id) }}" class="btn btn-xs btn-info" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('rantings.edit', $ranting->id) }}" class="btn btn-xs btn-warning text-white" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        Belum ada Ranting yang didaftarkan di MWC ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
