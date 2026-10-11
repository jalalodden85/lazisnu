@extends('layouts.adminlte')

@section('title', 'Manajemen Banner Slider Beranda')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-images text-light mr-2"></i>Banner Slider Beranda</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.sliders.create') }}" class="btn btn-success font-weight-bold">
                    <i class="fas fa-plus-circle mr-1"></i> Tambah Banner Baru
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        <div class="card card-outline card-success shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 70px;" class="text-center">Urutan</th>
                            <th style="width: 140px;">Preview Banner</th>
                            <th>Judul & Tagline Banner</th>
                            <th>Tombol & Link Aksi</th>
                            <th class="text-center">Status</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($sliders as $slider)
                            <tr>
                                <td class="text-center font-weight-bold fs-5">{{ $slider->sort_order }}</td>
                                <td>
                                    <img src="{{ $slider->image_desktop }}" style="width: 130px; height: 70px; object-fit: cover; border-radius: 8px;" alt="Banner">
                                </td>
                                <td>
                                    <strong>{{ $slider->title }}</strong>
                                    <div class="small text-muted">{{ Str::limit($slider->tagline, 80) }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-warning text-dark">{{ $slider->button_text }}</span>
                                    <div class="small text-muted mt-1"><code>{{ $slider->button_url }}</code></div>
                                </td>
                                <td class="text-center">
                                    @if($slider->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.sliders.edit', $slider->id) }}" class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.sliders.destroy', $slider->id) }}" method="POST" onsubmit="return confirm('Hapus banner ini?')" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center text-muted py-4">Belum ada banner slider.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
