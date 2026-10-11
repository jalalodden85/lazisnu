@extends('layouts.adminlte')

@section('title', 'Laporan Pentasyarufan & Penyaluran')
@section('page_title', 'Laporan Pentasyarufan & Penyaluran')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item">Pentasyarufan</li>
    <li class="breadcrumb-item active">Laporan</li>
@endsection

@section('content')
<!-- Filter Panel -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header bg-white">
        <h3 class="card-title font-weight-bold text-dark pt-1">
            <i class="fas fa-filter text-success mr-2"></i>Filter Parameter Laporan Pentasyarufan
        </h3>
    </div>
    <div class="card-body bg-light">
        <form action="{{ route('pentasyarufan.laporan') }}" method="GET" class="row">
            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Periode Penthong</label>
                <select name="penthong_id" class="form-control form-control-sm">
                    <option value="">-- Semua Periode --</option>
                    @foreach($penthongs as $pt)
                        <option value="{{ $pt->id }}" {{ request('penthong_id') == $pt->id ? 'selected' : '' }}>
                            {{ $pt->nama_periode }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Tingkatan Organisasi</label>
                <select name="tingkat" class="form-control form-control-sm">
                    <option value="">-- Semua Tingkat --</option>
                    <option value="pc" {{ request('tingkat') == 'pc' ? 'selected' : '' }}>Tingkat PC Lamongan</option>
                    <option value="mwc" {{ request('tingkat') == 'mwc' ? 'selected' : '' }}>Tingkat MWC (Kecamatan)</option>
                    <option value="ranting" {{ request('tingkat') == 'ranting' ? 'selected' : '' }}>Tingkat Ranting (Desa)</option>
                </select>
            </div>

            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Wilayah MWC NU</label>
                <select name="mwc_id" class="form-control form-control-sm">
                    <option value="">-- Semua MWC NU --</option>
                    @foreach($mwcs as $mwc)
                        <option value="{{ $mwc->id }}" {{ request('mwc_id') == $mwc->id ? 'selected' : '' }}>
                            [{{ $mwc->code }}] {{ $mwc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Pilar Program NU Care</label>
                <select name="pilar_id" class="form-control form-control-sm">
                    <option value="">-- Semua 5 Pilar --</option>
                    @foreach($pilars as $p)
                        <option value="{{ $p->id }}" {{ request('pilar_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->nama_pilar }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Periode Mulai</label>
                <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
            </div>

            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Periode Sampai</label>
                <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
            </div>

            <div class="col-md-9 form-group mb-2 d-flex align-items-end justify-content-end">
                <button type="submit" class="btn btn-sm btn-success mr-2 font-weight-bold">
                    <i class="fas fa-search mr-1"></i> Terapkan Filter
                </button>
                <a href="{{ route('pentasyarufan.laporan', array_merge(request()->all(), ['print' => 1])) }}" target="_blank" class="btn btn-sm btn-danger mr-2 font-weight-bold">
                    <i class="fas fa-print mr-1"></i> Cetak Laporan Resmi (PDF)
                </a>
                @if(request()->anyFilled(['tingkat', 'mwc_id', 'pilar_id', 'start_date', 'end_date']))
                    <a href="{{ route('pentasyarufan.laporan') }}" class="btn btn-sm btn-outline-secondary" title="Reset">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>
</div>

<!-- Ringkasan Alokasi Per Pilar -->
<div class="row">
    @foreach($pilars as $p)
        @php
            $subTotal = $rekapPilar[$p->id] ?? 0;
            $persenDariTotal = $totalNominal > 0 ? round(($subTotal / $totalNominal) * 100, 1) : 0;
        @endphp
        <div class="col-lg col-md-4 col-6 mb-3">
            <div class="card h-100 shadow-sm border-0" style="border-top: 4px solid {{ $p->warna }} !important;">
                <div class="card-body p-3">
                    <small class="text-muted text-uppercase font-weight-bold d-block text-truncate">
                        <i class="{{ $p->icon }} mr-1" style="color: {{ $p->warna }};"></i>{{ $p->kode }}
                    </small>
                    <h5 class="font-weight-bold text-dark mt-2 mb-1">
                        Rp {{ number_format($subTotal, 0, ',', '.') }}
                    </h5>
                    <small class="badge badge-light border">{{ $persenDariTotal }}% dari Penyaluran</small>
                </div>
            </div>
        </div>
    @endforeach
</div>

<!-- Tabel Laporan Rinci -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-table text-success mr-2"></i>Rincian Penyaluran Dana
        </h3>
        <span class="badge badge-success px-3 py-1 font-weight-bold" style="font-size: 0.95rem;">
            Total: Rp {{ number_format($totalNominal, 0, ',', '.') }} ({{ number_format($totalPenerima, 0, ',', '.') }} Penerima)
        </span>
    </div>
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>No. Transaksi</th>
                    <th>Tanggal</th>
                    <th>Program Kerja & Pilar</th>
                    <th>Wilayah Pelaksana</th>
                    <th>Penerima Manfaat</th>
                    <th>Nominal Penyaluran</th>
                    <th>Status SPJ</th>
                </tr>
            </thead>
            <tbody>
                @forelse($pentasyarufans as $index => $item)
                    <tr>
                        <td>{{ is_object($pentasyarufans) && method_exists($pentasyarufans, 'firstItem') ? $pentasyarufans->firstItem() + $index : $index + 1 }}</td>
                        <td>
                            <code>{{ $item->nomor_transaksi }}</code>
                            <small class="text-muted d-block"><i class="fas fa-coins text-warning mr-1"></i>{{ $item->penthong?->nama_periode ?? 'Global' }}</small>
                        </td>
                        <td>{{ $item->tanggal_penyaluran->format('d/m/Y') }}</td>
                        <td>
                            <strong>{{ $item->programKerja?->nama_kegiatan }}</strong>
                            <small class="text-muted d-block">{{ $item->pilarProgram?->nama_pilar }}</small>
                        </td>
                        <td>
                            <span class="badge badge-light border">{{ strtoupper($item->tingkat) }}</span>
                            <small class="font-weight-bold d-block">{{ $item->wilayah_label }}</small>
                        </td>
                        <td>
                            {{ $item->penerima_manfaat }}
                            <small class="text-muted d-block">{{ $item->jumlah_penerima }} Jiwa</small>
                        </td>
                        <td>
                            <strong class="text-danger">Rp {{ number_format($item->nominal_pencairan, 0, ',', '.') }}</strong>
                        </td>
                        <td>{!! $item->spj_badge !!}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-4 text-muted">
                            Tidak ada transaksi pentasyarufan yang sesuai dengan filter yang dipilih.
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="6" class="text-right">TOTAL PENTASYARUFAN TEREALISASI:</td>
                    <td class="text-danger font-weight-bold" style="font-size: 1.05rem;">
                        Rp {{ number_format($totalNominal, 0, ',', '.') }}
                    </td>
                    <td>-</td>
                </tr>
            </tfoot>
        </table>
    </div>

    @if(is_object($pentasyarufans) && method_exists($pentasyarufans, 'hasPages') && $pentasyarufans->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <small class="text-muted">
                Halaman {{ $pentasyarufans->currentPage() }} dari {{ $pentasyarufans->lastPage() }}
            </small>
            <div>
                {{ $pentasyarufans->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>
@endsection
