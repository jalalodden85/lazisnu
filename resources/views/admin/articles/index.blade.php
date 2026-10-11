@extends('layouts.adminlte')

@section('title', 'Manajemen Berita & Pentasyarufan')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-newspaper text-primary mr-2"></i>Portal Berita & Kabar Pentasyarufan</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.articles.create') }}" class="btn btn-success font-weight-bold">
                    <i class="fas fa-plus-circle mr-1"></i> Tulis Kabar Baru
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

        <!-- Filter Card -->
        <div class="card card-outline card-secondary shadow-sm mb-3">
            <div class="card-body p-3">
                <form action="{{ route('admin.articles.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-5">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari judul artikel..." value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="post_type" class="form-control form-control-sm">
                            <option value="">-- Semua Kategori Berita --</option>
                            <option value="pentasyarufan" {{ request('post_type') == 'pentasyarufan' ? 'selected' : '' }}>Penyaluran Bantuan</option>
                            <option value="berita" {{ request('post_type') == 'berita' ? 'selected' : '' }}>Kabar Ranting & MWC</option>
                            <option value="edukasi_zis" {{ request('post_type') == 'edukasi_zis' ? 'selected' : '' }}>Edukasi Fiqih ZIS</option>
                            <option value="pengumuman" {{ request('post_type') == 'pengumuman' ? 'selected' : '' }}>Pengumuman</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <select name="status" class="form-control form-control-sm">
                            <option value="">-- Status --</option>
                            <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Published</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter mr-1"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-outline card-success shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 80px;">Thumbnail</th>
                            <th>Judul Kabar / Berita</th>
                            <th>Tipe & Pilar</th>
                            <th>Penulis</th>
                            <th class="text-center">Dilihat</th>
                            <th class="text-center">Status</th>
                            <th>Tanggal Terbit</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($articles as $art)
                            <tr>
                                <td>
                                    <img src="{{ $art->thumbnail_image }}" style="width: 70px; height: 50px; object-fit: cover; border-radius: 6px;" alt="Thumb">
                                </td>
                                <td>
                                    <strong>{{ $art->title }}</strong>
                                    <div class="small text-muted">{{ Str::limit($art->excerpt, 60) }}</div>
                                </td>
                                <td>
                                    <span class="badge badge-info text-uppercase">{{ str_replace('_', ' ', $art->post_type) }}</span>
                                    @if($art->category)
                                        <div class="small text-muted mt-1">{{ $art->category->name }}</div>
                                    @endif
                                </td>
                                <td><small class="text-dark">{{ $art->author->name ?? 'Admin' }}</small></td>
                                <td class="text-center">
                                    <span class="badge badge-light border"><i class="fas fa-eye mr-1"></i>{{ $art->views_count }}</span>
                                </td>
                                <td class="text-center">
                                    @if($art->status == 'published')
                                        <span class="badge badge-success">Terbit</span>
                                    @else
                                        <span class="badge badge-secondary">Draft</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $art->published_at ? $art->published_at->format('d/m/Y') : '-' }}</small></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('articles.detail', $art->slug) }}" target="_blank" class="btn btn-info" title="Lihat di Web">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.articles.edit', $art->id) }}" class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.articles.destroy', $art->id) }}" method="POST" onsubmit="return confirm('Hapus berita ini?')" class="d-inline">
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
                                <td colspan="8" class="text-center text-muted py-4">Belum ada artikel berita.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($articles->hasPages())
                <div class="card-footer bg-white">
                    {{ $articles->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
