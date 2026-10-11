@extends('layouts.adminlte')

@section('title', 'Edit Data Mustahiq: ' . $mustahiq->nama . ' - LAZISNU Lamongan')
@section('page_title', 'Perbarui Data Mustahiq')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('mustahiqs.index') }}">Data Mustahiq</a></li>
    <li class="breadcrumb-item active">Edit Data</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-10">
        <form action="{{ route('mustahiqs.update', $mustahiq->id) }}" method="POST" enctype="multipart/form-data">
            @csrf
            @method('PUT')

            <div class="card card-outline card-warning shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-user-edit text-warning mr-2"></i>Perbarui Data Mustahiq: {{ $mustahiq->nama }}
                    </h3>
                    <div>
                        {!! $mustahiq->status_badge !!}
                    </div>
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

                    <!-- SEKSI STATUS KELAYAKAN BANTUAN -->
                    <div class="alert alert-light border mb-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <label class="font-weight-bold small text-dark mb-1">
                                    <i class="fas fa-toggle-on text-primary mr-1"></i> Status Kelayakan Bantuan <span class="text-danger">*</span>
                                </label>
                                <select name="status" id="statusSelect" class="form-control font-weight-bold">
                                    <option value="aktif" {{ old('status', $mustahiq->status) === 'aktif' ? 'selected' : '' }}>
                                        🟢 Aktif (Masih Layak Menerima Bantuan)
                                    </option>
                                    <option value="tidak_aktif" {{ old('status', $mustahiq->status) === 'tidak_aktif' ? 'selected' : '' }}>
                                        ⚪ Tidak Aktif (Sudah Mandiri / Meninggal / Pindah)
                                    </option>
                                </select>
                            </div>
                            <div class="col-md-6" id="alasanContainer">
                                <label class="font-weight-bold small text-dark mb-1">Alasan Tidak Aktif (Jika Non-Aktif)</label>
                                <input type="text" name="alasan_tidak_aktif" class="form-control" 
                                       placeholder="Contoh: Sudah mandiri secara ekonomi / Meninggal dunia" 
                                       value="{{ old('alasan_tidak_aktif', $mustahiq->alasan_tidak_aktif) }}">
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI IDENTITAS KEPENDUDUKAN -->
                    <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3">
                        <i class="fas fa-id-card text-success mr-1"></i> 1. Identitas Kependudukan & Wilayah
                    </h6>

                    <div class="row">
                        <!-- NIK -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">
                                NIK (Nomor Induk Kependudukan) <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nik" class="form-control font-weight-bold" 
                                   value="{{ old('nik', $mustahiq->nik) }}" maxlength="16" minlength="16" required>
                        </div>

                        <!-- Nama Lengkap -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">
                                Nama Lengkap Sesuai KTP <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="nama" class="form-control font-weight-bold" 
                                   value="{{ old('nama', $mustahiq->nama) }}" required>
                        </div>

                        <!-- Jenis Kelamin -->
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold small text-dark">Jenis Kelamin <span class="text-danger">*</span></label>
                            <select name="jenis_kelamin" class="form-control" required>
                                <option value="L" {{ old('jenis_kelamin', $mustahiq->jenis_kelamin) === 'L' ? 'selected' : '' }}>Laki-laki (L)</option>
                                <option value="P" {{ old('jenis_kelamin', $mustahiq->jenis_kelamin) === 'P' ? 'selected' : '' }}>Perempuan (P)</option>
                            </select>
                        </div>

                        <!-- Telepon -->
                        <div class="col-md-3 form-group">
                            <label class="font-weight-bold small text-dark">No. HP / WhatsApp</label>
                            <input type="text" name="telepon" class="form-control" value="{{ old('telepon', $mustahiq->telepon) }}">
                        </div>

                        <!-- Wilayah Ranting (Info Tetap) -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">Wilayah Ranting</label>
                            <div class="form-control bg-light text-muted font-weight-bold">
                                <i class="fas fa-mosque mr-2"></i> {{ $mustahiq->ranting?->name }} (MWC {{ $mustahiq->mwc?->name }})
                            </div>
                        </div>

                        <!-- Alamat Lengkap -->
                        <div class="col-md-12 form-group">
                            <label class="font-weight-bold small text-dark">Alamat Lengkap (Dusun, RT/RW) <span class="text-danger">*</span></label>
                            <textarea name="alamat" rows="2" class="form-control" required>{{ old('alamat', $mustahiq->alamat) }}</textarea>
                        </div>
                    </div>

                    <!-- SEKSI KLASIFIKASI ASNAF -->
                    <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3 mt-3">
                        <i class="fas fa-hand-holding-heart text-success mr-1"></i> 2. Kategori Asnaf & Kondisi Sosial-Ekonomi
                    </h6>

                    <div class="row">
                        <!-- Asnaf -->
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-dark">Kategori 8 Asnaf <span class="text-danger">*</span></label>
                            <select name="asnaf" class="form-control font-weight-bold text-success" required>
                                <option value="fakir" {{ old('asnaf', $mustahiq->asnaf) === 'fakir' ? 'selected' : '' }}>1. Fakir</option>
                                <option value="miskin" {{ old('asnaf', $mustahiq->asnaf) === 'miskin' ? 'selected' : '' }}>2. Miskin</option>
                                <option value="amil" {{ old('asnaf', $mustahiq->asnaf) === 'amil' ? 'selected' : '' }}>3. Amil</option>
                                <option value="muallaf" {{ old('asnaf', $mustahiq->asnaf) === 'muallaf' ? 'selected' : '' }}>4. Muallaf</option>
                                <option value="riqab" {{ old('asnaf', $mustahiq->asnaf) === 'riqab' ? 'selected' : '' }}>5. Riqab</option>
                                <option value="gharim" {{ old('asnaf', $mustahiq->asnaf) === 'gharim' ? 'selected' : '' }}>6. Gharim</option>
                                <option value="fisabilillah" {{ old('asnaf', $mustahiq->asnaf) === 'fisabilillah' ? 'selected' : '' }}>7. Fisabilillah</option>
                                <option value="ibnu_sabil" {{ old('asnaf', $mustahiq->asnaf) === 'ibnu_sabil' ? 'selected' : '' }}>8. Ibnu Sabil</option>
                            </select>
                        </div>

                        <!-- Kondisi Spesifik -->
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-dark">Kondisi Spesifik / Kerentanan</label>
                            <input type="text" name="kondisi_spesifik" class="form-control" 
                                   value="{{ old('kondisi_spesifik', $mustahiq->kondisi_spesifik) }}">
                        </div>

                        <!-- Pekerjaan -->
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold small text-dark">Pekerjaan</label>
                            <input type="text" name="pekerjaan" class="form-control" 
                                   value="{{ old('pekerjaan', $mustahiq->pekerjaan) }}">
                        </div>

                        <!-- Penghasilan Rata-rata -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">Penghasilan Rata-rata / Bulan</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="number" name="penghasilan_rata_rata" class="form-control" 
                                       value="{{ old('penghasilan_rata_rata', $mustahiq->penghasilan_rata_rata) }}" min="0">
                            </div>
                        </div>

                        <!-- Jumlah Tanggungan -->
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold small text-dark">Jumlah Jiwa Tanggungan</label>
                            <div class="input-group">
                                <input type="number" name="jumlah_tanggungan" class="form-control" 
                                       value="{{ old('jumlah_tanggungan', $mustahiq->jumlah_tanggungan) }}" min="0">
                                <div class="input-group-append"><span class="input-group-text">Jiwa</span></div>
                            </div>
                        </div>
                    </div>

                    <!-- SEKSI DOKUMENTASI & FOTO -->
                    <h6 class="font-weight-bold text-dark border-bottom pb-2 mb-3 mt-3">
                        <i class="fas fa-camera text-success mr-1"></i> 3. Foto Kondisi Rumah & Verifikasi Lapangan
                    </h6>

                    <div class="row align-items-center">
                        <div class="col-md-7 form-group">
                            <label class="font-weight-bold small text-dark">Ganti Foto Kondisi Rumah (Opsional)</label>
                            <input type="file" name="foto_kondisi" id="fotoKondisiInput" class="form-control-file border rounded p-2 bg-light" 
                                   accept="image/jpeg,image/png,image/jpg,image/webp">
                            <small class="text-muted d-block mt-1">Kosongkan jika tidak ingin mengubah foto kondisi yang tersimpan.</small>
                        </div>
                        <div class="col-md-5 text-center">
                            @if($mustahiq->foto_kondisi)
                                <div class="border rounded p-2 bg-white d-inline-block shadow-xs">
                                    <img id="currentOrNewFoto" src="{{ route('mustahiqs.foto', $mustahiq->id) }}" alt="Foto Rumah" class="img-fluid rounded" style="max-height: 140px;">
                                    <small class="d-block text-muted mt-1">Foto Saat Ini (Privat)</small>
                                </div>
                            @else
                                <div class="border rounded p-2 bg-light d-inline-block shadow-xs">
                                    <img id="currentOrNewFoto" src="" alt="Preview" class="img-fluid rounded d-none" style="max-height: 140px;">
                                    <div id="noFotoText" class="text-muted py-3 small">
                                        <i class="fas fa-image fa-2x d-block mb-1 opacity-50"></i> Belum ada foto tersimpan
                                    </div>
                                </div>
                            @endif
                        </div>

                        <!-- Catatan Verifikasi -->
                        <div class="col-md-12 form-group mt-2">
                            <label class="font-weight-bold small text-dark">Catatan Verifikasi</label>
                            <textarea name="catatan_verifikasi" rows="2" class="form-control">{{ old('catatan_verifikasi', $mustahiq->catatan_verifikasi) }}</textarea>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
                    <a href="{{ route('mustahiqs.show', $mustahiq->id) }}" class="btn btn-secondary px-4">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-warning px-5 font-weight-bold shadow-xs">
                        <i class="fas fa-check mr-1"></i> Perbarui Data Mustahiq
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
                $('#currentOrNewFoto').attr('src', event.target.result).removeClass('d-none');
                $('#noFotoText').addClass('d-none');
            };
            reader.readAsDataURL(file);
        }
    });
});
</script>
@endsection
