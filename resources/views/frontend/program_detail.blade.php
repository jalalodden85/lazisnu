@extends('layouts.frontend')

@section('title', $campaign->title . ' | NU Care - LAZISNU')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="{{ route('home') }}" class="text-success text-decoration-none">Beranda</a></li>
            <li class="breadcrumb-item"><a href="{{ route('programs.index') }}" class="text-success text-decoration-none">Program</a></li>
            <li class="breadcrumb-item active" aria-current="page">{{ Str::limit($campaign->title, 40) }}</li>
        </ol>
    </nav>

    <div class="row g-4">
        <!-- Main Content -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden mb-4">
                <img src="{{ $campaign->banner_image }}" class="w-100" style="max-height: 420px; object-fit: cover;" alt="{{ $campaign->title }}">
                <div class="card-body p-4">
                    <span class="badge bg-success bg-opacity-10 text-success fw-bold px-3 py-1 rounded-pill mb-2">
                        {{ $campaign->category->name ?? 'NU Care' }}
                    </span>
                    <h2 class="fw-bold mb-3">{{ $campaign->title }}</h2>

                    <!-- Progress Info -->
                    <div class="p-3 bg-light rounded-3 mb-4 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Dana Terkumpul:</span>
                            <span class="fw-bold text-success fs-5">Rp {{ number_format($campaign->collected_amount, 0, ',', '.') }}</span>
                        </div>
                        <div class="progress mb-2" style="height: 10px;">
                            <div class="progress-bar bg-success" style="width: {{ $campaign->progress_percentage }}%"></div>
                        </div>
                        <div class="d-flex justify-content-between small text-muted">
                            <span>Target: Rp {{ number_format($campaign->target_amount, 0, ',', '.') }}</span>
                            <span>{{ $campaign->progress_percentage }}% Tercapai</span>
                        </div>
                    </div>

                    <!-- Cerita / Story -->
                    <h5 class="fw-bold mb-3">Tentang Program Ini</h5>
                    <div class="lh-lg text-secondary mb-4">
                        {!! $campaign->story_description !!}
                    </div>

                    <hr class="my-4">

                    <!-- Donatur Terakhir -->
                    <h5 class="fw-bold mb-3">Donatur Terbaru ({{ $campaign->donations->count() }})</h5>
                    @forelse($campaign->donations as $don)
                        <div class="d-flex align-items-center gap-3 p-2 border-bottom">
                            <div class="bg-success bg-opacity-10 text-success rounded-circle d-flex align-items-center justify-content-center fw-bold" style="width: 44px; height: 44px;">
                                <i class="fa-solid fa-user"></i>
                            </div>
                            <div class="flex-grow-1">
                                <div class="d-flex justify-content-between align-items-center">
                                    <strong class="text-dark">{{ $don->display_name }}</strong>
                                    <span class="text-success fw-bold small">Rp {{ number_format($don->amount, 0, ',', '.') }}</span>
                                </div>
                                <small class="text-muted">{{ $don->created_at->diffForHumans() }}</small>
                                @if($don->prayer_message)
                                    <p class="small text-secondary fst-italic mb-0 mt-1">"{{ $don->prayer_message }}"</p>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-muted small">Jadilah donatur pertama untuk program kebaikan ini.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Sidebar Form Donasi -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4 sticky-top" style="top: 90px;">
                <h5 class="fw-bold mb-3 text-success"><i class="fa-solid fa-hand-holding-heart me-1 text-warning"></i> Salurkan Donasi Anda</h5>

                <form action="{{ route('donation.store') }}" method="POST">
                    @csrf
                    <input type="hidden" name="campaign_id" value="{{ $campaign->id }}">

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nominal Donasi (Rp):</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light fw-bold text-success">Rp</span>
                            <input type="text" id="displayAmountProgram" class="form-control fw-bold fs-5 text-dark" value="50.000" placeholder="50.000" autocomplete="off" required>
                            <input type="hidden" name="amount" id="customAmountProgram" value="50000">
                        </div>
                        <small class="form-text text-muted" style="font-size: 0.72rem;">*Pemisah ribuan titik otomatis saat mengetik.</small>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nama Lengkap:</label>
                        <input type="text" name="donor_name" class="form-control form-control-sm" placeholder="Nama Anda" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Nomor WhatsApp:</label>
                        <input type="tel" name="donor_phone" class="form-control form-control-sm" placeholder="08xxxxxxxxxx" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Alamat Lengkap (Desa / Kecamatan):</label>
                        <input type="text" name="alamat" class="form-control form-control-sm" placeholder="Contoh: Desa Plosowahyu, Kec. Lamongan" autocomplete="off">
                        <div class="form-text text-muted" style="font-size: 0.72rem;">
                            <i class="fa-solid fa-shield-halved text-success me-1"></i>
                            RT/RW atau nomor rumah otomatis dirahasiakan untuk transparansi publik.
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Metode Pembayaran:</label>
                        <select name="payment_method" class="form-select form-select-sm fw-semibold" required>
                            <option value="transfer_bri">Transfer BRI (Selain Zakat: Infaq &amp; Koin - 00411001448562)</option>
                            <option value="transfer_bsi">Transfer BSI (Khusus Zakat Mal/Profesi/Fitrah - 2026070708)</option>
                            <option value="qris">QRIS Bank BRI (Semua E-Wallet &amp; Mobile Banking)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold text-muted">Doa / Niat Kebaikan (Opsional):</label>
                        <textarea name="prayer_message" rows="2" class="form-control form-control-sm" placeholder="Tuliskan doa atau harapan..."></textarea>
                    </div>

                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" name="is_anonymous" value="1" id="anonCheck">
                        <label class="form-check-label small text-muted" for="anonCheck">
                            Donasi sebagai Hamba Allah
                        </label>
                    </div>

                    <button type="submit" class="btn btn-success fw-bold py-2 rounded-pill w-100 shadow-sm" style="background: var(--nu-green);">
                        Lanjutkan Pembayaran <i class="fa-solid fa-arrow-right ms-1"></i>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function formatRupiah(value) {
        if (!value) return '';
        let clean = value.toString().replace(/[^0-9]/g, '');
        if (!clean) return '';
        return clean.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    }

    function parseRupiah(value) {
        if (!value) return 0;
        return parseInt(value.toString().replace(/[^0-9]/g, ''), 10) || 0;
    }

    document.addEventListener('DOMContentLoaded', () => {
        const displayInput = document.getElementById('displayAmountProgram');
        const customInput = document.getElementById('customAmountProgram');

        if (displayInput && customInput) {
            displayInput.addEventListener('input', function() {
                let raw = parseRupiah(this.value);
                this.value = raw > 0 ? formatRupiah(raw) : '';
                customInput.value = raw;
            });
        }
    });
</script>
@endpush
