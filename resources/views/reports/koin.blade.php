@extends('layouts.adminlte')

@section('title', 'Laporan Rekapitulasi Perolehan Koin')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-coins text-warning mr-2"></i>Rekapitulasi Perolehan Koin NU</h1>
            </div>
            <div class="col-sm-6 text-right">
                <button type="button" class="btn btn-primary font-weight-bold" onclick="openModalCetakLaporan({format: 'pdf', mwc_id: '{{ $mwcId }}'})">
                    <i class="fas fa-print mr-1"></i> Cetak Laporan Resmi
                </button>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Stat Boxes -->
        <div class="row">
            <div class="col-lg-3 col-6">
                <div class="small-box bg-success shadow-sm">
                    <div class="inner">
                        <h3>Rp {{ number_format($totalNominal, 0, ',', '.') }}</h3>
                        <p>Total Perolehan Periode Ini</p>
                    </div>
                    <div class="icon"><i class="fas fa-wallet"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-info shadow-sm">
                    <div class="inner">
                        <h3>{{ number_format($totalTrx) }}</h3>
                        <p>Total Transaksi Setoran</p>
                    </div>
                    <div class="icon"><i class="fas fa-receipt"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-warning shadow-sm">
                    <div class="inner">
                        <h3>Rp {{ number_format($avgNominal, 0, ',', '.') }}</h3>
                        <p>Rata-rata per Setoran</p>
                    </div>
                    <div class="icon"><i class="fas fa-chart-line"></i></div>
                </div>
            </div>
            <div class="col-lg-3 col-6">
                <div class="small-box bg-danger shadow-sm">
                    <div class="inner">
                        <h3>Rp {{ number_format($maxNominal, 0, ',', '.') }}</h3>
                        <p>Setoran Tertinggi</p>
                    </div>
                    <div class="icon"><i class="fas fa-trophy"></i></div>
                </div>
            </div>
        </div>

        <!-- Filter Periode & MWC -->
        <div class="card card-outline card-success shadow-sm mb-3">
            <div class="card-body p-3">
                <form action="{{ route('reports.koin') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-3">
                        <label class="small font-weight-bold text-muted mb-1">Periode Bulan:</label>
                        <input type="month" name="period" class="form-control form-control-sm" value="{{ $selectedPeriod }}">
                    </div>
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-muted mb-1">Pilih Wilayah MWC:</label>
                        <select name="mwc_id" class="form-control form-control-sm">
                            <option value="">-- Semua MWC (Se-Kabupaten) --</option>
                            @foreach($mwcs as $mwc)
                                <option value="{{ $mwc->id }}" {{ $mwcId == $mwc->id ? 'selected' : '' }}>{{ $mwc->code }} - {{ $mwc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2 mt-auto">
                        <button type="submit" class="btn btn-success btn-sm btn-block font-weight-bold"><i class="fas fa-search mr-1"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Rincian Setoran -->
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Rincian Transaksi Setoran Koin Periode {{ $selectedPeriod }}</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Invoice / Tanggal</th>
                            <th>Relawan Pengumpul</th>
                            <th>Ranting & MWC</th>
                            <th>Area Tugas (RT/RW)</th>
                            <th class="text-right">Nominal Setoran (Rp)</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($transactions as $trx)
                            <tr>
                                <td>
                                    <strong>{{ $trx->invoice_code }}</strong>
                                    <div class="small text-muted">{{ $trx->collection_date ? $trx->collection_date->format('d/m/Y') : '-' }}</div>
                                </td>
                                <td>
                                    <strong>{{ $trx->relawan->nama ?? 'Relawan' }}</strong>
                                    <div class="small text-muted">HP: {{ $trx->relawan->no_hp ?? '-' }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-light border">{{ $trx->ranting->name ?? '-' }}</span>
                                    <div class="small text-muted">{{ $trx->ranting->mwc->code ?? '' }} - {{ $trx->ranting->mwc->name ?? '' }}</div>
                                </td>
                                <td><small>{{ $trx->relawan->area_tugas ?? '-' }}</small></td>
                                <td class="text-right font-weight-bold text-success fs-6">
                                    Rp {{ number_format($trx->amount, 0, ',', '.') }}
                                </td>
                                <td><span class="badge badge-success">Terkonfirmasi</span></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Tidak ada data transaksi pada periode ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($transactions->hasPages())
                <div class="card-footer bg-white">
                    {{ $transactions->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
