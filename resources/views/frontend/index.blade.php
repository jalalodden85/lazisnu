@extends('layouts.frontend')

@section('title', 'NU Care - LAZISNU | Portal Informasi, Gerakan Koin & ZIS Kemandirian Umat')

@push('styles')
<style>
    /* Hero Carousel & Widget */
    .hero-section {
        background: linear-gradient(135deg, #064d27 0%, #0a6b38 50%, #032b16 100%);
        padding: 40px 0 60px 0;
        position: relative;
        color: white;
    }
    .hero-slider-card {
        border-radius: 20px;
        overflow: hidden;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25);
        height: 480px;
        position: relative;
    }
    .hero-slider-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        filter: brightness(0.65);
    }
    .hero-slider-content {
        position: absolute;
        bottom: 0;
        left: 0;
        right: 0;
        padding: 40px;
        background: linear-gradient(180deg, rgba(0,0,0,0) 0%, rgba(3, 36, 18, 0.95) 80%);
    }

    /* Widget Donasi Kilat */
    .widget-donation-card {
        background: #ffffff;
        color: #1e293b;
        border-radius: 20px;
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.2);
        padding: 28px;
        border: 1px solid rgba(255, 255, 255, 0.2);
    }
    .nav-tabs-donation {
        border-bottom: 2px solid #e2e8f0;
        gap: 6px;
    }
    .nav-tabs-donation .nav-link {
        font-weight: 700;
        font-size: 0.92rem;
        color: #64748b;
        border: none;
        padding: 10px 18px;
        border-radius: 10px;
        background: #f1f5f9;
        transition: all 0.2s ease;
    }
    .nav-tabs-donation .nav-link.active {
        background: var(--nu-green);
        color: #ffffff;
    }
    .amount-pill {
        border: 1.5px solid #cbd5e1;
        background: #f8fafc;
        border-radius: 10px;
        padding: 8px 12px;
        font-weight: 700;
        font-size: 0.85rem;
        cursor: pointer;
        transition: all 0.2s ease;
        text-align: center;
    }
    .amount-pill:hover, .amount-pill.active {
        border-color: var(--nu-green);
        background: rgba(10, 107, 56, 0.08);
        color: var(--nu-green);
    }

    /* Live Counter Section */
    .counter-section {
        margin-top: -35px;
        position: relative;
        z-index: 10;
    }
    .counter-card {
        background: #ffffff;
        border-radius: 18px;
        padding: 24px 20px;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.07);
        border: 1px solid #f1f5f9;
        display: flex;
        align-items: center;
        gap: 18px;
        transition: all 0.3s ease;
    }
    .counter-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 15px 35px rgba(10, 107, 56, 0.12);
        border-color: rgba(10, 107, 56, 0.3);
    }
    .counter-icon {
        width: 60px;
        height: 60px;
        border-radius: 14px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.8rem;
        flex-shrink: 0;
    }

    /* 5 Pilar Cards */
    .pilar-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 24px;
        box-shadow: 0 4px 18px rgba(0, 0, 0, 0.04);
        border: 1px solid #f1f5f9;
        text-align: center;
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
    }
    .pilar-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 12px 30px rgba(0, 0, 0, 0.09);
    }
    .pilar-icon-wrap {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        margin: 0 auto 16px auto;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.9rem;
    }

    /* Crowdfunding Campaign Card */
    .campaign-card {
        background: #ffffff;
        border-radius: 18px;
        overflow: hidden;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.06);
        border: 1px solid #f1f5f9;
        transition: all 0.3s ease;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .campaign-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.12);
    }
    .campaign-img-wrap {
        position: relative;
        height: 200px;
        overflow: hidden;
    }
    .campaign-img {
        width: 100%;
        height: 100%;
        object-fit: cover;
        transition: transform 0.4s ease;
    }
    .campaign-card:hover .campaign-img {
        transform: scale(1.05);
    }
    .badge-emergency {
        position: absolute;
        top: 14px;
        left: 14px;
        background: #ef4444;
        color: white;
        font-weight: 700;
        font-size: 0.75rem;
        padding: 5px 12px;
        border-radius: 50px;
        box-shadow: 0 4px 10px rgba(239, 68, 68, 0.4);
    }
    .badge-category {
        position: absolute;
        top: 14px;
        right: 14px;
        background: rgba(255, 255, 255, 0.95);
        color: var(--nu-dark-green);
        font-weight: 700;
        font-size: 0.75rem;
        padding: 5px 12px;
        border-radius: 50px;
    }
    .progress-custom {
        height: 8px;
        border-radius: 10px;
        background-color: #e2e8f0;
        overflow: hidden;
    }
    .progress-bar-nu {
        background: linear-gradient(90deg, var(--nu-green), var(--nu-gold));
        border-radius: 10px;
    }

    /* News & Pentasyarufan */
    .article-big-card {
        border-radius: 18px;
        overflow: hidden;
        background: #ffffff;
        box-shadow: 0 6px 20px rgba(0, 0, 0, 0.05);
        height: 100%;
        transition: all 0.3s ease;
    }
    .article-big-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 12px 28px rgba(0, 0, 0, 0.1);
    }
    .article-mini-item {
        background: #ffffff;
        border-radius: 14px;
        padding: 14px;
        box-shadow: 0 3px 12px rgba(0, 0, 0, 0.04);
        border: 1px solid #f1f5f9;
        display: flex;
        gap: 16px;
        transition: all 0.2s ease;
        text-decoration: none;
        color: inherit;
    }
    .article-mini-item:hover {
        background: #fafbfd;
        transform: translateX(4px);
        color: var(--nu-green);
    }
    .article-mini-thumb {
        width: 105px;
        height: 85px;
        border-radius: 10px;
        object-fit: cover;
        flex-shrink: 0;
    }

    /* Bank Account Card */
    .bank-card {
        background: #ffffff;
        border-radius: 16px;
        padding: 20px;
        box-shadow: 0 4px 15px rgba(0, 0, 0, 0.05);
        border: 1px solid #f1f5f9;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        height: 100%;
        transition: all 0.3s ease;
    }
    .bank-card:hover {
        border-color: var(--nu-green);
        transform: translateY(-4px);
    }
    .bank-badge {
        font-weight: 800;
        font-size: 1.1rem;
        letter-spacing: 0.5px;
    }

    /* Kategori Rekening Khusus */
    .rekening-kategori-card {
        background: #ffffff;
        border-radius: 20px;
        padding: 26px;
        box-shadow: 0 8px 25px rgba(0, 0, 0, 0.06);
        border: 2px solid #e2e8f0;
        transition: all 0.3s ease;
        height: 100%;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
    }
    .rekening-kategori-card:hover {
        transform: translateY(-6px);
        box-shadow: 0 16px 35px rgba(0, 0, 0, 0.1);
    }
    .card-rekening-zakat {
        border-color: rgba(16, 185, 129, 0.45);
        background: linear-gradient(180deg, #ffffff 0%, #f0fdf4 100%);
    }
    .card-rekening-zakat:hover {
        border-color: #059669;
    }
    .card-rekening-infaq {
        border-color: rgba(0, 82, 156, 0.35);
        background: linear-gradient(180deg, #ffffff 0%, #f0f7ff 100%);
    }
    .card-rekening-infaq:hover {
        border-color: #00529c;
    }
    .card-rekening-qris {
        border-color: rgba(207, 21, 45, 0.35);
        background: linear-gradient(180deg, #ffffff 0%, #fff5f5 100%);
    }
    .card-rekening-qris:hover {
        border-color: #cf152d;
    }

    /* QRIS Preview Card */
    .qris-preview-box {
        background: #ffffff;
        border: 2px dashed #cbd5e1;
        border-radius: 14px;
        padding: 12px;
        text-align: center;
        transition: all 0.2s ease;
    }
    .qris-preview-box:hover {
        border-color: #cf152d;
        background: #fff;
    }
    .qris-img {
        max-height: 240px;
        width: auto;
        object-fit: contain;
        display: block;
        margin: 0 auto;
        border-radius: 8px;
    }

    /* Filter pill for articles */
    .filter-pill {
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #475569;
        font-weight: 600;
        font-size: 0.82rem;
        padding: 5px 14px;
        border-radius: 50px;
        transition: all 0.2s ease;
        text-decoration: none;
        display: inline-block;
        cursor: pointer;
    }
    .filter-pill:hover, .filter-pill.active {
        background: var(--nu-green);
        color: #ffffff;
        border-color: var(--nu-green);
    }

    /* Interactive Zakat Widget */
    .zakat-box {
        background: linear-gradient(135deg, #ffffff 0%, #f9fbf9 100%);
        border-radius: 20px;
        padding: 35px;
        border: 2px solid rgba(10, 107, 56, 0.15);
        box-shadow: 0 15px 35px rgba(0, 0, 0, 0.06);
    }
</style>
@endpush

@section('content')

<!-- =========================================================================
     1. HERO SECTION & QUICK DONATION WIDGET
========================================================================== -->
<section class="hero-section">
    <div class="container">
        <div class="row g-4 align-items-center">
            <!-- Left: Hero Slider Carousel -->
            <div class="col-lg-7">
                <div id="heroCarousel" class="carousel slide carousel-fade hero-slider-card" data-bs-ride="carousel">
                    <div class="carousel-indicators">
                        @foreach($sliders as $idx => $slider)
                            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="{{ $idx }}" class="{{ $idx === 0 ? 'active' : '' }}" aria-label="Slide {{ $idx + 1 }}"></button>
                        @endforeach
                    </div>
                    <div class="carousel-inner h-100">
                        @foreach($sliders as $idx => $slider)
                            <div class="carousel-item h-100 {{ $idx === 0 ? 'active' : '' }}" data-bs-interval="6000">
                                <img src="{{ $slider->image_desktop }}" class="hero-slider-img" alt="{{ $slider->title }}">
                                <div class="hero-slider-content">
                                    <span class="badge text-bg-warning text-dark fw-bold mb-2 px-3 py-1">
                                        <i class="fa-solid fa-star-and-crescent me-1"></i> LAZISNU BERKHIDMAT
                                    </span>
                                    <h2 class="text-white fw-bold mb-2">{{ $slider->title }}</h2>
                                    <p class="text-white-50 small mb-3 d-none d-md-block">{{ $slider->tagline }}</p>
                                    <a href="{{ $slider->button_url }}" class="btn btn-warning fw-bold px-4 py-2 rounded-pill text-dark shadow-sm">
                                        {{ $slider->button_text }} <i class="fa-solid fa-arrow-right ms-1"></i>
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Right: Quick Infaq & Donation Form (Widget Kilat) -->
            <div class="col-lg-5" id="donasi-cepat">
                <div class="widget-donation-card">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="fw-bold mb-0 text-success"><i class="fa-solid fa-hand-holding-dollar me-2 text-warning"></i> Infaq & Sedekah Kilat</h5>
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold">Amanah & Syar'i</span>
                    </div>

                    <!-- Tabs Pilihan -->
                    <ul class="nav nav-tabs nav-tabs-donation mb-3" id="donationTabs" role="tablist">
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link active w-100" id="infaq-tab" data-bs-toggle="tab" data-bs-target="#infaq-pane" type="button" role="tab">
                                <i class="fa-solid fa-coins me-1"></i> Infaq KOIN NU
                            </button>
                        </li>
                        <li class="nav-item flex-fill" role="presentation">
                            <button class="nav-link w-100" id="program-tab" data-bs-toggle="tab" data-bs-target="#program-pane" type="button" role="tab">
                                <i class="fa-solid fa-handshake-angle me-1"></i> Program 5 Pilar
                            </button>
                        </li>
                    </ul>

                    <form action="{{ route('donation.store') }}" method="POST" id="quickDonationForm">
                        @csrf
                        <div class="tab-content" id="donationTabContent">
                            <!-- Tab 1: Infaq Koin NU -->
                            <div class="tab-pane fade show active" id="infaq-pane" role="tabpanel">
                                <input type="hidden" name="campaign_id" value="{{ $featuredCampaigns->first()?->id ?? 1 }}">
                                <div class="p-2 mb-3 bg-light rounded text-muted small border">
                                    <i class="fa-solid fa-circle-info text-success me-1"></i>
                                    Infaq Anda langsung dialokasikan ke <strong>Gerakan Koin Kemandirian Umat</strong> tingkat Cabang/MWC/Ranting.
                                </div>
                            </div>

                            <!-- Tab 2: Pilih Program Khusus -->
                            <div class="tab-pane fade" id="program-pane" role="tabpanel">
                                <div class="mb-3">
                                    <label class="form-label small fw-bold text-muted">Pilih Kampanye Penyaluran:</label>
                                    <select class="form-select form-select-sm" id="campaignSelector" onchange="document.querySelector('input[name=campaign_id]').value = this.value">
                                        @foreach($featuredCampaigns as $c)
                                            <option value="{{ $c->id }}">{{ $c->title }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                        </div>

                        <!-- Pilih Nominal Cepat -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Pilih Nominal Infaq:</label>
                            <div class="row g-2 mb-2">
                                <div class="col-4">
                                    <div class="amount-pill" onclick="setNominal(10000, this)">Rp 10.000</div>
                                </div>
                                <div class="col-4">
                                    <div class="amount-pill" onclick="setNominal(20000, this)">Rp 20.000</div>
                                </div>
                                <div class="col-4">
                                    <div class="amount-pill active" onclick="setNominal(50000, this)">Rp 50.000</div>
                                </div>
                                <div class="col-4">
                                    <div class="amount-pill" onclick="setNominal(100000, this)">Rp 100.000</div>
                                </div>
                                <div class="col-4">
                                    <div class="amount-pill" onclick="setNominal(250000, this)">Rp 250.000</div>
                                </div>
                                <div class="col-4">
                                    <div class="amount-pill" onclick="setNominal(500000, this)">Rp 500.000</div>
                                </div>
                            </div>
                            <div class="input-group">
                                <span class="input-group-text bg-light fw-bold text-success">Rp</span>
                                <input type="text" id="displayAmount" class="form-control fw-bold text-dark fs-5" value="50.000" placeholder="50.000" autocomplete="off" required>
                                <input type="hidden" name="amount" id="customAmount" value="50000">
                            </div>
                            <small class="form-text text-muted" style="font-size: 0.72rem;">*Ketik bebas nominal yang dikehendaki, pemisah ribuan titik otomatis.</small>
                        </div>

                        <!-- Identitas Donatur -->
                        <div class="row g-2 mb-2">
                            <div class="col-6">
                                <input type="text" name="donor_name" class="form-control form-control-sm" placeholder="Nama Lengkap" required>
                            </div>
                            <div class="col-6">
                                <input type="tel" name="donor_phone" class="form-control form-control-sm" placeholder="No. WhatsApp (08xx)" required>
                            </div>
                        </div>

                        <!-- Metode Pembayaran Sesuai Rekening & QRIS Resmi -->
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Metode Penyaluran:</label>
                            <select name="payment_method" class="form-select form-select-sm fw-semibold" required>
                                <option value="transfer_bri">Transfer BRI (Selain Zakat: Infaq, Shadaqah & Koin - 00411001448562)</option>
                                <option value="transfer_bsi">Transfer BSI (Khusus Zakat Mal / Profesi / Fitrah - 2026070708)</option>
                                <option value="qris">QRIS Bank BRI (Semua E-Wallet & Mobile Banking)</option>
                                <option value="transfer_mandiri">Transfer Bank Mandiri</option>
                                <option value="transfer_bca">Transfer Bank BCA</option>
                            </select>
                        </div>

                        <!-- Opsi Hamba Allah & Doa -->
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" name="is_anonymous" value="1" id="anonCheck">
                            <label class="form-check-label small text-muted" for="anonCheck">
                                Sembunyikan nama saya (Hamba Allah)
                            </label>
                        </div>

                        <button type="submit" class="btn btn-success w-100 fw-bold py-2 rounded-pill shadow-sm" style="background: var(--nu-green);">
                            <i class="fa-solid fa-heart me-1 text-warning"></i> Lanjutkan Infaq Sekarang
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================================================
     2. REAL-TIME IMPACT COUNTERS (TERKONEKSI DATABASE APLIKASI)
========================================================================== -->
<section class="counter-section">
    <div class="container">
        <div class="row g-3">
            <!-- Counter 1: Total Dana Terhimpun -->
            <div class="col-lg-3 col-md-6">
                <div class="counter-card">
                    <div class="counter-icon bg-success bg-opacity-10 text-success">
                        <i class="fa-solid fa-vault"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Total Dana Terhimpun</div>
                        <div class="fs-5 fw-extrabold text-success mb-0">Rp {{ number_format($totalDanaHimpunan, 0, ',', '.') }}</div>
                        <small class="text-muted" style="font-size: 0.72rem;">*Akumulasi Koin & ZIS</small>
                    </div>
                </div>
            </div>

            <!-- Counter 2: Kotak Koin Terdistribusi -->
            <div class="col-lg-3 col-md-6">
                <div class="counter-card">
                    <div class="counter-icon bg-warning bg-opacity-10 text-warning">
                        <i class="fa-solid fa-coins"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Kotak Koin Beredar</div>
                        <div class="fs-5 fw-extrabold text-dark mb-0">{{ number_format($totalKotak) }} Kotak</div>
                        <small class="text-muted" style="font-size: 0.72rem;">*Donatur Warga Aktif</small>
                    </div>
                </div>
            </div>

            <!-- Counter 3: Penerima Manfaat -->
            <div class="col-lg-3 col-md-6">
                <div class="counter-card">
                    <div class="counter-icon bg-danger bg-opacity-10 text-danger">
                        <i class="fa-solid fa-hands-holding-child"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Penerima Manfaat</div>
                        <div class="fs-5 fw-extrabold text-danger mb-0">{{ number_format($penerimaManfaat) }}+ Jiwa</div>
                        <small class="text-muted" style="font-size: 0.72rem;">*Dhuafa, Santri, Yatim</small>
                    </div>
                </div>
            </div>

            <!-- Counter 4: Jaringan Ranting & MWC -->
            <div class="col-lg-3 col-md-6">
                <div class="counter-card">
                    <div class="counter-icon bg-primary bg-opacity-10 text-primary">
                        <i class="fa-solid fa-sitemap"></i>
                    </div>
                    <div>
                        <div class="text-muted small fw-bold text-uppercase">Jaringan Pelayanan</div>
                        <div class="fs-5 fw-extrabold text-primary mb-0">{{ $totalMwc }} MWC / {{ $totalRanting }} Rtg</div>
                        <small class="text-muted" style="font-size: 0.72rem;">*Didukung {{ $totalRelawan }} Relawan</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================================================
     2.1 HIGHLIGHT PROGRAM KOIN NU (RINGKAS, INFORMATIF & TRANSPARAN)
========================================================================== -->
<section class="py-5" style="background: linear-gradient(135deg, #064d27 0%, #0a6b38 60%, #08552d 100%); color: #ffffff;" id="highlight-koin-nu">
    <div class="container">
        <!-- Section Header -->
        <div class="text-center max-w-700 mx-auto mb-4">
            <span class="badge text-bg-warning text-dark fw-bold px-3 py-1 rounded-pill mb-2">
                <i class="fa-solid fa-coins me-1"></i> AKUNTABILITAS & KEMANDIRIAN UMAT
            </span>
            <h2 class="fw-bold text-white mb-2">Highlight Gerakan KOIN NU Lamongan</h2>
            <p class="text-white-50 small mb-0">
                Laporan ringkas dan transparan capaian infaq Koin Nahdlatul Ulama Cabang Lamongan yang kembali seutuhnya untuk kemaslahatan warga dan mustahik.
            </p>
        </div>

        <!-- 3 Metrik Utama KOIN NU -->
        <div class="row g-4 mb-4">
            <!-- 1. Total Koin Terhimpun -->
            <div class="col-lg-4 col-md-6">
                <div class="p-4 rounded-4 bg-white bg-opacity-10 border border-white-20 h-100 shadow-sm" style="backdrop-filter: blur(8px);">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-warning text-dark rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; font-size: 1.5rem;">
                            <i class="fa-solid fa-vault"></i>
                        </div>
                        <div>
                            <span class="badge bg-warning bg-opacity-25 text-warning fw-bold small">AKUMULASI TERVERIFIKASI</span>
                            <div class="text-white-50 small">Total Perolehan Koin Terhimpun</div>
                        </div>
                    </div>
                    <div class="fs-2 fw-extrabold text-white mb-2 tracking-tight">
                        Rp {{ number_format($koinTotal ?? $totalDanaHimpunan, 0, ',', '.') }}
                    </div>
                    <p class="small text-white-50 mb-0">
                        Akumulasi koin infaq warga terkonfirmasi dari 15 MWC & seluruh Ranting NU se-Kabupaten Lamongan secara akuntabel.
                    </p>
                </div>
            </div>

            <!-- 2. Kotak / Kaleng Aktif -->
            <div class="col-lg-4 col-md-6">
                <div class="p-4 rounded-4 bg-white bg-opacity-10 border border-white-20 h-100 shadow-sm" style="backdrop-filter: blur(8px);">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; font-size: 1.5rem; background: #16a34a !important;">
                            <i class="fa-solid fa-boxes-stacked"></i>
                        </div>
                        <div>
                            <span class="badge bg-success bg-opacity-50 text-white fw-bold small">TERSEBAR DI KELUARGA</span>
                            <div class="text-white-50 small">Jumlah Kaleng Koin Aktif</div>
                        </div>
                    </div>
                    <div class="fs-2 fw-extrabold text-white mb-2 tracking-tight">
                        {{ number_format($totalKotak) }} Kotak Donatur
                    </div>
                    <p class="small text-white-50 mb-0">
                        Kaleng KOIN NU aktif yang dipercayakan di rumah donatur, pertokoan, dan madrasah dengan barcode nomor kotak unik.
                    </p>
                </div>
            </div>

            <!-- 3. Sebaran Realisasi Manfaat -->
            <div class="col-lg-4 col-md-12">
                <div class="p-4 rounded-4 bg-white bg-opacity-10 border border-white-20 h-100 shadow-sm" style="backdrop-filter: blur(8px);">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="text-white rounded-3 d-flex align-items-center justify-content-center shadow-sm" style="width: 52px; height: 52px; font-size: 1.5rem; background: #0284c7 !important;">
                            <i class="fa-solid fa-hands-holding-child"></i>
                        </div>
                        <div>
                            <span class="badge bg-info bg-opacity-50 text-white fw-bold small">PENYALURAN TEREALISASI</span>
                            <div class="text-white-50 small">Penerima Manfaat Langsung</div>
                        </div>
                    </div>
                    <div class="fs-2 fw-extrabold text-white mb-2 tracking-tight">
                        {{ number_format($penerimaManfaat) }}+ Jiwa Mustahik
                    </div>
                    <p class="small text-white-50 mb-0">
                        Santri dhuafa, guru ngaji, pasien dhuafa pengguna ambulans, dan fakir miskin yang terbantu langsung dari koin umat.
                    </p>
                </div>
            </div>
        </div>

        <!-- Sebaran Realisasi Program Koin NU (5 Pilar) -->
        <div class="p-4 rounded-4 bg-dark bg-opacity-35 border border-white-15">
            <div class="row g-3 align-items-center">
                <div class="col-lg-4">
                    <h5 class="fw-bold text-white mb-1"><i class="fa-solid fa-chart-pie text-warning me-2"></i>Sebaran Penyaluran 5 Pilar</h5>
                    <p class="small text-white-50 mb-0">
                        Alokasi perolehan Gerakan KOIN NU disalurkan terukur dan transparan di 15 MWC dan Ranting.
                    </p>
                </div>
                <div class="col-lg-8">
                    <div class="row g-2 text-center">
                        <div class="col-sm col-6">
                            <div class="p-2 rounded bg-white bg-opacity-10">
                                <div class="text-info fw-bold fs-6"><i class="fa-solid fa-graduation-cap"></i> 25%</div>
                                <div class="text-white small fw-bold">Pendidikan</div>
                                <small class="text-white-50" style="font-size: 0.7rem;">Beasiswa Santri</small>
                            </div>
                        </div>
                        <div class="col-sm col-6">
                            <div class="p-2 rounded bg-white bg-opacity-10">
                                <div class="text-danger fw-bold fs-6"><i class="fa-solid fa-heart-pulse"></i> 30%</div>
                                <div class="text-white small fw-bold">Kesehatan</div>
                                <small class="text-white-50" style="font-size: 0.7rem;">Ambulans Siaga</small>
                            </div>
                        </div>
                        <div class="col-sm col-6">
                            <div class="p-2 rounded bg-white bg-opacity-10">
                                <div class="text-warning fw-bold fs-6"><i class="fa-solid fa-chart-line"></i> 20%</div>
                                <div class="text-white small fw-bold">Ekonomi</div>
                                <small class="text-white-50" style="font-size: 0.7rem;">Modal Usaha</small>
                            </div>
                        </div>
                        <div class="col-sm col-6">
                            <div class="p-2 rounded bg-white bg-opacity-10">
                                <div class="text-success fw-bold fs-6"><i class="fa-solid fa-hand-holding-heart"></i> 15%</div>
                                <div class="text-white small fw-bold">Sosial</div>
                                <small class="text-white-50" style="font-size: 0.7rem;">Santunan Yatim</small>
                            </div>
                        </div>
                        <div class="col-sm col-12">
                            <div class="p-2 rounded bg-white bg-opacity-10">
                                <div class="text-success fw-bold fs-6"><i class="fa-solid fa-leaf"></i> 10%</div>
                                <div class="text-white small fw-bold">Lingkungan</div>
                                <small class="text-white-50" style="font-size: 0.7rem;">Air Bersih &amp; Sampah</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================================================
     3. 5 PILAR PROGRAM NU CARE
========================================================================== -->
<section class="py-5 mt-4" id="lima-pilar">
    <div class="container">
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge text-bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill mb-2">
                <i class="fa-solid fa-shapes me-1"></i> PROGRAM STRATEGIS
            </span>
            <h2 class="fw-bold text-dark">5 Pilar Program NU Care - LAZISNU</h2>
            <p class="text-muted">
                Penyaluran dana ZIS dan Gerakan Koin NU dikelola secara terencana, terukur, dan bermartabat melalui lima pilar kebaikan untuk kemandirian umat.
            </p>
        </div>

        <div class="row g-4">
            @foreach($categories as $category)
                <div class="col-lg col-md-4 col-sm-6">
                    <div class="pilar-card">
                        <div class="pilar-icon-wrap" style="background-color: {{ $category->color_code }}15; color: {{ $category->color_code }};">
                            <i class="fa-solid {{ $category->icon }}"></i>
                        </div>
                        <h5 class="fw-bold mb-2">{{ $category->name }}</h5>
                        <p class="small text-muted flex-grow-1 mb-3">
                            {{ Str::limit($category->description, 90) }}
                        </p>
                        <a href="{{ route('programs.index', ['pilar' => $category->slug]) }}" class="btn btn-sm btn-outline-secondary rounded-pill fw-semibold">
                            Lihat Aksi <i class="fa-solid fa-arrow-right ms-1"></i>
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- =========================================================================
     4. KAMPANYE DONASI & PENGGALANGAN DANA AKTIF
========================================================================== -->
<section class="py-5 bg-light" id="program-pilihan">
    <div class="container">
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-4">
            <div>
                <span class="badge text-bg-warning text-dark fw-bold px-3 py-1 rounded-pill mb-2">
                    <i class="fa-solid fa-hand-holding-heart me-1"></i> MARI BERBAGI
                </span>
                <h2 class="fw-bold text-dark mb-0">Program & Kampanye Terkini</h2>
            </div>
            <a href="{{ route('programs.index') }}" class="btn btn-outline-success fw-bold rounded-pill mt-3 mt-md-0">
                Lihat Semua Program <i class="fa-solid fa-arrow-right ms-1"></i>
            </a>
        </div>

        <div class="row g-4">
            @foreach($featuredCampaigns as $campaign)
                <div class="col-lg-4 col-md-6">
                    <div class="campaign-card">
                        <div class="campaign-img-wrap">
                            <img src="{{ $campaign->banner_image }}" class="campaign-img" alt="{{ $campaign->title }}">
                            @if($campaign->is_emergency)
                                <div class="badge-emergency"><i class="fa-solid fa-fire me-1"></i> TANGGAP DARURAT</div>
                            @endif
                            <div class="badge-category">{{ $campaign->category->name ?? 'NU Care' }}</div>
                        </div>
                        <div class="p-4 d-flex flex-column flex-grow-1">
                            <h5 class="fw-bold mb-2 text-dark">
                                <a href="{{ route('programs.detail', $campaign->slug) }}" class="text-decoration-none text-dark hover-green">
                                    {{ Str::limit($campaign->title, 55) }}
                                </a>
                            </h5>
                            <p class="small text-muted mb-3 flex-grow-1">
                                {{ Str::limit($campaign->short_description, 100) }}
                            </p>

                            <!-- Progress Bar -->
                            <div class="mb-3">
                                <div class="d-flex justify-content-between small text-muted mb-1">
                                    <span>Terkumpul: <strong>Rp {{ number_format($campaign->collected_amount, 0, ',', '.') }}</strong></span>
                                    <span class="fw-bold text-success">{{ $campaign->progress_percentage }}%</span>
                                </div>
                                <div class="progress-custom">
                                    <div class="progress-bar-nu h-100" style="width: {{ $campaign->progress_percentage }}%"></div>
                                </div>
                                <div class="d-flex justify-content-between small text-muted mt-2">
                                    <span>Target: Rp {{ number_format($campaign->target_amount, 0, ',', '.') }}</span>
                                    <span>{{ $campaign->days_left !== null ? $campaign->days_left . ' hari lagi' : 'Program Abadi' }}</span>
                                </div>
                            </div>

                            <a href="{{ route('programs.detail', $campaign->slug) }}" class="btn btn-success fw-bold py-2 rounded-pill w-100" style="background: var(--nu-green);">
                                <i class="fa-solid fa-heart me-1"></i> Donasi Sekarang
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>

<!-- =========================================================================
     5. KABAR PENTASYARUFAN & BERITA TERKINI
========================================================================== -->
<section class="py-5" id="kabar-terkini">
    <div class="container">
        <!-- Header & Filter Dropdown -->
        <div class="d-flex flex-wrap justify-content-between align-items-end mb-3">
            <div>
                <span class="badge text-bg-success bg-opacity-10 text-success fw-bold px-3 py-1 rounded-pill mb-2">
                    <i class="fa-solid fa-bullhorn me-1"></i> KABAR PENTASYARUFAN
                </span>
                <h2 class="fw-bold text-dark mb-0">Dokumentasi Aksi & Penyaluran Berita</h2>
            </div>
            <div class="d-flex align-items-center gap-2 mt-3 mt-md-0">
                <!-- Dropdown Filter Kategori -->
                <div class="dropdown">
                    <button class="btn btn-outline-success dropdown-toggle fw-bold rounded-pill px-3 shadow-sm" type="button" id="dropdownFilterKabar" data-bs-toggle="dropdown" aria-expanded="false">
                        <i class="fa-solid fa-filter me-1"></i> <span id="labelKategoriAktif">Filter Kategori</span>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2" aria-labelledby="dropdownFilterKabar">
                        <li><a class="dropdown-item active opt-filter" href="javascript:void(0)" onclick="filterKabar('all', 'Semua Kabar (5 Terbaru)', this)"><i class="fa-solid fa-layer-group text-success me-2"></i> Semua (5 Terbaru)</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item opt-filter" href="javascript:void(0)" onclick="filterKabar('pentasyarufan', 'Pentasyarufan', this)"><i class="fa-solid fa-hand-holding-heart text-success me-2"></i> Pentasyarufan</a></li>
                        <li><a class="dropdown-item opt-filter" href="javascript:void(0)" onclick="filterKabar('berita', 'Berita & Kegiatan', this)"><i class="fa-solid fa-newspaper text-primary me-2"></i> Berita & Kegiatan</a></li>
                        <li><a class="dropdown-item opt-filter" href="javascript:void(0)" onclick="filterKabar('edukasi', 'Edukasi ZIS', this)"><i class="fa-solid fa-book-open text-warning me-2"></i> Edukasi ZIS</a></li>
                        <li><a class="dropdown-item opt-filter" href="javascript:void(0)" onclick="filterKabar('sosial', 'Aksi Kemanusiaan', this)"><i class="fa-solid fa-users text-danger me-2"></i> Aksi Kemanusiaan</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item fw-bold text-success" href="{{ route('articles.index') }}"><i class="fa-solid fa-arrow-up-right-from-square me-2"></i> Buka Seluruh Arsip &raquo;</a></li>
                    </ul>
                </div>

                <a href="{{ route('articles.index') }}" class="btn btn-success fw-bold rounded-pill px-3 shadow-sm" style="background: var(--nu-green);">
                    Arsip Berita & Aksi <i class="fa-solid fa-arrow-right ms-1"></i>
                </a>
            </div>
        </div>

        <!-- Opsi Filter Cepat (Pill Buttons) -->
        <div class="d-flex flex-wrap gap-2 mb-4 align-items-center">
            <span class="small text-muted fw-bold me-1"><i class="fa-solid fa-tags me-1"></i> Kategori:</span>
            <button type="button" class="filter-pill active" onclick="filterKabar('all', 'Semua (5 Terbaru)', this)">Semua (5 Terbaru)</button>
            <button type="button" class="filter-pill" onclick="filterKabar('pentasyarufan', 'Pentasyarufan', this)">Pentasyarufan</button>
            <button type="button" class="filter-pill" onclick="filterKabar('berita', 'Berita', this)">Berita</button>
            <button type="button" class="filter-pill" onclick="filterKabar('edukasi', 'Edukasi', this)">Edukasi</button>
            <button type="button" class="filter-pill" onclick="filterKabar('sosial', 'Sosial', this)">Aksi Sosial</button>
            <a href="{{ route('articles.index') }}" class="small text-success fw-bold text-decoration-none ms-auto"><i class="fa-solid fa-folder-open me-1"></i> Buka Arsip Lengkap &raquo;</a>
        </div>

        <!-- Daftar Berita (Default 5 Kabar Ditampilkan) -->
        <div class="row g-4" id="kabarContainer">
            <!-- Left: Featured Main Article -->
            @if($latestArticles->isNotEmpty())
                @php $mainArticle = $latestArticles->first(); @endphp
                <div class="col-lg-6 article-item-wrapper" data-post-type="{{ strtolower($mainArticle->post_type) }}" data-default="1">
                    <div class="article-big-card h-100">
                        <img src="{{ $mainArticle->thumbnail_image }}" class="w-100" style="height: 280px; object-fit: cover;" alt="{{ $mainArticle->title }}">
                        <div class="p-4 d-flex flex-column justify-content-between flex-grow-1">
                            <div>
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge bg-success bg-opacity-10 text-success fw-bold">{{ strtoupper($mainArticle->post_type) }}</span>
                                    <small class="text-muted"><i class="fa-regular fa-calendar me-1"></i> {{ $mainArticle->published_at ? $mainArticle->published_at->format('d M Y') : date('d M Y') }}</small>
                                </div>
                                <h4 class="fw-bold mb-2">
                                    <a href="{{ route('articles.detail', $mainArticle->slug) }}" class="text-decoration-none text-dark hover-green">
                                        {{ $mainArticle->title }}
                                    </a>
                                </h4>
                                <p class="text-muted small mb-3">
                                    {{ $mainArticle->excerpt }}
                                </p>
                            </div>
                            <div>
                                <a href="{{ route('articles.detail', $mainArticle->slug) }}" class="fw-bold text-success text-decoration-none">
                                    Baca Selengkapnya <i class="fa-solid fa-arrow-right ms-1"></i>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endif

            <!-- Right: List Mini Articles (4 Artikel Default) -->
            <div class="col-lg-6 d-flex flex-column gap-3" id="miniArticlesCol">
                @foreach($latestArticles->skip(1) as $item)
                    <div class="article-item-wrapper" data-post-type="{{ strtolower($item->post_type) }}" data-default="1">
                        <a href="{{ route('articles.detail', $item->slug) }}" class="article-mini-item">
                            <img src="{{ $item->thumbnail_image }}" class="article-mini-thumb" alt="{{ $item->title }}">
                            <div class="flex-grow-1">
                                <div class="d-flex align-items-center gap-2 mb-1">
                                    <span class="badge bg-light text-dark border small" style="font-size: 0.68rem;">{{ strtoupper($item->post_type) }}</span>
                                    <small class="text-muted" style="font-size: 0.75rem;"><i class="fa-regular fa-clock me-1"></i> {{ $item->published_at ? $item->published_at->format('d M Y') : date('d M Y') }}</small>
                                </div>
                                <h6 class="fw-bold mb-1 text-dark">{{ Str::limit($item->title, 65) }}</h6>
                                <p class="small text-muted mb-0 d-none d-sm-block">{{ Str::limit($item->excerpt, 80) }}</p>
                            </div>
                        </a>
                    </div>
                @endforeach

                <!-- Artikel Lainnya dari All Articles (Hidden by default, muncul saat difilter) -->
                @if(isset($allArticles))
                    @foreach($allArticles->slice(5) as $item)
                        <div class="article-item-wrapper d-none" data-post-type="{{ strtolower($item->post_type) }}" data-default="0">
                            <a href="{{ route('articles.detail', $item->slug) }}" class="article-mini-item">
                                <img src="{{ $item->thumbnail_image }}" class="article-mini-thumb" alt="{{ $item->title }}">
                                <div class="flex-grow-1">
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="badge bg-light text-dark border small" style="font-size: 0.68rem;">{{ strtoupper($item->post_type) }}</span>
                                        <small class="text-muted" style="font-size: 0.75rem;"><i class="fa-regular fa-clock me-1"></i> {{ $item->published_at ? $item->published_at->format('d M Y') : date('d M Y') }}</small>
                                    </div>
                                    <h6 class="fw-bold mb-1 text-dark">{{ Str::limit($item->title, 65) }}</h6>
                                    <p class="small text-muted mb-0 d-none d-sm-block">{{ Str::limit($item->excerpt, 80) }}</p>
                                </div>
                            </a>
                        </div>
                    @endforeach
                @endif
            </div>
        </div>

        <!-- Alert Not Found when filtered -->
        <div id="filterEmptyAlert" class="alert alert-light border text-center p-4 mt-3 d-none">
            <i class="fa-solid fa-newspaper text-muted fs-3 mb-2 d-block"></i>
            <h6 class="fw-bold mb-1">Tidak ditemukan kabar terbaru pada kategori ini</h6>
            <p class="small text-muted mb-3">Silakan lihat arsip artikel selengkapnya melalui halaman dokumentasi berita kami.</p>
            <a href="{{ route('articles.index') }}" class="btn btn-sm btn-outline-success rounded-pill fw-bold">
                <i class="fa-solid fa-folder-open me-1"></i> Buka Arsip Selengkapnya
            </a>
        </div>
    </div>
</section>

<!-- =========================================================================
     6. KALKULATOR ZAKAT INTERAKTIF
========================================================================== -->
<section class="py-5 bg-light" id="kalkulator-zakat">
    <div class="container">
        <div class="zakat-box">
            <div class="row g-4 align-items-center">
                <div class="col-lg-5">
                    <span class="badge text-bg-warning text-dark fw-bold px-3 py-1 rounded-pill mb-2">
                        <i class="fa-solid fa-calculator me-1"></i> SIMULASI ZIS
                    </span>
                    <h3 class="fw-bold text-dark mb-2">Kalkulator Zakat Penghasilan & Maal</h3>
                    <p class="text-muted small mb-3">
                        Hitung kewajiban zakat harta maupun profesi Anda sesuai standar Fiqih dan fatwa Bahtsul Masail Nahdlatul Ulama secara instan.
                    </p>
                    <div class="p-3 bg-white rounded border small mb-3">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Harga Emas Terkini:</span>
                            <strong>Rp {{ number_format($settings['gold_price_per_gram'] ?? 1450000, 0, ',', '.') }}/gram</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Nisab Zakat (85 gr Emas/th):</span>
                            <strong>Rp {{ number_format(($settings['gold_price_per_gram'] ?? 1450000) * 85, 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>Nisab per Bulan:</span>
                            <strong class="text-success">Rp {{ number_format((($settings['gold_price_per_gram'] ?? 1450000) * 85) / 12, 0, ',', '.') }}</strong>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7">
                    <div class="bg-white p-4 rounded-4 border shadow-sm">
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Penghasilan / Gaji per Bulan (Rp):</label>
                            <input type="number" id="calcIncome" class="form-control fw-bold" value="10000000" oninput="calculateZakat()">
                        </div>
                        <div class="mb-3">
                            <label class="form-label small fw-bold text-muted">Pengeluaran Pokok Bulanan (Rp):</label>
                            <input type="number" id="calcExpense" class="form-control" value="3000000" oninput="calculateZakat()">
                        </div>

                        <hr class="my-3">

                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <small class="text-muted d-block">Zakat yang Wajib Dikeluarkan (2.5%):</small>
                                <span class="fs-4 fw-extrabold text-success" id="zakatResult">Rp 175.000</span>
                            </div>
                            <div id="zakatStatusBadge">
                                <span class="badge bg-success p-2">Wajib Zakat</span>
                            </div>
                        </div>

                        <a href="#donasi-cepat" class="btn btn-warning w-100 fw-bold py-2 rounded-pill text-dark shadow-sm">
                            <i class="fa-solid fa-hand-holding-heart me-1"></i> Tunaikan Zakat Anda Sekarang
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- =========================================================================
     7. REKENING RESMI & METODE PENYALURAN DONASI
========================================================================== -->
<section class="py-5" id="rekening-resmi">
    <div class="container">
        <!-- Section Header -->
        <div class="text-center max-w-700 mx-auto mb-5">
            <span class="badge text-bg-success bg-opacity-10 text-success fw-bold px-3 py-2 rounded-pill mb-2">
                <i class="fa-solid fa-shield-halved me-1"></i> PENYALURAN AMANAH & SYAR'I
            </span>
            <h2 class="fw-bold text-dark">Rekening Resmi &amp; Metode Penyaluran Donasi</h2>
            <p class="text-muted">
                Untuk menjaga kemurnian dan amanah dana umat, rekening penerimaan dipisahkan secara tegas antara <strong>Khusus Zakat</strong>, <strong>Selain Zakat (Infaq/Shadaqah)</strong>, serta <strong>QRIS Nasional</strong>.
            </p>
        </div>

        <!-- 3 Kategori Rekening Donasi -->
        <div class="row g-4 mb-4">
            <!-- Kategori A: Khusus Zakat (BSI) -->
            <div class="col-lg-4 col-md-6">
                <div class="rekening-kategori-card card-rekening-zakat">
                    <div>
                        <!-- Header Card -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-success text-white fw-bold px-3 py-1 rounded-pill">
                                <i class="fa-solid fa-hand-holding-heart me-1"></i> KHUSUS ZAKAT
                            </span>
                            <span class="fw-bold text-success fs-5">Bank BSI</span>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.75rem;">Nomor Rekening Zakat:</small>
                            <div class="fs-3 fw-extrabold text-dark tracking-wide my-1" id="rek-bsi-zakat">
                                2026070708
                            </div>
                            <div class="small text-muted">
                                Atas Nama: <strong class="text-dark">Lazisnu Lamongan</strong>
                            </div>
                        </div>

                        <div class="p-3 bg-white rounded-3 border border-success-subtle mb-3 small text-muted">
                            <i class="fa-solid fa-circle-check text-success me-1"></i>
                            Dikhususkan untuk penerimaan dana <strong>Zakat Mal, Zakat Fitrah, Zakat Perniagaan, dan Zakat Profesi</strong> yang disalurkan murni kepada 8 Asnaf mustahik.
                        </div>
                    </div>

                    <div>
                        <button class="btn btn-success rounded-pill fw-bold w-100 py-2 shadow-sm" onclick="copyToClipboard('2026070708', 'btn-bsi-zakat')" id="btn-bsi-zakat" style="background: var(--nu-green);">
                            <i class="fa-regular fa-copy me-1"></i> Salin No. Rekening BSI
                        </button>
                    </div>
                </div>
            </div>

            <!-- Kategori B: Selain Zakat (BRI) -->
            <div class="col-lg-4 col-md-6">
                <div class="rekening-kategori-card card-rekening-infaq">
                    <div>
                        <!-- Header Card -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-primary text-white fw-bold px-3 py-1 rounded-pill">
                                <i class="fa-solid fa-coins me-1"></i> SELAIN ZAKAT (INFAQ &amp; KOIN)
                            </span>
                            <span class="fw-bold text-primary fs-5">Bank BRI</span>
                        </div>

                        <div class="mb-3">
                            <small class="text-muted text-uppercase fw-bold d-block" style="font-size: 0.75rem;">Nomor Rekening Infaq / Shadaqah:</small>
                            <div class="fs-3 fw-extrabold text-dark tracking-wide my-1" id="rek-bri-infaq">
                                00411001448562
                            </div>
                            <div class="small text-muted">
                                Atas Nama: <strong class="text-dark">LAZISNU LAMONGAN</strong>
                            </div>
                        </div>

                        <div class="p-3 bg-white rounded-3 border border-primary-subtle mb-3 small text-muted">
                            <i class="fa-solid fa-circle-check text-primary me-1"></i>
                            Untuk penerimaan <strong>Infaq Umum, Sedekah Subuh, Himpunan Gerakan KOIN NU</strong>, operasional armada ambulans gratis, dan dana tanggap darurat bencana.
                        </div>
                    </div>

                    <div>
                        <button class="btn btn-primary rounded-pill fw-bold w-100 py-2 shadow-sm" onclick="copyToClipboard('00411001448562', 'btn-bri-infaq')" id="btn-bri-infaq">
                            <i class="fa-regular fa-copy me-1"></i> Salin No. Rekening BRI
                        </button>
                    </div>
                </div>
            </div>

            <!-- Kategori C: QRIS Bank BRI -->
            <div class="col-lg-4 col-md-12">
                <div class="rekening-kategori-card card-rekening-qris">
                    <div>
                        <!-- Header Card -->
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span class="badge bg-danger text-white fw-bold px-3 py-1 rounded-pill">
                                <i class="fa-solid fa-qrcode me-1"></i> SCAN QRIS NASIONAL
                            </span>
                            <span class="badge text-bg-warning text-dark fw-bold">Bank BRI</span>
                        </div>

                        <!-- QR Code Preview Frame -->
                        <div class="qris-preview-box mb-3" data-bs-toggle="modal" data-bs-target="#modalQris" title="Klik untuk memperbesar QR Code" style="cursor: pointer;">
                            <img src="{{ asset('qris_lazisnu_bri.jpg') }}" alt="QRIS Bank BRI LAZISNU PCNU Lamongan" class="qris-img shadow-sm" style="max-height: 250px; width: 100%; object-fit: contain;">
                            <div class="text-muted small mt-2">
                                <i class="fa-solid fa-magnifying-glass-plus text-danger me-1"></i> Klik untuk memperbesar / simpan QRIS
                            </div>
                        </div>

                        <div class="text-center small mb-3">
                            <div class="fw-bold text-dark font-monospace fs-6">NMID: ID1026559758341</div>
                            <small class="text-muted">Kompatibel dengan seluruh E-Wallet &amp; Mobile Banking</small>
                            <div class="text-muted mt-1" style="font-size: 0.72rem;">
                                BRImo • Livin' Mandiri • BCA Mobile • BSI Mobile • GoPay • OVO • DANA • ShopeePay • LinkAja
                            </div>
                        </div>
                    </div>

                    <div class="d-flex gap-2">
                        <button type="button" class="btn btn-outline-danger rounded-pill fw-bold w-50 py-2 btn-sm" data-bs-toggle="modal" data-bs-target="#modalQris">
                            <i class="fa-solid fa-expand me-1"></i> Perbesar
                        </button>
                        <a href="{{ asset('qris_lazisnu_bri.jpg') }}" download="QRIS_LAZISNU_BRI_LAMONGAN.jpg" class="btn btn-danger rounded-pill fw-bold w-50 py-2 btn-sm shadow-sm" style="background: #cf152d;">
                            <i class="fa-solid fa-download me-1"></i> Unduh QR
                        </a>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sembunyikan sementara fitur Transparansi / Laporan Audit WTP sesuai instruksi --}}
        {{--
        <div class="bg-success text-white p-4 rounded-4 shadow-sm" style="background: linear-gradient(135deg, #064d27, #0a6b38);">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-3">
                        <div class="fs-1 text-warning"><i class="fa-solid fa-award"></i></div>
                        <div>
                            <h4 class="fw-bold mb-1">Opini Audit Wajar Tanpa Pengecualian (WTP)</h4>
                            <p class="small text-white-50 mb-0">
                                Laporan pertanggungjawaban keuangan LAZISNU diaudit secara berkala oleh Kantor Akuntan Publik (KAP) independen demi menjunjung transparansi dan integritas pengelolaan dana umat.
                            </p>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <a href="{{ route('reports.index') }}" class="btn btn-light fw-bold rounded-pill px-4 py-2 text-success shadow-sm">
                        <i class="fa-solid fa-file-pdf me-1"></i> Unduh Laporan Keuangan
                    </a>
                </div>
            </div>
        </div>
        --}}
    </div>
</section>

<!-- =========================================================================
     8. CALL TO ACTION - GERAKAN KOIN NU
========================================================================== -->
<section class="py-5" style="background: linear-gradient(180deg, #f8fafc 0%, #edf7f0 100%);">
    <div class="container text-center">
        <div class="max-w-700 mx-auto">
            <div class="brand-emblem mx-auto mb-3" style="width: 56px; height: 56px; font-size: 1.8rem;">
                <i class="fa-solid fa-coins text-warning"></i>
            </div>
            <h2 class="fw-bold text-dark mb-2">Ingin Memiliki Kaleng KOIN NU di Rumah Anda?</h2>
            <p class="text-muted mb-4">
                Mari bersama ribuan keluarga Nahdliyin lainnya membangun kemandirian madrasah, pesantren, dan ambulans dhuafa. Relawan kami di tingkat MWC dan Ranting siap mengantarkan kaleng koin infaq ke kediaman Anda.
            </p>
            <div class="d-flex flex-wrap justify-content-center gap-3">
                <a href="https://wa.me/6285188991926?text=Assalamu%27alaikum%20LAZISNU%20Lamongan,%20saya%20ingin%20mendaftar%20untuk%20mendapatkan%20Kaleng%20Koin%20NU%20di%20rumah" target="_blank" class="btn btn-success fw-bold px-4 py-3 rounded-pill shadow-sm" style="background: var(--nu-green);">
                    <i class="fa-brands fa-whatsapp fs-5 me-2"></i> Ajukan Permintaan Kaleng Koin (WA: 0851 8899 1926)
                </a>
                @guest
                    <a href="{{ route('login') }}" class="btn btn-outline-success fw-bold px-4 py-3 rounded-pill">
                        <i class="fa-solid fa-arrow-right-to-bracket me-2"></i> Portal Login
                    </a>
                @else
                    <a href="{{ route('dashboard') }}" class="btn btn-outline-success fw-bold px-4 py-3 rounded-pill">
                        <i class="fa-solid fa-gauge-high me-2"></i> Menuju Dashboard Sistem
                    </a>
                @endguest
            </div>
        </div>
    </div>
</section>

<!-- Modal Zoom QRIS Resmi (Bank BRI & BSI) -->
<div class="modal fade" id="modalQris" tabindex="-1" aria-labelledby="modalQrisLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-danger text-white border-0 py-3" style="background: linear-gradient(135deg, #cf152d, #9a0e20);">
                <h6 class="modal-title fw-bold" id="modalQrisLabel">
                    <i class="fa-solid fa-qrcode me-2"></i> QRIS Resmi LAZISNU PCNU Lamongan
                </h6>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center p-4">
                <!-- Nav Tabs Pilihan QRIS -->
                <ul class="nav nav-pills justify-content-center mb-3" id="pills-tab-qris" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active rounded-pill fw-bold px-3 py-1 btn-sm" id="pills-bri-tab" data-bs-toggle="pill" data-bs-target="#pills-bri" type="button" role="tab">
                            <i class="fa-solid fa-building-columns me-1"></i> QRIS BRI (Infaq &amp; Koin)
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link rounded-pill fw-bold px-3 py-1 btn-sm" id="pills-bsi-tab" data-bs-toggle="pill" data-bs-target="#pills-bsi" type="button" role="tab">
                            <i class="fa-solid fa-hand-holding-dollar me-1"></i> QRIS BSI (Zakat)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="pills-tabContentQris">
                    <!-- Tab BRI (Default) -->
                    <div class="tab-pane fade show active" id="pills-bri" role="tabpanel">
                        <img src="{{ asset('qris_lazisnu_bri.jpg') }}" alt="QRIS Bank BRI LAZISNU Lamongan" class="img-fluid rounded shadow-sm mb-2" style="max-height: 440px;">
                        <div class="font-monospace fw-bold text-dark small mb-1">NMID: ID1026559758341</div>
                        <div class="small text-muted mb-3">Penyelenggara: PT Bank Rakyat Indonesia (Persero) Tbk</div>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ asset('qris_lazisnu_bri.jpg') }}" download="QRIS_LAZISNU_BRI_LAMONGAN.jpg" class="btn btn-outline-danger btn-sm rounded-pill fw-bold">
                                <i class="fa-solid fa-download me-1"></i> Simpan QRIS BRI
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>

                    <!-- Tab BSI -->
                    <div class="tab-pane fade" id="pills-bsi" role="tabpanel">
                        <img src="{{ asset('qris_lazisnu_bsi.jpg') }}" alt="QRIS Bank BSI LAZISNU Lamongan" class="img-fluid rounded shadow-sm mb-2" style="max-height: 440px;">
                        <div class="font-monospace fw-bold text-dark small mb-1">NMID: ID1026582784843</div>
                        <div class="small text-muted mb-3">Penyelenggara: PT Bank Syariah Indonesia Tbk</div>
                        <div class="d-flex justify-content-center gap-2">
                            <a href="{{ asset('qris_lazisnu_bsi.jpg') }}" download="QRIS_LAZISNU_BSI_LAMONGAN.jpg" class="btn btn-outline-success btn-sm rounded-pill fw-bold">
                                <i class="fa-solid fa-download me-1"></i> Simpan QRIS BSI
                            </a>
                            <button type="button" class="btn btn-secondary btn-sm rounded-pill" data-bs-dismiss="modal">Tutup</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@push('scripts')
<script>
    // Format pemisah ribuan otomatis dengan titik (contoh: 100.000)
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

    // Pilihan nominal cepat
    function setNominal(amount, element) {
        const customInput = document.getElementById('customAmount');
        const displayInput = document.getElementById('displayAmount');
        if (customInput) customInput.value = amount;
        if (displayInput) displayInput.value = formatRupiah(amount);

        document.querySelectorAll('.amount-pill').forEach(el => el.classList.remove('active'));
        if (element) {
            element.classList.add('active');
        }
    }

    // Filter Kategori Berita & Kabar Pentasyarufan
    function filterKabar(category, label, element) {
        const labelEl = document.getElementById('labelKategoriAktif');
        if (labelEl) labelEl.innerText = label;

        // Update active class on dropdown and pills
        document.querySelectorAll('.opt-filter, .filter-pill').forEach(el => el.classList.remove('active'));
        if (element) element.classList.add('active');

        const items = document.querySelectorAll('.article-item-wrapper');
        const alertEmpty = document.getElementById('filterEmptyAlert');
        let visibleCount = 0;

        items.forEach(item => {
            const postType = item.getAttribute('data-post-type');
            const isDefault = item.getAttribute('data-default');

            if (category === 'all') {
                if (isDefault === '1') {
                    item.classList.remove('d-none');
                    visibleCount++;
                } else {
                    item.classList.add('d-none');
                }
            } else {
                if (postType === category.toLowerCase()) {
                    item.classList.remove('d-none');
                    visibleCount++;
                } else {
                    item.classList.add('d-none');
                }
            }
        });

        if (visibleCount === 0 && alertEmpty) {
            alertEmpty.classList.remove('d-none');
        } else if (alertEmpty) {
            alertEmpty.classList.add('d-none');
        }
    }

    // Kalkulator Zakat
    function calculateZakat() {
        const income = parseFloat(document.getElementById('calcIncome').value) || 0;
        const expense = parseFloat(document.getElementById('calcExpense').value) || 0;
        const net = Math.max(0, income - expense);
        const monthlyNisab = {{ (($settings['gold_price_per_gram'] ?? 1450000) * 85) / 12 }};

        const resultEl = document.getElementById('zakatResult');
        const badgeEl = document.getElementById('zakatStatusBadge');

        if (net >= monthlyNisab) {
            const zakat = net * 0.025;
            resultEl.innerText = 'Rp ' + Math.round(zakat).toLocaleString('id-ID');
            badgeEl.innerHTML = '<span class="badge bg-success p-2">Wajib Zakat (Mencapai Nisab)</span>';
        } else {
            resultEl.innerText = 'Rp 0';
            badgeEl.innerHTML = '<span class="badge bg-secondary p-2">Belum Wajib Zakat (Dianjurkan Infaq)</span>';
        }
    }

    // Event listener saat DOM siap
    document.addEventListener('DOMContentLoaded', () => {
        calculateZakat();

        // Listener format ribuan otomatis pada input nominal donasi
        const displayInput = document.getElementById('displayAmount');
        const customInput = document.getElementById('customAmount');

        if (displayInput && customInput) {
            displayInput.addEventListener('input', function() {
                let raw = parseRupiah(this.value);
                this.value = raw > 0 ? formatRupiah(raw) : '';
                customInput.value = raw;
                document.querySelectorAll('.amount-pill').forEach(el => el.classList.remove('active'));
            });
        }
    });
</script>
@endpush
