@extends('layouts.adminlte')

@section('title', 'Tambah Banner Slider Baru')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">Tambah Banner Slider</h1>
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
            <form action="{{ route('admin.sliders.store') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Judul Utama Banner <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title') }}" placeholder="Contoh: Gerakan Koin NU: Dari Umat untuk Umat" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Urutan Tampil (Sort Order)</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', 1) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Sub-judul / Tagline Pendukung</label>
                        <textarea name="tagline" class="form-control" rows="2" placeholder="Tuliskan pesan ajakan singkat...">{{ old('tagline') }}</textarea>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Teks Tombol Aksi</label>
                            <input type="text" name="button_text" class="form-control" value="{{ old('button_text', 'Donasi Sekarang') }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>URL / Target Link Tombol</label>
                            <input type="text" name="button_url" class="form-control" value="{{ old('button_url', '#donasi-cepat') }}" placeholder="#donasi-cepat atau /program">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>URL Gambar Banner (Resolusi Rekomendasi: 1200x600 px)</label>
                            <input type="text" name="image_desktop" class="form-control" value="{{ old('image_desktop') }}" placeholder="https://images.unsplash.com/...">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Atau Unggah File Gambar</label>
                            <input type="file" name="image_file" class="form-control-file border p-1 rounded w-100" accept="image/*">
                        </div>
                    </div>

                    <div class="form-check mt-2">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="checkActive" checked>
                        <label class="form-check-label font-weight-bold" for="checkActive">Tampilkan Banner di Carousel Halaman Depan</label>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Simpan Banner
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
