@extends('layouts.adminlte')

@php
    $role = $scopeRole ?? 'pc';
@endphp

@section('title')
    @if($role === 'ranting')
        Dashboard Ranting NU {{ $scopeTitle }}
    @elseif($role === 'mwc')
        Dashboard MWC NU {{ $scopeTitle }}
    @else
        Dashboard PC Lazisnu (Global)
    @endif
@endsection

@section('page_title')
    @if($role === 'ranting')
        Dashboard Ranting NU — {{ $scopeTitle }}
    @elseif($role === 'mwc')
        Dashboard MWC NU — Monitoring Ranting di Wilayah {{ $scopeTitle }}
    @else
        Dashboard Pimpinan Cabang (PC) — Monitoring 15 MWC
    @endif
@endsection

@section('breadcrumb')
    <li class="breadcrumb-item active">
        @if($role === 'ranting')
            Dashboard Ranting
        @elseif($role === 'mwc')
            Dashboard MWC
        @else
            Dashboard PC
        @endif
    </li>
@endsection

@section('content')
@php
    $isRantingSuspended = Auth::user()?->isRanting() && Auth::user()?->ranting && !Auth::user()->ranting->is_active;
@endphp

@if($isRantingSuspended)
    <x-adminlte-alert theme="danger" title="Pemberitahuan Operasional Ranting" icon="fas fa-ban" class="mb-4">
        <strong>Akses input dinonaktifkan oleh PC Lazisnu. Silakan selesaikan urusan administrasi Anda.</strong>
        <p class="mb-0 small mt-1">Anda masih dapat memantau riwayat setoran dan data relawan, namun penginputan setoran baru dan pendaftaran relawan sementara dikunci oleh Pimpinan Cabang.</p>
    </x-adminlte-alert>
@endif

<!-- Ringkasan KPI (Small-Box AdminLTE 3) -->
<div class="row">
    <!-- Total Dana Bulan Ini -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3>Rp {{ number_format($totalDanaBulanIni, 0, ',', '.') }}</h3>
                <p class="font-weight-bold">
                    @if($role === 'ranting')
                        Infaq Ranting (Bulan Ini)
                    @elseif($role === 'mwc')
                        Infaq MWC (Bulan Ini)
                    @else
                        Total Infaq Cabang (Bulan Ini)
                    @endif
                </p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <a href="{{ route('transactions.index') }}" class="small-box-footer">
                Rincian Transaksi <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <!-- Total Dana Akumulasi -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-info shadow-sm">
            <div class="inner">
                <h3>Rp {{ number_format($totalDanaAkumulasi, 0, ',', '.') }}</h3>
                <p class="font-weight-bold">
                    @if($role === 'ranting')
                        Akumulasi Infaq Ranting
                    @elseif($role === 'mwc')
                        Akumulasi Infaq MWC
                    @else
                        Akumulasi Dana Terkumpul
                    @endif
                </p>
            </div>
            <div class="icon">
                <i class="fas fa-vault"></i>
            </div>
            <a href="{{ route('transactions.index') }}" class="small-box-footer">
                Lihat Rekapitulasi <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <!-- Total Relawan RT/RW -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-warning shadow-sm">
            <div class="inner text-white">
                <h3>{{ number_format($totalRelawanAktif, 0, ',', '.') }}</h3>
                <p class="font-weight-bold">
                    @if($role === 'ranting')
                        Relawan di Ranting Ini
                    @elseif($role === 'mwc')
                        Relawan Wilayah MWC
                    @else
                        Total Relawan (Kord. RT/RW)
                    @endif
                </p>
            </div>
            <div class="icon">
                <i class="fas fa-user-friends"></i>
            </div>
            <a href="{{ route('relawans.index') }}" class="small-box-footer text-white">
                Kelola Relawan <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <!-- Total Cakupan -->
    <div class="col-lg-3 col-6">
        <div class="small-box bg-secondary shadow-sm">
            <div class="inner">
                @if($role === 'ranting')
                    <h3>{{ $totalRelawanAktif }} <small class="text-white">Relawan RT/RW</small></h3>
                    <p class="font-weight-bold">{{ auth()->user()->ranting?->desa ?? $scopeTitle }}</p>
                @elseif($role === 'mwc')
                    <h3>{{ $totalRantingAktif }} <small class="text-white">Ranting Desa</small></h3>
                    <p class="font-weight-bold">Wilayah Kec. {{ auth()->user()->mwc?->kecamatan ?? $scopeTitle }}</p>
                @else
                    <h3>{{ $totalMwcAktif }} <small class="text-white">MWC</small> / {{ $totalRantingAktif }} <small class="text-white">Ranting</small></h3>
                    <p class="font-weight-bold">Cakupan Wilayah Cabang</p>
                @endif
            </div>
            <div class="icon">
                <i class="fas fa-sitemap"></i>
            </div>
            <span class="small-box-footer">
                Terintegrasi Penuh
            </span>
        </div>
    </div>
</div>

<!-- Baris Grafik Komparasi -->
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header border-0 bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h3 class="card-title font-weight-bold text-success">
                            <i class="fas fa-chart-bar mr-2"></i>
                            @if($role === 'ranting')
                                Grafik Performa Pengumpulan per Relawan ({{ $scopeTitle }})
                            @elseif($role === 'mwc')
                                Grafik Performa Komparasi Ranting di Wilayah MWC {{ $scopeTitle }}
                            @else
                                Grafik Performa Komparasi 15 Majelis Wakil Cabang (MWC)
                            @endif
                        </h3>
                        <p class="text-muted text-sm mb-0 ml-4">
                            @if($role === 'ranting')
                                Perbandingan perolehan setoran koin infaq antar Relawan untuk periode <strong>{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</strong>
                            @elseif($role === 'mwc')
                                Perbandingan perolehan setoran koin infaq antar Ranting se-MWC untuk periode <strong>{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</strong>
                            @else
                                Perbandingan perolehan setoran koin infaq antar 15 MWC untuk periode <strong>{{ \Carbon\Carbon::now()->translatedFormat('F Y') }}</strong>
                            @endif
                        </p>
                    </div>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="position-relative mb-2" style="height: 340px;">
                    <canvas id="mwcComparisonChart"></canvas>
                </div>
                <div class="d-flex justify-content-center text-sm mt-3">
                    <span class="mr-3">
                        <i class="fas fa-square text-success mr-1"></i> Infaq Bulan Ini (Rp)
                    </span>
                    <span>
                        <i class="fas fa-square text-info mr-1"></i> Total Akumulasi (Rp)
                    </span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Baris Tabel Leaderboard & Transaksi Terkini -->
<div class="row">
    <!-- Tabel Peringkat Komparasi -->
    <div class="col-lg-8 col-12">
        <div class="card shadow-sm">
            <div class="card-header bg-white border-bottom">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-list-ol mr-2 text-warning"></i>
                    @if($role === 'ranting')
                        Rekapitulasi Perolehan Relawan di Ranting {{ $scopeTitle }}
                    @elseif($role === 'mwc')
                        Rekapitulasi & Peringkat Perolehan Ranting se-MWC {{ $scopeTitle }}
                    @else
                        Rekapitulasi & Peringkat Perolehan 15 MWC
                    @endif
                </h3>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover m-0 align-middle">
                        <thead class="thead-light">
                            @if($role === 'ranting')
                                <tr>
                                    <th>#</th>
                                    <th>Nama Relawan</th>
                                    <th>Area Tugas (RT/RW)</th>
                                    <th class="text-right">Bulan Ini</th>
                                    <th class="text-right">Total Akumulasi</th>
                                    <th class="text-center">Kontribusi</th>
                                </tr>
                            @elseif($role === 'mwc')
                                <tr>
                                    <th>#</th>
                                    <th>Kode</th>
                                    <th>Nama Ranting (Desa)</th>
                                    <th class="text-center">Relawan</th>
                                    <th class="text-right">Bulan Ini</th>
                                    <th class="text-right">Total Akumulasi</th>
                                    <th class="text-center">Kontribusi</th>
                                </tr>
                            @else
                                <tr>
                                    <th>#</th>
                                    <th>Kode</th>
                                    <th>Nama MWC (Kecamatan)</th>
                                    <th class="text-center">Ranting</th>
                                    <th class="text-center">Relawan</th>
                                    <th class="text-right">Bulan Ini</th>
                                    <th class="text-right">Total Akumulasi</th>
                                    <th class="text-center">Kontribusi</th>
                                </tr>
                            @endif
                        </thead>
                        <tbody>
                            @forelse($mwcStats as $index => $item)
                                @php
                                    $monthlyVal = (float) ($item->monthly_funds ?? 0);
                                    $allTimeVal = (float) ($item->alltime_funds ?? 0);
                                    $percentage = ($totalDanaBulanIni > 0) 
                                        ? round(($monthlyVal / $totalDanaBulanIni) * 100, 1) 
                                        : 0;
                                @endphp
                                <tr>
                                    <td class="font-weight-bold">{{ $index + 1 }}</td>
                                    @if($role === 'ranting')
                                        <td class="font-weight-bold text-dark">{{ $item->nama }}</td>
                                        <td><span class="badge badge-light border">{{ $item->area_tugas }}</span></td>
                                    @elseif($role === 'mwc')
                                        <td><span class="badge badge-light border">{{ $item->code }}</span></td>
                                        <td class="font-weight-bold text-dark">{{ $item->name }}</td>
                                        <td class="text-center"><span class="badge badge-warning text-dark">{{ $item->relawans_count ?? 0 }}</span></td>
                                    @else
                                        <td><span class="badge badge-light border">{{ $item->code }}</span></td>
                                        <td class="font-weight-bold text-dark">{{ $item->name }}</td>
                                        <td class="text-center"><span class="badge badge-info">{{ $item->rantings_count }}</span></td>
                                        <td class="text-center"><span class="badge badge-warning text-dark">{{ $item->relawans_count }}</span></td>
                                    @endif
                                    <td class="text-right font-weight-bold text-success">
                                        Rp {{ number_format($monthlyVal, 0, ',', '.') }}
                                    </td>
                                    <td class="text-right text-muted">
                                        Rp {{ number_format($allTimeVal, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <div class="progress progress-xs mb-1">
                                            <div class="progress-bar bg-success" style="width: {{ min(100, $percentage) }}%"></div>
                                        </div>
                                        <small class="font-weight-bold">{{ $percentage }}%</small>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4 text-muted">Belum ada data setoran koin pada periode ini.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Setoran Terkini & Pintasan Cepat -->
    <div class="col-lg-4 col-12">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-history mr-2 text-primary"></i> Setoran Koin Terkini
                </h3>
            </div>
            <div class="card-body p-0">
                <ul class="products-list product-list-in-card pl-2 pr-2">
                    @forelse($recentTransactions as $tx)
                        <li class="item p-2 border-bottom">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="font-weight-bold text-dark d-block">
                                        {{ $tx->relawan->nama }} <small class="text-muted">({{ $tx->relawan->area_tugas }})</small>
                                    </span>
                                    <small class="text-muted">
                                        <i class="fas fa-mosque mr-1"></i>{{ $tx->ranting->name }} &bull; {{ $tx->mwc->name }}
                                    </small>
                                </div>
                                <div class="text-right">
                                    <span class="font-weight-bold text-success d-block">
                                        {{ $tx->formatted_amount }}
                                    </span>
                                    <small class="badge badge-light border">{{ \Carbon\Carbon::parse($tx->collection_date)->format('d/m/Y') }}</small>
                                </div>
                            </div>
                        </li>
                    @empty
                        <li class="p-3 text-center text-muted">Belum ada transaksi tercatat.</li>
                    @endforelse
                </ul>
            </div>
            <div class="card-footer text-center">
                <a href="{{ route('transactions.index') }}" class="text-xs text-uppercase font-weight-bold">Lihat Semua Riwayat</a>
            </div>
        </div>

        <div class="card bg-gradient-success text-white shadow-sm">
            <div class="card-body">
                <h5 class="font-weight-bold mb-2">
                    <i class="fas fa-shield-alt mr-1"></i> Aturan Bisnis KOIN NU
                </h5>
                <p class="text-sm mb-3" style="color: #ecfdf3;">
                    Pencatatan koin <strong>TIDAK dilakukan per donatur</strong> untuk menghindari kesenjangan sosial warga. Pengurus Ranting mencatat murni total uang yang dihimpun oleh masing-masing Relawan RT/RW.
                </p>
                <a href="{{ route('transactions.create') }}" class="btn btn-light btn-sm text-success font-weight-bold shadow-sm">
                    <i class="fas fa-plus-circle mr-1"></i> Input Setoran Relawan
                </a>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
function initMwcComparisonChart() {
    const canvas = document.getElementById('mwcComparisonChart');
    if (!canvas) return;

    if (typeof Chart === 'undefined') {
        setTimeout(initMwcComparisonChart, 150);
        return;
    }

    const ctx = canvas.getContext('2d');
    const labels = {!! json_encode($mwcChartLabels) !!};
    const monthlyData = {!! json_encode($mwcChartMonthly) !!};
    const alltimeData = {!! json_encode($mwcChartAllTime) !!};

    if (window.mwcComparisonChartInstance) {
        window.mwcComparisonChartInstance.destroy();
    }

    window.mwcComparisonChartInstance = new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [
                {
                    label: 'Bulan Berjalan (Rp)',
                    backgroundColor: 'rgba(10, 107, 56, 0.85)',
                    borderColor: 'rgba(10, 107, 56, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    data: monthlyData,
                },
                {
                    label: 'Akumulasi Keseluruhan (Rp)',
                    backgroundColor: 'rgba(23, 162, 184, 0.7)',
                    borderColor: 'rgba(23, 162, 184, 1)',
                    borderWidth: 1,
                    borderRadius: 4,
                    data: alltimeData,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(val) {
                            return 'Rp ' + Number(val).toLocaleString('id-ID');
                        }
                    }
                },
                x: {
                    ticks: {
                        maxRotation: 45,
                        minRotation: 25
                    }
                }
            },
            plugins: {
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            let val = context.raw || 0;
                            return label + ': Rp ' + Number(val).toLocaleString('id-ID');
                        }
                    }
                }
            }
        }
    });
}

if (document.readyState === 'loading') {
    document.addEventListener("DOMContentLoaded", initMwcComparisonChart);
} else {
    initMwcComparisonChart();
}
</script>
@endpush
