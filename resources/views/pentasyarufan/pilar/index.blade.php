@extends('layouts.adminlte')

@section('title', '5 Pilar Program & Pagu Anggaran Berbasis Hirarki')
@section('page_title', '5 Pilar Program & Pagu Anggaran')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item">Pentasyarufan</li>
    <li class="breadcrumb-item active">5 Pilar & Pagu Hirarki</li>
@endsection

@section('content')
<!-- Filter Parameter Hirarki & Periode -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header bg-white py-2">
        <h3 class="card-title font-weight-bold text-dark mb-0 pt-1">
            <i class="fas fa-filter text-success mr-2"></i>Filter Pagu & Pemasukan Berdasarkan Hirarki Organisasi
        </h3>
        <span class="badge badge-success px-3 py-1 font-weight-bold float-right">
            <i class="fas fa-sitemap mr-1"></i> {{ $tingkatLabel }}
        </span>
    </div>
    <div class="card-body bg-light py-3">
        <form action="{{ route('pilar-program.index') }}" method="GET" class="row align-items-end">
            <!-- Filter Periode Penthong -->
            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Periode Penthong</label>
                <select name="penthong_id" class="form-control form-control-sm font-weight-bold">
                    <option value="">-- Semua Periode (Akumulasi) --</option>
                    @foreach($penthongs as $pt)
                        <option value="{{ $pt->id }}" {{ ($selectedPenthongId == $pt->id) ? 'selected' : '' }}>
                            [{{ strtoupper($pt->status) }}] {{ $pt->nama_periode }} ({{ $pt->tahun }})
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Tingkat Hirarki -->
            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Tingkat Hirarki Organisasi</label>
                <select name="tingkat" class="form-control form-control-sm font-weight-bold" {{ !$userCanFilterHierarchy ? 'disabled' : '' }}>
                    <option value="all" {{ ($selectedTingkat == 'all') ? 'selected' : '' }}>Semua Tingkat (Konsolidasi Cabang)</option>
                    <option value="pc" {{ ($selectedTingkat == 'pc') ? 'selected' : '' }}>Tingkat PC (Hak Cabang - {{ $pcPct }}% Netto)</option>
                    <option value="mwc" {{ ($selectedTingkat == 'mwc') ? 'selected' : '' }}>Tingkat MWC (Hak Kecamatan - {{ $mwcPct }}% Netto)</option>
                    <option value="ranting" {{ ($selectedTingkat == 'ranting') ? 'selected' : '' }}>Tingkat Ranting (Hak Desa - {{ $rantingPct }}% Netto)</option>
                </select>
            </div>

            <!-- Filter MWC -->
            <div class="col-md-2 form-group mb-2">
                <label class="small font-weight-bold text-muted">Wilayah MWC NU</label>
                <select name="mwc_id" id="filterMwc" class="form-control form-control-sm" {{ (auth()->user() && auth()->user()->isRanting()) ? 'disabled' : '' }}>
                    <option value="">-- Semua MWC --</option>
                    @foreach($mwcs as $mwc)
                        <option value="{{ $mwc->id }}" {{ ($selectedMwcId == $mwc->id) ? 'selected' : '' }}>
                            [{{ $mwc->code }}] {{ $mwc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <!-- Filter Ranting -->
            @if($rantings->isNotEmpty())
            <div class="col-md-2 form-group mb-2">
                <label class="small font-weight-bold text-muted">Ranting Desa</label>
                <select name="ranting_id" class="form-control form-control-sm">
                    <option value="">-- Semua Ranting --</option>
                    @foreach($rantings as $rt)
                        <option value="{{ $rt->id }}" {{ ($selectedRantingId == $rt->id) ? 'selected' : '' }}>
                            {{ $rt->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            @endif

            <div class="col-md form-group mb-2 d-flex">
                <button type="submit" class="btn btn-sm btn-success font-weight-bold mr-1 flex-grow-1">
                    <i class="fas fa-search mr-1"></i> Terapkan
                </button>
                @if(request()->anyFilled(['penthong_id', 'tingkat', 'mwc_id', 'ranting_id']))
                    <a href="{{ route('pilar-program.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset Filter">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Ringkasan Saldo & Pagu Akuntansi Sesuai Hirarki -->
<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-dark font-weight-bold">Rp {{ number_format($totalKoinBruto, 0, ',', '.') }}</h3>
                <p class="text-muted mb-0 font-weight-bold">Total Koin Kotor (Bruto)</p>
            </div>
            <div class="icon text-secondary" style="opacity: 0.25;">
                <i class="fas fa-coins"></i>
            </div>
            <div class="small-box-footer bg-light text-muted border-top py-1 small">
                <i class="fas fa-arrow-down mr-1 text-success"></i> Setoran Terkonfirmasi
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-danger font-weight-bold">- Rp {{ number_format($potonganOperasional, 0, ',', '.') }}</h3>
                <p class="text-muted mb-0 font-weight-bold">Hak Amil / Ops ({{ $operasionalPct }}%)</p>
            </div>
            <div class="icon text-danger" style="opacity: 0.25;">
                <i class="fas fa-percentage"></i>
            </div>
            <div class="small-box-footer bg-light text-muted border-top py-1 small">
                Biaya Operasional Sesuai Aturan
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-success shadow-sm">
            <div class="inner">
                <h3 class="font-weight-bold">Rp {{ number_format($danaBersihNetto, 0, ',', '.') }}</h3>
                <p class="mb-0">Dana Bersih (Netto Siap Tasyaruf)</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-holding-usd"></i>
            </div>
            <div class="small-box-footer py-1 small text-white" style="background: rgba(0,0,0,0.1);">
                Total 100% Hak Seluruh Lembaga
            </div>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box {{ $sisaTotalPagu < 0 ? 'bg-danger' : 'bg-primary' }} shadow-sm">
            <div class="inner">
                <h3 class="font-weight-bold">Rp {{ number_format($sisaTotalPagu, 0, ',', '.') }}</h3>
                <p class="mb-0">Sisa Pagu ({{ strtoupper($selectedTingkat) }})</p>
            </div>
            <div class="icon">
                <i class="fas fa-wallet"></i>
            </div>
            <div class="small-box-footer py-1 small text-white" style="background: rgba(0,0,0,0.1);">
                Serapan: {{ $overallPersentaseSerapan }}% dari Pagu Rp {{ number_format($basisPaguPilar, 0, ',', '.') }}
            </div>
        </div>
    </div>
</div>

<!-- Matriks Pembagian Tasharruf Berdasarkan Hirarki Organisasi -->
<div class="card card-outline card-info shadow-sm mb-4">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-layer-group text-info mr-2"></i>Matriks Porsi Dana Masuk Berdasarkan Hirarki Organisasi (Bagi Hasil Resmi)
        </h3>
        <span class="badge badge-light border">SK Distribusi KOIN NU Lamongan</span>
    </div>
    <div class="card-body p-3">
        <div class="row">
            <!-- 1. Porsi Ranting (80%) -->
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="p-3 rounded border h-100 {{ $selectedTingkat === 'ranting' ? 'bg-light border-success' : 'bg-white' }}" style="{{ $selectedTingkat === 'ranting' ? 'border-width: 2px !important;' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-success px-2 py-1 font-weight-bold">TINGKAT RANTING (DESA)</span>
                        <strong class="text-success font-weight-bold">80% Netto</strong>
                    </div>
                    <div class="small text-muted">Hak Alokasi Dana Bersih:</div>
                    <h4 class="font-weight-bold text-dark mb-2">Rp {{ number_format($porsiRanting, 0, ',', '.') }}</h4>
                    <div class="d-flex justify-content-between small text-muted border-top pt-2">
                        <span>Terealisasi:</span>
                        <span class="font-weight-bold text-danger">Rp {{ number_format($realisasiRanting, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>Sisa Saldo Ranting:</span>
                        <span class="font-weight-bold {{ ($porsiRanting - $realisasiRanting) < 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($porsiRanting - $realisasiRanting, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 2. Porsi MWC ({{ $mwcPct }}%) -->
            <div class="col-md-4 mb-3 mb-md-0">
                <div class="p-3 rounded border h-100 {{ $selectedTingkat === 'mwc' ? 'bg-light border-primary' : 'bg-white' }}" style="{{ $selectedTingkat === 'mwc' ? 'border-width: 2px !important;' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-primary px-2 py-1 font-weight-bold">TINGKAT MWC (KECAMATAN)</span>
                        <strong class="text-primary font-weight-bold" data-toggle="tooltip" title="Nisbah Pembagian MWC: {{ $mwcPct }}% dari Dana Bersih">{{ $mwcPct }}% Netto</strong>
                    </div>
                    <div class="small text-muted">Hak Alokasi Dana Bersih:</div>
                    <h4 class="font-weight-bold text-dark mb-2">Rp {{ number_format($porsiMwc, 0, ',', '.') }}</h4>
                    <div class="d-flex justify-content-between small text-muted border-top pt-2">
                        <span>Terealisasi:</span>
                        <span class="font-weight-bold text-danger">Rp {{ number_format($realisasiMwc, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>Sisa Saldo MWC:</span>
                        <span class="font-weight-bold {{ ($porsiMwc - $realisasiMwc) < 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($porsiMwc - $realisasiMwc, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>

            <!-- 3. Porsi PC Cabang ({{ $pcPct }}%) -->
            <div class="col-md-4">
                <div class="p-3 rounded border h-100 {{ $selectedTingkat === 'pc' ? 'bg-light border-info' : 'bg-white' }}" style="{{ $selectedTingkat === 'pc' ? 'border-width: 2px !important;' : '' }}">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="badge badge-info px-2 py-1 font-weight-bold">TINGKAT PC (CABANG)</span>
                        <strong class="text-info font-weight-bold" data-toggle="tooltip" title="Nisbah Pembagian PC: {{ $pcPct }}% dari Dana Bersih">{{ $pcPct }}% Netto</strong>
                    </div>
                    <div class="small text-muted">Hak Alokasi Dana Bersih:</div>
                    <h4 class="font-weight-bold text-dark mb-2">Rp {{ number_format($porsiPc, 0, ',', '.') }}</h4>
                    <div class="d-flex justify-content-between small text-muted border-top pt-2">
                        <span>Terealisasi:</span>
                        <span class="font-weight-bold text-danger">Rp {{ number_format($realisasiPc, 0, ',', '.') }}</span>
                    </div>
                    <div class="d-flex justify-content-between small text-muted mt-1">
                        <span>Sisa Saldo PC:</span>
                        <span class="font-weight-bold {{ ($porsiPc - $realisasiPc) < 0 ? 'text-danger' : 'text-success' }}">
                            Rp {{ number_format($porsiPc - $realisasiPc, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <div class="mt-2 text-muted small text-center">
            <i class="fas fa-info-circle mr-1"></i> Catatan: Sisa {{ $pwPct + $pbPct }}% dialokasikan ke porsi Wilayah PW ({{ $pwPct }}%) & Pengurus Besar PB ({{ $pbPct }}%) sebesar Rp {{ number_format($porsiPwPb, 0, ',', '.') }}.
        </div>
    </div>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<!-- Kalkulator Preview & Simulasi Pembagian Hasil Tasharruf (Nisbah Baru: MWC 13%, PC 5%) -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-calculator text-success mr-2"></i>Kalkulator Preview & Simulasi Alokasi Tasharruf (MWC {{ $mwcPct }}%, PC {{ $pcPct }}%)
        </h3>
        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i> Skema Baru Terverifikasi</span>
    </div>
    <div class="card-body">
        <div class="row align-items-center mb-3">
            <div class="col-md-6 mb-2 mb-md-0">
                <label class="font-weight-bold small text-muted mb-1">Simulasi Nominal Perolehan Bruto KOIN NU (Rp):</label>
                <div class="input-group">
                    <div class="input-group-prepend">
                        <span class="input-group-text font-weight-bold bg-success text-white">Rp</span>
                    </div>
                    <input type="number" id="previewGrossInput" class="form-control font-weight-bold text-dark" 
                           placeholder="Masukkan nominal bruto, contoh: 10000000" value="10000000" min="0" step="100000">
                </div>
            </div>
            <div class="col-md-6">
                <label class="font-weight-bold small text-muted mb-1">Preset Nominal Cepat:</label>
                <div class="btn-group btn-group-sm w-100" role="group">
                    <button type="button" class="btn btn-outline-secondary font-weight-bold" onclick="setPreviewGross(5000000)">5 Juta</button>
                    <button type="button" class="btn btn-outline-success font-weight-bold" onclick="setPreviewGross(10000000)">10 Juta</button>
                    <button type="button" class="btn btn-outline-secondary font-weight-bold" onclick="setPreviewGross(25000000)">25 Juta</button>
                    <button type="button" class="btn btn-outline-secondary font-weight-bold" onclick="setPreviewGross(50000000)">50 Juta</button>
                    <button type="button" class="btn btn-outline-secondary font-weight-bold" onclick="setPreviewGross(100000000)">100 Juta</button>
                </div>
            </div>
        </div>

        <!-- Visual Mini Cards Output Preview -->
        <div class="row">
            <div class="col-lg-2 col-md-4 col-6 mb-2">
                <div class="p-2 rounded bg-light border text-center">
                    <small class="text-muted font-weight-bold d-block">Operasional ({{ $operasionalPct }}%)</small>
                    <strong class="text-danger" id="prevDispOps">Rp 0</strong>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-2">
                <div class="p-2 rounded bg-light border text-center border-success">
                    <small class="text-muted font-weight-bold d-block">Dana Netto ({{ 100 - $operasionalPct }}%)</small>
                    <strong class="text-success" id="prevDispNetto">Rp 0</strong>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-2">
                <div class="p-2 rounded bg-light border text-center">
                    <small class="text-muted font-weight-bold d-block">Ranting ({{ $rantingPct }}%)</small>
                    <strong class="text-success" id="prevDispRanting">Rp 0</strong>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-2">
                <div class="p-2 rounded bg-light border text-center border-primary">
                    <small class="text-muted font-weight-bold d-block">MWC ({{ $mwcPct }}% Netto)</small>
                    <strong class="text-primary" id="prevDispMwc">Rp 0</strong>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-2">
                <div class="p-2 rounded bg-light border text-center border-info">
                    <small class="text-muted font-weight-bold d-block">PC Cabang ({{ $pcPct }}% Netto)</small>
                    <strong class="text-info" id="prevDispPc">Rp 0</strong>
                </div>
            </div>
            <div class="col-lg-2 col-md-4 col-6 mb-2">
                <div class="p-2 rounded bg-light border text-center">
                    <small class="text-muted font-weight-bold d-block">PW ({{ $pwPct }}%) + PB ({{ $pbPct }}%)</small>
                    <strong class="text-secondary" id="prevDispPwPb">Rp 0</strong>
                </div>
            </div>
        </div>
        <div class="small text-muted mt-1">
            <i class="fas fa-info-circle text-info mr-1"></i>
            Konstanta kalkulator pembagian: Pengali Operasional <strong>{{ $operasionalPct / 100 }}</strong> | Pengali Ranting <strong>{{ $rantingPct / 100 }}</strong> | Pengali MWC <strong>{{ $mwcPct / 100 }} (0.13)</strong> | Pengali PC <strong>{{ $pcPct / 100 }} (0.05)</strong>.
        </div>
    </div>
</div>

<!-- Chart Serapan Anggaran 5 Pilar Program (Target vs Realisasi) -->
<div class="row">
    <div class="col-lg-8">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-chart-bar text-success mr-2"></i>Komparasi Pagu Anggaran vs Realisasi Aktual (5 Bidang Pilar)
                </h3>
                <span class="badge badge-light border">Basis: {{ $tingkatLabel }}</span>
            </div>
            <div class="card-body">
                <canvas id="serapanBarChart" style="min-height: 280px; height: 280px; max-height: 280px; max-width: 100%;"></canvas>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-chart-pie text-info mr-2"></i>Komposisi Alokasi (Target)
                </h3>
            </div>
            <div class="card-body">
                <canvas id="alokasiPieChart" style="min-height: 240px; height: 240px; max-height: 240px; max-width: 100%;"></canvas>
                <div class="text-center mt-2 small text-muted">
                    Total target alokasi harus genap <strong>100%</strong>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabel Master Data 5 Pilar Program & Seting Persentase -->
<div class="card card-outline card-primary shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-shapes text-primary mr-2"></i>Status Pagu & Serapan 5 Pilar Program NU Care (Basis: {{ $tingkatLabel }})
        </h3>
        @if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->isBendahara()))
            <button type="button" class="btn btn-sm btn-outline-primary font-weight-bold" data-toggle="modal" data-target="#modalEditPersentase">
                <i class="fas fa-sliders-h mr-1"></i> Sesuaikan Persentase Alokasi
            </button>
        @endif
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Bidang / Pilar Program</th>
                    <th class="text-center" style="width: 110px;">Target Alokasi</th>
                    <th>Pagu Anggaran</th>
                    <th>Realisasi Penyaluran</th>
                    <th>Sisa Pagu Anggaran</th>
                    <th style="width: 160px;">Serapan (%)</th>
                    <th class="text-center" style="width: 130px;">Status</th>
                </tr>
            </thead>
            <tbody>
                @php $sumPersen = 0; $sumPagu = 0; $sumRealisasi = 0; @endphp
                @foreach($pilars as $index => $item)
                    @php
                        $sumPersen += $item['target_persentase'];
                        $sumPagu += $item['pagu_nominal'];
                        $sumRealisasi += $item['realisasi'];
                    @endphp
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>
                            <div class="font-weight-bold text-dark">
                                <i class="{{ $item['icon'] }} mr-1" style="color: {{ $item['warna'] }};"></i>
                                {{ $item['nama_pilar'] }}
                            </div>
                            <small class="text-muted">{{ $item['deskripsi'] }}</small>
                        </td>
                        <td class="text-center">
                            <span class="badge badge-primary px-2 py-1 font-weight-bold" style="font-size: 0.95rem;">
                                {{ number_format($item['target_persentase'], 1) }}%
                            </span>
                        </td>
                        <td>
                            <strong>Rp {{ number_format($item['pagu_nominal'], 0, ',', '.') }}</strong>
                        </td>
                        <td>
                            <span class="text-danger font-weight-bold">Rp {{ number_format($item['realisasi'], 0, ',', '.') }}</span>
                            <br><small class="text-muted">{{ $item['count_penyaluran'] }}x penyaluran</small>
                        </td>
                        <td>
                            @if($item['sisa_pagu'] < 0)
                                <strong class="text-danger"><i class="fas fa-exclamation-triangle mr-1"></i>Defisit: Rp {{ number_format(abs($item['sisa_pagu']), 0, ',', '.') }}</strong>
                            @else
                                <strong class="text-success">Rp {{ number_format($item['sisa_pagu'], 0, ',', '.') }}</strong>
                            @endif
                        </td>
                        <td>
                            <div class="d-flex align-items-center">
                                <div class="progress flex-grow-1 mr-2" style="height: 10px;">
                                    <div class="progress-bar {{ $item['serapan'] > 100 ? 'bg-danger' : ($item['serapan'] < 30 ? 'bg-warning' : 'bg-success') }}" 
                                         role="progressbar" 
                                         style="width: {{ min(100, $item['serapan']) }}%;" 
                                         aria-valuenow="{{ $item['serapan'] }}" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100"></div>
                                </div>
                                <small class="font-weight-bold {{ $item['serapan'] > 100 ? 'text-danger' : 'text-dark' }}">
                                    {{ $item['serapan'] }}%
                                </small>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="badge {{ $item['status']['badge'] }} px-2 py-1">
                                {{ $item['status']['label'] }}
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="2" class="text-right">TOTAL PAGU & REALISASI ({{ strtoupper($selectedTingkat) }}):</td>
                    <td class="text-center">
                        <span class="badge badge-success px-2 py-1">{{ number_format($sumPersen, 1) }}%</span>
                    </td>
                    <td>Rp {{ number_format($sumPagu, 0, ',', '.') }}</td>
                    <td class="text-danger">Rp {{ number_format($sumRealisasi, 0, ',', '.') }}</td>
                    <td class="text-success">Rp {{ number_format($sumPagu - $sumRealisasi, 0, ',', '.') }}</td>
                    <td>{{ $sumPagu > 0 ? round(($sumRealisasi / $sumPagu) * 100, 2) : 0 }}%</td>
                    <td class="text-center">-</td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>

<!-- Modal Setting Alokasi Persentase -->
@if(auth()->check() && (auth()->user()->isSuperAdmin() || auth()->user()->isBendahara()))
<div class="modal fade" id="modalEditPersentase" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('pilar-program.update') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-sliders-h mr-1"></i> Atur Persentase Pagu 5 Pilar Program
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 small mb-3">
                    <i class="fas fa-info-circle mr-1"></i> Total penjumlahan persentase kelima pilar wajib tepat <strong>100.0%</strong>.
                </div>

                @foreach($pilarsModel as $p)
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-dark d-flex justify-content-between">
                            <span><i class="{{ $p->icon }} mr-1" style="color: {{ $p->warna }};"></i> {{ $p->nama_pilar }}</span>
                            <span class="text-primary font-weight-bold val-display" id="val_{{ $p->id }}">{{ $p->target_persentase }}%</span>
                        </label>
                        <div class="input-group">
                            <input type="number" 
                                   name="percentages[{{ $p->id }}]" 
                                   value="{{ $p->target_persentase }}" 
                                   step="0.5" 
                                   min="0" 
                                   max="100" 
                                   class="form-control persen-input" 
                                   required 
                                   data-id="{{ $p->id }}">
                            <div class="input-group-append">
                                <span class="input-group-text font-weight-bold">%</span>
                            </div>
                        </div>
                    </div>
                @endforeach

                <div class="p-2 rounded bg-light border d-flex justify-content-between align-items-center mt-3">
                    <span class="font-weight-bold">Total Kalkulasi:</span>
                    <span id="totalPersenDisplay" class="badge badge-success px-3 py-2 font-weight-bold" style="font-size: 1rem;">100%</span>
                </div>
                <div id="persenError" class="text-danger small font-weight-bold mt-1 d-none">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Total alokasi harus 100%. Tidak boleh lebih atau kurang!
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" id="btnSubmitPersen" class="btn btn-success font-weight-bold">
                    <i class="fas fa-save mr-1"></i> Simpan Alokasi Pagu
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Fetch Chart Data Terintegrasi dengan Filter Query String di URL
    const chartUrl = '{{ route('pilar-program.chart-data') }}' + window.location.search;

    fetch(chartUrl)
        .then(response => response.json())
        .then(data => {
            if (!data.success) return;

            const ctxBar = document.getElementById('serapanBarChart').getContext('2d');
            new Chart(ctxBar, {
                type: 'bar',
                data: {
                    labels: data.chart.labels.map(l => l.replace(/NU Care\s*/i, '')),
                    datasets: [
                        {
                            label: 'Pagu Anggaran Target (Rp)',
                            data: data.chart.target_nominal,
                            backgroundColor: 'rgba(10, 107, 56, 0.45)',
                            borderColor: '#0a6b38',
                            borderWidth: 2,
                            borderRadius: 4
                        },
                        {
                            label: 'Realisasi Pentasyarufan Aktual (Rp)',
                            data: data.chart.realisasi_nominal,
                            backgroundColor: 'rgba(220, 53, 69, 0.85)',
                            borderColor: '#dc3545',
                            borderWidth: 2,
                            borderRadius: 4
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
                                callback: function(value) {
                                    if (value >= 1000000) {
                                        return 'Rp ' + (value / 1000000).toFixed(1) + ' Jt';
                                    }
                                    return 'Rp ' + new Intl.NumberFormat('id-ID').format(value);
                                }
                            }
                        }
                    },
                    plugins: {
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.dataset.label + ': Rp ' + new Intl.NumberFormat('id-ID').format(context.raw);
                                }
                            }
                        }
                    }
                }
            });

            // 2. Pie Chart Komposisi Alokasi
            const ctxPie = document.getElementById('alokasiPieChart').getContext('2d');
            new Chart(ctxPie, {
                type: 'doughnut',
                data: {
                    labels: data.chart.labels.map(l => l.replace(/NU Care\s*/i, '')),
                    datasets: [{
                        data: data.chart.target_persentase,
                        backgroundColor: data.chart.colors,
                        borderWidth: 2
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom',
                            labels: { boxWidth: 12, font: { size: 10 } }
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    return context.label + ': ' + context.raw + '%';
                                }
                            }
                        }
                    }
                }
            });
        });

    // Validasi Real-time Input Persentase Modal
    const inputs = document.querySelectorAll('.persen-input');
    const displayTotal = document.getElementById('totalPersenDisplay');
    const errText = document.getElementById('persenError');
    const btnSubmit = document.getElementById('btnSubmitPersen');

    function calculateTotal() {
        let total = 0;
        inputs.forEach(input => {
            const val = parseFloat(input.value) || 0;
            total += val;
            const display = document.getElementById('val_' + input.dataset.id);
            if (display) display.textContent = val.toFixed(1) + '%';
        });

        if (displayTotal) {
            displayTotal.textContent = total.toFixed(1) + '%';
            if (Math.abs(total - 100.0) < 0.01) {
                displayTotal.className = 'badge badge-success px-3 py-2 font-weight-bold';
                errText.classList.add('d-none');
                btnSubmit.disabled = false;
            } else {
                displayTotal.className = 'badge badge-danger px-3 py-2 font-weight-bold';
                errText.classList.remove('d-none');
                btnSubmit.disabled = true;
            }
        }
    }

    inputs.forEach(input => input.addEventListener('input', calculateTotal));

    // 3. Kalkulator Preview Simulasi Pembagian Hasil Tasharruf (MWC 13% PC 5%)
    const grossInput = document.getElementById('previewGrossInput');
    const constMwcPct = {{ $mwcPct }} / 100;         // 0.13
    const constPcPct  = {{ $pcPct }} / 100;          // 0.05
    const constOpsPct = {{ $operasionalPct }} / 100; // 0.20
    const constRantingPct = {{ $rantingPct }} / 100; // 0.80
    const constPwPct = {{ $pwPct }} / 100;           // 0.015
    const constPbPct = {{ $pbPct }} / 100;           // 0.005

    function updatePreviewSharing() {
        if (!grossInput) return;
        const gross = parseFloat(grossInput.value) || 0;
        const ops = Math.round(gross * constOpsPct);
        const net = Math.max(0, gross - ops);
        const ranting = Math.round(net * constRantingPct);
        const mwc = Math.round(net * constMwcPct); // Konstanta 0.13
        const pc  = Math.round(net * constPcPct);  // Konstanta 0.05
        const pw  = Math.round(net * constPwPct);
        const pb  = Math.round(net * constPbPct);

        const formatRp = (num) => 'Rp ' + Number(num || 0).toLocaleString('id-ID');

        const elOps = document.getElementById('prevDispOps');
        const elNetto = document.getElementById('prevDispNetto');
        const elRanting = document.getElementById('prevDispRanting');
        const elMwc = document.getElementById('prevDispMwc');
        const elPc = document.getElementById('prevDispPc');
        const elPwPb = document.getElementById('prevDispPwPb');

        if (elOps) elOps.innerText = formatRp(ops);
        if (elNetto) elNetto.innerText = formatRp(net);
        if (elRanting) elRanting.innerText = formatRp(ranting);
        if (elMwc) elMwc.innerText = formatRp(mwc);
        if (elPc) elPc.innerText = formatRp(pc);
        if (elPwPb) elPwPb.innerText = formatRp(pw + pb);
    }

    window.setPreviewGross = function(val) {
        if (grossInput) {
            grossInput.value = val;
            updatePreviewSharing();
        }
    };

    if (grossInput) {
        grossInput.addEventListener('input', updatePreviewSharing);
        updatePreviewSharing();
    }
});
</script>
@endpush
