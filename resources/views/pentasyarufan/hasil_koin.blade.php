@extends('layouts.adminlte')

@section('title', 'Hasil Koin - Simplifikasi Pembagian Bruto')
@section('page_title', 'Hasil Koin & Pembagian Bruto')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pentasyarufan.index') }}">Pentasyarufan</a></li>
    <li class="breadcrumb-item active">Hasil Koin</li>
@endsection

@section('content')
<div class="container-fluid">
    {{-- Notifikasi Flash Message --}}
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle mr-2"></i> <strong>Berhasil!</strong> {{ session('success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> <strong>Peringatan!</strong> {{ session('error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- Banner Informasi Simplifikasi Bisnis --}}
    <div class="alert alert-info alert-dismissible fade show shadow-sm border-left-info" role="alert">
        <h5><i class="fas fa-info-circle mr-2"></i> Simplifikasi Tahap Awal (Direct Split KOIN NU)</h5>
        <p class="mb-0">
            Sesuai kesepakatan sosialisasi dengan pengurus MWC NU, sistem menggunakan <strong>Logika Pembagian Langsung (Direct Split)</strong>.
            Potongan operasional amil 20% di awal <strong>dibypass/ditiadakan</strong>. 
            Dana <strong>100% Koin Bruto</strong> langsung dibagikan ke struktur: 
            <span class="badge badge-light border text-dark font-weight-bold">Ranting 80%</span> &bull; 
            <span class="badge badge-light border text-dark font-weight-bold">MWC 13%</span> &bull; 
            <span class="badge badge-light border text-dark font-weight-bold">PC 5%</span> &bull; 
            <span class="badge badge-light border text-dark font-weight-bold">PW 1.5%</span> &bull; 
            <span class="badge badge-light border text-dark font-weight-bold">PB 0.5%</span> 
            (Total Genap 100%).
        </p>
    </div>

    {{-- Header Widget: Periode Aktif, Total Bruto, & Tombol Aksi --}}
    <div class="row">
        {{-- Card Periode Pentong Aktif --}}
        <div class="col-lg-4 col-md-6 col-12 mb-3">
            <div class="card card-outline card-success shadow-sm h-100 mb-0">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted text-uppercase font-weight-bold small">Periode Pentong Aktif</span>
                        @if($currentPenthong && $currentPenthong->isOpen())
                            <span class="badge badge-success px-2 py-1"><i class="fas fa-unlock-alt mr-1"></i> Aktif (Open)</span>
                        @elseif($currentPenthong)
                            <span class="badge badge-secondary px-2 py-1"><i class="fas fa-lock mr-1"></i> Tutup</span>
                        @else
                            <span class="badge badge-danger px-2 py-1">Tidak Ada Periode</span>
                        @endif
                    </div>

                    @if($currentPenthong)
                        <h4 class="font-weight-bold text-dark mb-1">
                            {{ $currentPenthong->nama_periode }}
                        </h4>
                        <p class="text-secondary mb-3">
                            <i class="far fa-calendar-alt mr-1"></i> 
                            {{ $currentPenthong->bulan_nama }} {{ $currentPenthong->tahun }}
                        </p>

                        {{-- Form Pilih Periode Lain --}}
                        <form method="GET" action="{{ route('pentasyarufan.hasil-koin.index') }}" class="mt-2">
                            <div class="input-group input-group-sm">
                                <select name="penthong_id" class="form-control" onchange="this.form.submit()">
                                    @foreach($penthongs as $p)
                                        <option value="{{ $p->id }}" {{ $currentPenthong->id == $p->id ? 'selected' : '' }}>
                                            {{ $p->nama_periode }} ({{ $p->bulan_nama }} {{ $p->tahun }}) {{ $p->isOpen() ? '[Aktif]' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="submit"><i class="fas fa-sync-alt"></i></button>
                                </div>
                            </div>
                        </form>
                    @else
                        <p class="text-danger mb-0">Belum ada Periode Penthong yang dibuat.</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- Card Total Koin Bruto --}}
        <div class="col-lg-4 col-md-6 col-12 mb-3">
            <div class="card card-outline card-primary shadow-sm h-100 mb-0">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase font-weight-bold small">Total Koin Bruto (100%)</span>
                        <h2 class="font-weight-bold text-primary my-2">
                            Rp {{ number_format($totalKoinBruto, 0, ',', '.') }}
                        </h2>
                        <p class="text-muted small mb-0">
                            <i class="fas fa-coins text-warning mr-1"></i> Keseluruhan dana koin terkumpul sebelum dipotong apapun.
                        </p>
                    </div>
                    <div class="mt-3 pt-2 border-top d-flex justify-content-between text-muted small">
                        <span>Jumlah Ranting Menyetor: <strong>{{ $rantingBreakdown->count() }} Ranting</strong></span>
                        <span>Status: 
                            @if($isProcessed)
                                <strong class="text-success"><i class="fas fa-check-circle"></i> Sudah Dibagikan</strong>
                            @else
                                <strong class="text-warning"><i class="fas fa-clock"></i> Belum Diproses</strong>
                            @endif
                        </span>
                    </div>
                </div>
            </div>
        </div>

        {{-- Card Tombol Aksi "Proses & Bagikan Hasil Koin" --}}
        <div class="col-lg-4 col-12 mb-3">
            <div class="card card-outline card-warning shadow-sm h-100 mb-0">
                <div class="card-body d-flex flex-column justify-content-between">
                    <div>
                        <span class="text-muted text-uppercase font-weight-bold small">Aksi Eksekusi Distribusi</span>
                        <p class="small text-secondary mt-1 mb-2">
                            Kalkulasikan langsung persentase 80%, 13%, 5%, 1.5%, 0.5% dan masukkan otomatis ke Saldo Utama BKU Ranting, MWC, dan PC.
                        </p>
                    </div>

                    <div>
                        @php
                            $user = Auth::user();
                            $canProcess = \Illuminate\Support\Facades\Gate::allows('proses-hasil-koin');
                        @endphp

                        @if(!$canProcess)
                            <div class="alert alert-light border text-center p-3 mb-0 shadow-sm">
                                <span class="badge badge-info px-2 py-1 mb-2 font-weight-bold"><i class="fas fa-eye mr-1"></i> Mode Tinjauan (Read-Only)</span>
                                <p class="small text-muted mb-0">
                                    Rincian pembagian koin dapat ditinjau oleh Ranting. Eksekusi pencatatan saldo BKU dilakukan oleh <strong>Pengurus Cabang (PC)</strong> atau <strong>Pengurus MWC</strong>.
                                </p>
                            </div>
                        @elseif($currentPenthong && $totalKoinBruto > 0)
                            <button type="button" class="btn btn-success btn-block btn-lg shadow-sm font-weight-bold" data-toggle="modal" data-target="#modalProsesHasilKoin">
                                <i class="fas fa-share-alt mr-2"></i> 
                                {{ $isProcessed ? 'Sinkronisasi / Proses Ulang' : 'Proses & Bagikan Hasil Koin' }}
                            </button>
                            <small class="text-muted d-block text-center mt-2">
                                <i class="fas fa-shield-alt text-success mr-1"></i> Transaksi dijamin aman & atomic (DB Transaction).
                            </small>
                        @else
                            <button type="button" class="btn btn-secondary btn-block btn-lg" disabled>
                                <i class="fas fa-ban mr-2"></i> Tidak Ada Koin untuk Diproses
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Kartu Simulasi Pembagian Tingkatan (Direct Split Logic 80% - 13% - 5% - 1.5% - 0.5%) --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white border-bottom">
            <div class="d-flex justify-content-between align-items-center">
                <h5 class="card-title font-weight-bold mb-0 text-dark">
                    <i class="fas fa-chart-pie text-info mr-2"></i> Simulasi Direct Split Tingkatan (100% Koin Bruto)
                </h5>
                <span class="badge badge-pill badge-primary px-3 py-1 font-weight-bold">Total: 100% Pas</span>
            </div>
        </div>
        <div class="card-body">
            <div class="row text-center">
                {{-- Ranting 80% --}}
                <div class="col-lg-4 col-md-4 col-12 mb-3">
                    <div class="p-3 rounded border border-success bg-light h-100">
                        <span class="badge badge-success px-2 py-1 mb-2 font-weight-bold">RANTING &bull; 80%</span>
                        <h4 class="font-weight-bold text-success mb-1">
                            Rp {{ number_format($summary['ranting_total'], 0, ',', '.') }}
                        </h4>
                        <p class="text-muted small mb-0 font-weight-bold">
                            <i class="fas fa-wallet mr-1"></i> Saldo Utama BKU Ranting
                        </p>
                        <small class="text-secondary d-block mt-1">Dikelola mandiri untuk program & pentasyarufan desa</small>
                    </div>
                </div>

                {{-- MWC 13% --}}
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="p-3 rounded border border-primary bg-light h-100">
                        <span class="badge badge-primary px-2 py-1 mb-2 font-weight-bold">MWC &bull; 13%</span>
                        <h5 class="font-weight-bold text-primary mb-1">
                            Rp {{ number_format($summary['mwc_total'], 0, ',', '.') }}
                        </h5>
                        <p class="text-muted small mb-0 font-weight-bold">
                            <i class="fas fa-landmark mr-1"></i> Saldo Utama MWC
                        </p>
                        <small class="text-secondary d-block mt-1">Kecamatan</small>
                    </div>
                </div>

                {{-- PC 5% --}}
                <div class="col-lg-2 col-md-4 col-6 mb-3">
                    <div class="p-3 rounded border border-info bg-light h-100">
                        <span class="badge badge-info px-2 py-1 mb-2 font-weight-bold">PC &bull; 5%</span>
                        <h5 class="font-weight-bold text-info mb-1">
                            Rp {{ number_format($summary['pc_total'], 0, ',', '.') }}
                        </h5>
                        <p class="text-muted small mb-0 font-weight-bold">
                            <i class="fas fa-city mr-1"></i> Saldo Utama PC
                        </p>
                        <small class="text-secondary d-block mt-1">Kabupaten Lamongan</small>
                    </div>
                </div>

                {{-- PW 1.5% --}}
                <div class="col-lg-2 col-md-6 col-6 mb-3">
                    <div class="p-3 rounded border border-warning bg-light h-100">
                        <span class="badge badge-warning text-dark px-2 py-1 mb-2 font-weight-bold">PW &bull; 1.5%</span>
                        <h5 class="font-weight-bold text-dark mb-1">
                            Rp {{ number_format($summary['pw_total'], 0, ',', '.') }}
                        </h5>
                        <p class="text-muted small mb-0 font-weight-bold">
                            <i class="fas fa-map-marker-alt mr-1"></i> Hak PW
                        </p>
                        <small class="text-secondary d-block mt-1">Jawa Timur</small>
                    </div>
                </div>

                {{-- PB 0.5% --}}
                <div class="col-lg-2 col-md-6 col-6 mb-3">
                    <div class="p-3 rounded border border-secondary bg-light h-100">
                        <span class="badge badge-secondary px-2 py-1 mb-2 font-weight-bold">PB &bull; 0.5%</span>
                        <h5 class="font-weight-bold text-secondary mb-1">
                            Rp {{ number_format($summary['pb_total'], 0, ',', '.') }}
                        </h5>
                        <p class="text-muted small mb-0 font-weight-bold">
                            <i class="fas fa-globe mr-1"></i> Hak PBNU
                        </p>
                        <small class="text-secondary d-block mt-1">Pusat</small>
                    </div>
                </div>
            </div>

            {{-- Progress Bar Visualisasi Proporsi 100% --}}
            <div class="progress mt-2" style="height: 18px;">
                <div class="progress-bar bg-success" role="progressbar" style="width: 80%" title="Ranting 80%">80% Ranting</div>
                <div class="progress-bar bg-primary" role="progressbar" style="width: 13%" title="MWC 13%">13% MWC</div>
                <div class="progress-bar bg-info" role="progressbar" style="width: 5%" title="PC 5%">5% PC</div>
                <div class="progress-bar bg-warning" role="progressbar" style="width: 1.5%" title="PW 1.5%"></div>
                <div class="progress-bar bg-secondary" role="progressbar" style="width: 0.5%" title="PB 0.5%"></div>
            </div>
            <div class="d-flex justify-content-between small text-muted mt-1">
                <span>Ranting Desa (80%)</span>
                <span>MWC (13%) &bull; PC (5%) &bull; PW (1.5%) &bull; PB (0.5%)</span>
            </div>
        </div>
    </div>

    {{-- Tabel Rincian Simulasi Hasil Koin per Ranting --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center">
            <h5 class="card-title font-weight-bold mb-0 text-dark">
                <i class="fas fa-table mr-2 text-primary"></i> Rincian Simulasi Pembagian per Ranting
            </h5>
            <span class="text-muted small">Periode: <strong>{{ $currentPenthong?->nama_periode ?? '-' }}</strong></span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="bg-light text-dark text-nowrap">
                    <tr>
                        <th style="width: 50px;">No</th>
                        <th>Ranting NU</th>
                        <th>MWC</th>
                        <th class="text-center">Setoran</th>
                        <th class="text-right bg-light font-weight-bold">Koin Bruto (100%)</th>
                        <th class="text-right text-success font-weight-bold">Ranting (80%)</th>
                        <th class="text-right text-primary font-weight-bold">MWC (13%)</th>
                        <th class="text-right text-info font-weight-bold">PC (5%)</th>
                        <th class="text-right text-warning font-weight-bold">PW (1.5%)</th>
                        <th class="text-right text-secondary font-weight-bold">PB (0.5%)</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($rantingBreakdown as $index => $row)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                <strong>{{ $row['ranting_name'] }}</strong>
                                @if(!empty($row['ranting_code']))
                                    <span class="badge badge-light border ml-1">{{ $row['ranting_code'] }}</span>
                                @endif
                            </td>
                            <td>{{ $row['mwc_name'] }}</td>
                            <td class="text-center">
                                <span class="badge badge-secondary">{{ $row['total_transaksi'] }} TRX</span>
                            </td>
                            <td class="text-right font-weight-bold text-dark">
                                Rp {{ number_format($row['gross_amount'], 0, ',', '.') }}
                            </td>
                            <td class="text-right font-weight-bold text-success">
                                Rp {{ number_format($row['ranting_amount'], 0, ',', '.') }}
                            </td>
                            <td class="text-right font-weight-bold text-primary">
                                Rp {{ number_format($row['mwc_amount'], 0, ',', '.') }}
                            </td>
                            <td class="text-right font-weight-bold text-info">
                                Rp {{ number_format($row['pc_amount'], 0, ',', '.') }}
                            </td>
                            <td class="text-right font-weight-bold text-warning">
                                Rp {{ number_format($row['pw_amount'], 0, ',', '.') }}
                            </td>
                            <td class="text-right font-weight-bold text-secondary">
                                Rp {{ number_format($row['pb_amount'], 0, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="10" class="text-center py-4 text-muted">
                                <i class="fas fa-inbox fa-3x mb-2 d-block text-secondary"></i>
                                Belum ada transaksi setoran koin yang terkonfirmasi pada periode penthong ini.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($rantingBreakdown->isNotEmpty())
                    <tfoot class="bg-light font-weight-bold border-top-2">
                        <tr>
                            <td colspan="4" class="text-center text-uppercase">Total Keseluruhan</td>
                            <td class="text-right text-dark">
                                Rp {{ number_format($summary['gross_total'], 0, ',', '.') }}
                            </td>
                            <td class="text-right text-success">
                                Rp {{ number_format($summary['ranting_total'], 0, ',', '.') }}
                            </td>
                            <td class="text-right text-primary">
                                Rp {{ number_format($summary['mwc_total'], 0, ',', '.') }}
                            </td>
                            <td class="text-right text-info">
                                Rp {{ number_format($summary['pc_total'], 0, ',', '.') }}
                            </td>
                            <td class="text-right text-warning">
                                Rp {{ number_format($summary['pw_total'], 0, ',', '.') }}
                            </td>
                            <td class="text-right text-secondary">
                                Rp {{ number_format($summary['pb_total'], 0, ',', '.') }}
                            </td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
</div>

{{-- Modal Konfirmasi Eksekusi "Proses & Bagikan Hasil Koin" --}}
@if($currentPenthong)
<div class="modal fade" id="modalProsesHasilKoin" tabindex="-1" role="dialog" aria-labelledby="modalProsesHasilKoinLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow">
            <form action="{{ route('pentasyarufan.hasil-koin.proses') }}" method="POST">
                @csrf
                <input type="hidden" name="penthong_id" value="{{ $currentPenthong->id }}">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title font-weight-bold" id="modalProsesHasilKoinLabel">
                        <i class="fas fa-coins mr-2"></i> Konfirmasi Proses & Bagikan Hasil Koin
                    </h5>
                    <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="mb-3">
                        Anda akan memproses dan membagikan hasil koin pada periode:
                        <br>
                        <strong class="text-dark font-size-lg">{{ $currentPenthong->nama_periode }} ({{ $currentPenthong->bulan_nama }} {{ $currentPenthong->tahun }})</strong>
                        @if(Auth::user()?->isMwc())
                            <span class="badge badge-primary d-block mt-2 py-2 text-left">
                                <i class="fas fa-map-marker-alt mr-1"></i> Data Scoping: <strong>MWC {{ Auth::user()->mwc?->name ?? 'Kecamatan Anda' }}</strong>
                            </span>
                        @endif
                    </p>

                    <div class="p-3 bg-light rounded border mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Koin Bruto:</span>
                            <strong class="text-dark">Rp {{ number_format($totalKoinBruto, 0, ',', '.') }}</strong>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-1 text-success">
                            <span>Bagian Ranting (80%):</span>
                            <strong>Rp {{ number_format($summary['ranting_total'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1 text-primary">
                            <span>Bagian MWC (13%):</span>
                            <strong>Rp {{ number_format($summary['mwc_total'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1 text-info">
                            <span>Bagian PC (5%):</span>
                            <strong>Rp {{ number_format($summary['pc_total'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1 text-warning">
                            <span>Bagian PW (1.5%):</span>
                            <strong>Rp {{ number_format($summary['pw_total'], 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between text-secondary">
                            <span>Bagian PB (0.5%):</span>
                            <strong>Rp {{ number_format($summary['pb_total'], 0, ',', '.') }}</strong>
                        </div>
                    </div>

                    <div class="alert alert-warning small mb-0">
                        <i class="fas fa-exclamation-triangle mr-1"></i>
                        <strong>Catatan Sistem:</strong> Uang bagian 80% akan otomatis masuk ke BKU Ranting sebagai Saldo Utama, bagian 13% otomatis masuk ke BKU MWC, dan bagian 5% otomatis masuk ke BKU PC. Eksekusi ini aman dan atomic (DB Transaction).
                        @if(Auth::user()?->isMwc())
                            <br><strong>Isolasi Wilayah:</strong> Proses ini hanya akan memproses Ranting di bawah naungan MWC {{ Auth::user()->mwc?->name }}. Wilayah MWC lain dijamin tidak akan terpengaruh.
                        @endif
                    </div>
                </div>

                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success font-weight-bold">
                        <i class="fas fa-check-circle mr-1"></i> Ya, Proses & Bagikan Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endif
@endsection
