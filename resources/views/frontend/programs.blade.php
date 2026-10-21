@extends('layouts.frontend')

@section('title', 'Program Donasi & Gerakan Koin | NU Care - LAZISNU')

@section('content')
<div class="bg-success text-white py-5" style="background: linear-gradient(135deg, #064d27, #0a6b38) !important;">
    <div class="container text-center">
        <h1 class="fw-bold mb-2">Program Kebaikan & Infaq</h1>
        <p class="text-white-50 max-w-700 mx-auto mb-0">
            Salurkan zakat, infaq, sedekah, dan partisipasi Koin NU Anda melalui program-program terverifikasi yang langsung menyentuh kaum dhuafa.
        </p>
    </div>
</div>

<div class="container py-5">
    <!-- Filter Kategori & Search -->
    <div class="row g-3 mb-4 align-items-center">
        <div class="col-lg-8">
            <div class="d-flex flex-wrap gap-2">
                <a href="{{ route('programs.index') }}" class="btn btn-sm {{ !request('pilar') ? 'btn-success' : 'btn-outline-secondary' }} rounded-pill px-3">
                    Semua Pilar
                </a>
                @foreach($categories as $cat)
                    <a href="{{ route('programs.index', ['pilar' => $cat->slug]) }}" class="btn btn-sm {{ request('pilar') == $cat->slug ? 'btn-success' : 'btn-outline-secondary' }} rounded-pill px-3">
                        <i class="fa-solid {{ $cat->icon }} me-1"></i> {{ $cat->name }}
                    </a>
                @endforeach
            </div>
        </div>
        <div class="col-lg-4">
            <form action="{{ route('programs.index') }}" method="GET">
                @if(request('pilar'))
                    <input type="hidden" name="pilar" value="{{ request('pilar') }}">
                @endif
                <div class="input-group">
                    <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari kampanye..." value="{{ request('q') }}">
                    <button class="btn btn-success btn-sm" type="submit"><i class="fa-solid fa-magnifying-glass"></i></button>
                </div>
            </form>
        </div>
    </div>

    <!-- Grid Kampanye -->
    <div class="row g-4">
        @forelse($campaigns as $camp)
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden">
                    <div style="height: 200px; overflow: hidden; position: relative;">
                        <img src="{{ $camp->banner_image }}" class="w-100 h-100 object-fit-cover" alt="{{ $camp->title }}">
                        <span class="badge bg-white text-dark position-absolute top-0 end-0 m-3 fw-bold shadow-sm">
                            {{ $camp->category->name ?? 'NU Care' }}
                        </span>
                    </div>
                    <div class="card-body p-4 d-flex flex-column">
                        <h5 class="fw-bold mb-2">
                            <a href="{{ route('programs.detail', $camp->slug) }}" class="text-decoration-none text-dark">
                                {{ $camp->title }}
                            </a>
                        </h5>
                        <p class="small text-muted mb-3 flex-grow-1">
                            {{ Str::limit($camp->short_description, 90) }}
                        </p>

                        <!-- Progress Bar & Target -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between small text-muted mb-1">
                                <span>Terkumpul: <strong class="text-success">Rp {{ number_format($camp->collected_amount, 0, ',', '.') }}</strong></span>
                                <span class="fw-bold text-success">{{ $camp->progress_percentage }}%</span>
                            </div>
                            <div class="progress rounded-pill mb-2" style="height: 8px;">
                                <div class="progress-bar bg-success" style="width: {{ $camp->progress_percentage }}%"></div>
                            </div>
                            <div class="d-flex justify-content-between small text-muted">
                                <span>Target: <strong class="text-dark">Rp {{ number_format($camp->target_amount, 0, ',', '.') }}</strong></span>
                                <span><i class="fa-regular fa-clock me-1"></i>{{ $camp->days_left !== null ? $camp->days_left . ' hari lagi' : 'Program Abadi' }}</span>
                            </div>
                        </div>

                        <a href="{{ route('programs.detail', $camp->slug) }}" class="btn btn-success fw-bold rounded-pill w-100">
                            Donasi Sekarang
                        </a>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <div class="text-muted fs-4 mb-2"><i class="fa-regular fa-folder-open"></i></div>
                <h5>Tidak ada kampanye ditemukan</h5>
                <p class="text-muted small">Silakan pilih kategori lain atau kata kunci pencarian yang berbeda.</p>
                <a href="{{ route('programs.index') }}" class="btn btn-outline-success btn-sm rounded-pill">Reset Filter</a>
            </div>
        @endforelse
    </div>

    <div class="mt-4">
        {{ $campaigns->withQueryString()->links() }}
    </div>
</div>
@endsection
