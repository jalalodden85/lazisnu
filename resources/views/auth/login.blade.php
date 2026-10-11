<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Login | KOIN NU - LAZISNU Cabang Lamongan</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_lazisnu_lmg.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo_lazisnu_lmg.png') }}">

    <!-- Google Font: Plus Jakarta Sans & Source Sans Pro -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Source+Sans+Pro:wght@400;600;700&display=swap" rel="stylesheet">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Theme style (AdminLTE 3.2) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        :root {
            --nu-green: #0a6b38;
            --nu-dark-green: #064d27;
            --nu-light-green: #e8f5e9;
            --nu-emerald: #16a34a;
            --nu-gold: #cba052;
            --nu-slate: #1e293b;
            --nu-muted: #64748b;
        }

        body.login-page {
            background-color: #f4fbf6;
            background-image:
                radial-gradient(at 0% 0%, rgba(16, 185, 129, 0.08) 0px, transparent 50%),
                radial-gradient(at 100% 0%, rgba(10, 107, 56, 0.10) 0px, transparent 50%),
                radial-gradient(at 50% 100%, rgba(203, 160, 82, 0.07) 0px, transparent 50%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Plus Jakarta Sans', 'Source Sans Pro', -apple-system, BlinkMacSystemFont, sans-serif;
            padding: 30px 15px;
            position: relative;
            overflow-x: hidden;
        }

        /* 1. Watermark Pola Logo LAZISNU Kecil-Kecil Berulang */
        .bg-lazisnu-pattern {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: url("{{ asset('logo_lazisnu_lmg.png') }}");
            background-size: 75px auto;
            background-repeat: repeat;
            opacity: 0.055;
            filter: contrast(110%);
            pointer-events: none;
            z-index: 1;
        }

        /* 2. Ambient Soft Glow Orbs */
        .glow-orb {
            position: fixed;
            border-radius: 50%;
            filter: blur(90px);
            pointer-events: none;
            z-index: 1;
        }
        .glow-orb-1 {
            width: 420px;
            height: 420px;
            background: rgba(16, 185, 129, 0.18);
            top: -120px;
            left: -100px;
        }
        .glow-orb-2 {
            width: 450px;
            height: 450px;
            background: rgba(203, 160, 82, 0.12);
            bottom: -120px;
            right: -100px;
        }

        /* 3. Card Container */
        .login-box {
            width: 430px;
            max-width: 96%;
            position: relative;
            z-index: 2;
        }

        .login-logo {
            margin-bottom: 1.2rem;
        }
        .login-logo a {
            color: var(--nu-dark-green);
            text-decoration: none;
        }
        .logo-badge {
            background: #ffffff;
            padding: 10px 16px;
            border-radius: 20px;
            display: inline-block;
            box-shadow: 0 10px 25px rgba(10, 107, 56, 0.10);
            border: 1.5px solid rgba(10, 107, 56, 0.12);
            transition: transform 0.3s cubic-bezier(0.34, 1.56, 0.64, 1);
        }
        .logo-badge:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 15px 30px rgba(10, 107, 56, 0.14);
        }
        .brand-title {
            color: var(--nu-dark-green);
            font-size: clamp(0.72rem, 2.5vw, 0.95rem);
            font-weight: 700;
            letter-spacing: 0.3px;
            margin-top: 8px;
            margin-bottom: 0;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-wrap: nowrap;
            gap: 6px;
            white-space: nowrap;
        }
        .brand-title .brand-name {
            font-weight: 800;
            color: var(--nu-dark-green);
        }
        .brand-title .brand-sep {
            color: var(--nu-gold);
            font-weight: 700;
            font-size: 0.85rem;
            line-height: 1;
        }
        .brand-title .brand-sub {
            font-weight: 600;
            color: var(--nu-green);
        }

        /* 4. Card Login Body & Header Banner */
        .card-nu {
            background: rgba(255, 255, 255, 0.98);
            backdrop-filter: blur(14px);
            -webkit-backdrop-filter: blur(14px);
            border-radius: 20px;
            box-shadow: 0 25px 50px -12px rgba(10, 107, 56, 0.20), 0 0 0 1px rgba(10, 107, 56, 0.10);
            border: none;
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .card-header-nu {
            background: linear-gradient(135deg, #0a6b38 0%, #15803d 100%);
            padding: 1.75rem 1.8rem 1.45rem;
            color: #ffffff;
            position: relative;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(10, 107, 56, 0.22);
        }
        .card-header-nu::before {
            content: '';
            position: absolute;
            top: -25px;
            right: -25px;
            width: 120px;
            height: 120px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.18) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .card-header-nu::after {
            content: '';
            position: absolute;
            bottom: -30px;
            left: -15px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(203, 160, 82, 0.22) 0%, transparent 70%);
            border-radius: 50%;
            pointer-events: none;
        }
        .header-icon-pill {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            width: 42px;
            height: 42px;
            background: rgba(255, 255, 255, 0.18);
            border: 1px solid rgba(255, 255, 255, 0.30);
            border-radius: 12px;
            color: #ffffff;
            font-size: 1.1rem;
            backdrop-filter: blur(4px);
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.08);
            position: relative;
            z-index: 1;
        }
        .card-header-nu .login-title {
            font-size: 1.35rem;
            font-weight: 800;
            color: #ffffff;
            letter-spacing: 0.4px;
            margin-bottom: 4px;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.15);
            position: relative;
            z-index: 1;
        }
        .card-header-nu .login-subtitle {
            color: rgba(255, 255, 255, 0.90);
            font-size: 0.85rem;
            font-weight: 400;
            margin-bottom: 0;
            letter-spacing: 0.2px;
            position: relative;
            z-index: 1;
        }

        .login-card-body {
            padding: 1.85rem 2.2rem 1.8rem !important;
        }

        /* 5. Inputs & Labels */
        .form-label {
            font-size: 0.82rem;
            font-weight: 600;
            color: #334155;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
        }
        .form-label i {
            color: var(--nu-green);
            margin-right: 6px;
        }

        .custom-input-group .form-control {
            border-radius: 10px 0 0 10px;
            height: 48px;
            border: 1.5px solid #e2e8f0;
            border-right: none;
            background: #f8fafc;
            font-size: 0.92rem;
            color: #1e293b;
            padding-left: 14px;
            transition: all 0.2s ease;
        }
        .custom-input-group .form-control:focus {
            background: #ffffff;
            border-color: var(--nu-green);
            box-shadow: none;
        }
        .custom-input-group .input-group-append .input-group-text,
        .custom-input-group .input-group-append .btn-toggle-eye {
            border-radius: 0 10px 10px 0;
            border: 1.5px solid #e2e8f0;
            border-left: none;
            background: #f8fafc;
            color: #64748b;
            transition: all 0.2s ease;
        }
        .custom-input-group:focus-within .form-control,
        .custom-input-group:focus-within .input-group-text,
        .custom-input-group:focus-within .btn-toggle-eye {
            border-color: var(--nu-green);
            background: #ffffff;
        }
        .custom-input-group:focus-within {
            box-shadow: 0 0 0 3.5px rgba(10, 107, 56, 0.12);
            border-radius: 10px;
        }

        /* 6. Tombol Masuk */
        .btn-nu {
            background: linear-gradient(135deg, #0a6b38 0%, #15803d 100%);
            border: none;
            color: #ffffff;
            font-weight: 700;
            height: 48px;
            border-radius: 10px;
            font-size: 0.95rem;
            letter-spacing: 0.3px;
            box-shadow: 0 6px 16px rgba(10, 107, 56, 0.28);
            transition: all 0.25s cubic-bezier(0.34, 1.56, 0.64, 1);
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-nu:hover {
            background: linear-gradient(135deg, #064d27 0%, #0f6f34 100%);
            color: #ffffff;
            transform: translateY(-2px);
            box-shadow: 0 8px 22px rgba(10, 107, 56, 0.38);
        }
        .btn-nu:active {
            transform: translateY(0);
        }

        /* 7. Footer Back Link & Security Badge */
        .btn-back-portal {
            color: var(--nu-muted);
            font-size: 0.84rem;
            font-weight: 500;
            text-decoration: none !important;
            transition: color 0.2s ease;
        }
        .btn-back-portal:hover {
            color: var(--nu-green);
        }

        .security-badge {
            color: #64748b;
            font-size: 0.78rem;
            line-height: 1.4;
        }
        .security-badge i {
            color: var(--nu-emerald);
        }
    </style>
</head>
<body class="hold-transition login-page">

<!-- Watermark Pola Logo LAZISNU Berulang -->
<div class="bg-lazisnu-pattern"></div>
<div class="glow-orb glow-orb-1"></div>
<div class="glow-orb glow-orb-2"></div>

<div class="login-box my-3">
    <!-- Brand Header -->
    <div class="login-logo text-center">
        <a href="{{ url('/') }}" class="logo-badge mb-2">
            <img src="{{ asset('logo_lazisnu_lmg.png') }}" alt="Logo LAZISNU Lamongan" style="max-height: 75px; object-fit: contain;">
        </a>
        <div class="brand-title">
            <span class="brand-name">SISTEM KOIN NU</span>
            <span class="brand-sep">&bull;</span>
            <span class="brand-sub">NU CARE - LAZISNU PCNU LAMONGAN</span>
        </div>
    </div>

    <!-- Login Card -->
    <div class="card card-nu">
        <!-- Card Header Banner: Gradien Hijau Identik dengan Tombol Masuk ke Sistem -->
        <div class="card-header-nu text-center">
            <div class="header-icon-pill mb-2">
                <i class="fas fa-lock"></i>
            </div>
            <h2 class="login-title">Masuk ke Akun</h2>
            <p class="login-subtitle">Gunakan Email atau Username / Kode Wilayah Anda</p>
        </div>

        <div class="card-body login-card-body">

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" style="border-radius: 10px;" role="alert">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-warning alert-dismissible fade show shadow-sm" style="border-radius: 10px;" role="alert">
                    <i class="fas fa-exclamation-circle mr-1"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            @if(request()->has('expired') && !session('error'))
                <div class="alert alert-warning alert-dismissible fade show shadow-sm" style="border-radius: 10px;" role="alert">
                    <i class="fas fa-clock mr-1"></i> Sesi Anda telah habis, silakan login kembali.
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            @if($errors->has('login'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" style="border-radius: 10px;" role="alert">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $errors->first('login') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif

            <form action="{{ route('login.post') }}" method="POST">
                @csrf
                <div class="form-group mb-3">
                    <label class="form-label">
                        <i class="fas fa-user-circle"></i> Email atau Username / Kode
                    </label>
                    <div class="input-group custom-input-group">
                        <input type="text" name="login" class="form-control @error('login') is-invalid @enderror" 
                               placeholder="adminpc / M01 / email@lazisnu.or.id"
                               value="{{ old('login') }}" required autofocus autocomplete="username">
                        <div class="input-group-append">
                            <span class="input-group-text px-3">
                                <i class="fas fa-id-badge text-muted"></i>
                            </span>
                        </div>
                    </div>
                </div>

                <div class="form-group mb-4">
                    <label class="form-label">
                        <i class="fas fa-lock"></i> Kata Sandi (Password)
                    </label>
                    <div class="input-group custom-input-group">
                        <input type="password" id="passwordInput" name="password" class="form-control" 
                               placeholder="Masukkan kata sandi Anda" required autocomplete="current-password">
                        <div class="input-group-append">
                            <button class="btn btn-toggle-eye px-3" type="button" onclick="togglePassword()" title="Lihat/Sembunyikan Password">
                                <i class="fas fa-eye" id="toggleIcon"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row align-items-center mb-4">
                    <div class="col-6">
                        <div class="custom-control custom-checkbox">
                            <input type="checkbox" class="custom-control-input" id="remember" name="remember" checked>
                            <label class="custom-control-label text-muted small" for="remember" style="cursor: pointer; user-select: none;">
                                Ingat Saya
                            </label>
                        </div>
                    </div>
                    <div class="col-6 text-right">
                        <span class="text-muted small">
                            <i class="fas fa-key text-success mr-1"></i> Akses Resmi
                        </span>
                    </div>
                </div>

                <button type="submit" class="btn btn-nu btn-block">
                    <i class="fas fa-sign-in-alt mr-2"></i> Login
                </button>
            </form>

            <div class="text-center mt-4 pt-3 border-top">
                <a href="{{ url('/') }}" class="btn-back-portal">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Beranda Portal NU Care
                </a>
            </div>

        </div>
    </div>

    <!-- Security Badge & Copyright -->
    <div class="text-center mt-3 security-badge">
        <i class="fas fa-shield-alt mr-1"></i> Terenkripsi & Terlindungi Sistem Keamanan LAZISNU<br>
        &copy; {{ date('Y') }} PC LAZISNU Kabupaten Lamongan. Hak cipta dilindungi.
    </div>
</div>

<!-- jQuery -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<!-- Bootstrap 4 -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<!-- AdminLTE App -->
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>

<script>
    // Bersihkan token / state autentikasi client-side saat berada di halaman login
    try {
        sessionStorage.removeItem('koinnu_auth_active');
        sessionStorage.removeItem('koinnu_user_id');
        sessionStorage.removeItem('koinnu_user_role');
        localStorage.removeItem('koinnu_auth_active');
    } catch(e) {}

    function togglePassword() {
        var input = document.getElementById("passwordInput");
        var icon = document.getElementById("toggleIcon");
        if (input.type === "password") {
            input.type = "text";
            icon.classList.remove("fa-eye");
            icon.classList.add("fa-eye-slash");
        } else {
            input.type = "password";
            icon.classList.remove("fa-eye-slash");
            icon.classList.add("fa-eye");
        }
    }
</script>
</body>
</html>
