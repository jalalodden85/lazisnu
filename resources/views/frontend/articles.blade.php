@extends('layouts.frontend')

@section('title', 'Kabar Penyaluran & Portal Berita | NU Care - LAZISNU')

@section('content')
<div class="bg-success text-white py-5" style="background: linear-gradient(135deg, #064d27, #0a6b38) !important;">
    <div class="container text-center">
        <h1 class="fw-bold mb-2">Kabar Pentasyarufan & Informasi Terkini</h1>
        <p class="text-white-50 max-w-700 mx-auto mb-0">
            Dokumentasi aksi lapangan, laporan penyaluran bantuan koin, dan wawasan edukasi ZISWAF Nahdlatul Ulama.
        </p>
    </div>
</div>

<div class="container py-5">
    <!-- Filter Tipe -->
    <div class="d-flex flex-wrap gap-2 mb-4">
        <a href="{{ route('articles.index') }}" class="btn btn-sm {{ !request('tipe') ? 'btn-success' : 'btn-outline-secondary' }} rounded-pill px-3">
            Semua Kabar
        </a>
        <a href="{{ route('articles.index', ['tipe' => 'pentasyarufan']) }}" class="btn btn-sm {{ request('tipe') == 'pentasyarufan' ? 'btn-success' : 'btn-outline-secondary' }} rounded-pill px-3">
            <i class="fa-solid fa-hands-holding me-1"></i> Penyaluran Bantuan
        </a>
        <a href="{{ route('articles.index', ['tipe' => 'berita']) }}" class="btn btn-sm {{ request('tipe') == 'berita' ? 'btn-success' : 'btn-outline-secondary' }} rounded-pill px-3">
            <i class="fa-solid fa-newspaper me-1"></i> Kabar Cabang & Ranting
        </a>
        <a href="{{ route('articles.index', ['tipe' => 'edukasi_zis']) }}" class="btn btn-sm {{ request('tipe') == 'edukasi_zis' ? 'btn-success' : 'btn-outline-secondary' }} rounded-pill px-3">
            <i class="fa-solid fa-book-open-reader me-1"></i> Edukasi Fiqih ZIS
        </a>
    </div>

    <!-- Grid Berita -->
    <div class="row g-4">
        @forelse($articles as $art)
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <img src="{{ $art->thumbnail_image }}" class="w-100" style="height: 200px; object-fit: cover;" alt="{{ $art->title }}">
                    <div class="card-body p-4 d-flex flex-column">
                        <div class="d-flex justify-content-between small text-muted mb-2">
                            <span class="badge bg-success bg-opacity-10 text-success fw-bold">{{ strtoupper($art->post_type) }}</span>
                            <span><i class="fa-regular fa-calendar me-1"></i> {{ $art->published_at ? $art->published_at->format('d M Y') : date('d M Y') }}</span>
                        </div>
                        <h5 class="fw-bold mb-2">
                            <a href="{{ route('articles.detail', $art->slug) }}" class="text-decoration-none text-dark">
                                {{ $art->title }}
                            </a>
                        </h5>
                        <p class="small text-muted mb-3 flex-grow-1">
                            {{ Str::limit($art->excerpt, 110) }}
                        </p>
                        <a href="{{ route('articles.detail', $art->slug) }}" class="fw-bold text-success text-decoration-none small">
                            Baca Selengkapnya <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="text-muted fs-4 mb-2"><i class="fa-regular fa-newspaper"></i></div>
                <h5>Belum ada artikel pada kategori ini</h5>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $articles->withQueryString()->links() }}
    </div>
</div>
@endsection
