@extends('layouts.adminlte')

@section('title', 'Laporan Capaian per MWC & Ranting')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-landmark text-primary mr-2"></i>Laporan Capaian per MWC & Ranting</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('reports.print', ['type' => 'rekap_mwc', 'period' => $selectedPeriod]) }}" target="_blank" class="btn btn-primary font-weight-bold">
                    <i class="fas fa-print mr-1"></i> Cetak Rekap Wilayah
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Filter Periode -->
        <div class="card card-outline card-primary shadow-sm mb-3">
            <div class="card-body p-3">
                <form action="{{ route('reports.wilayah') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <label class="small font-weight-bold text-muted mb-1">Periode Bulan Perolehan:</label>
                        <input type="month" name="period" class="form-control form-control-sm" value="{{ $selectedPeriod }}">
                    </div>
                    <div class="col-md-2 mt-auto">
                        <button type="submit" class="btn btn-primary btn-sm btn-block font-weight-bold"><i class="fas fa-filter mr-1"></i> Tampilkan</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Tabel Peringkat & Perolehan Koin 15 MWC NU (Periode: {{ $selectedPeriod }})</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 60px;" class="text-center">Rank</th>
                            <th>Kode & Nama MWC</th>
                            <th>Kecamatan</th>
                            <th class="text-center">Jumlah Ranting</th>
                            <th class="text-center">Relawan Aktif</th>
                            <th class="text-center">Kotak Koin</th>
                            <th class="text-right">Perolehan Bulan Ini (Rp)</th>
                            <th class="text-right">Total Akumulasi (Rp)</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($mwcs->values() as $idx => $mwc)
                            <tr>
                                <td class="text-center font-weight-bold fs-6">
                                    @if($idx == 0)
                                        <span class="badge badge-warning text-dark"><i class="fas fa-crown mr-1"></i>1</span>
                                    @elseif($idx == 1)
                                        <span class="badge badge-secondary">2</span>
                                    @elseif($idx == 2)
                                        <span class="badge badge-danger">3</span>
                                    @else
                                        {{ $idx + 1 }}
                                    @endif
                                </td>
                                <td>
                                    <strong>{{ $mwc->name }}</strong>
                                    <div class="small text-muted">Kode: <code>{{ $mwc->code }}</code></div>
                                </td>
                                <td>{{ $mwc->kecamatan }}</td>
                                <td class="text-center"><span class="badge badge-info">{{ $mwc->rantings_count }} Ranting</span></td>
                                <td class="text-center"><span class="badge badge-light border">{{ $mwc->relawan_count }} Relawan</span></td>
                                <td class="text-center"><span class="badge badge-light border">{{ $mwc->donators_count }} Kotak</span></td>
                                <td class="text-right font-weight-bold text-success fs-6">
                                    Rp {{ number_format($mwc->period_amount, 0, ',', '.') }}
                                </td>
                                <td class="text-right font-weight-bold text-dark">
                                    Rp {{ number_format($mwc->total_amount, 0, ',', '.') }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="6" class="text-right">TOTAL KESELURUHAN (15 MWC):</td>
                            <td class="text-right text-success fs-5">Rp {{ number_format($mwcs->sum('period_amount'), 0, ',', '.') }}</td>
                            <td class="text-right text-dark fs-5">Rp {{ number_format($mwcs->sum('total_amount'), 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
