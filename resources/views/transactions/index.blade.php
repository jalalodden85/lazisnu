@extends('layouts.adminlte')

@section('title', 'Riwayat Setoran Relawan')
@section('page_title', 'Rekapitulasi Setoran Koin Infaq Per Relawan')

@section('breadcrumb')
    <li class="breadcrumb-item active">Riwayat Setoran</li>
@endsection

@section('content')

@php
    $currentPeriodeObj = null;
    if ($selectedPeriode !== 'all' && !empty($selectedPeriode)) {
        $currentPeriodeObj = $allPenthongs->firstWhere('id', (int) $selectedPeriode);
    }
@endphp

<!-- Filter Data Setoran (Termasuk Filter Utama: Periode Pentong) -->
<div class="card card-default shadow-sm mb-4 border-top border-success">
    <div class="card-header bg-white">
        <h3 class="card-title font-weight-bold text-success">
            <i class="fas fa-filter mr-1"></i> Filter Data Setoran Berdasarkan Periode & Wilayah
        </h3>
        <div class="card-tools">
            <button type="button" class="btn btn-tool" data-card-widget="collapse">
                <i class="fas fa-minus"></i>
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('transactions.index') }}" class="row align-items-end" id="filterForm">
            <!-- 1. Dropdown Filter Periode Pentong (Utama) -->
            <div class="col-md-4 col-12 form-group">
                <label class="font-weight-bold text-dark">
                    <i class="fas fa-drum mr-1 text-success"></i> Pilih Periode Pentong:
                </label>
                <select name="periode_id" class="form-control font-weight-bold border-success" onchange="this.form.submit()">
                    @if($activePenthong)
                        <option value="{{ $activePenthong->id }}" {{ ($selectedPeriode == $activePenthong->id) ? 'selected' : '' }}>
                            🟢 [SEDANG AKTIF] {{ $activePenthong->nama_periode }}
                        </option>
                    @endif
                    <option value="all" {{ ($selectedPeriode === 'all') ? 'selected' : '' }}>
                        -- Semua Periode (Akumulasi Keseluruhan) --
                    </option>
                    @foreach($allPenthongs as $p)
                        @if(!$activePenthong || $p->id !== $activePenthong->id)
                            <option value="{{ $p->id }}" {{ ($selectedPeriode == $p->id) ? 'selected' : '' }}>
                                {{ $p->nama_periode }} ({{ $p->bulan_nama }} {{ $p->tahun }}) - [{{ strtoupper($p->status) }}]
                            </option>
                        @endif
                    @endforeach
                </select>
                <small class="form-text text-muted">
                    Memilih periode akan otomatis memperbarui tabel dan total akumulasi.
                </small>
            </div>

            <!-- 2. Filter MWC (Kecamatan) -->
            <div class="col-md-3 col-12 form-group">
                <label>Pilih MWC (Kecamatan):</label>
                <select name="mwc_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua 15 MWC --</option>
                    @foreach($mwcs as $m)
                        <option value="{{ $m->id }}" {{ request('mwc_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                    @endforeach
                </select>
            </div>

            <!-- 3. Filter Ranting (Desa) -->
            <div class="col-md-3 col-12 form-group">
                <label>Pilih Ranting (Desa):</label>
                <select name="ranting_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Semua Ranting --</option>
                    @foreach($rantings as $rtg)
                        <option value="{{ $rtg->id }}" {{ request('ranting_id') == $rtg->id ? 'selected' : '' }}>{{ $rtg->name }} ({{ $rtg->mwc->name ?? '' }})</option>
                    @endforeach
                </select>
            </div>

            <!-- Tombol Aksi Filter -->
            <div class="col-md-2 col-12 form-group d-flex justify-content-end">
                <a href="{{ route('transactions.index', ['periode_id' => 'all']) }}" class="btn btn-outline-secondary mr-2 w-100" title="Reset Filter">
                    <i class="fas fa-undo mr-1"></i> Reset
                </a>
                <button type="submit" class="btn btn-success w-100">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Banner Indikator Periode yang Sedang Dilihat -->
<div class="callout {{ $currentPeriodeObj ? ($currentPeriodeObj->isOpen() ? 'callout-success' : 'callout-secondary') : 'callout-info' }} py-2 px-3 mb-3 bg-white shadow-sm d-flex justify-content-between align-items-center flex-wrap">
    <div>
        <i class="fas fa-info-circle mr-1 {{ $currentPeriodeObj ? ($currentPeriodeObj->isOpen() ? 'text-success' : 'text-secondary') : 'text-info' }}"></i>
        @if($currentPeriodeObj)
            Menampilkan data setoran untuk: <strong class="text-dark">{{ $currentPeriodeObj->nama_periode }}</strong>
            ({{ $currentPeriodeObj->bulan_nama }} {{ $currentPeriodeObj->tahun }})
            @if($currentPeriodeObj->isOpen())
                <span class="badge badge-success ml-2"><i class="fas fa-check-circle mr-1"></i>Periode Aktif (Open)</span>
            @else
                <span class="badge badge-secondary ml-2"><i class="fas fa-lock mr-1"></i>Periode Ditutup (Closed)</span>
            @endif
        @else
            Menampilkan data setoran: <strong class="text-dark">Akumulasi Seluruh Periode (Semua Waktu)</strong>
        @endif
    </div>
    <div class="small text-muted mt-1 mt-md-0">
        Filter Aktif: {{ $totalCount }} Transaksi Terdata
    </div>
</div>

<!-- Info Box Ringkasan Akumulasi Nominal Setoran Sesuai Filter -->
<div class="row mb-3">
    <div class="col-md-6 col-12">
        <div class="info-box bg-light shadow-sm border border-success">
            <span class="info-box-icon bg-success"><i class="fas fa-coins"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted font-weight-bold">
                    Total Akumulasi Nominal (Hasil Filter)
                </span>
                <span class="info-box-number text-success font-weight-bold" style="font-size: 1.6rem;">
                    Rp {{ number_format($totalNominal, 0, ',', '.') }}
                </span>
            </div>
        </div>
    </div>
    <div class="col-md-6 col-12">
        <div class="info-box bg-light shadow-sm border border-info">
            <span class="info-box-icon bg-info"><i class="fas fa-receipt"></i></span>
            <div class="info-box-content">
                <span class="info-box-text text-muted font-weight-bold">
                    Frekuensi Setoran Masuk
                </span>
                <span class="info-box-number text-info font-weight-bold" style="font-size: 1.6rem;">
                    {{ number_format($totalCount, 0, ',', '.') }} Transaksi
                </span>
            </div>
        </div>
    </div>
</div>

@php
    $isRantingSuspended = Auth::user()?->isRanting() && Auth::user()?->ranting && !Auth::user()->ranting->is_active;
    $isPeriodeOpen = ($activePenthong && $activePenthong->isOpen());
@endphp

@if($isRantingSuspended)
    <x-adminlte-alert theme="danger" title="Akses Input Dinonaktifkan" icon="fas fa-ban" class="mb-3">
        Akses input dinonaktifkan oleh PC Lazisnu. Silakan selesaikan urusan administrasi Anda.
    </x-adminlte-alert>
@endif

<!-- Tabel Riwayat Transaksi -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-list mr-1 text-success"></i> Riwayat Transaksi Pengumpulan Koin Infaq
        </h3>
        <div class="d-flex align-items-center flex-wrap gap-2">
            <!-- Tombol Ekspor Laporan (PDF & Excel) -->
            @php
                $targetExportPenthong = $currentPeriodeObj ?? $activePenthong ?? $allPenthongs->first();
            @endphp
            @if($targetExportPenthong)
                <div class="btn-group mr-2">
                    <button type="button" class="btn btn-sm btn-outline-danger font-weight-bold" onclick="openModalCetakLaporan({format: 'pdf', penthong_id: '{{ $targetExportPenthong->id }}', mwc_id: '{{ request('mwc_id') }}', ranting_id: '{{ request('ranting_id') }}'})" title="Konfigurasi & Cetak Dokumen Resmi PDF">
                        <i class="fas fa-file-pdf mr-1"></i> Cetak Laporan (PDF)
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-success font-weight-bold" onclick="openModalCetakLaporan({format: 'excel', penthong_id: '{{ $targetExportPenthong->id }}', mwc_id: '{{ request('mwc_id') }}', ranting_id: '{{ request('ranting_id') }}'})" title="Konfigurasi & Download Spreadsheet Excel">
                        <i class="fas fa-file-excel mr-1"></i> Ekspor Data (Excel)
                    </button>
                </div>
            @endif

            @if($isRantingSuspended)
                <button type="button" class="btn btn-sm btn-secondary" disabled title="Akses input dinonaktifkan oleh PC Lazisnu">
                    <i class="fas fa-lock mr-1"></i> Input Dinonaktifkan (Suspended)
                </button>
            @elseif(!$isPeriodeOpen)
                <button type="button" class="btn btn-sm btn-secondary" disabled title="Periode pentong sedang ditutup">
                    <i class="fas fa-lock mr-1"></i> Input Dinonaktifkan (Periode Tutup)
                </button>
            @else
                <a href="{{ route('transactions.create') }}" class="btn btn-sm btn-success shadow-sm font-weight-bold">
                    <i class="fas fa-plus-circle mr-1"></i> Input Setoran Baru
                </a>
            @endif
        </div>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover m-0 align-middle text-sm">
                <thead class="thead-light">
                    <tr>
                        <th style="width: 140px;">No. Kuitansi</th>
                        <th>Periode Pentong</th>
                        <th>Tanggal</th>
                        <th>Relawan Penyetor</th>
                        <th>Area RT/RW</th>
                        <th>Ranting (Desa)</th>
                        <th>MWC (Kecamatan)</th>
                        <th class="text-right">Nominal Setoran</th>
                        <th class="text-center" style="width: 175px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($transactions as $tx)
                        @php
                            $user = Auth::user();
                            $canManage = false;
                            if ($user) {
                                if ($user->isPc()) {
                                    $canManage = true;
                                } elseif ($user->isMwc() && (int)$tx->mwc_id === (int)$user->mwc_id) {
                                    $canManage = true;
                                } elseif ($user->isRanting() && (int)$tx->ranting_id === (int)$user->ranting_id) {
                                    $canManage = true;
                                }
                            }
                            $isTxLocked = (!$tx->penthong || !$tx->penthong->isOpen());
                        @endphp
                        <tr>
                            <td class="font-weight-bold text-success">
                                <a href="{{ route('transactions.show', $tx->id) }}" class="text-success">
                                    {{ $tx->invoice_code }}
                                </a>
                            </td>
                            <td>
                                @if($tx->penthong)
                                    <span class="badge {{ $tx->penthong->isOpen() ? 'badge-success' : 'badge-light border' }}" title="{{ $tx->penthong->nama_periode }}">
                                        <i class="fas fa-drum mr-1"></i>{{ Str::limit($tx->penthong->nama_periode, 22) }}
                                    </span>
                                @else
                                    <span class="badge badge-secondary">-</span>
                                @endif
                            </td>
                            <td>{{ \Carbon\Carbon::parse($tx->collection_date)->format('d/m/Y') }}</td>
                            <td class="font-weight-bold text-dark">{{ $tx->relawan->nama ?? '-' }}</td>
                            <td><span class="badge badge-light border">{{ $tx->relawan->area_tugas ?? '-' }}</span></td>
                            <td>{{ $tx->ranting->name ?? '-' }}</td>
                            <td><small class="text-muted">{{ $tx->mwc->name ?? '-' }}</small></td>
                            <td class="text-right font-weight-bold text-success">{{ $tx->formatted_amount }}</td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm" role="group">
                                    <a href="{{ route('transactions.show', $tx->id) }}" class="btn btn-xs btn-outline-info" title="Lihat Kuitansi">
                                        <i class="fas fa-eye"></i>
                                    </a>

                                    @if($canManage)
                                        @if($isTxLocked)
                                            <span class="d-inline-block ml-1" tabindex="0" data-toggle="tooltip" data-placement="top" title="Transaksi terkunci karena periode pentong telah ditutup.">
                                                <button type="button" class="btn btn-xs btn-outline-secondary disabled" style="pointer-events: none;" disabled>
                                                    <i class="fas fa-lock text-muted mr-1"></i>Edit
                                                </button>
                                            </span>
                                            <span class="d-inline-block ml-1" tabindex="0" data-toggle="tooltip" data-placement="top" title="Transaksi terkunci karena periode pentong telah ditutup.">
                                                <button type="button" class="btn btn-xs btn-outline-secondary disabled" style="pointer-events: none;" disabled>
                                                    <i class="fas fa-trash text-muted"></i>
                                                </button>
                                            </span>
                                        @else
                                            <a href="{{ route('transactions.edit', $tx->id) }}" class="btn btn-xs btn-warning text-dark font-weight-bold ml-1" title="Edit Transaksi">
                                                <i class="fas fa-edit mr-1"></i>Edit
                                            </a>
                                            <form action="{{ route('transactions.destroy', $tx->id) }}" method="POST" class="d-inline ml-1" onsubmit="return confirm('Apakah Anda yakin ingin menghapus transaksi setoran {{ $tx->invoice_code }} ini?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-xs btn-danger" title="Hapus Transaksi">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        @endif
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-5 text-muted">
                                <i class="fas fa-inbox fa-3x mb-3 text-secondary d-block"></i>
                                <h6>Tidak ada data setoran koin yang sesuai dengan filter yang dipilih.</h6>
                                <p class="small mb-0">Coba pilih opsi <em>-- Semua Periode --</em> untuk melihat riwayat keseluruhan.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
                @if($transactions->isNotEmpty())
                    <tfoot class="bg-light font-weight-bold">
                        <tr>
                            <td colspan="7" class="text-right text-uppercase">
                                Total Halaman Ini:
                            </td>
                            <td class="text-right text-success">
                                Rp {{ number_format($transactions->sum('amount'), 0, ',', '.') }}
                            </td>
                            <td></td>
                        </tr>
                    </tfoot>
                @endif
            </table>
        </div>
    </div>
    <div class="card-footer bg-white clearfix d-flex justify-content-between align-items-center">
        <small class="text-muted">
            Menampilkan {{ $transactions->firstItem() ?? 0 }} - {{ $transactions->lastItem() ?? 0 }} dari {{ $transactions->total() }} data setoran
        </small>
        <div>
            {{ $transactions->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection
