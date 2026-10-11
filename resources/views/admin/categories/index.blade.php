@extends('layouts.adminlte')

@section('title', '5 Pilar Program NU Care')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-shapes text-info mr-2"></i>5 Pilar Program NU Care</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.categories.create') }}" class="btn btn-success font-weight-bold">
                    <i class="fas fa-plus-circle mr-1"></i> Tambah Pilar Baru
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
        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show">
                <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        <div class="card card-outline card-success shadow-sm">
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Daftar Pilar Strategis NU Care - LAZISNU</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 60px;" class="text-center">No</th>
                            <th>Pilar & Ikon</th>
                            <th>Kode Slug</th>
                            <th>Aksen Warna</th>
                            <th>Deskripsi Penyaluran</th>
                            <th class="text-center">Program Aktif</th>
                            <th class="text-center">Status</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($categories as $idx => $cat)
                            <tr>
                                <td class="text-center font-weight-bold">{{ $idx + 1 }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="rounded-circle mr-2 d-flex align-items-center justify-content-center text-white" style="width: 36px; height: 36px; background-color: {{ $cat->color_code }};">
                                            <i class="fas {{ $cat->icon }}"></i>
                                        </div>
                                        <div>
                                            <strong class="text-dark">{{ $cat->name }}</strong>
                                        </div>
                                    </div>
                                </td>
                                <td><code>{{ $cat->slug }}</code></td>
                                <td>
                                    <span class="badge text-white px-2 py-1" style="background-color: {{ $cat->color_code }};">
                                        {{ $cat->color_code }}
                                    </span>
                                </td>
                                <td><small class="text-muted">{{ Str::limit($cat->description, 75) }}</small></td>
                                <td class="text-center">
                                    <span class="badge badge-info">{{ $cat->campaigns_count }} Program</span>
                                </td>
                                <td class="text-center">
                                    @if($cat->is_active)
                                        <span class="badge badge-success">Aktif</span>
                                    @else
                                        <span class="badge badge-secondary">Nonaktif</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.categories.edit', $cat->id) }}" class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.categories.destroy', $cat->id) }}" method="POST" onsubmit="return confirm('Hapus pilar {{ $cat->name }}?')" class="d-inline">
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
                                <td colspan="8" class="text-center text-muted py-4">Belum ada pilar terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection
