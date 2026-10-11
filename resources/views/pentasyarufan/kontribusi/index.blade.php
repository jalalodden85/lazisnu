@extends('layouts.adminlte')

@section('title', 'Daftar Setoran Kontribusi Struktur')
@section('page_title', 'Setoran Kontribusi Struktur')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pentasyarufan.index') }}">Pentasyarufan</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bku.index') }}">Buku Kas Umum</a></li>
    <li class="breadcrumb-item active">Riwayat Kontribusi</li>
@endsection

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <h3 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-hand-holding-usd text-success mr-2"></i>Riwayat Setoran Kontribusi Struktur (MWC/PC/PW/PB)
                    </h3>
                    <p class="text-muted small mb-0 mt-1">
                        Pencatatan pembagian hasil koin yang dibukukan sebagai pengeluaran kas BKU Ranting
                    </p>
                </div>
                <div class="card-tools">
                    <a href="{{ route('pentasyarufan.kontribusi.create') }}" class="btn btn-success font-weight-bold shadow-sm">
                        <i class="fas fa-plus-circle mr-1"></i> Input Setoran Kontribusi Baru
                    </a>
                </div>
            </div>

            <!-- Filter Periode Pentong -->
            <div class="card-body bg-light py-2 border-bottom">
                <form action="{{ route('pentasyarufan.kontribusi.index') }}" method="GET" class="form-inline">
                    <label class="mr-2 font-weight-bold small text-muted">Filter Periode Pentong:</label>
                    <select name="penthong_id" class="form-control form-control-sm mr-2" onchange="this.form.submit()">
                        <option value="">-- Semua Periode Pentong --</option>
                        @foreach($penthongs as $pt)
                            <option value="{{ $pt->id }}" {{ request('penthong_id') == $pt->id ? 'selected' : '' }}>
                                {{ $pt->nama_periode }} ({{ $pt->bulan_nama }} {{ $pt->tahun }}) - [{{ $pt->isOpen() ? 'AKTIF' : 'TUTUP' }}]
                            </option>
                        @endforeach
                    </select>
                    @if(request('penthong_id'))
                        <a href="{{ route('pentasyarufan.kontribusi.index') }}" class="btn btn-sm btn-outline-secondary">Reset</a>
                    @endif
                </form>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-striped table-hover m-0 align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>#</th>
                                <th>No. Transaksi</th>
                                <th>Periode Pentong</th>
                                <th>Ranting / MWC</th>
                                <th class="text-right">Bruto Dihimpun</th>
                                <th class="text-right">Operasional (20%)</th>
                                <th class="text-right">MWC (12%)</th>
                                <th class="text-right">PC (6%)</th>
                                <th class="text-right font-weight-bold text-success">Total Disetor</th>
                                <th class="text-center">Status</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($kontribusis as $index => $item)
                                <tr>
                                    <td>{{ $kontribusis->firstItem() + $index }}</td>
                                    <td class="font-weight-bold">{{ $item->nomor_transaksi }}</td>
                                    <td>
                                        <span class="badge badge-info">
                                            {{ $item->penthong?->nama_periode ?? 'Periode #' . $item->periode_pentong_id }}
                                        </span>
                                    </td>
                                    <td>
                                        <strong>Ranting {{ $item->ranting?->name }}</strong><br>
                                        <small class="text-muted">MWC {{ $item->mwc?->name }}</small>
                                    </td>
                                    <td class="text-right">Rp {{ number_format($item->nominal_bruto, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($item->nominal_operasional, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($item->nominal_mwc, 0, ',', '.') }}</td>
                                    <td class="text-right">Rp {{ number_format($item->nominal_pc, 0, ',', '.') }}</td>
                                    <td class="text-right font-weight-bold text-success">
                                        Rp {{ number_format($item->total_disetor, 0, ',', '.') }}
                                    </td>
                                    <td class="text-center">
                                        <span class="badge badge-success px-2 py-1">
                                            <i class="fas fa-check-circle mr-1"></i> Dibukukan
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <a href="{{ route('pentasyarufan.kontribusi.show', $item->id) }}" class="btn btn-xs btn-info">
                                            <i class="fas fa-eye"></i> Detail
                                        </a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="11" class="text-center py-4 text-muted">
                                        <i class="fas fa-info-circle fa-2x mb-2 d-block text-secondary"></i>
                                        Belum ada riwayat setoran kontribusi struktur untuk filter ini.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            @if($kontribusis->hasPages())
                <div class="card-footer bg-white">
                    {{ $kontribusis->links() }}
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
