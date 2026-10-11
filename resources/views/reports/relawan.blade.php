@extends('layouts.adminlte')

@section('title', 'Laporan Kinerja Relawan Lapangan')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-users-cog text-info mr-2"></i>Laporan Kinerja & Aktivitas Relawan</h1>
            </div>
            <div class="col-sm-6 text-right">
                <span class="text-muted small">Periode: <strong>{{ $selectedPeriod }}</strong></span>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Filter Card -->
        <div class="card card-outline card-info shadow-sm mb-3">
            <div class="card-body p-3">
                <form action="{{ route('reports.relawan') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-muted mb-1">Periode Bulan:</label>
                        <input type="month" name="period" class="form-control form-control-sm" value="{{ $selectedPeriod }}">
                    </div>
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-muted mb-1">Filter MWC:</label>
                        <select name="mwc_id" class="form-control form-control-sm">
                            <option value="">-- Semua MWC --</option>
                            @foreach($mwcs as $mwc)
                                <option value="{{ $mwc->id }}" {{ $mwcId == $mwc->id ? 'selected' : '' }}>{{ $mwc->code }} - {{ $mwc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mt-auto">
                        <button type="submit" class="btn btn-info btn-sm btn-block font-weight-bold"><i class="fas fa-filter mr-1"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-outline card-success shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Nama Relawan</th>
                            <th>No. WhatsApp / NIK</th>
                            <th>Ranting & MWC</th>
                            <th>Area Tugas</th>
                            <th class="text-center">Donatur / Kotak Diampu</th>
                            <th class="text-right">Setoran Periode Ini (Rp)</th>
                            <th class="text-right">Total Akumulasi (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($relawans as $rel)
                            <tr>
                                <td>
                                    <strong>{{ $rel->nama }}</strong>
                                </td>
                                <td>
                                    <div><a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $rel->no_hp) }}" target="_blank" class="text-success"><i class="fab fa-whatsapp mr-1"></i>{{ $rel->no_hp }}</a></div>
                                    <small class="text-muted">NIK: {{ $rel->nik }}</small>
                                </td>
                                <td>
                                    <span class="badge badge-light border">{{ $rel->ranting->name ?? '-' }}</span>
                                    <div class="small text-muted">{{ $rel->ranting->mwc->code ?? '' }} - {{ $rel->ranting->mwc->name ?? '' }}</div>
                                </td>
                                <td><small class="font-weight-bold">{{ $rel->area_tugas }}</small></td>
                                <td class="text-center">
                                    <span class="badge badge-primary">{{ $rel->donators_count }} Kotak</span>
                                </td>
                                <td class="text-right font-weight-bold text-success fs-6">
                                    Rp {{ number_format($rel->period_amount, 0, ',', '.') }}
                                </td>
                                <td class="text-right font-weight-bold text-dark">
                                    Rp {{ number_format($rel->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada data relawan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($relawans->hasPages())
                <div class="card-footer bg-white">
                    {{ $relawans->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
