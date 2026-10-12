@extends('layouts.adminlte')

@section('title', 'Tambah MWC Baru')
@section('page_title', 'Form Tambah Master Data MWC')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('mwcs.index') }}">Master MWC</a></li>
    <li class="breadcrumb-item active">Tambah MWC</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-12">
        <form action="{{ route('mwcs.store') }}" method="POST">
            @csrf

            <!-- 1. IDENTITAS WILAYAH MWC -->
            <div class="card card-outline card-success shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h3 class="card-title font-weight-bold text-success">
                        <i class="fas fa-map-marked-alt mr-2"></i> Identitas Wilayah MWC
                    </h3>
                </div>
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

                    <div class="form-group mb-0">
                        <label for="address" class="font-weight-bold">Alamat Kantor MWC</label>
                        <textarea name="address" id="address" rows="2" class="form-control @error('address') is-invalid @enderror" placeholder="Jl. Raya...">{{ old('address') }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 2. SUSUNAN PENGURUS MWC & KONTAK WHATSAPP -->
            <div class="card card-outline card-info shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-info">
                        <i class="fas fa-users-cog mr-2"></i> Susunan Pengurus MWC & Kontak WhatsApp
                    </h3>
                    <span class="badge badge-light border text-muted">
                        <i class="fab fa-whatsapp text-success mr-1"></i> Format otomatis wa.me
                    </span>
                </div>
                <div class="card-body">
                    <p class="small text-muted mb-3">
                        <i class="fas fa-info-circle mr-1 text-info"></i>
                        Data nomor WhatsApp otomatis diformat ke format internasional (misal <code>0812...</code> diubah menjadi <code>62812...</code>). Kontak ini digunakan untuk otomatisasi nama tanda tangan laporan PDF serta akses komunikasi cepat.
                    </p>

                    <!-- Row 1: Ketua Tanfidziyah & Ketua LAZISNU -->
                    <div class="row">
                        <div class="col-md-6 mb-3 p-3 bg-light rounded border">
                            <h6 class="font-weight-bold text-success mb-2">
                                <i class="fas fa-user-tie mr-1"></i> 1. Ketua Tanfidziyah MWC NU
                            </h6>
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Nama Lengkap & Gelar</label>
                                <input type="text" name="nama_ketua_mwc" class="form-control form-control-sm" placeholder="Contoh: K.H. Ahmad Fauzi, S.Pd.I" value="{{ old('nama_ketua_mwc') }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small font-weight-bold">Nomor WhatsApp Aktif</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                    </div>
                                    <input type="text" name="wa_ketua_mwc" class="form-control" placeholder="081234567890" value="{{ old('wa_ketua_mwc') }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3 p-3 bg-light rounded border">
                            <h6 class="font-weight-bold text-primary mb-2">
                                <i class="fas fa-hand-holding-heart mr-1"></i> 2. Ketua LAZISNU MWC
                            </h6>
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Nama Lengkap & Gelar</label>
                                <input type="text" name="nama_ketua_lazisnu" class="form-control form-control-sm" placeholder="Contoh: Ust. M. Sholihin, M.Pd" value="{{ old('nama_ketua_lazisnu') }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small font-weight-bold">Nomor WhatsApp Aktif</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                    </div>
                                    <input type="text" name="wa_ketua_lazisnu" class="form-control" placeholder="081234567890" value="{{ old('wa_ketua_lazisnu') }}">
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Row 2: Manager UPZIS, Sekretaris, Bendahara -->
                    <div class="row">
                        <div class="col-md-4 mb-3 p-3 bg-light rounded border">
                            <h6 class="font-weight-bold text-info mb-2">
                                <i class="fas fa-briefcase mr-1"></i> 3. Manager UPZIS
                            </h6>
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Nama Lengkap</label>
                                <input type="text" name="nama_manager_upzis" class="form-control form-control-sm" placeholder="Nama Manager" value="{{ old('nama_manager_upzis') }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small font-weight-bold">WhatsApp</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                    </div>
                                    <input type="text" name="wa_manager_upzis" class="form-control" placeholder="08xxxxxxxxxx" value="{{ old('wa_manager_upzis') }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3 p-3 bg-light rounded border">
                            <h6 class="font-weight-bold text-secondary mb-2">
                                <i class="fas fa-file-signature mr-1"></i> 4. Sekretaris UPZIS
                            </h6>
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Nama Lengkap</label>
                                <input type="text" name="nama_sekretaris" class="form-control form-control-sm" placeholder="Nama Sekretaris" value="{{ old('nama_sekretaris') }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small font-weight-bold">WhatsApp</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                    </div>
                                    <input type="text" name="wa_sekretaris" class="form-control" placeholder="08xxxxxxxxxx" value="{{ old('wa_sekretaris') }}">
                                </div>
                            </div>
                        </div>

                        <div class="col-md-4 mb-3 p-3 bg-light rounded border">
                            <h6 class="font-weight-bold text-dark mb-2">
                                <i class="fas fa-coins mr-1 text-warning"></i> 5. Bendahara UPZIS
                            </h6>
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Nama Lengkap</label>
                                <input type="text" name="nama_bendahara" class="form-control form-control-sm" placeholder="Nama Bendahara" value="{{ old('nama_bendahara') }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small font-weight-bold">WhatsApp</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                    </div>
                                    <input type="text" name="wa_bendahara" class="form-control" placeholder="08xxxxxxxxxx" value="{{ old('wa_bendahara') }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('mwcs.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar MWC
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4 shadow-sm">
                        <i class="fas fa-save mr-1"></i> Simpan Data MWC
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
