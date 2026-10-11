@extends('layouts.adminlte')

@section('title', 'Cairkan Dana Pentasyarufan')
@section('page_title', 'Cairkan Dana Pentasyarufan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pentasyarufan.index') }}">Pentasyarufan</a></li>
    <li class="breadcrumb-item active">Pencairan Dana</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <form action="{{ route('pentasyarufan.store') }}" method="POST" enctype="multipart/form-data" id="pentasyarufanForm">
            @csrf
            
            <div class="card card-outline card-success shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-hand-holding-usd text-success mr-2"></i>Formulir Realisasi Pencairan Dana Pentasyarufan
                    </h3>
                    <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-shield-alt mr-1"></i> Isolasi Saldo Berbasis Periode Penthong
                    </span>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Validasi Pengeluaran Gagal:</h6>
                            <ul class="mb-0 pl-3 small">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <!-- Informasi Alur Akuntansi -->
                    <div class="alert alert-light border-left-success py-2 px-3 small mb-4 text-dark shadow-xs" style="border-left: 4px solid #28a745; background-color: #f8fbf9;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-info-circle text-success fa-2x mr-3"></i>
                            <div>
                                <strong>Ketentuan Business Logic LAZISNU:</strong>
                                <span class="text-muted d-block">
                                    Setiap pencairan dana <strong>terisolasi pada satu Periode Penthong</strong>. 
                                    Total koin bruto dipotong <strong>Hak Amil/Operasional</strong> terlebih dahulu, kemudian dana bersih dialokasikan ke <strong>5 Pilar Program</strong>. Nominal pencairan tidak boleh melebihi Sisa Dompet Pilar pada periode tersebut dan Sisa RAB Proker.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- =============================================================== -->
                    <!-- =============================================================== -->
                    <!-- TAHAP 1: PILIH SUMBER DANA & LINGKUP TINGKATAN WILAYAH          -->
                    <!-- =============================================================== -->
                    <div class="card bg-light border mb-4">
                        <div class="card-header bg-white py-2 d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge badge-dark mr-2" style="font-size: 0.9rem;">Langkah 1</span>
                                <strong class="text-dark"><i class="fas fa-calendar-alt text-primary mr-1"></i> Pilih Sumber Dana & Lingkup Tingkatan Wilayah</strong>
                            </div>
                            <div class="p-1 border rounded bg-white shadow-xs text-left">
                                <small class="text-muted mr-1 font-weight-bold text-uppercase" style="font-size: 0.72rem;">Status Periode:</small>
                                <span id="periodeStatusBadge" class="badge badge-success px-2 py-1 font-weight-bold">
                                    {{ $periodBudget ? strtoupper($periodBudget['status_periode']) : 'PILIH PERIODE' }}
                                </span>
                                <span id="periodeNameDisplay" class="font-weight-bold text-dark ml-1">
                                    {{ $periodBudget ? $periodBudget['nama_periode'] : '' }}
                                </span>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-5 mb-2">
                                    <label class="font-weight-bold small text-dark">
                                        Sumber Dana dari Periode Penthong <span class="text-danger">*</span>
                                    </label>
                                    <select name="penthong_id" id="penthongSelect" class="form-control form-control-lg font-weight-bold" required>
                                        <option value="">-- Pilih Periode Penthong --</option>
                                        @foreach($penthongs as $pt)
                                            <option value="{{ $pt->id }}" 
                                                    {{ (old('penthong_id', $selectedPenthongId) == $pt->id) ? 'selected' : '' }}
                                                    data-nama="{{ $pt->nama_periode }}"
                                                    data-status="{{ $pt->status }}"
                                                    data-tahun="{{ $pt->tahun }}"
                                                    data-bulan="{{ $pt->bulan }}">
                                                [{{ strtoupper($pt->status) }}] {{ $pt->nama_periode }} (Tahun {{ $pt->tahun }})
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted">Pilih periode pengumpulan koin yang dananya ingin dicairkan.</small>
                                </div>

                                <div class="col-md-7 mb-2">
                                    <div class="row">
                                        <!-- Tingkatan Selector -->
                                        <div class="col-md-{{ Auth::user()->isPc() ? '4' : (Auth::user()->isMwc() ? '6' : '12') }} mb-2">
                                            <label class="font-weight-bold small text-dark">
                                                Tingkatan Struktur <span class="text-danger">*</span>
                                            </label>
                                            @if(Auth::user()->isRanting())
                                                <input type="hidden" name="tingkat" id="tingkatSelect" value="ranting">
                                                <input type="hidden" name="ranting_id" id="rantingIdInput" value="{{ Auth::user()->ranting_id }}">
                                                <div class="form-control bg-white font-weight-bold text-info d-flex align-items-center">
                                                    <i class="fas fa-map-marker-alt mr-2"></i> Ranting (Desa)
                                                </div>
                                                <small class="text-muted">{{ Auth::user()->ranting?->name ?? 'Ranting Terdaftar' }}</small>
                                            @elseif(Auth::user()->isMwc())
                                                <input type="hidden" name="mwc_id" id="mwcIdInput" value="{{ Auth::user()->mwc_id }}">
                                                <select name="tingkat" id="tingkatSelect" class="form-control font-weight-bold text-dark">
                                                    <option value="mwc" {{ old('tingkat', $tingkat) === 'mwc' ? 'selected' : '' }}>MWC (Kecamatan)</option>
                                                    <option value="ranting" {{ old('tingkat', $tingkat) === 'ranting' ? 'selected' : '' }}>Ranting (Desa)</option>
                                                </select>
                                                <small class="text-muted">MWC {{ Auth::user()->mwc?->name }}</small>
                                            @else
                                                <select name="tingkat" id="tingkatSelect" class="form-control font-weight-bold text-dark">
                                                    <option value="pc" {{ old('tingkat', $tingkat) === 'pc' ? 'selected' : '' }}>PC (Cabang Lamongan)</option>
                                                    <option value="mwc" {{ old('tingkat', $tingkat) === 'mwc' ? 'selected' : '' }}>MWC (Kecamatan)</option>
                                                    <option value="ranting" {{ old('tingkat', $tingkat) === 'ranting' ? 'selected' : '' }}>Ranting (Desa)</option>
                                                </select>
                                                <small class="text-muted">Pilih tingkatan penyaluran.</small>
                                            @endif
                                        </div>

                                        <!-- MWC Selector (Untuk PC) -->
                                        @if(Auth::user()->isPc())
                                            <div class="col-md-4 mb-2 {{ old('tingkat', $tingkat) === 'pc' ? 'd-none' : '' }}" id="mwcSelectContainer">
                                                <label class="font-weight-bold small text-dark">
                                                    Pilih MWC <span class="text-danger">*</span>
                                                </label>
                                                <select name="mwc_id" id="mwcSelect" class="form-control font-weight-bold text-dark">
                                                    <option value="">-- Pilih MWC --</option>
                                                    @foreach($mwcs as $m)
                                                        <option value="{{ $m->id }}" {{ (old('mwc_id', $mwcId) == $m->id) ? 'selected' : '' }}>
                                                            {{ $m->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Kecamatan pelaksana.</small>
                                            </div>
                                        @endif

                                        <!-- Ranting Selector -->
                                        <div class="col-md-{{ Auth::user()->isPc() ? '4' : '6' }} mb-2 {{ old('tingkat', $tingkat) !== 'ranting' ? 'd-none' : '' }}" id="rantingSelectContainer">
                                            <label class="font-weight-bold small text-dark">
                                                Pilih Ranting <span class="text-danger">*</span>
                                            </label>
                                            @if(Auth::user()->isRanting())
                                                <div class="form-control bg-white font-weight-bold text-dark">
                                                    {{ Auth::user()->ranting?->name }}
                                                </div>
                                            @else
                                                <select name="ranting_id" id="rantingSelect" class="form-control font-weight-bold text-dark">
                                                    <option value="">-- Pilih Ranting --</option>
                                                    @foreach($rantings as $r)
                                                        <option value="{{ $r->id }}" data-mwc-id="{{ $r->mwc_id }}" {{ (old('ranting_id', $rantingId) == $r->id) ? 'selected' : '' }}>
                                                            {{ $r->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                <small class="text-muted">Ranting pelaksana.</small>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =============================================================== -->
                    <!-- TAHAP 2: RINGKASAN REKENING & 5 PILAR PROGRAM (CARD/WIDGET)      -->
                    <!-- =============================================================== -->
                    <div class="mb-4" id="sectionPilarOverview">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <div>
                                <span class="badge badge-dark mr-2" style="font-size: 0.9rem;">Langkah 2</span>
                                <strong class="text-dark"><i class="fas fa-wallet text-success mr-1"></i> Ringkasan Rekening & Saldo 5 Pilar Program (Periode Ini)</strong>
                            </div>
                            <div id="loaderBalance" class="spinner-border spinner-border-sm text-success d-none" role="status">
                                <span class="sr-only">Loading...</span>
                            </div>
                        </div>

                        <!-- Scope Context Banner -->
                        <div class="alert alert-light border shadow-xs d-flex align-items-center justify-content-between py-2 px-3 mb-3" id="tingkatInfoBanner">
                            <div class="d-flex align-items-center">
                                <span id="tingkatBadge" class="badge badge-{{ ($periodBudget['tingkat'] ?? 'pc') === 'pc' ? 'primary' : (($periodBudget['tingkat'] ?? '') === 'mwc' ? 'success' : 'info') }} px-2 py-1 font-weight-bold mr-2" style="font-size: 0.85rem;">
                                    {{ strtoupper($periodBudget['tingkat'] ?? 'PC') }}
                                </span>
                                <div>
                                    <strong id="tingkatTitle" class="text-dark">{{ $periodBudget['wilayah_label'] ?? 'PC LAZISNU Kabupaten Lamongan' }}</strong>
                                    <small id="tingkatSubtitle" class="text-muted d-block">
                                        @if(($periodBudget['tingkat'] ?? 'pc') === 'ranting')
                                            Hak 5 Pilar Ranting: 80% dari Netto (Rp {{ number_format($periodBudget['dana_pilar_basis'] ?? 0, 0, ',', '.') }}) setelah porsi Struktur Atas 20% (Rp {{ number_format($periodBudget['total_alokasi_struktur_atas'] ?? 0, 0, ',', '.') }})
                                        @elseif(($periodBudget['tingkat'] ?? '') === 'mwc')
                                            Hak Bagi Hasil 5 Pilar MWC: {{ $periodBudget['mwc_pct'] ?? 13 }}% dari Netto Kecamatan (Rp {{ number_format($periodBudget['dana_pilar_basis'] ?? 0, 0, ',', '.') }})
                                        @else
                                            Hak Bagi Hasil 5 Pilar PC: {{ $periodBudget['pc_pct'] ?? 5 }}% dari Netto Kabupaten (Rp {{ number_format($periodBudget['dana_pilar_basis'] ?? 0, 0, ',', '.') }})
                                        @endif
                                    </small>
                                </div>
                            </div>
                            <span class="badge badge-secondary px-2 py-1" id="tingkatRuleBadge">
                                Porsi Pilar: {{ ($periodBudget['tingkat'] ?? 'pc') === 'ranting' ? '80% Netto' : (($periodBudget['tingkat'] ?? '') === 'mwc' ? ($periodBudget['mwc_pct'] ?? 13) . '% Netto' : ($periodBudget['pc_pct'] ?? 5) . '% Netto') }}
                            </span>
                        </div>

                        <!-- Card Header Angka Akuntansi: Bruto, Potongan Operasional, Netto, Sisa Saldo -->
                        <div class="row mb-3">
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="info-box bg-white border shadow-xs mb-0">
                                    <span class="info-box-icon bg-info text-white"><i class="fas fa-coins"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted small font-weight-bold text-uppercase" id="labelBox1">
                                            {{ ($periodBudget['tingkat'] ?? 'pc') === 'ranting' ? 'Total Koin Ranting (Bruto)' : (($periodBudget['tingkat'] ?? '') === 'mwc' ? 'Total Koin MWC (Bruto)' : 'Total Koin PC (Bruto)') }}
                                        </span>
                                        <span class="info-box-number text-dark font-weight-bold" id="widgetTotalBruto" style="font-size: 1.1rem;">
                                            Rp {{ number_format($periodBudget['total_koin_bruto'] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="info-box bg-white border shadow-xs mb-0">
                                    <span class="info-box-icon bg-warning text-dark"><i class="fas fa-percentage"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted small font-weight-bold text-uppercase" id="labelBox2">
                                            {{ ($periodBudget['tingkat'] ?? 'pc') === 'ranting' ? 'Hak Amil / Ops (20%)' : 'Biaya Operasional' }}
                                        </span>
                                        <span class="info-box-number text-danger font-weight-bold" id="widgetPotonganOperasional" style="font-size: 1.1rem;">
                                            - Rp {{ number_format($periodBudget['potongan_operasional'] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="info-box bg-success text-white shadow-xs mb-0">
                                    <span class="info-box-icon text-white"><i class="fas fa-hand-holding-usd"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text small font-weight-bold text-uppercase" id="labelBox3">
                                            {{ ($periodBudget['tingkat'] ?? 'pc') === 'ranting' ? 'Dana Bersih (Netto) Ranting' : (($periodBudget['tingkat'] ?? '') === 'mwc' ? 'Hak Bagi Hasil MWC (' . ($periodBudget['mwc_pct'] ?? 13) . '%)' : 'Hak Bagi Hasil PC (' . ($periodBudget['pc_pct'] ?? 5) . '%)') }}
                                        </span>
                                        <span class="info-box-number font-weight-bold" id="widgetDanaBersihNetto" style="font-size: 1.15rem;">
                                            Rp {{ number_format($periodBudget['dana_bersih_netto'] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3 col-sm-6 mb-2">
                                <div class="info-box bg-white border shadow-xs mb-0">
                                    <span class="info-box-icon bg-light text-success"><i class="fas fa-piggy-bank"></i></span>
                                    <div class="info-box-content">
                                        <span class="info-box-text text-muted small font-weight-bold text-uppercase" id="labelBox4">
                                            {{ ($periodBudget['tingkat'] ?? 'pc') === 'ranting' ? 'Sisa Hak Pilar Ranting' : 'Sisa Saldo 5 Pilar' }}
                                        </span>
                                        <span class="info-box-number text-success font-weight-bold" id="widgetSisaNetto" style="font-size: 1.1rem;">
                                            Rp {{ number_format($periodBudget['sisa_netto_periode'] ?? 0, 0, ',', '.') }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 5 Pilar Program Cards Grid -->
                        <div class="row" id="pilarCardsContainer">
                            @if($periodBudget && isset($periodBudget['pilars']))
                                @foreach($periodBudget['pilars'] as $pilarId => $pilarData)
                                    <div class="col-md col-sm-6 mb-3 pilar-card-col" id="pilarCol_{{ $pilarId }}" data-pilar-id="{{ $pilarId }}">
                                        <div class="card h-100 border pilar-budget-card transition-all" id="pilarCard_{{ $pilarId }}" style="border-top: 4px solid {{ $pilarData['warna'] ?? '#0a6b38' }} !important; border-radius: 8px;">
                                            <div class="card-body p-2 d-flex flex-column justify-content-between">
                                                <div>
                                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                                        <span class="badge text-white px-2 py-1 font-weight-bold" style="background-color: {{ $pilarData['warna'] ?? '#0a6b38' }}; font-size: 0.75rem;">
                                                            {{ $pilarData['kode'] }} ({{ $pilarData['target_persentase'] }}%)
                                                        </span>
                                                        <span class="badge {{ $pilarData['status_evaluasi']['badge'] ?? 'badge-success' }} badge-status-text" style="font-size: 0.68rem;">
                                                            {{ $pilarData['sisa_saldo'] < 0 ? 'Defisit' : 'Tersedia' }}
                                                        </span>
                                                    </div>
                                                    <h6 class="font-weight-bold text-dark text-truncate mb-1" title="{{ $pilarData['nama_pilar'] }}">
                                                        {{ $pilarData['nama_pilar'] }}
                                                    </h6>
                                                    <div class="small text-muted mb-1">
                                                        Pagu: <span class="font-weight-bold text-dark span-pagu">Rp {{ number_format($pilarData['pagu_nominal'], 0, ',', '.') }}</span>
                                                    </div>
                                                    <div class="small text-muted mb-2">
                                                        Terpakai: <span class="text-danger span-terpakai">Rp {{ number_format($pilarData['total_terpakai'], 0, ',', '.') }}</span>
                                                    </div>
                                                </div>
                                                <div class="pt-2 border-top">
                                                    <small class="text-muted d-block text-uppercase font-weight-bold" style="font-size: 0.68rem;">Sisa Dompet Pilar:</small>
                                                    <div class="font-weight-bold span-sisa-saldo {{ $pilarData['sisa_saldo'] < 0 ? 'text-danger' : 'text-success' }}" style="font-size: 1rem;">
                                                        Rp {{ number_format($pilarData['sisa_saldo'], 0, ',', '.') }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <!-- =============================================================== -->
                    <!-- TAHAP 3: PILIH PROGRAM KERJA (PROKER)                           -->
                    <!-- =============================================================== -->
                    <div class="card bg-light border mb-4">
                        <div class="card-header bg-white py-2">
                            <span class="badge badge-dark mr-2" style="font-size: 0.9rem;">Langkah 3</span>
                            <strong class="text-dark"><i class="fas fa-tasks text-info mr-1"></i> Pilih Program Kerja (Proker Disetujui)</strong>
                        </div>
                        <div class="card-body">
                            <div class="form-group mb-2">
                                <label class="font-weight-bold small text-dark">
                                    Program Kerja yang Diajukan <span class="text-danger">*</span>
                                </label>
                                <select name="program_kerja_id" id="prokerSelect" class="form-control form-control-lg font-weight-bold" required>
                                    <option value="">-- Pilih Program Kerja yang Telah Disetujui --</option>
                                    @foreach($approvedProkers as $proker)
                                        @php
                                            $pilar = $proker->pilarProgram;
                                        @endphp
                                        <option value="{{ $proker->id }}" 
                                                {{ (old('program_kerja_id', $selectedProkerId) == $proker->id) ? 'selected' : '' }}
                                                data-pilar-id="{{ $proker->pilar_program_id }}"
                                                data-pilar-nama="{{ $pilar?->nama_pilar }}"
                                                data-pilar-kode="{{ $pilar?->kode }}"
                                                data-pilar-warna="{{ $pilar?->warna }}"
                                                data-pilar-persen="{{ $pilar?->target_persentase }}"
                                                data-proker-rab="{{ $proker->rencana_anggaran }}"
                                                data-proker-sisa="{{ $proker->sisa_anggaran }}"
                                                data-proker-wilayah="{{ $proker->wilayah_label }}"
                                                data-proker-tingkat="{{ $proker->tingkat }}"
                                                data-proker-mwc-id="{{ $proker->mwc_id }}"
                                                data-proker-ranting-id="{{ $proker->ranting_id }}">
                                            [{{ strtoupper($proker->tingkat) }} - {{ $proker->wilayah_label }}] [{{ $proker->nomor_proker }}] {{ $proker->nama_kegiatan }} ({{ $pilar?->nama_pilar }}) — Sisa RAB: Rp {{ number_format($proker->sisa_anggaran, 0, ',', '.') }}
                                        </option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Hanya menampilkan Program Kerja berstatus 'Disetujui' dengan sisa pagu RAB > 0.</small>
                            </div>

                            <!-- Card Info Komparasi Batas Anggaran (Muncul Dinamis) -->
                            <div class="mt-3 d-none" id="infoPaguCard">
                                <div class="card bg-white border mb-0 shadow-xs">
                                    <div class="card-body p-3">
                                        <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3">
                                            <i class="fas fa-balance-scale text-primary mr-2"></i>Komparasi Batas Anggaran (RAB Proker vs Dompet Pilar)
                                        </h6>
                                        <div class="row text-center text-md-left">
                                            <div class="col-md-3 mb-2">
                                                <small class="text-muted d-block font-weight-bold">Pilar Program NU Care:</small>
                                                <span id="displayPilarBadge" class="badge text-white px-2 py-1 font-weight-bold" style="font-size: 0.9rem;">-</span>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <small class="text-muted d-block font-weight-bold">Sisa Anggaran Proker (RAB):</small>
                                                <strong id="displayProkerSisa" class="text-success" style="font-size: 1.1rem;">Rp 0</strong>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <small class="text-muted d-block font-weight-bold">Sisa Dompet Pilar Periode Ini:</small>
                                                <strong id="displayPilarSisa" class="text-primary" style="font-size: 1.1rem;">Rp 0</strong>
                                            </div>
                                            <div class="col-md-3 mb-2">
                                                <small class="text-muted d-block font-weight-bold">Batas Max Pencairan:</small>
                                                <strong id="displayMaxPencairan" class="text-danger" style="font-size: 1.15rem;">Rp 0</strong>
                                            </div>
                                        </div>
                                        <div class="mt-2 text-dark small" id="displayMaxNote"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =============================================================== -->
                    <!-- TAHAP 4: NOMINAL PENCAIRAN & DETAIL PENYALURAN                  -->
                    <!-- =============================================================== -->
                    <div class="card bg-light border mb-3">
                        <div class="card-header bg-white py-2">
                            <span class="badge badge-dark mr-2" style="font-size: 0.9rem;">Langkah 4</span>
                            <strong class="text-dark"><i class="fas fa-money-check-alt text-success mr-1"></i> Masukkan Nominal & Detail Penyaluran</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Nominal Pencairan Dana -->
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small text-dark">Nominal Pencairan Dana <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text font-weight-bold bg-success text-white">Rp</span>
                                        </div>
                                        <input type="number" name="nominal_pencairan" id="nominalInput" class="form-control form-control-lg font-weight-bold text-danger" 
                                               placeholder="Contoh: 2500000" value="{{ old('nominal_pencairan') }}" required min="1000">
                                    </div>
                                    <div id="nominalWarning" class="alert alert-danger py-1 px-2 small font-weight-bold mt-2 d-none">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> <span id="nominalWarningText"></span>
                                    </div>
                                </div>

                                <!-- Tanggal Penyaluran -->
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small text-dark">Tanggal Penyaluran <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_penyaluran" class="form-control form-control-lg" 
                                           value="{{ old('tanggal_penyaluran', date('Y-m-d')) }}" required>
                                </div>

                                <!-- Penerima Manfaat Terhubung Data Mustahiq -->
                                <div class="col-md-12 form-group">
                                    <label class="font-weight-bold small text-dark d-flex justify-content-between align-items-center">
                                        <span>
                                            <i class="fas fa-hand-holding-heart text-success mr-1"></i> Pilih Penerima Manfaat dari Database Mustahiq (Opsional)
                                        </span>
                                        <a href="{{ route('mustahiqs.create') }}" target="_blank" class="text-success small font-weight-bold">
                                            <i class="fas fa-plus-circle mr-1"></i> Daftarkan Mustahiq Baru
                                        </a>
                                    </label>
                                    <select name="mustahiq_id" id="selectMustahiq" class="form-control" style="width: 100%;">
                                        <option value="">-- Cari Nama, NIK, atau Dusun Mustahiq Desa Anda --</option>
                                    </select>
                                    <div id="mustahiqSelectedInfo" class="alert alert-info py-2 px-3 small mt-2 d-none shadow-xs">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <i class="fas fa-check-circle text-success mr-1"></i>
                                                Penerima Terpilih: <strong id="mustahiqNamaDisplay" class="text-dark"></strong>
                                                <span class="text-muted ml-2">(NIK: <span id="mustahiqNikDisplay"></span>)</span>
                                            </div>
                                            <div>
                                                <span class="badge badge-warning" id="mustahiqAsnafBadge"></span>
                                                <span class="badge badge-light border text-success ml-1">
                                                    Histori Bantuan: <strong id="mustahiqHistoriDisplay">Rp 0</strong>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <small class="text-muted">Ketik minimal 2 karakter. Jika dipilih, nama dan jumlah penerima otomatis terisi.</small>
                                </div>

                                <!-- Penerima Manfaat -->
                                <div class="col-md-8 form-group">
                                    <label class="font-weight-bold small text-dark">Nama / Kelompok Penerima Manfaat <span class="text-danger">*</span></label>
                                    <input type="text" name="penerima_manfaat" class="form-control" 
                                           placeholder="Contoh: Mustahiq Lansia & Anak Yatim Desa Paciran" 
                                           value="{{ old('penerima_manfaat') }}" required>
                                </div>

                                <!-- Jumlah Penerima -->
                                <div class="col-md-4 form-group">
                                    <label class="font-weight-bold small text-dark">Jumlah Jiwa / Mustahiq <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" name="jumlah_penerima" class="form-control" 
                                               value="{{ old('jumlah_penerima', 1) }}" required min="1">
                                        <div class="input-group-append">
                                            <span class="input-group-text">Orang/Jiwa</span>
                                        </div>
                                    </div>
                                </div>

                                <!-- Upload Bukti Penyaluran -->
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small text-dark">Upload Bukti Penyaluran (Foto Kegiatan / Kwitansi Sementara)</label>
                                    <input type="file" name="bukti_penyaluran" class="form-control-file border rounded p-1 bg-white" accept="image/*,application/pdf">
                                    <small class="text-muted">Format: JPG, PNG, atau PDF (Maks. 5 MB).</small>
                                </div>

                                <!-- Keterangan Penyaluran -->
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold small text-dark">Keterangan / Catatan Penyaluran</label>
                                    <textarea name="keterangan" rows="2" class="form-control" 
                                              placeholder="Catatan tambahan mengenai lokasi, petugas penyalur, atau serah terima...">{{ old('keterangan') }}</textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                    <a href="{{ route('pentasyarufan.index') }}" class="btn btn-secondary px-4">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" id="btnSubmitPentasyarufan" class="btn btn-success font-weight-bold px-4 py-2">
                        <i class="fas fa-check-circle mr-1"></i> Proses Pencairan Dana
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('styles')
<style>
.transition-all {
    transition: all 0.25s ease-in-out;
}
.pilar-highlighted {
    transform: translateY(-4px);
    box-shadow: 0 0.5rem 1rem rgba(40, 167, 69, 0.25) !important;
    border: 2px solid #28a745 !important;
}
.shadow-xs {
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
}
</style>
@endpush

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
    const penthongSelect = document.getElementById('penthongSelect');
    const tingkatSelect = document.getElementById('tingkatSelect');
    const mwcSelect = document.getElementById('mwcSelect');
    const rantingSelect = document.getElementById('rantingSelect');
    const mwcSelectContainer = document.getElementById('mwcSelectContainer');
    const rantingSelectContainer = document.getElementById('rantingSelectContainer');

    const periodeStatusBadge = document.getElementById('periodeStatusBadge');
    const periodeNameDisplay = document.getElementById('periodeNameDisplay');
    const loaderBalance = document.getElementById('loaderBalance');

    const tingkatInfoBanner = document.getElementById('tingkatInfoBanner');
    const tingkatBadge = document.getElementById('tingkatBadge');
    const tingkatTitle = document.getElementById('tingkatTitle');
    const tingkatSubtitle = document.getElementById('tingkatSubtitle');
    const tingkatRuleBadge = document.getElementById('tingkatRuleBadge');

    const labelBox1 = document.getElementById('labelBox1');
    const widgetTotalBruto = document.getElementById('widgetTotalBruto');
    const labelBox2 = document.getElementById('labelBox2');
    const widgetPotonganOperasional = document.getElementById('widgetPotonganOperasional');
    const labelBox3 = document.getElementById('labelBox3');
    const widgetDanaBersihNetto = document.getElementById('widgetDanaBersihNetto');
    const labelBox4 = document.getElementById('labelBox4');
    const widgetSisaNetto = document.getElementById('widgetSisaNetto');

    // Proker & Info Card
    const prokerSelect = document.getElementById('prokerSelect');
    const infoCard = document.getElementById('infoPaguCard');
    const displayPilarBadge = document.getElementById('displayPilarBadge');
    const displayProkerSisa = document.getElementById('displayProkerSisa');
    const displayPilarSisa = document.getElementById('displayPilarSisa');
    const displayMaxPencairan = document.getElementById('displayMaxPencairan');
    const displayMaxNote = document.getElementById('displayMaxNote');

    // Inputs & Button
    const nominalInput = document.getElementById('nominalInput');
    const nominalWarning = document.getElementById('nominalWarning');
    const nominalWarningText = document.getElementById('nominalWarningText');
    const btnSubmit = document.getElementById('btnSubmitPentasyarufan');

    const allRantingsData = @json($allRantings ?? []);

    // State in memory
    let currentPeriodData = @json($periodBudget ?? null);
    let currentMaxLimit = 0;
    let currentProkerSisa = 0;
    let currentPilarSisa = 0;

    function formatRupiah(num) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(num || 0);
    }

    function getCurrentScope() {
        const tingkat = tingkatSelect ? tingkatSelect.value : 'pc';
        const mwcId = mwcSelect ? mwcSelect.value : (document.getElementById('mwcIdInput')?.value || '');
        const rantingId = rantingSelect ? rantingSelect.value : (document.getElementById('rantingIdInput')?.value || '');
        return { tingkat, mwcId, rantingId };
    }

    function toggleWilayahInputs(tingkat) {
        if (mwcSelectContainer) {
            if (tingkat === 'pc') {
                mwcSelectContainer.classList.add('d-none');
            } else {
                mwcSelectContainer.classList.remove('d-none');
            }
        }
        if (rantingSelectContainer) {
            if (tingkat === 'ranting') {
                rantingSelectContainer.classList.remove('d-none');
            } else {
                rantingSelectContainer.classList.add('d-none');
            }
        }
    }

    function updateRantingOptions(selectedMwcId, preselectedRantingId) {
        if (!rantingSelect) return;
        const currentRantingVal = preselectedRantingId || rantingSelect.value;
        rantingSelect.innerHTML = '<option value="">-- Pilih Ranting --</option>';

        const filtered = allRantingsData.filter(r => !selectedMwcId || r.mwc_id == selectedMwcId);
        filtered.forEach(r => {
            const opt = document.createElement('option');
            opt.value = r.id;
            opt.textContent = r.name;
            opt.dataset.mwcId = r.mwc_id;
            if (r.id == currentRantingVal) {
                opt.selected = true;
            }
            rantingSelect.appendChild(opt);
        });
    }

    function filterProkersByScope(tingkat, mwcId, rantingId) {
        if (!prokerSelect) return;
        const currentVal = prokerSelect.value;
        let hasSelectedOptionVisible = false;

        Array.from(prokerSelect.options).forEach((opt, idx) => {
            if (idx === 0) return;
            const optTingkat = opt.dataset.prokerTingkat;
            const optMwcId = opt.dataset.prokerMwcId;
            const optRantingId = opt.dataset.prokerRantingId;

            let visible = true;
            if (tingkat === 'pc') {
                visible = (optTingkat === 'pc');
            } else if (tingkat === 'mwc') {
                visible = (optTingkat === 'mwc') && (!mwcId || optMwcId == mwcId);
            } else if (tingkat === 'ranting') {
                visible = (optTingkat === 'ranting') && (!rantingId || optRantingId == rantingId);
            }

            opt.hidden = !visible;
            if (opt.value === currentVal && visible) {
                hasSelectedOptionVisible = true;
            }
        });

        if (!hasSelectedOptionVisible && currentVal) {
            prokerSelect.value = '';
            updateProkerLimit();
        }
    }

    // 1. Fetch balance data with explicit scoping
    async function fetchPeriodBalance(penthongId, tingkat, mwcId, rantingId, prokerId) {
        penthongId = penthongId || penthongSelect.value;
        if (!penthongId) return;

        loaderBalance.classList.remove('d-none');
        try {
            const params = new URLSearchParams();
            if (tingkat) params.append('tingkat', tingkat);
            if (mwcId) params.append('mwc_id', mwcId);
            if (rantingId) params.append('ranting_id', rantingId);
            if (prokerId) params.append('proker_id', prokerId);

            const url = `{{ url('pentasyarufan/realisasi/periode-balance') }}/${penthongId}?${params.toString()}`;
            const response = await fetch(url, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                }
            });
            const result = await response.json();
            if (result.success && result.data) {
                currentPeriodData = result.data;
                renderPeriodOverview(result.data);
                updateProkerLimit();
            }
        } catch (err) {
            console.error('Gagal mengambil data saldo periode:', err);
        } finally {
            loaderBalance.classList.add('d-none');
        }
    }

    // 2. Render Widgets and 5 Pilar Cards
    function renderPeriodOverview(data) {
        if (!data) return;

        periodeStatusBadge.textContent = (data.status_periode || 'OPEN').toUpperCase();
        periodeStatusBadge.className = data.status_periode === 'open' 
            ? 'badge badge-success px-2 py-1 font-weight-bold' 
            : 'badge badge-secondary px-2 py-1 font-weight-bold';
        periodeNameDisplay.textContent = data.nama_periode;

        // Context Banner
        const tingkat = data.tingkat || 'pc';
        if (tingkatBadge) {
            tingkatBadge.textContent = tingkat.toUpperCase();
            tingkatBadge.className = `badge badge-${tingkat === 'pc' ? 'primary' : (tingkat === 'mwc' ? 'success' : 'info')} px-2 py-1 font-weight-bold mr-2`;
        }
        if (tingkatTitle) {
            tingkatTitle.textContent = data.wilayah_label || 'PC LAZISNU Kabupaten Lamongan';
        }
        if (tingkatSubtitle) {
            if (tingkat === 'ranting') {
                tingkatSubtitle.textContent = `Hak 5 Pilar Ranting: 80% dari Netto (${formatRupiah(data.dana_pilar_basis)}) setelah porsi Struktur Atas 20% (${formatRupiah(data.total_alokasi_struktur_atas)})`;
            } else if (tingkat === 'mwc') {
                const mwcPctVal = data.mwc_pct || 13;
                tingkatSubtitle.textContent = `Hak Bagi Hasil 5 Pilar MWC: ${mwcPctVal}% dari Netto Kecamatan (${formatRupiah(data.dana_pilar_basis)})`;
            } else {
                const pcPctVal = data.pc_pct || 5;
                tingkatSubtitle.textContent = `Hak Bagi Hasil 5 Pilar PC: ${pcPctVal}% dari Netto Kabupaten (${formatRupiah(data.dana_pilar_basis)})`;
            }
        }
        if (tingkatRuleBadge) {
            const mwcPctVal = data.mwc_pct || 13;
            const pcPctVal = data.pc_pct || 5;
            tingkatRuleBadge.textContent = tingkat === 'ranting' ? 'Porsi Pilar: 80% Netto' : (tingkat === 'mwc' ? `Porsi Pilar: ${mwcPctVal}% Netto` : `Porsi Pilar: ${pcPctVal}% Netto`);
        }

        // Labels
        if (labelBox1) {
            labelBox1.textContent = tingkat === 'ranting' ? 'Total Koin Ranting (Bruto)' : (tingkat === 'mwc' ? 'Total Koin MWC (Bruto)' : 'Total Koin PC (Bruto)');
        }
        if (labelBox2) {
            labelBox2.textContent = tingkat === 'ranting' ? `Hak Amil / Ops (${data.operasional_pct}%)` : 'Biaya Operasional';
        }
        if (labelBox3) {
            const mwcPctVal = data.mwc_pct || 13;
            const pcPctVal = data.pc_pct || 5;
            labelBox3.textContent = tingkat === 'ranting' ? 'Dana Bersih (Netto) Ranting' : (tingkat === 'mwc' ? `Hak Bagi Hasil MWC (${mwcPctVal}%)` : `Hak Bagi Hasil PC (${pcPctVal}%)`);
        }
        if (labelBox4) {
            labelBox4.textContent = tingkat === 'ranting' ? 'Sisa Hak Pilar Ranting' : 'Sisa Saldo 5 Pilar';
        }

        // Values
        widgetTotalBruto.textContent = formatRupiah(data.total_koin_bruto);
        widgetPotonganOperasional.textContent = '- ' + formatRupiah(data.potongan_operasional);
        widgetDanaBersihNetto.textContent = formatRupiah(data.dana_bersih_netto);
        widgetSisaNetto.textContent = formatRupiah(data.sisa_netto_periode);

        // Update cards
        if (data.pilars) {
            Object.values(data.pilars).forEach(p => {
                const card = document.getElementById(`pilarCard_${p.pilar_id}`);
                if (card) {
                    const spanPagu = card.querySelector('.span-pagu');
                    const spanTerpakai = card.querySelector('.span-terpakai');
                    const spanSisa = card.querySelector('.span-sisa-saldo');
                    const badgeStatus = card.querySelector('.badge-status-text');

                    if (spanPagu) spanPagu.textContent = formatRupiah(p.pagu_nominal);
                    if (spanTerpakai) spanTerpakai.textContent = formatRupiah(p.total_terpakai);
                    if (spanSisa) {
                        spanSisa.textContent = formatRupiah(p.sisa_saldo);
                        spanSisa.className = `font-weight-bold span-sisa-saldo ${p.sisa_saldo < 0 ? 'text-danger' : 'text-success'}`;
                    }
                    if (badgeStatus) {
                        badgeStatus.textContent = p.sisa_saldo < 0 ? 'Defisit' : 'Tersedia';
                        badgeStatus.className = `badge ${p.status_evaluasi?.badge || 'badge-success'} badge-status-text`;
                    }
                }
            });
        }
    }

    // 3. Highlight Pilar Card & Update Proker Limit
    function updateProkerLimit() {
        const selected = prokerSelect.options[prokerSelect.selectedIndex];
        
        // Reset highlights
        document.querySelectorAll('.pilar-budget-card').forEach(c => c.classList.remove('pilar-highlighted'));

        if (!selected || !selected.value) {
            infoCard.classList.add('d-none');
            currentMaxLimit = 0;
            currentProkerSisa = 0;
            currentPilarSisa = 0;
            checkNominal();
            return;
        }

        const pilarId = parseInt(selected.dataset.pilarId);
        const pilarNama = selected.dataset.pilarNama;
        const pilarKode = selected.dataset.pilarKode;
        const pilarWarna = selected.dataset.pilarWarna;
        const pilarPersen = selected.dataset.pilarPersen;

        currentProkerSisa = parseFloat(selected.dataset.prokerSisa) || 0;

        // Ambil sisa saldo pilar dari data periode aktif
        if (currentPeriodData && currentPeriodData.pilars && currentPeriodData.pilars[pilarId]) {
            currentPilarSisa = parseFloat(currentPeriodData.pilars[pilarId].sisa_saldo) || 0;
        } else {
            currentPilarSisa = 0;
        }

        // Highlight card pilar bersangkutan
        const targetCard = document.getElementById(`pilarCard_${pilarId}`);
        if (targetCard) {
            targetCard.classList.add('pilar-highlighted');
        }

        // Tampilkan info komparasi
        displayPilarBadge.textContent = `${pilarNama} (${pilarPersen}%)`;
        displayPilarBadge.style.backgroundColor = pilarWarna || '#0a6b38';

        displayProkerSisa.textContent = formatRupiah(currentProkerSisa);
        displayPilarSisa.textContent = formatRupiah(Math.max(0, currentPilarSisa));

        currentMaxLimit = Math.min(currentProkerSisa, Math.max(0, currentPilarSisa));
        displayMaxPencairan.textContent = formatRupiah(currentMaxLimit);

        if (currentPilarSisa <= 0) {
            displayMaxNote.innerHTML = `<div class="alert alert-danger py-1 px-2 mb-0 font-weight-bold">
                <i class="fas fa-ban mr-1"></i> Dompet pilar [${pilarNama}] pada periode ini telah habis / defisit. Tidak dapat mencairkan dana dari periode ini.
            </div>`;
        } else if (currentMaxLimit < currentProkerSisa) {
            displayMaxNote.innerHTML = `<span class="badge badge-warning text-dark">
                <i class="fas fa-exclamation-circle mr-1"></i> Dibatasi oleh Sisa Dompet Pilar Periode Ini: Maksimal Rp ${new Intl.NumberFormat('id-ID').format(currentMaxLimit)}
            </span>`;
        } else {
            displayMaxNote.innerHTML = `<span class="badge badge-success">
                <i class="fas fa-check-circle mr-1"></i> Saldo Pilar mencukupi untuk memenuhi sisa RAB Proker ini.
            </span>`;
        }

        infoCard.classList.remove('d-none');
        checkNominal();
    }

    // 4. Validasi Real-time Nominal Input
    function checkNominal() {
        const val = parseFloat(nominalInput.value) || 0;
        if (val <= 0) {
            nominalWarning.classList.add('d-none');
            btnSubmit.disabled = false;
            return;
        }

        if (!penthongSelect.value) {
            nominalWarningText.textContent = 'Harap pilih Sumber Dana (Periode Penthong) terlebih dahulu!';
            nominalWarning.classList.remove('d-none');
            btnSubmit.disabled = true;
            return;
        }

        if (!prokerSelect.value) {
            nominalWarningText.textContent = 'Harap pilih Program Kerja (Proker) yang diajukan terlebih dahulu!';
            nominalWarning.classList.remove('d-none');
            btnSubmit.disabled = true;
            return;
        }

        if (val > currentProkerSisa) {
            nominalWarningText.textContent = `Nominal (Rp ${new Intl.NumberFormat('id-ID').format(val)}) MELEBIHI sisa anggaran Proker (Maks. ${formatRupiah(currentProkerSisa)})!`;
            nominalWarning.classList.remove('d-none');
            btnSubmit.disabled = true;
        } else if (val > currentPilarSisa) {
            nominalWarningText.textContent = `Nominal (Rp ${new Intl.NumberFormat('id-ID').format(val)}) MELEBIHI sisa dompet Pilar pada periode ini (Sisa Saldo: ${formatRupiah(Math.max(0, currentPilarSisa))})!`;
            nominalWarning.classList.remove('d-none');
            btnSubmit.disabled = true;
        } else {
            nominalWarning.classList.add('d-none');
            btnSubmit.disabled = false;
        }
    }

    // Event Listeners
    penthongSelect.addEventListener('change', function () {
        const sc = getCurrentScope();
        fetchPeriodBalance(this.value, sc.tingkat, sc.mwcId, sc.rantingId, prokerSelect.value);
    });

    if (tingkatSelect && tingkatSelect.tagName === 'SELECT') {
        tingkatSelect.addEventListener('change', function () {
            toggleWilayahInputs(this.value);
            const sc = getCurrentScope();
            if (this.value === 'ranting' && mwcSelect && mwcSelect.value) {
                updateRantingOptions(mwcSelect.value);
            }
            fetchPeriodBalance(penthongSelect.value, sc.tingkat, sc.mwcId, sc.rantingId);
            filterProkersByScope(sc.tingkat, sc.mwcId, sc.rantingId);
        });
    }

    if (mwcSelect) {
        mwcSelect.addEventListener('change', function () {
            updateRantingOptions(this.value);
            const sc = getCurrentScope();
            fetchPeriodBalance(penthongSelect.value, sc.tingkat, this.value, sc.rantingId);
            filterProkersByScope(sc.tingkat, this.value, sc.rantingId);
        });
    }

    if (rantingSelect) {
        rantingSelect.addEventListener('change', function () {
            const sc = getCurrentScope();
            fetchPeriodBalance(penthongSelect.value, sc.tingkat, sc.mwcId, this.value);
            filterProkersByScope(sc.tingkat, sc.mwcId, this.value);
        });
    }

    prokerSelect.addEventListener('change', function () {
        const opt = this.options[this.selectedIndex];
        if (opt && opt.value) {
            const prokerTingkat = opt.dataset.prokerTingkat;
            const prokerMwcId = opt.dataset.prokerMwcId;
            const prokerRantingId = opt.dataset.prokerRantingId;

            // Auto-sync UI controls
            if (tingkatSelect && tingkatSelect.value !== prokerTingkat) {
                tingkatSelect.value = prokerTingkat;
                toggleWilayahInputs(prokerTingkat);
            }
            if (mwcSelect && prokerMwcId) {
                mwcSelect.value = prokerMwcId;
                updateRantingOptions(prokerMwcId, prokerRantingId);
            }
            if (rantingSelect && prokerRantingId) {
                rantingSelect.value = prokerRantingId;
            }

            // Fetch balance for this proker's tier & period
            fetchPeriodBalance(
                penthongSelect.value, 
                prokerTingkat, 
                prokerMwcId, 
                prokerRantingId, 
                opt.value
            );
        } else {
            updateProkerLimit();
        }
    });

    nominalInput.addEventListener('input', checkNominal);

    // Initial check & filtering on load
    const initialScope = getCurrentScope();
    filterProkersByScope(initialScope.tingkat, initialScope.mwcId, initialScope.rantingId);
    if (prokerSelect.value) {
        updateProkerLimit();
    }

    // ===============================================================
    // Select2 Autocomplete Pencarian Mustahiq Berbasis Ranting
    // ===============================================================
    if (window.jQuery && $('#selectMustahiq').length) {
        $('#selectMustahiq').select2({
            theme: 'bootstrap4',
            placeholder: '-- Cari Nama, NIK, atau Dusun Mustahiq --',
            allowClear: true,
            minimumInputLength: 2,
            ajax: {
                url: "{{ route('mustahiqs.search') }}",
                dataType: 'json',
                delay: 300,
                data: function (params) {
                    const sc = getCurrentScope();
                    return {
                        q: params.term,
                        page: params.page || 1,
                        ranting_id: sc.rantingId || null
                    };
                },
                processResults: function (data, params) {
                    params.page = params.page || 1;
                    return {
                        results: data.results,
                        pagination: {
                            more: data.pagination.more
                        }
                    };
                },
                cache: true
            },
            templateResult: function (item) {
                if (item.loading) return item.text;
                return $(`
                    <div class="py-1">
                        <div class="font-weight-bold text-dark">${item.nama} <span class="badge badge-light border ml-1">${item.nik}</span></div>
                        <div class="small text-muted">
                            <span class="badge badge-info mr-1">${item.asnaf_label}</span>
                            <i class="fas fa-map-marker-alt text-danger mr-1"></i>${item.ranting_nama} - ${item.alamat}
                        </div>
                        <div class="small text-success mt-1">
                            <i class="fas fa-coins mr-1"></i>Total Bantuan Pernah Diterima: Rp ${new Intl.NumberFormat('id-ID').format(item.total_bantuan_sebelumnya || 0)}
                        </div>
                    </div>
                `);
            },
            templateSelection: function (item) {
                return item.text || item.nama;
            }
        });

        $('#selectMustahiq').on('select2:select', function(e) {
            const data = e.params.data;
            $('#mustahiqNamaDisplay').text(data.nama);
            $('#mustahiqNikDisplay').text(data.nik);
            $('#mustahiqAsnafBadge').text(data.asnaf_label);
            $('#mustahiqHistoriDisplay').text('Rp ' + new Intl.NumberFormat('id-ID').format(data.total_bantuan_sebelumnya || 0));
            $('#mustahiqSelectedInfo').removeClass('d-none');

            // Auto-fill nama penerima dan jumlah penerima = 1
            const penerimaInput = document.querySelector('input[name="penerima_manfaat"]');
            const jumlahInput = document.querySelector('input[name="jumlah_penerima"]');
            if (penerimaInput) {
                penerimaInput.value = data.nama + ' (NIK: ' + data.nik + ')';
            }
            if (jumlahInput) {
                jumlahInput.value = 1;
            }
        });

        $('#selectMustahiq').on('select2:clear', function() {
            $('#mustahiqSelectedInfo').addClass('d-none');
        });
    }
});
</script>
@endpush
