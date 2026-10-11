@extends('layouts.adminlte')

@section('title', 'Edit Ranting - ' . $ranting->code)
@section('page_title', 'Edit Master Data Ranting NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('rantings.index') }}">Master Ranting</a></li>
    <li class="breadcrumb-item active">Edit {{ $ranting->code }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-12">
        <div class="card card-outline card-warning shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-edit mr-2 text-warning"></i> Perbarui Data Ranting NU: {{ $ranting->name }}
                </h3>
            </div>
            <form action="{{ route('rantings.update', $ranting->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="mwc_id" class="font-weight-bold">MWC Induk <span class="text-danger">*</span></label>
                            <select name="mwc_id" id="mwc_id" class="form-control" required>
                                @foreach($mwcs as $m)
                                    <option value="{{ $m->id }}" {{ (old('mwc_id', $ranting->mwc_id) == $m->id) ? 'selected' : '' }}>
                                        [{{ $m->code }}] {{ $m->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="code" class="font-weight-bold">Kode Ranting NU <span class="text-danger">*</span></label>
                            <input type="text" name="code" id="code" class="form-control font-weight-bold @error('code') is-invalid @enderror" value="{{ old('code', $ranting->code) }}" required>
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="name" class="font-weight-bold">Nama Ranting <span class="text-danger">*</span></label>
                            <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $ranting->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="desa" class="font-weight-bold">Desa / Kelurahan</label>
                            <input type="text" name="desa" id="desa" class="form-control @error('desa') is-invalid @enderror" value="{{ old('desa', $ranting->desa) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="address" class="font-weight-bold">Alamat Sekretariat</label>
                        <textarea name="address" id="address" rows="2" class="form-control">{{ old('address', $ranting->address) }}</textarea>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('rantings.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
