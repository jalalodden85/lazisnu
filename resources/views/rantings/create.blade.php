@extends('layouts.adminlte')

@section('title', 'Tambah Ranting NU')
@section('page_title', 'Form Registrasi Master Data Ranting NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('rantings.index') }}">Master Ranting</a></li>
    <li class="breadcrumb-item active">Tambah Ranting</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-12">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-success">
                    <i class="fas fa-plus-circle mr-2"></i> Pendaftaran Ranting Baru
                </h3>
            </div>
            <form action="{{ route('rantings.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="callout callout-info py-2 mb-4">
                        <h6 class="font-weight-bold text-dark mb-1">
                            <i class="fas fa-magic mr-1 text-primary"></i> Penomoran Otomatis Kode Ranting:
                        </h6>
                        <p class="text-xs mb-0 text-muted">
                            Kode Ranting disusun dari <strong>Kode MWC</strong> + <strong>R</strong> + <strong>Nomor 2 Digit</strong>. 
                            Misal MWC NU Brondong (<code>M01</code>) no 1 (Ranting Brengkok) &rarr; <strong class="text-success">M01R01</strong>.
                        </p>
                    </div>

                    <div class="row">
                        <!-- Pilih MWC Induk -->
                        <div class="col-md-7 form-group">
                            <label for="mwc_id" class="font-weight-bold">
                                MWC Induk (Kecamatan) <span class="text-danger">*</span>
                            </label>
                            <select name="mwc_id" id="mwc_id" class="form-control @error('mwc_id') is-invalid @enderror" required>
                                <option value="">-- Pilih MWC --</option>
                                @foreach($mwcs as $m)
                                    <option value="{{ $m->id }}" 
                                            data-code="{{ $m->code }}" 
                                            {{ (old('mwc_id', $selectedMwcId) == $m->id) ? 'selected' : '' }}>
                                        [{{ $m->code }}] {{ $m->name }} (Kec. {{ $m->kecamatan }})
                                    </option>
                                @endforeach
                            </select>
                            @error('mwc_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Nomor Urut Ranting di MWC -->
                        <div class="col-md-5 form-group">
                            <label for="nomor_urut" class="font-weight-bold">
                                No. Urut di MWC (Opsional)
                            </label>
                            <input type="number" 
                                   min="1" 
                                   name="nomor_urut" 
                                   id="nomor_urut" 
                                   class="form-control" 
                                   placeholder="Contoh: 1 (Otomatis jika kosong)" 
                                   value="{{ old('nomor_urut') }}">
                            <small class="form-text text-muted">Biarkan kosong untuk urutan berikutnya.</small>
                        </div>
                    </div>

                    <!-- Preview Hasil Kode Ranting -->
                    <div class="form-group bg-light p-3 border rounded mb-3">
                        <label class="font-weight-bold text-muted mb-1">
                            <i class="fas fa-barcode mr-1 text-success"></i> Hasil Generate Kode Ranting NU:
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold bg-success text-white">KODE</span>
                            </div>
                            <input type="text" 
                                   name="code" 
                                   id="preview_code" 
                                   class="form-control font-weight-bold @error('code') is-invalid @enderror" 
                                   style="font-size: 1.15rem; letter-spacing: 1px;" 
                                   value="{{ old('code', $previewCode) }}" 
                                   placeholder="Pilih MWC untuk generate kode...">
                            @error('code')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="form-text text-muted">Kode ini otomatis disusun atau dapat disesuaikan jika diperlukan.</small>
                    </div>

                    <div class="row">
                        <!-- Nama Ranting -->
                        <div class="col-md-6 form-group">
                            <label for="name" class="font-weight-bold">
                                Nama Ranting NU <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   name="name" 
                                   id="name" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   placeholder="Contoh: Ranting Brengkok" 
                                   value="{{ old('name') }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Desa / Kelurahan -->
                        <div class="col-md-6 form-group">
                            <label for="desa" class="font-weight-bold">Nama Desa / Kelurahan</label>
                            <input type="text" 
                                   name="desa" 
                                   id="desa" 
                                   class="form-control @error('desa') is-invalid @enderror" 
                                   placeholder="Contoh: Desa Brengkok" 
                                   value="{{ old('desa') }}">
                            @error('desa')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Nama Penanggung Jawab (PJ) / Petugas Ranting -->
                    <div class="form-group">
                        <label for="pj_name" class="font-weight-bold">
                            Nama Penanggung Jawab (PJ) / Petugas Ranting
                        </label>
                        <input type="text" 
                               name="pj_name" 
                               id="pj_name" 
                               class="form-control @error('pj_name') is-invalid @enderror" 
                               placeholder="Contoh: Petugas Ranting Brengkok" 
                               value="{{ old('pj_name') }}">
                        @error('pj_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Alamat / Sekretariat -->
                    <div class="form-group mb-4">
                        <label for="address" class="font-weight-bold">Alamat Sekretariat / Kantor Ranting (Opsional)</label>
                        <textarea name="address" 
                                  id="address" 
                                  rows="2" 
                                  class="form-control @error('address') is-invalid @enderror" 
                                  placeholder="Contoh: Komplek Masjid Jami Baiturrahim, RT 01 / RW 01">{{ old('address') }}</textarea>
                    </div>

                    <!-- SUSUNAN PENGURUS RANTING & KONTAK WHATSAPP -->
                    <div class="border rounded p-3 bg-white mb-2 shadow-xs">
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <h6 class="font-weight-bold text-info mb-0">
                                <i class="fas fa-users-cog mr-1"></i> Susunan Pengurus Ranting & Kontak WhatsApp
                            </h6>
                            <span class="badge badge-light border text-muted">
                                <i class="fab fa-whatsapp text-success mr-1"></i> Format otomatis wa.me
                            </span>
                        </div>
                        <p class="small text-muted mb-3">
                            <i class="fas fa-info-circle mr-1 text-info"></i>
                            Nomor WhatsApp otomatis diformat ke format internasional (misal <code>08123...</code> menjadi <code>628123...</code>). Digunakan untuk otomatisasi nama penandatangan laporan PDF dan komunikasi langsung.
                        </p>

                        <div class="row">
                            <!-- 1. Ketua Tanfidziyah Ranting NU -->
                            <div class="col-md-4 mb-3 p-3 bg-light rounded border">
                                <h6 class="font-weight-bold text-success mb-1 small text-uppercase">
                                    <i class="fas fa-user-tie mr-1"></i> 1. Ketua Tanfidziyah NU
                                </h6>
                                <small class="text-muted d-block mb-2">Penandatangan: <em>Mengetahui</em></small>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Nama Lengkap & Gelar</label>
                                    <input type="text" name="nama_ketua_ranting" class="form-control form-control-sm" placeholder="Nama Ketua Tanfidziyah" value="{{ old('nama_ketua_ranting') }}">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold">WhatsApp</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                        </div>
                                        <input type="text" name="wa_ketua_ranting" class="form-control" placeholder="081234567890" value="{{ old('wa_ketua_ranting') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- 2. Ketua UPZIS / JPZIS Ranting -->
                            <div class="col-md-4 mb-3 p-3 bg-light rounded border">
                                <h6 class="font-weight-bold text-primary mb-1 small text-uppercase">
                                    <i class="fas fa-hand-holding-heart mr-1"></i> 2. Ketua UPZIS / JPZIS
                                </h6>
                                <small class="text-muted d-block mb-2">Penandatangan: <em>Kiri Bawah</em></small>
                                <div class="form-group mb-2">
                                    <label class="small font-weight-bold">Nama Lengkap & Gelar</label>
                                    <input type="text" name="nama_ketua_upzis" class="form-control form-control-sm" placeholder="Nama Ketua UPZIS" value="{{ old('nama_ketua_upzis') }}">
                                </div>
                                <div class="form-group mb-0">
                                    <label class="small font-weight-bold">WhatsApp</label>
                                    <div class="input-group input-group-sm">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white text-success"><i class="fab fa-whatsapp"></i></span>
                                        </div>
                                        <input type="text" name="wa_ketua_upzis" class="form-control" placeholder="081234567890" value="{{ old('wa_ketua_upzis') }}">
                                    </div>
                                </div>
                            </div>

                            <!-- 3. Bendahara UPZIS Ranting -->
                            <div class="col-md-4 mb-3 p-3 bg-light rounded border">
                                <h6 class="font-weight-bold text-dark mb-1 small text-uppercase">
                                    <i class="fas fa-coins mr-1 text-warning"></i> 3. Bendahara UPZIS
                                </h6>
                                <small class="text-muted d-block mb-2">Penandatangan: <em>Kanan Bawah</em></small>
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
                                        <input type="text" name="wa_bendahara" class="form-control" placeholder="081234567890" value="{{ old('wa_bendahara') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('rantings.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Simpan Data Ranting
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const mwcSelect = document.getElementById('mwc_id');
    const nomorInput = document.getElementById('nomor_urut');
    const previewCodeInput = document.getElementById('preview_code');

    function updatePreviewCode() {
        const mwcId = mwcSelect.value;
        const number = nomorInput.value;

        if (!mwcId) {
            previewCodeInput.value = '';
            return;
        }

        fetch(`{{ route('rantings.get-next-code') }}?mwc_id=${mwcId}&number=${number}`)
            .then(res => res.json())
            .then(data => {
                if (data.code) {
                    previewCodeInput.value = data.code;
                }
            })
            .catch(err => console.error(err));
    }

    if (mwcSelect) {
        mwcSelect.addEventListener('change', updatePreviewCode);
    }
    if (nomorInput) {
        nomorInput.addEventListener('input', updatePreviewCode);
    }
});
</script>
@endpush
