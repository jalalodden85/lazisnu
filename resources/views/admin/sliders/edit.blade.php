@extends('layouts.adminlte')

@section('title', 'Edit Banner Slider')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">Edit Banner Slider</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.sliders.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-success shadow-sm">
            <form action="{{ route('admin.sliders.update', $slider->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Judul Utama Banner <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $slider->title) }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Urutan Tampil (Sort Order)</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $slider->sort_order) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Sub-judul / Tagline Pendukung</label>
                        <textarea name="tagline" class="form-control" rows="2">{{ old('tagline', $slider->tagline) }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Teks Tombol Aksi</label>
                            <input type="text" name="button_text" class="form-control" value="{{ old('button_text', $slider->button_text) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>URL / Target Link Tombol</label>
                            <input type="text" name="button_url" class="form-control" value="{{ old('button_url', $slider->button_url) }}">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>URL Gambar Banner</label>
                            <input type="text" name="image_desktop" class="form-control" value="{{ old('image_desktop', $slider->image_desktop) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Ganti File Gambar</label>
                            <input type="file" name="image_file" class="form-control-file border p-1 rounded w-100" accept="image/*">
                        </div>
                    </div>

                    <div class="form-check mt-2">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="checkActive" {{ $slider->is_active ? 'checked' : '' }}>
                        <label class="form-check-label font-weight-bold" for="checkActive">Tampilkan Banner di Carousel Halaman Depan</label>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Perbarui Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
