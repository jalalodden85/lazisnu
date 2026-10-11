@extends('layouts.adminlte')

@section('title', 'Tulis Berita / Kabar Pentasyarufan Baru')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">Tulis Kabar / Berita Baru</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.articles.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-success shadow-sm">
            <form action="{{ route('admin.articles.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Judul Kabar / Artikel <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control @error('title') is-invalid @enderror" value="{{ old('title') }}" placeholder="Contoh: LAZISNU Salurkan Paket Sembako di Ranting Sukomulyo" required>
                            @error('title')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Tipe Konten <span class="text-danger">*</span></label>
                            <select name="post_type" class="form-control" required>
                                <option value="pentasyarufan" {{ old('post_type') == 'pentasyarufan' ? 'selected' : '' }}>Penyaluran Bantuan (Aksi Lapangan)</option>
                                <option value="berita" {{ old('post_type') == 'berita' ? 'selected' : '' }}>Kabar Cabang & Ranting</option>
                                <option value="edukasi_zis" {{ old('post_type') == 'edukasi_zis' ? 'selected' : '' }}>Edukasi Fiqih Zakat / Infaq</option>
                                <option value="pengumuman" {{ old('post_type') == 'pengumuman' ? 'selected' : '' }}>Pengumuman Resmi</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Pilar NU Care Terkait (Opsional)</label>
                            <select name="category_id" class="form-control">
                                <option value="">-- Pilih Pilar --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Khusus MWC Terkait (Opsional)</label>
                            <select name="mwc_id" class="form-control">
                                <option value="">-- Umum / Cabang --</option>
                                @foreach($mwcs as $mwc)
                                    <option value="{{ $mwc->id }}" {{ old('mwc_id') == $mwc->id ? 'selected' : '' }}>{{ $mwc->code }} - {{ $mwc->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Status Publikasi</label>
                            <select name="status" class="form-control">
                                <option value="published" {{ old('status') == 'published' ? 'selected' : '' }}>Langsung Terbit (Published)</option>
                                <option value="draft" {{ old('status') == 'draft' ? 'selected' : '' }}>Simpan sebagai Draft</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ringkasan / Excerpt (Muncul di Halaman Depan) <span class="text-danger">*</span></label>
                        <textarea name="excerpt" class="form-control" rows="2" maxlength="400" placeholder="Ringkasan inti berita dalam 1-2 kalimat..." required>{{ old('excerpt') }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Isi Lengkap Berita (Mendukung Format HTML/Paragraf) <span class="text-danger">*</span></label>
                        <textarea name="content" class="form-control" rows="8" placeholder="Tuliskan berita lengkap di sini..." required>{{ old('content') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>URL Gambar Thumbnail</label>
                            <input type="text" name="thumbnail_image" class="form-control" value="{{ old('thumbnail_image') }}" placeholder="https://images.unsplash.com/...">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Atau Unggah File Foto</label>
                            <input type="file" name="thumbnail_file" class="form-control-file border p-1 rounded w-100" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-paper-plane mr-1"></i> Publikasikan Kabar
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
