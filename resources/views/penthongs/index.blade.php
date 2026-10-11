@extends('layouts.adminlte')

@section('title', 'Manajemen Periode Penthong')
@section('page_title', 'Periode Buka Kotak KOIN NU (Penthong)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Penthong & Bagi Hasil</li>
@endsection

@section('content')
<!-- Section Penthong Aktif Saat Ini -->
@if($activePenthong)
    <div class="card card-outline card-success shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold text-success">
                <i class="fas fa-box-open mr-2"></i> Periode Buka Kotak Aktif: <u>{{ $activePenthong->nama_periode }}</u>
            </h3>
            <div>
                <span class="badge badge-success px-3 py-1 font-weight-bold mr-2">
                    <i class="fas fa-circle mr-1 text-xs"></i> SEDANG BERJALAN
                </span>
                @if(Auth::user()?->isPc())
                    <form action="{{ route('penthongs.close', $activePenthong->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin MENUTUP & MENGUNCI periode Penthong ini? Data bagi hasil agregat dan per-ranting akan dihitung dan dikunci secara permanen.');">
                        @csrf
                        <button type="submit" class="btn btn-sm btn-danger shadow-sm font-weight-bold">
                            <i class="fas fa-lock mr-1"></i> Tutup & Kunci Penthong
                        </button>
                    </form>
                @endif
            </div>
        </div>
        <div class="card-body">
            <div class="row">
                <!-- Total Gross & Operasional -->
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="p-3 bg-light rounded border h-100">
                        <small class="text-muted text-uppercase font-weight-bold d-block">Total Perolehan Kotor:</small>
                        <div class="fs-4 font-weight-bold text-dark my-1">
                            Rp {{ number_format($activeSummary['total_gross'] ?? 0, 0, ',', '.') }}
                        </div>
                        <small class="text-success font-weight-bold">
                            <i class="fas fa-receipt mr-1"></i> {{ $activePenthong->transactions_count }} Transaksi Masuk
                        </small>
                    </div>
                </div>

                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="p-3 bg-light rounded border border-warning h-100">
                        <div class="d-flex justify-content-between align-items-center">
                            <small class="text-muted text-uppercase font-weight-bold">Potongan Operasional:</small>
                            <span class="badge badge-warning text-dark font-weight-bold">{{ $activeSummary['operasional_pct'] ?? 20 }}%</span>
                        </div>
                        <div class="fs-4 font-weight-bold text-warning my-1">
                            Rp {{ number_format($activeSummary['operasional_amount'] ?? 0, 0, ',', '.') }}
                        </div>
                        <small class="text-muted">Armada, kaleng & administrasi</small>
                    </div>
                </div>

                <!-- Dana Bersih Bagi Hasil -->
                <div class="col-lg-6 col-md-12 mb-3">
                    <div class="p-3 bg-light rounded border border-success h-100">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <small class="text-muted text-uppercase font-weight-bold">Simulasi Bagi Hasil Dana Bersih (80% Net):</small>
                            <span class="badge badge-success font-weight-bold">Rp {{ number_format($activeSummary['net_amount'] ?? 0, 0, ',', '.') }}</span>
                        </div>
                        <div class="row text-center">
                            <div class="col">
                                <span class="badge badge-primary">Ranting (80%)</span>
                                <div class="font-weight-bold text-sm mt-1">Rp {{ number_format($activeSummary['hak_ranting'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="col">
                                <span class="badge badge-info">MWC ({{ $setting->mwc_pct }}%)</span>
                                <div class="font-weight-bold text-sm mt-1">Rp {{ number_format($activeSummary['hak_mwc'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="col">
                                <span class="badge badge-success">PC ({{ $setting->pc_pct }}%)</span>
                                <div class="font-weight-bold text-sm mt-1">Rp {{ number_format($activeSummary['hak_pc'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="col">
                                <span class="badge badge-secondary">PW (1.5%)</span>
                                <div class="font-weight-bold text-sm mt-1">Rp {{ number_format($activeSummary['hak_pw'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                            <div class="col">
                                <span class="badge badge-dark">PB (0.5%)</span>
                                <div class="font-weight-bold text-sm mt-1">Rp {{ number_format($activeSummary['hak_pb'] ?? 0, 0, ',', '.') }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-between align-items-center mt-2 pt-2 border-top">
                <small class="text-muted">
                    <i class="fas fa-calendar-alt mr-1"></i> Bulan: <strong>{{ $activePenthong->bulan_nama }} {{ $activePenthong->tahun }}</strong> | Dibuka pada: {{ $activePenthong->created_at->format('d/m/Y H:i') }}
                </small>
                <a href="{{ route('penthongs.show', $activePenthong->id) }}" class="btn btn-sm btn-outline-success font-weight-bold">
                    <i class="fas fa-chart-pie mr-1"></i> Lihat Rincian Bagi Hasil per Ranting &raquo;
                </a>
            </div>
        </div>
    </div>
@else
    <div class="alert alert-warning shadow-sm mb-4">
        <div class="d-flex justify-content-between align-items-center flex-wrap">
            <div>
                <h5 class="mb-1"><i class="icon fas fa-exclamation-triangle"></i> Belum Ada Penthong yang Aktif</h5>
                <p class="mb-0 small">Buka periode Penthong baru untuk mengaitkan setoran koin infaq bulanan dan mengaktifkan skema bagi hasil.</p>
            </div>
            @if(Auth::user()?->isPc())
                <a href="{{ route('penthongs.create') }}" class="btn btn-success btn-sm font-weight-bold mt-2 mt-md-0 shadow-sm">
                    <i class="fas fa-plus-circle mr-1"></i> Buka Periode Penthong Baru
                </a>
            @endif
        </div>
    </div>
@endif

<!-- Tabel Daftar Seluruh Periode Penthong -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-history mr-1 text-success"></i> Riwayat Periode Penthong (Tasharruf)
        </h3>
        <div class="card-tools d-flex align-items-center gap-2">
            @if(Auth::user()?->isPc())
                <a href="{{ route('penthongs.create') }}" class="btn btn-sm btn-success mr-2 font-weight-bold shadow-sm">
                    <i class="fas fa-plus-circle mr-1"></i> Buka Penthong Baru
                </a>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover m-0 align-middle">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Nama Periode</th>
                        <th>Bulan / Tahun</th>
                        <th>Status</th>
                        <th class="text-right">Total Perolehan</th>
                        <th class="text-right">Operasional (20%)</th>
                        <th class="text-right text-success">Hak Ranting (80% Net)</th>
                        <th class="text-right text-info">Hak MWC</th>
                        <th class="text-right text-primary">Hak PC</th>
                        <th class="text-center" style="width: 175px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($penthongs as $index => $p)
                        <tr>
                            <td>{{ $penthongs->firstItem() + $index }}</td>
                            <td>
                                <strong class="text-dark d-block">{{ $p->nama_periode }}</strong>
                                <small class="text-muted">{{ $p->transactions_count }} Transaksi</small>
                            </td>
                            <td>
                                <span class="badge badge-light border">{{ $p->bulan_nama }} {{ $p->tahun }}</span>
                            </td>
                            <td>{!! $p->status_badge !!}</td>
                            <td class="text-right font-weight-bold">
                                Rp {{ number_format($p->total_collected, 0, ',', '.') }}
                            </td>
                            <td class="text-right text-muted">
                                Rp {{ number_format($p->operasional_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-weight-bold text-success">
                                Rp {{ number_format($p->ranting_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-weight-bold text-info">
                                Rp {{ number_format($p->mwc_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-right font-weight-bold text-primary">
                                Rp {{ number_format($p->pc_amount, 0, ',', '.') }}
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('penthongs.show', $p->id) }}" class="btn btn-xs btn-info" title="Lihat Rincian Distribusi">
                                        <i class="fas fa-table mr-1"></i> Rincian
                                    </a>
                                    <button type="button" class="btn btn-xs btn-outline-danger" onclick="openModalCetakLaporan({format: 'pdf', penthong_id: '{{ $p->id }}'})" title="Konfigurasi & Cetak PDF">
                                        <i class="fas fa-file-pdf"></i>
                                    </button>
                                    <button type="button" class="btn btn-xs btn-outline-success" onclick="openModalCetakLaporan({format: 'excel', penthong_id: '{{ $p->id }}'})" title="Konfigurasi & Ekspor Excel">
                                        <i class="fas fa-file-excel"></i>
                                    </button>

                                    @if(Auth::user()?->isPc())
                                        @if($p->isOpen())
                                            <form action="{{ route('penthongs.close', $p->id) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('Kunci dan tutup Periode {{ $p->nama_periode }} ini?');">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-outline-danger" title="Tutup & Kunci Periode">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('penthongs.toggle-status', $p->id) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('Buka kembali Periode {{ $p->nama_periode }} ini sebagai periode aktif?');">
                                                @csrf
                                                <button type="submit" class="btn btn-xs btn-outline-success" title="Buka Kembali Periode">
                                                    <i class="fas fa-lock-open"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                Belum ada data periode Penthong yang tercatat di sistem.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white clearfix">
        <div class="float-right">
            {{ $penthongs->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection
