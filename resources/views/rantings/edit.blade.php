@extends('layouts.adminlte')

@section('title', 'Edit Ranting - ' . $ranting->code)
@section('page_title', 'Edit Profil & Susunan Pengurus Ranting NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('rantings.index') }}">Master Ranting</a></li>
    <li class="breadcrumb-item"><a href="{{ route('rantings.show', $ranting->id) }}">{{ $ranting->code }}</a></li>
    <li class="breadcrumb-item active">Edit</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10 col-12">
        <form action="{{ route('rantings.update', $ranting->id) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- 1. IDENTITAS WILAYAH RANTING -->
            <div class="card card-outline card-warning shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-edit mr-2 text-warning"></i> Identitas Ranting NU: {{ $ranting->name }} ({{ $ranting->code }})
                    </h3>
                    <a href="{{ route('rantings.show', $ranting->id) }}" class="btn btn-outline-info btn-xs font-weight-bold">
                        <i class="fas fa-eye mr-1"></i> Lihat Detail
                    </a>
                </div>
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

                    <div class="form-group mb-0">
                        <label for="address" class="font-weight-bold">Alamat Sekretariat</label>
                        <textarea name="address" id="address" rows="2" class="form-control">{{ old('address', $ranting->address) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- 2. SUSUNAN PENGURUS RANTING & KONTAK WHATSAPP -->
            <div class="card card-outline card-info shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-info mb-0">
                        <i class="fas fa-users-cog mr-2"></i> Susunan Pengurus Ranting & Kontak WhatsApp
                    </h3>
                    <span class="badge badge-light border text-muted">
                        <i class="fab fa-whatsapp text-success mr-1"></i> Penandatangan Dokumen BKU/SPJ & wa.me
                    </span>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border small text-muted mb-3">
                        <i class="fas fa-info-circle mr-1 text-info"></i>
                        Nomor WhatsApp otomatis diformat ke format internasional (misal <code>08123...</code> otomatis menjadi <code>628123...</code>). Data pengurus ini otomatis menjadi <strong>nama penandatangan resmi</strong> pada dokumen cetak PDF (Laporan BKU & SPJ) serta memfasilitasi komunikasi langsung bagi pengurus PC & MWC.
                    </div>

                    <div class="row">
                        <!-- 1. Ketua Tanfidziyah Ranting NU -->
                        <div class="col-md-4 mb-3 p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="font-weight-bold text-success mb-0">
                                    <i class="fas fa-user-tie mr-1"></i> 1. Ketua Tanfidziyah NU
                                </h6>
                                @if($ranting->wa_ketua_ranting)
                                    <a href="https://wa.me/{{ $ranting->wa_ketua_ranting }}" target="_blank" class="badge badge-success px-2 py-1">
                                        <i class="fab fa-whatsapp mr-1"></i> Chat
                                    </a>
                                @endif
                            </div>
                            <small class="text-muted d-block mb-2">Penandatangan: <em>Mengetahui (Tengah)</em></small>
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Nama Lengkap & Gelar</label>
                                <input type="text" name="nama_ketua_ranting" class="form-control form-control-sm" placeholder="Contoh: K.H. Abdul Muhaimin" value="{{ old('nama_ketua_ranting', $ranting->nama_ketua_ranting) }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small font-weight-bold">WhatsApp</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                    </div>
                                    <input type="text" name="wa_ketua_ranting" class="form-control" placeholder="081234567890" value="{{ old('wa_ketua_ranting', $ranting->wa_ketua_ranting) }}">
                                </div>
                            </div>
                        </div>

                        <!-- 2. Ketua UPZIS / JPZIS Ranting -->
                        <div class="col-md-4 mb-3 p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="font-weight-bold text-primary mb-0">
                                    <i class="fas fa-hand-holding-heart mr-1"></i> 2. Ketua UPZIS / JPZIS
                                </h6>
                                @if($ranting->wa_ketua_upzis)
                                    <a href="https://wa.me/{{ $ranting->wa_ketua_upzis }}" target="_blank" class="badge badge-success px-2 py-1">
                                        <i class="fab fa-whatsapp mr-1"></i> Chat
                                    </a>
                                @endif
                            </div>
                            <small class="text-muted d-block mb-2">Penandatangan: <em>Kiri Bawah</em></small>
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Nama Lengkap & Gelar</label>
                                <input type="text" name="nama_ketua_upzis" class="form-control form-control-sm" placeholder="Contoh: H. M. Zainuri" value="{{ old('nama_ketua_upzis', $ranting->nama_ketua_upzis) }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small font-weight-bold">WhatsApp</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                    </div>
                                    <input type="text" name="wa_ketua_upzis" class="form-control" placeholder="081234567890" value="{{ old('wa_ketua_upzis', $ranting->wa_ketua_upzis) }}">
                                </div>
                            </div>
                        </div>

                        <!-- 3. Bendahara UPZIS Ranting -->
                        <div class="col-md-4 mb-3 p-3 bg-light rounded border">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="font-weight-bold text-dark mb-0">
                                    <i class="fas fa-coins mr-1 text-warning"></i> 3. Bendahara UPZIS
                                </h6>
                                @if($ranting->wa_bendahara)
                                    <a href="https://wa.me/{{ $ranting->wa_bendahara }}" target="_blank" class="badge badge-success px-2 py-1">
                                        <i class="fab fa-whatsapp mr-1"></i> Chat
                                    </a>
                                @endif
                            </div>
                            <small class="text-muted d-block mb-2">Penandatangan: <em>Kanan Bawah</em></small>
                            <div class="form-group mb-2">
                                <label class="small font-weight-bold">Nama Lengkap</label>
                                <input type="text" name="nama_bendahara" class="form-control form-control-sm" placeholder="Contoh: Achmad Syukron, S.E" value="{{ old('nama_bendahara', $ranting->nama_bendahara) }}">
                            </div>
                            <div class="form-group mb-0">
                                <label class="small font-weight-bold">WhatsApp</label>
                                <div class="input-group input-group-sm">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                    </div>
                                    <input type="text" name="wa_bendahara" class="form-control" placeholder="081234567890" value="{{ old('wa_bendahara', $ranting->wa_bendahara) }}">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('rantings.show', $ranting->id) }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal / Kembali
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4 shadow-sm">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan Data Ranting
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
