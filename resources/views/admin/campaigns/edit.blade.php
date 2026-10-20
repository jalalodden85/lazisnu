@extends('layouts.adminlte')

@section('title', 'Edit Kampanye Donasi')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">Edit Kampanye: {{ Str::limit($campaign->title, 40) }}</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.campaigns.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-success shadow-sm">
            <form action="{{ route('admin.campaigns.update', $campaign->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Kategori Judul <span class="text-danger">*</span></label>
                            <input type="text" name="kategori_judul" class="form-control @error('kategori_judul') is-invalid @enderror" value="{{ old('kategori_judul', $campaign->kategori_judul) }}" placeholder="Contoh: Beasiswa Santri / Sedekah Ambulans" required>
                            @error('kategori_judul')<span class="invalid-feedback">{{ $message }}</span>@enderror
                            <small class="form-text text-muted">Kategori ringkas yang ditampilkan pada rekap donasi.</small>
                        </div>
                        <div class="col-md-5 form-group">
                            <label>Judul Kampanye Donasi <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $campaign->title) }}" required>
                        </div>
                        <div class="col-md-3 form-group">
                            <label>Pilar NU Care <span class="text-danger">*</span></label>
                            <select name="category_id" class="form-control" required>
                                @foreach($categories as $cat)
                                    <option value="{{ $cat->id }}" {{ old('category_id', $campaign->category_id) == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Slug URL</label>
                            <input type="text" name="slug" class="form-control" value="{{ old('slug', $campaign->slug) }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Target Dana (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                                <input type="number" name="target_amount" class="form-control font-weight-bold" value="{{ old('target_amount', $campaign->target_amount) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Dana Terkumpul Saat Ini (Rp)</label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text">Rp</span></div>
                                <input type="number" name="collected_amount" class="form-control" value="{{ old('collected_amount', $campaign->collected_amount) }}">
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Tanggal Mulai</label>
                            <input type="date" name="start_date" class="form-control" value="{{ old('start_date', $campaign->start_date?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Tanggal Berakhir (Kosongkan jika Abadi)</label>
                            <input type="date" name="end_date" class="form-control" value="{{ old('end_date', $campaign->end_date?->format('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Status Kampanye</label>
                            <select name="status" class="form-control">
                                <option value="active" {{ old('status', $campaign->status) == 'active' ? 'selected' : '' }}>Aktif (Menerima Donasi)</option>
                                <option value="completed" {{ old('status', $campaign->status) == 'completed' ? 'selected' : '' }}>Selesai / Terpenuhi</option>
                                <option value="suspended" {{ old('status', $campaign->status) == 'suspended' ? 'selected' : '' }}>Ditangguhkan</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Ringkasan Singkat</label>
                        <textarea name="short_description" class="form-control" rows="2" maxlength="350" required>{{ old('short_description', $campaign->short_description) }}</textarea>
                    </div>

                    <div class="form-group">
                        <label>Cerita Lengkap & Rencana Pentasyarufan</label>
                        <textarea name="story_description" class="form-control" rows="6" required>{{ old('story_description', $campaign->story_description) }}</textarea>
                    </div>

                    <div class="row align-items-center">
                        <div class="col-md-6 form-group">
                            <label>URL Gambar Banner</label>
                            <input type="text" name="banner_image" class="form-control" value="{{ old('banner_image', $campaign->banner_image) }}">
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Ganti File Banner</label>
                            <input type="file" name="banner_file" class="form-control-file border p-1 rounded w-100" accept="image/*">
                        </div>
                    </div>

                    <div class="row mt-2">
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_featured" value="1" class="form-check-input" id="checkFeatured" {{ $campaign->is_featured ? 'checked' : '' }}>
                                <label class="form-check-label font-weight-bold" for="checkFeatured">Jadikan Kampanye Unggulan di Beranda</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input type="checkbox" name="is_emergency" value="1" class="form-check-input" id="checkEmergency" {{ $campaign->is_emergency ? 'checked' : '' }}>
                                <label class="form-check-label font-weight-bold text-danger" for="checkEmergency">Tandai sebagai Tanggap Bencana / Darurat</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Perbarui Kampanye
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
