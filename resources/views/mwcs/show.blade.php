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
    <div class="col-lg-4 col-12 mb-4">
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-body box-profile">
                <div class="text-center mb-3">
                    <span class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 1.2rem;">
                        {{ $mwc->code }}
                    </span>
                </div>
                <h3 class="profile-username text-center font-weight-bold text-dark mb-1">{{ $mwc->name }}</h3>
                <p class="text-muted text-center"><i class="fas fa-map-marker-alt mr-1"></i> Kecamatan {{ $mwc->kecamatan }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>Jumlah Ranting NU</b> <a class="float-right font-weight-bold text-success">{{ $mwc->rantings->count() }} Ranting</a>
                    </li>
                    <li class="list-group-item">
                        <b>Total Koin Terhimpun</b> <a class="float-right font-weight-bold text-primary">Rp {{ number_format($totalCollected, 0, ',', '.') }}</a>
                    </li>
                    <li class="list-group-item">
                        <b>No. Telepon / Kantor</b> <span class="float-right text-muted">{{ $mwc->phone ?? '-' }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Alamat Kantor</b> <span class="float-right text-muted text-right small" style="max-width: 60%;">{{ $mwc->address ?? 'Belum ada data alamat' }}</span>
                    </li>
                </ul>

                <div class="d-flex flex-column" style="gap: 8px;">
                    <a href="{{ route('mwcs.edit', $mwc->id) }}" class="btn btn-warning text-white btn-block font-weight-bold shadow-xs">
                        <i class="fas fa-user-edit mr-1"></i> Kelola Profil & Susunan Pengurus
                    </a>
                    <a href="{{ route('rantings.create', ['mwc_id' => $mwc->id]) }}" class="btn btn-success btn-block font-weight-bold shadow-xs">
                        <i class="fas fa-plus mr-1"></i> Tambah Ranting di MWC Ini
                    </a>
                    <a href="{{ route('mwcs.index') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar MWC
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten Kanan: Susunan Pengurus & Daftar Ranting -->
    <div class="col-lg-8 col-12">
        <!-- 1. Susunan Pengurus MWC & Kontak Cepat WhatsApp -->
        <div class="card card-outline card-info shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-users-cog text-info mr-2"></i> Susunan Pengurus MWC & Jalur Komunikasi WhatsApp
                </h3>
                <a href="{{ route('mwcs.edit', $mwc->id) }}" class="btn btn-outline-info btn-xs font-weight-bold">
                    <i class="fas fa-edit mr-1"></i> Edit Pengurus
                </a>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr class="small font-weight-bold text-uppercase text-dark">
                            <th>Jabatan Struktur</th>
                            <th>Nama Pengurus / Pejabat</th>
                            <th>Nomor WhatsApp</th>
                            <th class="text-center" style="width: 170px;">Aksi Cepat</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mwc->pengurus_list as $pengurus)
                            <tr>
                                <td>
                                    <span class="badge {{ $pengurus['badge'] }} px-2 py-1">
                                        <i class="{{ $pengurus['icon'] }} mr-1"></i> {{ $pengurus['jabatan'] }}
                                    </span>
                                </td>
                                <td class="font-weight-bold text-dark">
                                    {{ $pengurus['nama'] ?: '-' }}
                                </td>
                                <td>
                                    @if($pengurus['wa'])
                                        <span class="font-monospace text-muted">+{{ $pengurus['wa'] }}</span>
                                    @else
                                        <span class="text-muted small font-italic">Belum diisi</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($pengurus['link'])
                                        <a href="{{ $pengurus['link'] }}" target="_blank" class="btn btn-success btn-xs font-weight-bold shadow-xs px-2 py-1" title="Chat via WhatsApp">
                                            <i class="fab fa-whatsapp mr-1"></i> Chat WhatsApp
                                        </a>
                                    @else
                                        <button class="btn btn-light btn-xs text-muted border" disabled>
                                            <i class="fab fa-whatsapp mr-1 text-muted"></i> No WhatsApp
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light py-2 text-muted small">
                <i class="fas fa-shield-alt text-success mr-1"></i>
                Data pengurus ini otomatis digunakan sebagai penandatangan resmi berkas pertanggungjawaban cetak PDF (BKU & SPJ).
            </div>
        </div>

        <!-- 2. Daftar Ranting di Bawah Naungan MWC Ini -->
        <div class="card shadow-sm mb-4">
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
                                <th>Ketua UPZIS</th>
                                <th>Relawan</th>
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
                                    <td class="font-weight-bold text-dark">
                                        {{ $ranting->name }}
                                        @if($ranting->desa)
                                            <small class="text-muted d-block">Desa {{ $ranting->desa }}</small>
                                        @endif
                                    </td>
                                    <td>
                                        {{ $ranting->nama_ketua_upzis ?: ($ranting->pj_name ?: '-') }}
                                        @if($ranting->wa_ketua_upzis)
                                            <a href="https://wa.me/{{ $ranting->wa_ketua_upzis }}" target="_blank" class="ml-1 text-success" title="WhatsApp: {{ $ranting->wa_ketua_upzis }}">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        @endif
                                    </td>
                                    <td><span class="badge badge-warning text-dark">{{ $ranting->relawans->count() }} Relawan</span></td>
                                    <td class="text-center">
                                        <a href="{{ route('rantings.show', $ranting->id) }}" class="btn btn-xs btn-info" title="Detail Ranting">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('rantings.edit', $ranting->id) }}" class="btn btn-xs btn-warning text-white" title="Edit Ranting">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center py-4 text-muted">
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
