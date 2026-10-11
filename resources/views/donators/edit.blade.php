@extends('layouts.adminlte')

@section('title', 'Edit Donatur & Petugas Pengampu')
@section('page_title', 'Perbarui Data Penerima Kotak KOIN NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('donators.index') }}">Penerima Kotak</a></li>
    <li class="breadcrumb-item active">Edit Donatur</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9 col-md-12">
        <div class="card card-outline card-warning shadow-sm">
            <div class="card-header bg-white py-3">
                <h3 class="card-title font-weight-bold text-dark">
                    <i class="fas fa-edit mr-2 text-warning"></i> Edit Penerima Kotak: {{ $donator->name }} (Kotak #{{ $donator->box_number }})
                </h3>
            </div>
            <form action="{{ route('donators.update', $donator->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <!-- Nomor Kaleng / Kotak -->
                        <div class="col-md-6 form-group">
                            <label for="box_number" class="font-weight-bold">
                                Nomor Kotak / Kaleng KOIN NU <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                </div>
                                <input type="text" 
                                       name="box_number" 
                                       id="box_number" 
                                       class="form-control @error('box_number') is-invalid @enderror" 
                                       value="{{ old('box_number', $donator->box_number) }}" 
                                       required>
                                @error('box_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Nama Kepala Keluarga / Donatur -->
                        <div class="col-md-6 form-group">
                            <label for="name" class="font-weight-bold">
                                Nama Kepala Keluarga / Donatur <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $donator->name) }}" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- NIK KTP -->
                        <div class="col-md-6 form-group">
                            <label for="nik" class="font-weight-bold">NIK KTP (16 Digit)</label>
                            <input type="text" 
                                   name="nik" 
                                   id="nik" 
                                   maxlength="16" 
                                   class="form-control @error('nik') is-invalid @enderror" 
                                   value="{{ old('nik', $donator->nik) }}">
                            @error('nik')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- No HP / WhatsApp -->
                        <div class="col-md-6 form-group">
                            <label for="phone" class="font-weight-bold">Nomor WhatsApp / HP</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                                </div>
                                <input type="text" 
                                       name="phone" 
                                       id="phone" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone', $donator->phone) }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h5 class="text-success font-weight-bold mb-3">
                        <i class="fas fa-map-marked-alt mr-2"></i> Wilayah Domisili & Penugasan Petugas Lapangan
                    </h5>

                    <div class="row">
                        <!-- Ranting / Desa -->
                        <div class="col-md-6 form-group">
                            <label for="ranting_id" class="font-weight-bold">Ranting / Desa Domisili</label>
                            @if(Auth::user()?->isPc() || !Auth::user())
                                <select name="ranting_id" id="ranting_id" class="form-control @error('ranting_id') is-invalid @enderror" required>
                                    @foreach($rantings as $r)
                                        <option value="{{ $r->id }}" {{ old('ranting_id', $donator->ranting_id) == $r->id ? 'selected' : '' }}>
                                            {{ $r->code }} - {{ $r->name }} ({{ $r->mwc?->code }})
                                        </option>
                                    @endforeach
                                </select>
                            @else
                                <input type="text" class="form-control bg-light" value="{{ $donator->ranting?->code }} - {{ $donator->ranting?->name }}" readonly>
                                <input type="hidden" name="ranting_id" value="{{ $donator->ranting_id }}">
                            @endif
                        </div>

                        <!-- Petugas Relawan Pengampu -->
                        <div class="col-md-6 form-group">
                            <label for="relawan_id" class="font-weight-bold">
                                Petugas Lapangan Pengampu (Relawan)
                            </label>
                            <select name="relawan_id" id="relawan_id" class="form-control @error('relawan_id') is-invalid @enderror">
                                <option value="">-- Belum Ditugaskan / Kosongkan --</option>
                                @foreach($relawans as $rel)
                                    <option value="{{ $rel->id }}" {{ old('relawan_id', $donator->relawan_id) == $rel->id ? 'selected' : '' }}>
                                        {{ $rel->nama }} — Wilayah: {{ $rel->area_tugas }}
                                    </option>
                                @endforeach
                            </select>
                            @error('relawan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Alamat Lengkap Rumah -->
                    <div class="form-group">
                        <label for="address" class="font-weight-bold">
                            Alamat Rumah Lengkap <span class="text-danger">*</span>
                        </label>
                        <textarea name="address" 
                                  id="address" 
                                  rows="2" 
                                  class="form-control @error('address') is-invalid @enderror" 
                                  required>{{ old('address', $donator->address) }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="rt" class="font-weight-bold">RT:</label>
                            <input type="text" name="rt" id="rt" class="form-control" value="{{ old('rt', $donator->rt) }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="rw" class="font-weight-bold">RW:</label>
                            <input type="text" name="rw" id="rw" class="form-control" value="{{ old('rw', $donator->rw) }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="joined_date" class="font-weight-bold">Tanggal Penyerahan Kotak:</label>
                            <input type="date" name="joined_date" id="joined_date" class="form-control" value="{{ old('joined_date', $donator->joined_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="status" class="font-weight-bold">Status Kotak <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="active" {{ old('status', $donator->status) == 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ old('status', $donator->status) == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="font-weight-bold">Catatan Tambahan:</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control">{{ old('notes', $donator->notes) }}</textarea>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('donators.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-warning font-weight-bold px-4 text-white">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
