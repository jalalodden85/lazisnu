<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="Portal Resmi NU Care - LAZISNU: Layanan Zakat, Infaq, Sedekah, Gerakan Koin NU, dan Penyaluran 5 Pilar Program Mandiri Berdaya.">
    <title>@yield('title', 'NU Care - LAZISNU | Berkhidmat untuk Kemandirian Umat')</title>

    <!-- Favicon KOIN NU / LAZISNU -->
    <link rel="icon" type="image/png" href="{{ asset('logo_lazisnu_lmg.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo_lazisnu_lmg.png') }}">
    <link rel="apple-touch-icon" href="{{ asset('logo_lazisnu_lmg.png') }}">

    <!-- Google Fonts: Plus Jakarta Sans & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800&display=swap" rel="stylesheet">

    <!-- Bootstrap 5 CSS & FontAwesome 6 -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">

    <style>
        :root {
            --nu-green: #0a6b38;
            --nu-dark-green: #064d27;
            --nu-deep: #032b16;
            --nu-emerald: #10b981;
            --nu-gold: #cba052;
            --nu-gold-dark: #b38536;
            --nu-gold-light: #fef3c7;
            --nu-cream: #faf8f2;
            --nu-slate: #1e293b;
        }

        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            color: #334155;
            background-color: #f8fafc;
            overflow-x: hidden;
        }

        h1, h2, h3, h4, h5, h6, .font-heading {
            font-family: 'Outfit', 'Plus Jakarta Sans', sans-serif;
        }

        /* Top Bar */
        .topbar-nu {
            background-color: var(--nu-deep);
            color: rgba(255, 255, 255, 0.85);
            font-size: 0.85rem;
            padding: 8px 0;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .topbar-nu a {
            color: rgba(255, 255, 255, 0.85);
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .topbar-nu a:hover {
            color: var(--nu-gold);
        }

        /* Navbar Header */
        .navbar-main {
            background: #ffffff;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.06);
            padding: 14px 0;
            transition: all 0.3s ease;
        }
        .navbar-brand-wrapper {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .brand-emblem {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--nu-dark-green), var(--nu-green));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--nu-gold);
            font-size: 1.6rem;
            box-shadow: 0 4px 12px rgba(10, 107, 56, 0.25);
        }
        .brand-text-main {
            font-size: 1.35rem;
            font-weight: 800;
            color: var(--nu-green);
            line-height: 1.1;
            letter-spacing: -0.5px;
        }
        .brand-text-sub {
            font-size: 0.72rem;
            font-weight: 600;
            color: #64748b;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2px;
        }

        .nav-link-custom {
            font-weight: 600;
            font-size: 0.94rem;
            color: #334155 !important;
            padding: 8px 14px !important;
            border-radius: 8px;
            transition: all 0.2s ease;
        }
        .nav-link-custom:hover, .nav-link-custom.active {
            color: var(--nu-green) !important;
            background-color: rgba(10, 107, 56, 0.07);
        }

        /* Tombol Donasi Cepat & Login */
        .btn-gold-cta {
            background: linear-gradient(135deg, var(--nu-gold), var(--nu-gold-dark));
            color: #ffffff !important;
            font-weight: 700;
            font-size: 0.9rem;
            padding: 9px 20px;
            border-radius: 50px;
            border: none;
            box-shadow: 0 4px 14px rgba(203, 160, 82, 0.4);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .btn-gold-cta:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 18px rgba(203, 160, 82, 0.55);
            color: #ffffff !important;
        }

        .btn-login-portal {
            border: 1.5px solid var(--nu-green);
            color: var(--nu-green) !important;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 8px 18px;
            border-radius: 50px;
            background: transparent;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-login-portal:hover {
            background-color: var(--nu-green);
            color: #ffffff !important;
        }

        .btn-dashboard-portal {
            background: linear-gradient(135deg, var(--nu-green) 0%, var(--nu-dark-green) 100%);
            color: #ffffff !important;
            font-weight: 700;
            font-size: 0.88rem;
            padding: 8px 18px;
            border-radius: 50px;
            box-shadow: 0 4px 12px rgba(10, 107, 56, 0.3);
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }
        .btn-dashboard-portal:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(10, 107, 56, 0.45);
            color: #ffffff !important;
        }

        .btn-auth-profile {
            border: 1.5px solid #e2e8f0;
            background-color: #ffffff;
            color: #1e293b;
            font-weight: 600;
            font-size: 0.85rem;
            padding: 5px 12px 5px 6px;
            border-radius: 50px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s ease;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.05);
        }
        .btn-auth-profile:hover, .btn-auth-profile:focus {
            background-color: #f8fafc;
            border-color: #cbd5e1;
            color: var(--nu-green);
        }
        .auth-avatar {
            width: 28px;
            height: 28px;
            background-color: var(--nu-light-green);
            color: var(--nu-green);
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 0.8rem;
            font-weight: 700;
        }
        .dropdown-menu-custom {
            border-radius: 12px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.12) !important;
            border: 1px solid #f1f5f9 !important;
            min-width: 230px;
        }

        /* Footer */
        .footer-nu {
            background: linear-gradient(180deg, #053b1e 0%, #032412 100%);
            color: #cbd5e1;
            padding-top: 60px;
            padding-bottom: 24px;
            position: relative;
        }
        .footer-nu a {
            color: #cbd5e1;
            text-decoration: none;
            transition: all 0.2s ease;
        }
        .footer-nu a:hover {
            color: var(--nu-gold);
            padding-left: 4px;
        }
        .footer-bottom {
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            margin-top: 40px;
            padding-top: 20px;
            font-size: 0.85rem;
            color: #94a3b8;
        }

        /* Floating WhatsApp Hotline */
        .floating-wa {
            position: fixed;
            bottom: 25px;
            right: 25px;
            z-index: 1050;
            background: #25d366;
            color: white;
            border-radius: 50px;
            padding: 12px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            box-shadow: 0 8px 24px rgba(37, 211, 102, 0.45);
            text-decoration: none;
            transition: all 0.3s ease;
        }
        .floating-wa:hover {
            transform: translateY(-4px) scale(1.02);
            color: white;
            box-shadow: 0 12px 30px rgba(37, 211, 102, 0.6);
        }

        /* Copy Button Tooltip */
        .btn-copy {
            cursor: pointer;
            transition: all 0.2s ease;
        }
        .btn-copy:hover {
            opacity: 0.85;
        }
    </style>
    @stack('styles')
</head>
<body>

    <!-- TOPBAR -->
    <div class="topbar-nu">
        <div class="container d-flex flex-wrap justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-3">
                <span><i class="fa-solid fa-headset text-warning me-1"></i> Hotline: <a href="https://wa.me/6285188991926" target="_blank" class="text-white fw-bold text-decoration-none">0851 8899 1926</a></span>
                <span class="d-none d-md-inline text-white-50">|</span>
                <span class="d-none d-md-inline"><i class="fa-regular fa-clock me-1 text-warning"></i> {{ $settings['office_hours'] ?? 'Senin - Sabtu 08.00 - 16.00 WIB' }}</span>
            </div>
            <div class="d-flex align-items-center gap-3">
                <a href="#rekening-resmi" class="badge text-bg-warning text-dark fw-bold px-2 py-1"><i class="fa-solid fa-credit-card me-1"></i> Rekening Resmi</a>
                <span class="text-white-50">|</span>
                <div class="d-flex gap-2">
                    <a href="https://facebook.com" target="_blank" title="Facebook"><i class="fa-brands fa-facebook"></i></a>
                    <a href="{{ $settings['instagram_url'] ?? 'https://www.instagram.com/lazisnulamongan?xtok=MXN1aXRja2MxM3Ricw==' }}" target="_blank" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                    <a href="https://youtube.com" target="_blank" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
                    <a href="https://tiktok.com" target="_blank" title="TikTok"><i class="fa-brands fa-tiktok"></i></a>
                </div>
            </div>
        </div>
    </div>

    <!-- MAIN NAVBAR -->
    <nav class="navbar navbar-expand-lg navbar-main sticky-top">
        <div class="container">
            <a class="navbar-brand-wrapper" href="{{ route('home') }}">
                <img src="{{ asset('logo_lazisnu_lmg.png') }}" alt="Logo KOIN NU LAZISNU PCNU Lamongan" style="height: 48px; width: auto; object-fit: contain;">
                <div>
                    <div class="brand-text-main">NU CARE - LAZISNU</div>
                    <div class="brand-text-sub">Pengurus Cabang Nahdlatul Ulama Lamongan</div>
                </div>
            </a>

            <button class="navbar-toggler border-0 shadow-none" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMainContent">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="navbarMainContent">
                <ul class="navbar-nav mx-auto mb-2 mb-lg-0 gap-1">
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom {{ request()->routeIs('home') ? 'active' : '' }}" href="{{ route('home') }}">Beranda</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="{{ route('home') }}#lima-pilar">5 Pilar Program</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom {{ request()->routeIs('programs.*') ? 'active' : '' }}" href="{{ route('programs.index') }}">Donasi & Koin</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom {{ request()->routeIs('articles.*') ? 'active' : '' }}" href="{{ route('articles.index') }}">Kabar Penyaluran</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom" href="{{ route('home') }}#transparansi">Transparansi</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link nav-link-custom {{ request()->routeIs('zakat.calculator') ? 'active' : '' }}" href="{{ route('zakat.calculator') }}">Kalkulator Zakat</a>
                    </li>
                </ul>

                <div class="d-flex align-items-center gap-2 mt-3 mt-lg-0">
                    <a href="{{ route('home') }}#donasi-cepat" class="btn-gold-cta">
                        <i class="fa-solid fa-hand-holding-dollar"></i> Infaq
                    </a>

                    @guest
                        <a href="{{ route('login') }}" class="btn-login-portal" title="Login ke Sistem">
                            <i class="fa-solid fa-arrow-right-to-bracket"></i> Login
                        </a>
                    @endguest

                    @auth
                        <a href="{{ route('dashboard') }}" class="btn-dashboard-portal" title="Masuk ke Panel Dashboard">
                            <i class="fa-solid fa-gauge-high"></i> Dashboard
                        </a>

                        <div class="dropdown">
                            <button class="btn btn-auth-profile dropdown-toggle" type="button" id="userMenuDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                                <span class="auth-avatar"><i class="fa-solid fa-user"></i></span>
                                <span class="d-none d-md-inline text-truncate" style="max-width: 130px;">{{ Auth::user()->name }}</span>
                            </button>
                            <ul class="dropdown-menu dropdown-menu-end shadow border-0 py-2 mt-2 dropdown-menu-custom" aria-labelledby="userMenuDropdown">
                                <li class="px-3 py-2 border-bottom">
                                    <div class="fw-bold text-dark text-truncate">{{ Auth::user()->name }}</div>
                                    <div class="text-success small fw-semibold" style="font-size: 0.78rem;">
                                        <i class="fa-solid fa-shield-halved me-1"></i>{{ Auth::user()->role_display_name }}
                                    </div>
                                    <div class="text-muted" style="font-size: 0.72rem;">{{ Auth::user()->email }}</div>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="{{ route('dashboard') }}">
                                        <i class="fa-solid fa-gauge-high text-success"></i> Panel Dashboard
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="{{ route('profile.edit') }}">
                                        <i class="fa-solid fa-user-gear text-primary"></i> Profil Saya
                                    </a>
                                </li>
                                <li>
                                    <a class="dropdown-item py-2 small d-flex align-items-center gap-2" href="{{ route('profile.edit') }}#card-password">
                                        <i class="fa-solid fa-key text-warning"></i> Ubah Password
                                    </a>
                                </li>
                                <li><hr class="dropdown-divider my-1"></li>
                                <li>
                                    <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                                        @csrf
                                        <button type="submit" class="dropdown-item py-2 small text-danger d-flex align-items-center gap-2 w-100 border-0 bg-transparent text-start">
                                            <i class="fa-solid fa-right-from-bracket"></i> Logout
                                        </button>
                                    </form>
                                </li>
                            </ul>
                        </div>
                    @endauth
                </div>
            </div>
        </div>
    </nav>

    <!-- ALERT MESSAGES -->
    @if(session('success'))
        <div class="container mt-3">
            <div class="alert alert-success alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="fa-solid fa-circle-check fs-4 me-2"></i>
                <div>{{ session('success') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="container mt-3">
            <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center shadow-sm" role="alert">
                <i class="fa-solid fa-triangle-exclamation fs-4 me-2"></i>
                <div>{{ session('error') }}</div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        </div>
    @endif

    <!-- MAIN CONTENT -->
    <main>
        @yield('content')
    </main>

    <!-- FLOATING WHATSAPP BUTTON (KONSULTASI & LAYANAN) -->
    <a href="https://wa.me/6285188991926?text=Assalamu%27alaikum%20LAZISNU%20Lamongan,%20saya%20ingin%20konsultasi%20layanan%20ZIS%20dan%20Koin%20NU" target="_blank" class="floating-wa shadow">
        <i class="fa-brands fa-whatsapp fs-5"></i>
        <span>Konsultasi & Layanan</span>
    </a>

    <!-- FOOTER -->
    <footer class="footer-nu" id="kontak-kami">
        <div class="container">
            <div class="row g-4">
                <!-- Kolom 1: Profil -->
                <div class="col-lg-4 col-md-6">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div class="bg-white p-1 rounded shadow-sm d-flex align-items-center justify-content-center" style="height: 46px; width: 64px;">
                            <img src="{{ asset('logo_lazisnu_lmg.png') }}" alt="Logo NU CARE-LAZISNU" style="max-height: 40px; max-width: 100%; object-fit: contain;">
                        </div>
                        <div>
                            <h5 class="text-white fw-bold mb-0">NU CARE - LAZISNU</h5>
                            <small class="text-warning">Pengurus Cabang Nahdlatul Ulama Lamongan</small>
                        </div>
                    </div>
                    <p class="small text-white-50 lh-base">
                        Lembaga resmi Nahdlatul Ulama yang berkhidmat menghimpun, mengelola, dan mendayagunakan dana Zakat, Infaq, Sedekah (ZIS), serta Gerakan KOIN NU demi kemandirian dan martabat umat.
                    </p>
                    <div class="bg-dark bg-opacity-50 p-2 rounded small text-white-50 border border-white-10">
                        <i class="fa-solid fa-certificate text-warning me-1"></i>
                        {{ $settings['legal_kemenag'] ?? 'SK Kemenag RI No. 255 Tahun 2021 | Terdaftar Resmi BAZNAS' }}
                    </div>
                </div>

                <!-- Kolom 2: 5 Pilar Program -->
                <div class="col-lg-2 col-md-6">
                    <h6 class="text-white fw-bold mb-3 border-bottom border-warning pb-2 d-inline-block">5 Pilar Program</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><a href="{{ route('programs.index', ['pilar' => 'nu-care-cerdas']) }}"><i class="fa-solid fa-graduation-cap me-1 text-info"></i> NU Care Cerdas</a></li>
                        <li><a href="{{ route('programs.index', ['pilar' => 'nu-care-sehat']) }}"><i class="fa-solid fa-heart-pulse me-1 text-danger"></i> NU Care Sehat</a></li>
                        <li><a href="{{ route('programs.index', ['pilar' => 'nu-care-berdaya']) }}"><i class="fa-solid fa-chart-line me-1 text-warning"></i> NU Care Berdaya</a></li>
                        <li><a href="{{ route('programs.index', ['pilar' => 'nu-care-damai']) }}"><i class="fa-solid fa-hand-holding-heart me-1 text-success"></i> NU Care Damai</a></li>
                        <li><a href="{{ route('programs.index', ['pilar' => 'nu-care-hijau']) }}"><i class="fa-solid fa-leaf me-1 text-success"></i> NU Care Hijau</a></li>
                    </ul>
                </div>

                <!-- Kolom 3: Layanan Publik & Tautan -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3 border-bottom border-warning pb-2 d-inline-block">Layanan & Kemandirian</h6>
                    <ul class="list-unstyled small d-flex flex-column gap-2">
                        <li><a href="{{ route('zakat.calculator') }}"><i class="fa-solid fa-calculator me-1"></i> Hitung Zakat Online</a></li>
                        {{-- <li><a href="{{ route('reports.index') }}"><i class="fa-solid fa-file-invoice-dollar me-1"></i> Laporan Audit Publik (WTP)</a></li> --}}
                        <li><a href="{{ route('articles.index', ['tipe' => 'pentasyarufan']) }}"><i class="fa-solid fa-images me-1"></i> Dokumentasi Pentasyarufan</a></li>
                        <li><a href="{{ route('home') }}#rekening-resmi"><i class="fa-solid fa-credit-card me-1"></i> Rekening Resmi Donasi</a></li>
                        <li><a href="https://wa.me/6285188991926?text=Assalamu%27alaikum%20LAZISNU%20Lamongan,%20saya%20ingin%20konsultasi%20layanan" target="_blank"><i class="fa-brands fa-whatsapp me-1 text-success"></i> Konsultasi & Layanan</a></li>
                        @guest
                            <li><a href="{{ route('login') }}"><i class="fa-solid fa-arrow-right-to-bracket me-1"></i> Login Portal KOIN NU</a></li>
                        @else
                            <li><a href="{{ route('dashboard') }}"><i class="fa-solid fa-gauge-high me-1"></i> Panel Dashboard Admin</a></li>
                        @endguest
                    </ul>
                </div>

                <!-- Kolom 4: Sekretariat & Layanan -->
                <div class="col-lg-3 col-md-6">
                    <h6 class="text-white fw-bold mb-3 border-bottom border-warning pb-2 d-inline-block">Sekretariat & Hotline</h6>
                    <p class="small text-white-50 mb-2">
                        <i class="fa-solid fa-location-dot text-warning me-2"></i> Jl. Kyai Amin No 9 Lamongan 62213
                    </p>
                    <p class="small text-white-50 mb-2">
                        <i class="fa-solid fa-phone text-warning me-2"></i> <a href="tel:085188991926" class="text-white-50 text-decoration-none">+62 851 8899 1926</a>
                    </p>
                    <p class="small text-white-50 mb-2">
                        <i class="fa-solid fa-envelope text-warning me-2"></i> <a href="mailto:lazisnulamongan@gmail.com" class="text-white-50 text-decoration-none">lazisnulamongan@gmail.com</a>
                    </p>
                    <p class="small text-white-50 mb-3">
                        <i class="fa-solid fa-globe text-warning me-2"></i> <a href="{{ $settings['website_url'] ?? 'https://lazisnulamongan.or.id' }}" target="_blank" class="text-white-50 text-decoration-underline">{{ $settings['website_url'] ?? 'https://lazisnulamongan.or.id' }}</a>
                    </p>
                    <div class="p-2 bg-success bg-opacity-25 rounded border border-success text-center">
                        <span class="badge bg-danger mb-1"><i class="fa-solid fa-phone-volume"></i> RESPON CEPAT</span>
                        <div class="text-white small fw-bold">Konsultasi & Layanan ZIS</div>
                        <div class="text-warning fw-bold fs-6">0851 8899 1926</div>
                    </div>
                </div>
            </div>

            <!-- Footer Bottom -->
            <div class="footer-bottom text-center">
                <div class="row align-items-center">
                    <div class="col-md-6 text-md-start mb-2 mb-md-0">
                        © 2026 <strong>NU Care - LAZISNU Lamongan</strong>. Mantap Melayani Umat.
                    </div>
                    <div class="col-md-6 text-md-end">
                        <span class="text-white-50">Pengurus Cabang Nahdlatul Ulama Lamongan</span>
                    </div>
                </div>
            </div>
        </div>
    </footer>

    <!-- Bootstrap 5 Bundle JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Clipboard & Toast Script -->
    <script>
        function copyToClipboard(text, elementId) {
            navigator.clipboard.writeText(text).then(() => {
                const el = document.getElementById(elementId);
                const originalHtml = el.innerHTML;
                el.innerHTML = '<i class="fa-solid fa-check text-success"></i> Tersalin!';
                setTimeout(() => {
                    el.innerHTML = originalHtml;
                }, 2000);
            }).catch(err => {
                alert('Gagal menyalin: ' + text);
            });
        }
    </script>
    @stack('scripts')
</body>
</html>
