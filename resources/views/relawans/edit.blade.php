@extends('layouts.adminlte')

@section('title', 'Edit Relawan: ' . $relawan->nama)
@section('page_title', 'Perbarui Data Relawan KOIN NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('relawans.index') }}">Relawan</a></li>
    <li class="breadcrumb-item active">Edit Relawan</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-12">
        <div class="card card-outline card-warning shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-user-edit mr-2 text-warning"></i> Kontrol & Perbarui Relawan: {{ $relawan->nama }}
                </h3>
            </div>
            <form action="{{ route('relawans.update', $relawan->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="nama" class="font-weight-bold">Nama Lengkap Relawan <span class="text-danger">*</span></label>
                            <input type="text" name="nama" id="nama" class="form-control @error('nama') is-invalid @enderror" value="{{ old('nama', $relawan->nama) }}" required>
                            @error('nama')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="nik" class="font-weight-bold">NIK (16 Digit) <span class="text-danger">*</span></label>
                            <input type="text" name="nik" id="nik" maxlength="16" class="form-control @error('nik') is-invalid @enderror" value="{{ old('nik', $relawan->nik) }}" required>
                            @error('nik')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="no_hp" class="font-weight-bold">Nomor HP / WhatsApp</label>
                            <input type="text" name="no_hp" id="no_hp" class="form-control @error('no_hp') is-invalid @enderror" value="{{ old('no_hp', $relawan->no_hp) }}">
                            @error('no_hp')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label for="area_tugas" class="font-weight-bold">Area Tugas / Teritori (RT/RW) <span class="text-danger">*</span></label>
                            <input type="text" name="area_tugas" id="area_tugas" class="form-control @error('area_tugas') is-invalid @enderror" value="{{ old('area_tugas', $relawan->area_tugas) }}" required>
                            @error('area_tugas')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label for="status" class="font-weight-bold">Status Keaktifan Relawan <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror" required>
                                <option value="active" {{ old('status', $relawan->status) === 'active' ? 'selected' : '' }}>Aktif (Dapat Menyetor)</option>
                                <option value="inactive" {{ old('status', $relawan->status) === 'inactive' ? 'selected' : '' }}>Nonaktif (Berhenti)</option>
                            </select>
                            @error('status')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Wilayah Ranting</label>
                            @if(auth()->check() && auth()->user()->isRanting())
                                <input type="text" class="form-control" value="{{ $relawan->ranting?->name }} ({{ $relawan->ranting?->code }})" readonly>
                            @else
                                <select name="ranting_id" class="form-control">
                                    @foreach($rantings as $ranting)
                                        <option value="{{ $ranting->id }}" {{ (old('ranting_id', $relawan->ranting_id) == $ranting->id) ? 'selected' : '' }}>
                                            [{{ $ranting->code }}] {{ $ranting->name }}
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="font-weight-bold">Catatan Tambahan (Opsional)</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror">{{ old('notes', $relawan->notes) }}</textarea>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('relawans.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan Relawan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
