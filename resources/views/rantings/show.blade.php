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
    <div class="col-lg-4 col-12 mb-4">
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-body box-profile">
                <div class="text-center mb-2">
                    <span class="badge badge-primary px-3 py-2 font-weight-bold" style="font-size: 1.3rem; letter-spacing: 1px;">
                        {{ $ranting->code }}
                    </span>
                </div>
                <h3 class="profile-username text-center font-weight-bold text-dark mb-1">{{ $ranting->name }}</h3>
                <p class="text-muted text-center"><i class="fas fa-map-pin mr-1 text-danger"></i> Desa {{ $ranting->desa ?: $ranting->name }}</p>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                        <b>MWC Induk</b> <span class="float-right font-weight-bold text-success">[{{ $ranting->mwc->code }}] {{ $ranting->mwc->name }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Status Program</b> <span class="float-right">{!! $ranting->program_status_badge !!}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Total Relawan (RT/RW)</b> <span class="float-right font-weight-bold text-warning">{{ $ranting->relawans->count() }} Orang</span>
                    </li>
                    <li class="list-group-item">
                        <b>Total Koin Terkumpul</b> <span class="float-right font-weight-bold text-success">Rp {{ number_format($totalCollected, 0, ',', '.') }}</span>
                    </li>
                    <li class="list-group-item">
                        <b>Alamat Sekretariat</b> <span class="float-right text-muted small text-right" style="max-width: 60%;">{{ $ranting->address ?: '-' }}</span>
                    </li>
                </ul>

                <div class="d-flex flex-column" style="gap: 8px;">
                    <a href="{{ route('rantings.edit', $ranting->id) }}" class="btn btn-warning text-white btn-block font-weight-bold shadow-xs">
                        <i class="fas fa-user-edit mr-1"></i> Kelola Profil & Susunan Pengurus
                    </a>
                    <a href="{{ route('relawans.create') }}" class="btn btn-success btn-block font-weight-bold shadow-xs">
                        <i class="fas fa-user-plus mr-1"></i> Tambah Relawan di Ranting Ini
                    </a>
                    <a href="{{ route('rantings.index') }}" class="btn btn-outline-secondary btn-block">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Ranting
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Konten Kanan: Susunan Pengurus & Daftar Relawan -->
    <div class="col-lg-8 col-12">
        <!-- 1. Susunan Pengurus Ranting & Kontak WhatsApp -->
        <div class="card card-outline card-info shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-users-cog text-info mr-2"></i> Susunan Pengurus Ranting & Jalur WhatsApp
                </h3>
                <a href="{{ route('rantings.edit', $ranting->id) }}" class="btn btn-outline-info btn-xs font-weight-bold">
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
                        @foreach($ranting->pengurus_list as $pengurus)
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
                Data pengurus ini otomatis digunakan sebagai penandatangan resmi laporan BKU & SPJ (Ketua UPZIS di kiri bawah, Bendahara di kanan bawah, Mengetahui Ketua Tanfidziyah di tengah).
            </div>
        </div>

        <!-- 2. Daftar Relawan RT/RW pada Ranting ini -->
        <div class="card shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-users mr-2 text-success"></i> Relawan Koordinator RT/RW pada {{ $ranting->name }}
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
                                    <td>
                                        @if($relawan->no_hp)
                                            <a href="https://wa.me/{{ \App\Services\WhatsAppHelper::formatNumber($relawan->no_hp) }}" target="_blank" class="text-success font-weight-bold">
                                                <i class="fab fa-whatsapp mr-1"></i>{{ $relawan->no_hp }}
                                            </a>
                                        @else
                                            -
                                        @endif
                                    </td>
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
