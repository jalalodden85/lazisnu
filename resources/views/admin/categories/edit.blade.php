@extends('layouts.adminlte')

@section('title', 'Edit Pilar Program NU Care')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">Edit Pilar: {{ $category->name }}</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.categories.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-success shadow-sm">
            <form action="{{ route('admin.categories.update', $category->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Nama Pilar <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $category->name) }}" required>
                            @error('name')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Slug URL <span class="text-danger">*</span></label>
                            <input type="text" name="slug" class="form-control @error('slug') is-invalid @enderror" value="{{ old('slug', $category->slug) }}" required>
                            @error('slug')<span class="invalid-feedback">{{ $message }}</span>@enderror
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>FontAwesome Icon Class <span class="text-danger">*</span></label>
                            <input type="text" name="icon" class="form-control" value="{{ old('icon', $category->icon) }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Kode Warna Hex <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <input type="color" name="color_code" class="form-control form-control-color" value="{{ old('color_code', $category->color_code) }}" style="max-width: 60px;">
                                <input type="text" class="form-control" value="{{ old('color_code', $category->color_code) }}" readonly>
                            </div>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Urutan Tampil</label>
                            <input type="number" name="sort_order" class="form-control" value="{{ old('sort_order', $category->sort_order) }}">
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Deskripsi Fokus Program</label>
                        <textarea name="description" class="form-control" rows="3">{{ old('description', $category->description) }}</textarea>
                    </div>

                    <div class="form-check">
                        <input type="checkbox" name="is_active" value="1" class="form-check-input" id="checkActive" {{ $category->is_active ? 'checked' : '' }}>
                        <label class="form-check-label font-weight-bold" for="checkActive">Aktifkan Pilar di Halaman Depan</label>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Perbarui Pilar
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
