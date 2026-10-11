@extends('layouts.adminlte')

@section('title', 'Buka Periode Penthong Baru')
@section('page_title', 'Form Pembukaan Periode Penthong')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('penthongs.index') }}">Penthong</a></li>
    <li class="breadcrumb-item active">Buka Periode</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8">
        <div class="card card-success card-outline shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-box-open mr-1 text-success"></i> Informasi Periode Buka Kotak (Penthong)
                </h3>
            </div>
            <form action="{{ route('penthongs.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="callout callout-info py-2 mb-3">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1 text-info"></i>
                            <strong>Aturan Sistem:</strong> Membuka periode Penthong baru dengan status <strong>Aktif</strong> akan otomatis mengakhiri periode aktif sebelumnya. Seluruh transaksi setoran koin yang diinput ke depan akan langsung terikat ke periode Penthong ini.
                        </small>
                    </div>

                    <div class="form-group">
                        <label for="nama_periode" class="font-weight-bold">Nama Periode Penthong: <span class="text-danger">*</span></label>
                        <input type="text" name="nama_periode" id="nama_periode" class="form-control @error('nama_periode') is-invalid @enderror" value="{{ old('nama_periode', $defaultName) }}" placeholder="Contoh: Penthong 1 - Oktober 2026" required>
                        @error('nama_periode')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="bulan" class="font-weight-bold">Bulan: <span class="text-danger">*</span></label>
                            <select name="bulan" id="bulan" class="form-control @error('bulan') is-invalid @enderror" required>
                                @php
                                    $bulanList = [
                                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                                    ];
                                @endphp
                                @foreach($bulanList as $num => $nama)
                                    <option value="{{ $num }}" {{ old('bulan', $currentMonth) == $num ? 'selected' : '' }}>
                                        {{ $nama }}
                                    </option>
                                @endforeach
                            </select>
                            @error('bulan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="tahun" class="font-weight-bold">Tahun: <span class="text-danger">*</span></label>
                            <input type="number" name="tahun" id="tahun" class="form-control @error('tahun') is-invalid @enderror" value="{{ old('tahun', $currentYear) }}" min="2020" max="2099" required>
                            @error('tahun')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="start_date" class="font-weight-bold">Tanggal Mulai Penthong:</label>
                            <input type="date" name="start_date" id="start_date" class="form-control @error('start_date') is-invalid @enderror" value="{{ old('start_date', date('Y-m-01')) }}">
                            @error('start_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="col-md-6 form-group">
                            <label for="end_date" class="font-weight-bold">Perkiraan Tanggal Selesai:</label>
                            <input type="date" name="end_date" id="end_date" class="form-control @error('end_date') is-invalid @enderror" value="{{ old('end_date', date('Y-m-t')) }}">
                            @error('end_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if(Auth::user()?->isPc())
                        <div class="form-group">
                            <label for="mwc_id" class="font-weight-bold">Wilayah MWC (Opsional):</label>
                            <select name="mwc_id" id="mwc_id" class="form-control">
                                <option value="">-- Berlaku Serentak se-Cabang Lamongan --</option>
                                @foreach($mwcs as $m)
                                    <option value="{{ $m->id }}" {{ old('mwc_id') == $m->id ? 'selected' : '' }}>
                                        [{{ $m->code }}] {{ $m->name }}
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Kosongkan jika periode Penthong ini dibuka secara serentak untuk seluruh 15 MWC.</small>
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="notes" class="font-weight-bold">Catatan / Arahan Tambahan:</label>
                        <textarea name="notes" id="notes" rows="3" class="form-control" placeholder="Tuliskan arahan pelaksanaan pembukaan kaleng koin infaq...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between">
                    <a href="{{ route('penthongs.index') }}" class="btn btn-default">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Buka Periode Penthong
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
