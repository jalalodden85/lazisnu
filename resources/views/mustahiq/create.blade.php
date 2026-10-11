@extends('layouts.adminlte')

@section('title', 'Pendaftaran Data Mustahiq Baru - LAZISNU Lamongan')
@section('page_title', 'Tambah Data Mustahiq')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('mustahiqs.index') }}">Data Mustahiq</a></li>
    <li class="breadcrumb-item active">Tambah Baru</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <form action="{{ route('mustahiqs.store') }}" method="POST" enctype="multipart/form-data">
            @csrf

            <div class="card card-outline card-success shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-user-plus text-success mr-2"></i>Formulir Pendataan Mustahiq (Penerima Manfaat)
                    </h3>
                    <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-map-pin mr-1"></i> Berbasis Ranting Desa
                    </span>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Validasi Formulir Gagal:</h6>
                            <ul class="mb-0 pl-3 small">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <!-- SEKSI 1: IDENTITAS KEPENDUDUKAN & WILAYAH -->
                    <h6 class="font-weight-bold text-success border-bottom pb-2 mb-3">
                        <i class="fas fa-id-card text-success mr-1"></i> 1. Identitas Kependudukan & Wilayah Ranting
                    </h6>

                    <div class="row">
                        <!-- NIK -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">
                                NIK (Nomor Induk Kependudukan) <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-id-badge text-secondary"></i></span>
                                </div>
                                <input type="text" name="nik" class="form-control font-weight-bold @error('nik') is-invalid @enderror" 
                                       placeholder="16 digit NIK KTP..." value="{{ old('nik') }}" maxlength="16" minlength="16" required>
                            </div>
                            <small class="text-muted">Wajib 16 digit angka. NIK bersifat unik nasional untuk mencegah data ganda.</small>
                        </div>

                        <!-- Nama Lengkap -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">
                                Nama Lengkap Sesuai KTP <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama" class="form-control font-weight-bold @error('nama') is-invalid @enderror" 
                                   placeholder="Contoh: Mbah Suparmi" value="{{ old('nama') }}" required>
                        </div>

                        <!-- Jenis Kelamin -->
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold small text-dark">
                                Jenis Kelamin <span class="text-danger">*</span>
                            </label>
                            <select name="jenis_kelamin" class="form-control" required>
                                <option value="L" {{ old('jenis_kelamin') === 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                                <option value="P" {{ old('jenis_kelamin') === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                            </select>
                        </div>

                        <!-- Nomor Telepon -->
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold small text-dark">No. HP / WhatsApp</label>
                            <input type="text" name="telepon" class="form-control" placeholder="08xxxxxxxxxx" value="{{ old('telepon') }}">
                        </div>

                        <!-- Penentuan Ranting -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">
                                Asal Ranting (Desa) <span class="text-danger">*</span>
                            </label>
                            @if($user->isRanting())
                                <input type="hidden" name="ranting_id" value="{{ $user->ranting_id }}">
                                <div class="form-control bg-light font-weight-bold text-success d-flex align-items-center">
                                    <i class="fas fa-mosque mr-2"></i> {{ $user->ranting?->name ?? 'Ranting Terdaftar' }} (Kec. {{ $user->mwc?->name ?? '-' }})
                                </div>
                                <small class="text-muted">Terkunci otomatis pada wilayah Ranting Anda.</small>
                            @elseif($user->isMwc())
                                <select name="ranting_id" class="form-control font-weight-bold" required>
                                    <option value="">-- Pilih Ranting di Wilayah Anda --</option>
                                    @foreach($rantings as $r)
                                        <option value="{{ $r->id }}" {{ old('ranting_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                                    @endforeach
                                </select>
                            @else
                                <select name="ranting_id" class="form-control font-weight-bold" required>
                                    <option value="">-- Pilih Ranting Pelaksana --</option>
                                    @foreach($rantings as $r)
                                        <option value="{{ $r->id }}" {{ old('ranting_id') == $r->id ? 'selected' : '' }}>
                                            {{ $r->name }} (MWC {{ $r->mwc?->name }})
                                        </option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <!-- Alamat Lengkap -->
                        <div class="col-md-12 form-group">
                            <label class="font-weight-bold small text-dark">
                                Alamat Lengkap (Dusun, RT/RW, Desa) <span class="text-danger">*</span>
                            </label>
                            <textarea name="alamat" rows="2" class="form-control @error('alamat') is-invalid @enderror" 
                                      placeholder="Contoh: Dusun Krajan RT 02 RW 01 Desa Paciran..." required>{{ old('alamat') }}</textarea>
                        </div>
                    </div>

                    <!-- SEKSI 2: KLASIFIKASI ASNAF & KONDISI SOSIAL -->
                    <h6 class="font-weight-bold text-success border-bottom pb-2 mb-3 mt-3">
                        <i class="fas fa-hand-holding-heart text-success mr-1"></i> 2. Kategori Asnaf & Kondisi Sosial-Ekonomi
                    </h6>

                    <div class="row">
                        <!-- Kategori Asnaf -->
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-dark">
                                Kategori 8 Asnaf ZIS <span class="text-danger">*</span>
                            </label>
                            <select name="asnaf" class="form-control form-control-lg font-weight-bold text-success" required>
                                <option value="fakir" {{ old('asnaf') === 'fakir' ? 'selected' : '' }}>1. Fakir (Sangat Membutuhkan)</option>
                                <option value="miskin" {{ old('asnaf', 'miskin') === 'miskin' ? 'selected' : '' }}>2. Miskin (Penghasilan Tidak Cukup)</option>
                                <option value="amil" {{ old('asnaf') === 'amil' ? 'selected' : '' }}>3. Amil (Pengelola ZIS)</option>
                                <option value="muallaf" {{ old('asnaf') === 'muallaf' ? 'selected' : '' }}>4. Muallaf (Penguatan Iman)</option>
                                <option value="riqab" {{ old('asnaf') === 'riqab' ? 'selected' : '' }}>5. Riqab (Pembebasan)</option>
                                <option value="gharim" {{ old('asnaf') === 'gharim' ? 'selected' : '' }}>6. Gharim (Terlilit Hutang Kebutuhan Pokok)</option>
                                <option value="fisabilillah" {{ old('asnaf') === 'fisabilillah' ? 'selected' : '' }}>7. Fisabilillah (Dakwah & Sosial Keagamaan)</option>
                                <option value="ibnu_sabil" {{ old('asnaf') === 'ibnu_sabil' ? 'selected' : '' }}>8. Ibnu Sabil (Musafir Kehabisan Bekal)</option>
                            </select>
                        </div>

                        <!-- Kondisi Spesifik -->
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-dark">Kondisi Spesifik / Kerentanan</label>
                            <input type="text" name="kondisi_spesifik" class="form-control" 
                                   placeholder="Contoh: Janda lansia, yatim piatu, stroke menahun, disabilitas..." 
                                   value="{{ old('kondisi_spesifik') }}">
                            <small class="text-muted">Faktor kerentanan khusus di lapangan.</small>
                        </div>

                        <!-- Pekerjaan -->
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-dark">Pekerjaan / Sumber Nafkah</label>
                            <input type="text" name="pekerjaan" class="form-control" 
                                   placeholder="Contoh: Buruh tani serabutan, tidak bekerja..." 
                                   value="{{ old('pekerjaan') }}">
                        </div>

                        <!-- Penghasilan Rata-rata -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">Estimasi Penghasilan Rata-rata / Bulan</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="number" name="penghasilan_rata_rata" class="form-control" 
                                       placeholder="Contoh: 300000" value="{{ old('penghasilan_rata_rata', 0) }}" min="0">
                            </div>
                        </div>

                        <!-- Jumlah Tanggungan -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">Jumlah Jiwa Tanggungan</label>
                            <div class="input-group">
                                <input type="number" name="jumlah_tanggungan" class="form-control" 
                                       placeholder="Contoh: 3" value="{{ old('jumlah_tanggungan', 0) }}" min="0">
                                <div class="input-group-append"><span class="input-group-text">Jiwa/Orang</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI 3: DOKUMENTASI LAPANGAN & PRIVASI DATA -->
                    <h6 class="font-weight-bold text-success border-bottom pb-2 mb-3 mt-3">
                        <i class="fas fa-camera text-success mr-1"></i> 3. Bukti Validasi Lapangan (Dokumentasi Privat)
                    </h6>

                    <div class="row align-items-center">
                        <div class="col-md-7 form-group">
                            <label class="font-weight-bold small text-dark">
                                Upload Foto Rumah / Kondisi Mustahiq
                            </label>
                            <input type="file" name="foto_kondisi" id="fotoKondisiInput" class="form-control-file border rounded p-2 bg-light" 
                                   accept="image/jpeg,image/png,image/jpg,image/webp">
                            <small class="text-muted d-block mt-1">
                                <i class="fas fa-lock text-success mr-1"></i> Data Tersimpan di Direktori Privat Aman. Foto tidak dapat diakses publik tanpa login.
                            </small>
                        </div>
                        <div class="col-md-5 text-center">
                            <div class="border rounded p-2 bg-light d-inline-block shadow-xs" style="min-width: 160px; min-height: 120px;">
                                <img id="previewFoto" src="" alt="Preview Foto" class="img-fluid rounded d-none" style="max-height: 140px;">
                                <div id="placeholderFoto" class="text-muted py-4 small">
                                    <i class="fas fa-image fa-2x d-block mb-1 opacity-50"></i> Preview Foto
                                </div>
                            </div>
                        </div>

                        <!-- Catatan Verifikasi -->
                        <div class="col-md-12 form-group mt-2">
                            <label class="font-weight-bold small text-dark">Catatan Verifikasi Lapangan Petugas</label>
                            <textarea name="catatan_verifikasi" rows="2" class="form-control" 
                                      placeholder="Contoh: Telah disurvei oleh pengurus Ranting pada 8 Oktober 2026. Kondisi rumah dinding bambu dan atap bocor.">{{ old('catatan_verifikasi') }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                    <a href="{{ route('mustahiqs.index') }}" class="btn btn-secondary px-4">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success px-5 font-weight-bold shadow-xs">
                        <i class="fas fa-save mr-1"></i> Simpan Data Mustahiq
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('scripts')
<script>
$(document).ready(function() {
    $('#fotoKondisiInput').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function(event) {
                $('#previewFoto').attr('src', event.target.result).removeClass('d-none');
                $('#placeholderFoto').addClass('d-none');
            };
            reader.readAsDataURL(file);
        } else {
            $('#previewFoto').addClass('d-none');
            $('#placeholderFoto').removeClass('d-none');
        }
    });
});
</script>
@endsection
