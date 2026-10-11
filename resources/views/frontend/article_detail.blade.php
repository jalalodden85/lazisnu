@extends('layouts.frontend')

@section('title', $article->title . ' | NU Care - LAZISNU')

@section('content')
<div class="container py-4">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-success text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('articles.index') }}" class="text-success text-decoration-none">Berita</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($article->title, 40) }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Article -->
        <div class="col-lg-8">
            <article class="card border-0 shadow-sm rounded-4 p-4 mb-4">
                <div class="d-flex align-items-center gap-2 mb-2">
                    <span class="badge bg-success bg-opacity-10 text-success fw-bold">{{ strtoupper($article->post_type) }}</span>
                    <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i> {{ $article->published_at ? $article->published_at->format('d F Y') : date('d F Y') }}</small>
                    <small class="text-muted ms-2"><i class="fa-regular fa-eye me-1"></i> {{ $article->views_count }}x dibaca</small>
                </div>

                <h1 class="fw-bold mb-3 text-dark">{{ $article->title }}</h1>

                <img src="{{ $article->thumbnail_image }}" class="w-100 rounded-3 mb-4" style="max-height: 420px; object-fit: cover;" alt="{{ $article->title }}">

                <div class="lh-lg text-secondary" style="font-size: 1.05rem;">
                    {!! $article->content !!}
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                    <span class="fw-bold text-muted small"><i class="fa-solid fa-share-nodes me-1"></i> Bagikan Kabar Kebaikan:</span>
                    <div class="d-flex gap-2">
                        <a href="https://api.whatsapp.com/send?text={{ urlencode($article->title . ' ' . url()->current()) }}" target="_blank" class="btn btn-sm btn-success rounded-pill px-3">
                            <i class="fa-brands fa-whatsapp me-1"></i> WhatsApp
                        </a>
                        <a href="https://www.facebook.com/sharer/sharer.php?u={{ urlencode(url()->current()) }}" target="_blank" class="btn btn-sm btn-primary rounded-pill px-3">
                            <i class="fa-brands fa-facebook me-1"></i> Facebook
                        </a>
                    </div>
                </div>
            </article>
        </div>

        <!-- Sidebar Kabar Lainnya -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px;">
                <h5 class="fw-bold mb-3 border-bottom pb-2">Kabar Terkait Lainnya</h5>
                <div class="d-flex flex-column gap-3">
                    @foreach($relatedArticles as $rel)
                        <a href="{{ route('articles.detail', $rel->slug) }}" class="text-decoration-none text-dark d-flex gap-3 align-items-center">
                            <img src="{{ $rel->thumbnail_image }}" style="width: 75px; height: 60px; object-fit: cover; border-radius: 8px; flex-shrink: 0;" alt="{{ $rel->title }}">
                            <div>
                                <small class="text-muted d-block" style="font-size: 0.72rem;">{{ $rel->published_at ? $rel->published_at->format('d M Y') : date('d M Y') }}</small>
                                <span class="fw-bold small lh-sm">{{ Str::limit($rel->title, 55) }}</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                <hr class="my-4">

                <!-- CTA Infaq -->
                <div class="p-3 bg-light rounded-3 text-center border">
                    <i class="fa-solid fa-hand-holding-heart text-success fs-2 mb-2"></i>
                    <h6 class="fw-bold">Ingin Turut Berbagi?</h6>
                    <p class="small text-muted mb-2">Salurkan sedekah atau koin infaq Anda sekarang.</p>
                    <a href="{{ route('home') }}#donasi-cepat" class="btn btn-success btn-sm fw-bold rounded-pill w-100">
                        Infaq Sekarang
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
