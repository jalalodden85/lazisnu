@extends('layouts.adminlte')

@section('title', 'Edit Berita / Kabar Pentasyarufan')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">Edit Kabar / Berita</h1>
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
            <form action="{{ route('admin.articles.update', $article->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Judul Kabar / Artikel <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $article->title) }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Tipe Konten <span class="text-danger">*</span></label>
                            <select name="post_type" class="form-control" required>
                                <option value="pentasyarufan" {{ old('post_type', $article->post_type) == 'pentasyarufan' ? 'selected' : '' }}>Penyaluran Bantuan (Aksi Lapangan)</option>
                                <option value="berita" {{ old('post_type', $article->post_type) == 'berita' ? 'selected' : '' }}>Kabar Cabang & Ranting</option>
                                <option value="edukasi_zis" {{ old('post_type', $article->post_type) == 'edukasi_zis' ? 'selected' : '' }}>Edukasi Fiqih Zakat / Infaq</option>
                                <option value="pengumuman" {{ old('post_type', $article->post_type) == 'pengumuman' ? 'selected' : '' }}>Pengumuman Resmi</option>
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Slug URL <span class="text-danger">*</span></label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug', $article->slug) }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Pilar NU Care Terkait</label>
                            <select name="category_id" class="form-control">
                                <option value="">-- Pilih Pilar --</option>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $article->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Status Publikasi</label>
                            <select name="status" class="form-control">
                                <option value="published" {{ old('status', $article->status) == 'published' ? 'selected' : '' }}>Terbit (Published)</option>
                                <option value="draft" {{ old('status', $article->status) == 'draft' ? 'selected' : '' }}>Draft</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ringkasan / Excerpt</label>
                        <textarea name="excerpt" class="form-control" rows="2" maxlength="400" required>{{ old('excerpt', $article->excerpt) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Isi Lengkap Berita</label>
                        <textarea name="content" class="form-control" rows="8" required>{{ old('content', $article->content) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>URL Gambar Thumbnail</label>
                            <input type="text" name="thumbnail_image" class="form-control" value="{{ old('thumbnail_image', $article->thumbnail_image) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Ganti File Foto</label>
                            <input type="file" name="thumbnail_file" class="form-control-file border p-1 rounded w-100" accept="image/*">
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Perbarui Artikel
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
