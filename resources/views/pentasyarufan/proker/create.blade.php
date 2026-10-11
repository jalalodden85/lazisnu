@extends('layouts.adminlte')

@section('title', 'Ajukan Program Kerja (Proker) Baru')
@section('page_title', 'Ajukan Program Kerja Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('proker.index') }}">Program Kerja</a></li>
    <li class="breadcrumb-item active">Ajukan Baru</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <form action="{{ route('proker.store') }}" method="POST">
            @csrf
            <div class="card card-outline card-success shadow-sm">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-file-signature text-success mr-2"></i>Formulir Rencana Anggaran Biaya (RAB) Program Kerja
                    </h3>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Terjadi Kesalahan Pengisian:</h6>
                            <ul class="mb-0 pl-3 small">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <div class="row">
                        <!-- Nama Kegiatan -->
                        <div class="col-md-8 form-group">
                            <label class="font-weight-bold small text-dark">Nama Program Kerja / Kegiatan <span class="text-danger">*</span></label>
                            <input type="text" name="nama_kegiatan" class="form-control" 
                                   placeholder="Contoh: Santunan 100 Anak Yatim & Dhuafa Ranting Brondong" 
                                   value="{{ old('nama_kegiatan') }}" required>
                        </div>

                        <!-- Pilar Program -->
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-dark">Pilar Program NU Care <span class="text-danger">*</span></label>
                            <select name="pilar_program_id" class="form-control" required>
                                <option value="">-- Pilih 5 Pilar Program --</option>
                                @foreach($pilars as $p)
                                    <option value="{{ $p->id }}" {{ old('pilar_program_id') == $p->id ? 'selected' : '' }}>
                                        [{{ $p->kode }}] {{ $p->nama_pilar }} ({{ $p->target_persentase }}%)
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tingkatan & Wilayah -->
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-dark">Tingkatan Pengusul <span class="text-danger">*</span></label>
                            <select name="tingkat" id="tingkatSelect" class="form-control" required>
                                <option value="ranting" {{ old('tingkat', $defaultTingkat) == 'ranting' ? 'selected' : '' }}>Tingkat Ranting (Desa)</option>
                                <option value="mwc" {{ old('tingkat', $defaultTingkat) == 'mwc' ? 'selected' : '' }}>Tingkat MWC (Kecamatan)</option>
                                <option value="pc" {{ old('tingkat', $defaultTingkat) == 'pc' ? 'selected' : '' }}>Tingkat PC LAZISNU Lamongan</option>
                            </select>
                        </div>

                        <!-- MWC Dropdown -->
                        <div class="col-md-4 form-group" id="mwcGroup">
                            <label class="font-weight-bold small text-dark">Wilayah MWC NU</label>
                            <select name="mwc_id" class="form-control">
                                <option value="">-- Pilih MWC NU --</option>
                                @foreach($mwcs as $mwc)
                                    <option value="{{ $mwc->id }}" {{ (old('mwc_id') == $mwc->id || auth()->user()?->mwc_id == $mwc->id) ? 'selected' : '' }}>
                                        [{{ $mwc->code }}] {{ $mwc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Tahun Anggaran -->
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-dark">Tahun Anggaran <span class="text-danger">*</span></label>
                            <input type="number" name="tahun_anggaran" class="form-control" 
                                   value="{{ old('tahun_anggaran', date('Y')) }}" required>
                        </div>

                        <!-- Rencana Anggaran (RAB) -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">Rencana Anggaran Biaya (RAB) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold">Rp</span>
                                </div>
                                <input type="number" name="rencana_anggaran" class="form-control font-weight-bold text-success" 
                                       placeholder="Contoh: 5000000" value="{{ old('rencana_anggaran') }}" required min="10000">
                            </div>
                            <small class="text-muted">Total pagu biaya yang dibutuhkan untuk menyelesaikan kegiatan ini.</small>
                        </div>

                        <!-- Target Penerima Manfaat -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">Target Penerima Manfaat</label>
                            <input type="text" name="target_penerima_manfaat" class="form-control" 
                                   placeholder="Contoh: 50 Anak Yatim, 20 Guru Ngaji, Warga Dhuafa" 
                                   value="{{ old('target_penerima_manfaat') }}">
                        </div>

                        <!-- Estimasi Jadwal Pelaksanaan -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">Estimasi Tanggal Mulai</label>
                            <input type="date" name="tanggal_mulai" class="form-control" value="{{ old('tanggal_mulai') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">Estimasi Tanggal Selesai</label>
                            <input type="date" name="tanggal_selesai" class="form-control" value="{{ old('tanggal_selesai') }}">
                        </div>

                        <!-- Deskripsi / Rincian Kegiatan -->
                        <div class="col-12 form-group">
                            <label class="font-weight-bold small text-dark">Deskripsi Rincian Pelaksanaan & Peruntukan Dana</label>
                            <textarea name="deskripsi_kegiatan" rows="4" class="form-control" 
                                      placeholder="Jelaskan latar belakang, mekanisme penyaluran, dan rincian alokasi kebutuhan dana...">{{ old('deskripsi_kegiatan') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('proker.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Simpan Sebagai Draft
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
