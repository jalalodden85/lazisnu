@extends('layouts.adminlte')

@section('title', 'Detail Ranting - ' . $ranting->code)
@section('page_title', 'Ranting: ' . $ranting->name . ' (' . $ranting->code . ')')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('rantings.index') }}">Master Ranting</a></li>
    <li class="breadcrumb-item active">{{ $ranting->code }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Profil Ranting -->
    <div class="col-md-4 col-12">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-body box-profile">
                <div class="text-center mb-2">
                    <span class="badge badge-primary px-3 py-2 font-weight-bold" style="font-size: 1.3rem; letter-spacing: 1px;">
                        {{ $ranting->code }}
                    </span>
                </div>
                <h3 class="profile-username text-center font-weight-bold text-dark">{{ $ranting->name }}</h3>
                <p class="text-muted text-center"><i class="fas fa-map-pin mr-1 text-danger"></i> Desa {{ $ranting->desa }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>MWC Induk</b> <span class="float-right font-weight-bold text-success">[{{ $ranting->mwc->code }}] {{ $ranting->mwc->name }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Total Relawan (RT/RW)</b> <span class="float-right font-weight-bold text-warning">{{ $ranting->relawans->count() }} Orang</span>
                    </li>
                    <li class="list-group-item">
                        <b>Total Koin Terkumpul</b> <span class="float-right font-weight-bold text-success">Rp {{ number_format($totalCollected, 0, ',', '.') }}</span>
                    </li>
                </ul>

                <a href="{{ route('relawans.create') }}" class="btn btn-success btn-block font-weight-bold shadow-sm mb-2">
                    <i class="fas fa-user-plus mr-1"></i> Tambah Relawan di Ranting Ini
                </a>
                <a href="{{ route('rantings.edit', $ranting->id) }}" class="btn btn-warning text-white btn-block font-weight-bold mb-2">
                    <i class="fas fa-edit mr-1"></i> Edit Data Ranting
                </a>
                <a href="{{ route('rantings.index') }}" class="btn btn-outline-secondary btn-block">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Ranting
                </a>
            </div>
        </div>
    </div>

    <!-- Daftar Relawan RT/RW pada Ranting ini -->
    <div class="col-md-8 col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-users-cog mr-2 text-success"></i> Relawan Koordinator RT/RW pada {{ $ranting->name }}
                </h3>
                <span class="badge badge-light border">{{ $ranting->relawans->count() }} Relawan</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover m-0 align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>Nama Relawan</th>
                                <th>NIK</th>
                                <th>Area Tugas (RT/RW)</th>
                                <th>No. HP</th>
                                <th class="text-center">Aksi Setor</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($ranting->relawans as $relawan)
                                <tr>
                                    <td class="font-weight-bold text-dark">{{ $relawan->nama }}</td>
                                    <td><small class="text-muted">{{ $relawan->nik }}</small></td>
                                    <td><span class="badge badge-light border font-weight-bold">{{ $relawan->area_tugas }}</span></td>
                                    <td>{{ $relawan->no_hp ?? '-' }}</td>
                                    <td class="text-center">
                                        <a href="{{ route('transactions.create', ['relawan_id' => $relawan->id]) }}" class="btn btn-xs btn-success font-weight-bold">
                                            <i class="fas fa-coins mr-1"></i> Input Setor
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
                                        Belum ada Relawan terdaftar di Ranting ini. Silakan tambahkan relawan atau gunakan Bulk Import.
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
