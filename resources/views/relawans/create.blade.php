@extends('layouts.adminlte')

@section('title', 'Tambah Relawan Baru')
@section('page_title', 'Form Registrasi Relawan KOIN NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('relawans.index') }}">Relawan</a></li>
    <li class="breadcrumb-item active">Tambah Baru</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-12">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-success">
                    <i class="fas fa-user-plus mr-2"></i> Pendaftaran Relawan Koordinator RT / RW
                </h3>
            </div>
            <form action="{{ route('relawans.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="nama" class="font-weight-bold">Nama Lengkap Relawan <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="nama" class="form-control @error('nama') is-invalid @enderror" placeholder="Contoh: Bpk. H. Ahmad Fauzi" value="{{ old('nama') }}" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="nik" class="font-weight-bold">NIK (16 Digit) <span class="text-danger">*</span></label>
                            <input type="text" name="nik" id="nik" maxlength="16" class="form-control @error('nik') is-invalid @enderror" placeholder="16 digit NIK unik KTP" value="{{ old('nik') }}" required>
                            @error('nik')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="no_hp" class="font-weight-bold">Nomor HP / WhatsApp</label>
                            <input type="text" name="no_hp" id="no_hp" class="form-control @error('no_hp') is-invalid @enderror" placeholder="Contoh: 081234567890" value="{{ old('no_hp') }}">
                            @error('no_hp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="area_tugas" class="font-weight-bold">Area Tugas / Teritori (RT/RW) <span class="text-danger">*</span></label>
                            <input type="text" name="area_tugas" id="area_tugas" class="form-control @error('area_tugas') is-invalid @enderror" placeholder="Contoh: RT 02 / RW 01 Dusun Krajan" value="{{ old('area_tugas') }}" required>
                            <small class="form-text text-muted">Wilayah pengambilan koin dari warga oleh relawan ini.</small>
                            @error('area_tugas')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    @if(auth()->check() && auth()->user()->isRanting())
                        <div class="alert alert-light border d-flex align-items-center mb-3">
                            <i class="fas fa-mosque text-success fa-2x mr-3"></i>
                            <div>
                                <span class="text-muted small d-block">Wilayah Penugasan Ranting:</span>
                                <strong class="text-dark">{{ auth()->user()->ranting?->name }} ({{ auth()->user()->ranting?->code }})</strong>
                                <span class="badge badge-success ml-2">Terkunci Otomatis</span>
                            </div>
                        </div>
                    @else
                        <div class="form-group mb-3">
                            <label for="ranting_id" class="font-weight-bold">Pilih Ranting NU <span class="text-danger">*</span></label>
                            <select name="ranting_id" id="ranting_id" class="form-control @error('ranting_id') is-invalid @enderror" required>
                                @foreach($rantings as $ranting)
                                    <option value="{{ $ranting->id }}" {{ (old('ranting_id', request('ranting_id')) == $ranting->id) ? 'selected' : '' }}>
                                        [{{ $ranting->code }}] {{ $ranting->name }} (MWC: {{ $ranting->mwc?->name ?? '-' }})
                                    </option>
                                @endforeach
                            </select>
                            @error('ranting_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    @endif

                    <div class="form-group">
                        <label for="notes" class="font-weight-bold">Catatan Tambahan (Opsional)</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Catatan relawan...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('relawans.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Simpan Data Relawan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
