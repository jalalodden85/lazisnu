@extends('layouts.adminlte')

@section('title', 'Tambah MWC Baru')
@section('page_title', 'Form Tambah Master Data MWC')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('mwcs.index') }}">Master MWC</a></li>
    <li class="breadcrumb-item active">Tambah MWC</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-12">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-success">
                    <i class="fas fa-plus-circle mr-2"></i> Pendaftaran MWC Baru
                </h3>
            </div>
            <form action="{{ route('mwcs.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label for="code" class="font-weight-bold">Kode MWC <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="code" class="form-control @error('code') is-invalid @enderror" placeholder="Contoh: M16" value="{{ old('code') }}" required>
                            <small class="form-text text-muted">Format: M01 s/d M15.</small>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-8 form-group">
                            <label for="name" class="font-weight-bold">Nama Lengkap MWC <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" placeholder="Contoh: MWCNU Sambeng" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="kecamatan" class="font-weight-bold">Kecamatan <span class="text-danger">*</span></label>
                            <input type="text" name="kecamatan" id="kecamatan" class="form-control @error('kecamatan') is-invalid @enderror" placeholder="Contoh: Sambeng" value="{{ old('kecamatan') }}" required>
                            @error('kecamatan')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="phone" class="font-weight-bold">No. Telepon / Kantor</label>
                            <input type="text" name="phone" id="phone" class="form-control @error('phone') is-invalid @enderror" placeholder="081234567890" value="{{ old('phone') }}">
                            @error('phone')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address" class="font-weight-bold">Alamat Kantor MWC</label>
                        <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="Jl. Raya...">{{ old('address') }}</textarea>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('mwcs.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Simpan Data MWC
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
