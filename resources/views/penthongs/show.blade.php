@extends('layouts.adminlte')

@section('title', 'Laporan Tasharruf Penthong: ' . $penthong->nama_periode)
@section('page_title', 'Laporan Distribusi & Bagi Hasil Penthong')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('penthongs.index') }}">Penthong</a></li>
    <li class="breadcrumb-item active">{{ $penthong->nama_periode }}</li>
@endsection

@section('content')
<!-- Header Kartu Status & Informasi Penthong -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h3 class="card-title font-weight-bold text-success mb-1">
                <i class="fas fa-boxes mr-1"></i> {{ $penthong->nama_periode }}
            </h3>
            <span class="ml-2">{!! $penthong->status_badge !!}</span>
            <span class="badge badge-light border ml-1">Periode: {{ $penthong->bulan_nama }} {{ $penthong->tahun }}</span>
        </div>
        <div class="mt-2 mt-sm-0 d-flex align-items-center flex-wrap gap-2">
            <!-- Modul Cetak Laporan (Export PDF & Excel) Sesuai Kop & Format Resmi -->
            <div class="btn-group mr-2">
                <button type="button" class="btn btn-sm btn-outline-danger font-weight-bold shadow-sm" onclick="openModalCetakLaporan({format: 'pdf', penthong_id: '{{ $penthong->id }}', mwc_id: '{{ request('mwc_id') }}'})" title="Konfigurasi & Cetak Dokumen Resmi PDF dengan Kop Surat">
                    <i class="fas fa-file-pdf mr-1"></i> Cetak Laporan (PDF)
                </button>
                <button type="button" class="btn btn-sm btn-outline-success font-weight-bold shadow-sm" onclick="openModalCetakLaporan({format: 'excel', penthong_id: '{{ $penthong->id }}', mwc_id: '{{ request('mwc_id') }}'})" title="Konfigurasi & Ekspor Data ke File Spreadsheet Excel">
                    <i class="fas fa-file-excel mr-1"></i> Ekspor Data (Excel)
                </button>
            </div>

            @if(Auth::user()?->isPc())
                @if($penthong->isOpen())
                    <form action="{{ route('penthongs.close', $penthong->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin MENUTUP & MENGUNCI periode Penthong ini? Data bagi hasil agregat dan per-ranting akan dihitung dan dikunci secara permanen.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger shadow-sm font-weight-bold mr-1">
                            <i class="fas fa-lock mr-1"></i> Tutup & Kunci Penthong
                        </button>
                    </form>
                @else
                    <form action="{{ route('penthongs.toggle-status', $penthong->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Buka kembali Periode {{ $penthong->nama_periode }} ini sebagai periode aktif?');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-success shadow-sm font-weight-bold mr-1">
                            <i class="fas fa-lock-open mr-1"></i> Buka Kembali Periode
                        </button>
                    </form>
                @endif
            @endif
        </div>
    </div>
    <div class="card-body">
        <!-- Ringkasan Agregat Tasharruf -->
        <div class="row">
            <div class="col-md-3 col-sm-6 mb-3">
                <div class="info-box bg-light border shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-coins"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted">Total Pendapatan Kotor</span>
                        <span class="info-box-number text-dark font-weight-bold fs-5">
                            Rp {{ number_format($split['total_gross'] ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-3">
                <div class="info-box bg-light border shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-tools"></i></span>
                    <div class="info-box-content">
                        @if(Auth::user()?->isMwc())
                            <span class="info-box-text text-muted">Hak Amil MWC (20% Kontribusi)</span>
                            <span class="info-box-number text-warning font-weight-bold fs-5">
                                Rp {{ number_format($split['operasional_mwc'] ?? round(($split['hak_mwc'] ?? 0) * 0.2), 0, ',', '.') }}
                            </span>
                            <small class="text-muted d-block">Dari Hak MWC: Rp {{ number_format($split['hak_mwc'] ?? 0, 0, ',', '.') }}</small>
                        @elseif(Auth::user()?->isPc())
                            <span class="info-box-text text-muted">Hak Amil PC (20% Kontribusi)</span>
                            <span class="info-box-number text-warning font-weight-bold fs-5">
                                Rp {{ number_format($split['operasional_pc'] ?? round(($split['hak_pc'] ?? 0) * 0.2), 0, ',', '.') }}
                            </span>
                            <small class="text-muted d-block">Ops Ranting: Rp {{ number_format($split['operasional_amount'] ?? 0, 0, ',', '.') }}</small>
                        @else
                            <span class="info-box-text text-muted">Operasional Ranting ({{ $setting->operasional_pct }}%)</span>
                            <span class="info-box-number text-warning font-weight-bold fs-5">
                                Rp {{ number_format($split['operasional_amount'] ?? 0, 0, ',', '.') }}
                            </span>
                            <small class="text-muted d-block">20% dari Rekap Koin Bruto</small>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-3">
                <div class="info-box bg-light border shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-hand-holding-heart"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted">Dana Bersih (80% Net)</span>
                        <span class="info-box-number text-info font-weight-bold fs-5">
                            Rp {{ number_format($split['net_amount'] ?? 0, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <div class="col-md-3 col-sm-6 mb-3">
                <div class="info-box bg-light border shadow-sm">
                    <span class="info-box-icon bg-primary"><i class="fas fa-sitemap"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text text-muted">Total Ranting Berpartisipasi</span>
                        <span class="info-box-number text-primary font-weight-bold fs-5">
                            {{ $distributions->count() }} Ranting
                        </span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Banner Formula Skema Bagi Hasil Tasharruf & Hak Amil Operasional -->
        <div class="callout callout-success py-2 mb-0 bg-light">
            <h6 class="font-weight-bold mb-1 text-success">
                <i class="fas fa-calculator mr-1"></i> Rumus Alokasi Distribusi Tasharruf (Dana Bersih) & Hak Amil Operasional:
            </h6>
            <div class="d-flex flex-wrap gap-3 small text-muted">
                <span><i class="fas fa-check text-success mr-1"></i><strong>Ranting:</strong> {{ $setting->ranting_pct }}% (Rp {{ number_format($split['hak_ranting'] ?? 0, 0, ',', '.') }}) <span class="badge badge-light border text-warning" title="20% dari koin bruto">Ops Koin: Rp {{ number_format($split['operasional_ranting'] ?? $split['operasional_amount'] ?? 0, 0, ',', '.') }}</span></span>
                <span class="text-muted">|</span>
                <span><i class="fas fa-check text-info mr-1"></i><strong>MWC:</strong> {{ $setting->mwc_pct }}% (Rp {{ number_format($split['hak_mwc'] ?? 0, 0, ',', '.') }}) <span class="badge badge-light border text-warning" title="20% dari kontribusi MWC">Ops: Rp {{ number_format($split['operasional_mwc'] ?? round(($split['hak_mwc'] ?? 0) * 0.2), 0, ',', '.') }} &bull; 5 Pilar: Rp {{ number_format($split['pilar_mwc'] ?? round(($split['hak_mwc'] ?? 0) * 0.8), 0, ',', '.') }}</span></span>
                <span class="text-muted">|</span>
                <span><i class="fas fa-check text-primary mr-1"></i><strong>PC Cabang:</strong> {{ $setting->pc_pct }}% (Rp {{ number_format($split['hak_pc'] ?? 0, 0, ',', '.') }}) <span class="badge badge-light border text-warning" title="20% dari kontribusi PC">Ops: Rp {{ number_format($split['operasional_pc'] ?? round(($split['hak_pc'] ?? 0) * 0.2), 0, ',', '.') }} &bull; 5 Pilar: Rp {{ number_format($split['pilar_pc'] ?? round(($split['hak_pc'] ?? 0) * 0.8), 0, ',', '.') }}</span></span>
                <span class="text-muted">|</span>
                <span><i class="fas fa-check text-secondary mr-1"></i><strong>PW Wilayah:</strong> {{ $setting->pw_pct }}% (Rp {{ number_format($split['hak_pw'] ?? 0, 0, ',', '.') }})</span>
                <span class="text-muted">|</span>
                <span><i class="fas fa-check text-dark mr-1"></i><strong>PB Pusat:</strong> {{ $setting->pb_pct }}% (Rp {{ number_format($split['hak_pb'] ?? 0, 0, ',', '.') }})</span>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Laporan Penthong per Ranting (Level MWC / PC) -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-table mr-1 text-success"></i> Rincian Tasharruf Bagi Hasil per Ranting NU
        </h3>
        @if(Auth::user()?->isPc())
            <form method="GET" action="{{ route('penthongs.show', $penthong->id) }}" class="form-inline mt-2 mt-md-0">
                <label class="mr-2 small font-weight-bold">Filter MWC:</label>
                <select name="mwc_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                    <option value="">-- Semua 15 MWC --</option>
                    @foreach($mwcs as $m)
                        <option value="{{ $m->id }}" {{ request('mwc_id') == $m->id ? 'selected' : '' }}>
                            [{{ $m->code }}] {{ $m->name }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-bordered table-striped table-hover m-0 align-middle text-sm">
                <thead class="thead-light text-center">
                    <tr>
                        <th style="width: 40px;" rowspan="2">No</th>
                        <th rowspan="2">Nama Ranting (Desa)</th>
                        <th rowspan="2">MWC (Kecamatan)</th>
                        <th rowspan="2" class="text-right">Total Pendapatan (Kotor)</th>
                        <th rowspan="2" class="text-right bg-warning-subtle">Operasional ({{ $setting->operasional_pct }}%)</th>
                        <th colspan="5" class="bg-success-subtle">Alokasi Dana Bersih (80% Net)</th>
                    </tr>
                    <tr>
                        <th class="text-right text-success" title="Porsi Ranting">Ranting ({{ $setting->ranting_pct }}%)</th>
                        <th class="text-right text-info" title="Porsi MWC">MWC ({{ $setting->mwc_pct }}%)</th>
                        <th class="text-right text-primary" title="Porsi PC">PC ({{ $setting->pc_pct }}%)</th>
                        <th class="text-right text-secondary" title="Porsi PW">PW ({{ $setting->pw_pct }}%)</th>
                        <th class="text-right text-dark" title="Porsi PB">PB ({{ $setting->pb_pct }}%)</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $sumGross = 0;
                        $sumOps = 0;
                        $sumRanting = 0;
                        $sumMwc = 0;
                        $sumPc = 0;
                        $sumPw = 0;
                        $sumPb = 0;
                    @endphp

                    @forelse($distributions as $index => $dist)
                        @php
                            $sumGross += $dist->gross_amount;
                            $sumOps += $dist->operasional_amount;
                            $sumRanting += $dist->ranting_amount;
                            $sumMwc += $dist->mwc_amount;
                            $sumPc += $dist->pc_amount;
                            $sumPw += $dist->pw_amount;
                            $sumPb += $dist->pb_amount;
                        @endphp
                        <tr>
                            <td class="text-center">{{ $index + 1 }}</td>
                            <td>
                                <strong class="text-dark">{{ $dist->ranting->name ?? 'Ranting #' . $dist->ranting_id }}</strong>
                                <small class="text-muted d-block font-monospace">[{{ $dist->ranting->code ?? '-' }}]</small>
                            </td>
                            <td>
                                <span class="badge badge-light border">{{ $dist->mwc->code ?? $dist->ranting->mwc->code ?? '-' }}</span>
                                <small class="text-muted ml-1">{{ $dist->mwc->name ?? $dist->ranting->mwc->name ?? '-' }}</small>
                            </td>
                            <td class="text-right font-weight-bold">
                                Rp {{ number_format($dist->gross_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-right text-warning font-weight-bold">
                                Rp {{ number_format($dist->operasional_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-right text-success font-weight-bold">
                                Rp {{ number_format($dist->ranting_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-right text-info font-weight-bold">
                                Rp {{ number_format($dist->mwc_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-right text-primary font-weight-bold">
                                Rp {{ number_format($dist->pc_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-right text-secondary">
                                Rp {{ number_format($dist->pw_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-right text-dark">
                                Rp {{ number_format($dist->pb_amount, 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fs-3 mb-2 d-block"></i>
                                Belum ada transaksi setoran koin yang tercatat pada periode Penthong ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($distributions->isNotEmpty())
                    <tfoot class="thead-dark font-weight-bold text-right">
                        <tr>
                            <td colspan="3" class="text-center font-weight-bold text-uppercase">Total Rekapitulasi</td>
                            <td class="text-warning">Rp {{ number_format($sumGross, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($sumOps, 0, ',', '.') }}</td>
                            <td class="text-success">Rp {{ number_format($sumRanting, 0, ',', '.') }}</td>
                            <td class="text-info">Rp {{ number_format($sumMwc, 0, ',', '.') }}</td>
                            <td class="text-primary">Rp {{ number_format($sumPc, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($sumPw, 0, ',', '.') }}</td>
                            <td>Rp {{ number_format($sumPb, 0, ',', '.') }}</td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
    <div class="card-footer bg-light d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Status: {!! $penthong->status_badge !!} | Dicetak oleh: {{ Auth::user()->name }} pada {{ now()->format('d/m/Y H:i') }}
        </small>
        <a href="{{ route('penthongs.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Penthong
        </a>
    </div>
</div>
@endsection
