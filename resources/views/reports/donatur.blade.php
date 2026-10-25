@extends('layouts.adminlte')

@section('title', 'Laporan Distribusi Kotak Koin Donatur')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-hand-holding-heart text-danger mr-2"></i>Laporan Distribusi Kotak Koin Donatur</h1>
            </div>
            <div class="col-sm-6 text-right">
                <span class="text-muted small">Total Kotak Aktif: <strong class="text-dark fs-5">{{ number_format($stats['total_kotak']) }}</strong></span>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Stat Boxes -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-primary"><i class="fas fa-boxes"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Kotak Terdistribusi</span>
                        <span class="info-box-number">{{ number_format($stats['total_kotak']) }} Kotak</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-user-check"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Kotak Berpengampu (Ada Relawan)</span>
                        <span class="info-box-number text-success">{{ number_format($stats['kotak_assigned']) }} Kotak</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-user-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Kotak Belum Berpengampu</span>
                        <span class="info-box-number text-warning">{{ number_format($stats['kotak_unassigned']) }} Kotak</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card card-outline card-secondary shadow-sm mb-3">
            <div class="card-body p-3">
                <form action="{{ route('reports.donatur') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <select name="mwc_id" class="form-control form-control-sm">
                            <option value="">-- Semua Wilayah MWC --</option>
                            @foreach($mwcs as $mwc)
                                <option value="{{ $mwc->id }}" {{ $mwcId == $mwc->id ? 'selected' : '' }}>{{ $mwc->code }} - {{ $mwc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <select name="status_petugas" class="form-control form-control-sm">
                            <option value="">-- Status Petugas Pengampu --</option>
                            <option value="assigned" {{ request('status_petugas') == 'assigned' ? 'selected' : '' }}>Sudah Ditugaskan ke Relawan</option>
                            <option value="unassigned" {{ request('status_petugas') == 'unassigned' ? 'selected' : '' }}>Belum Memiliki Relawan Pengampu</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-secondary btn-sm btn-block font-weight-bold"><i class="fas fa-filter mr-1"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-outline card-success shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Nomor Kotak</th>
                            <th>Nama Donatur</th>
                            <th>No. HP & Alamat</th>
                            <th>Ranting & MWC</th>
                            <th>RT / RW</th>
                            <th>Petugas Relawan Pengampu</th>
                            <th class="text-center">Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($donators as $don)
                            <tr>
                                <td>
                                    <span class="badge badge-light border font-weight-bold text-dark fs-6">{{ $don->box_number }}</span>
                                </td>
                                <td>
                                    <strong>{{ $don->name }}</strong>
                                    <div class="small text-muted">NIK: {{ $don->nik }}</div>
                                </td>
                                <td>
                                    @php
                                        $reportWa = \App\Services\WhatsAppHelper::generateKoinNuThankYouLink($don->phone, $don->name, $don->box_number);
                                    @endphp
                                    @if($reportWa)
                                        <div><a href="{{ $reportWa }}" target="_blank" class="text-success font-weight-bold" title="Kirim WA Ucapan Terima Kasih"><i class="fab fa-whatsapp mr-1"></i>{{ $don->phone }}</a></div>
                                    @elseif($don->phone)
                                        <div class="text-muted small"><i class="fas fa-phone mr-1"></i>{{ $don->phone }}</div>
                                    @else
                                        <div class="text-muted small fst-italic"><i class="fas fa-phone-slash mr-1"></i>-</div>
                                    @endif
                                    <small class="text-muted">{{ Str::limit($don->address, 45) }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-light border">{{ $don->ranting->name ?? '-' }}</span>
                                    <div class="small text-muted">{{ $don->ranting->mwc->code ?? '' }}</div>
                                </td>
                                <td><span class="badge badge-secondary">RT {{ $don->rt }} / RW {{ $don->rw }}</span></td>
                                <td>
                                    @if($don->relawan)
                                        <strong class="text-success"><i class="fas fa-user-check mr-1"></i>{{ $don->relawan->nama }}</strong>
                                        <div class="small text-muted">Area: {{ $don->relawan->area_tugas }}</div>
                                    @else
                                        <span class="badge badge-warning text-dark"><i class="fas fa-exclamation-circle mr-1"></i>Belum Ada Relawan</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <span class="badge badge-success">Aktif</span>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada donatur terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($donators->hasPages())
                <div class="card-footer bg-white">
                    {{ $donators->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
