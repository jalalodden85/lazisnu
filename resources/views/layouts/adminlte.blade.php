<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Dashboard PC') | KOIN NU - LAZISNU</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_lazisnu_lmg.png') }}">
    <link rel="shortcut icon" type="image/png" href="{{ asset('logo_lazisnu_lmg.png') }}">

    <!-- Google Font: Source Sans Pro -->
    <link rel="stylesheet" href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700&display=fallback">
    <!-- Font Awesome Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <!-- Theme style (AdminLTE 3.2) -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/css/adminlte.min.css">

    <style>
        :root {
            --nu-green: #0a6b38;
            --nu-dark-green: #064d27;
            --nu-gold: #cba052;
        }
        .main-header.navbar-nu {
            background-color: var(--nu-green);
            color: #ffffff;
        }
        .main-header.navbar-nu .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
        }
        .brand-link.nu-brand {
            background-color: var(--nu-dark-green) !important;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-dark-success .nav-sidebar>.nav-item>.nav-link.active {
            background-color: var(--nu-green) !important;
        }
    </style>
    <!-- Frontend Route Guard & Client Auth State Check -->
    <script>
        (function() {
            @guest
                window.location.replace("{{ route('login') }}?expired=1");
                return;
            @endguest

            try {
                sessionStorage.setItem('koinnu_auth_active', '1');
                sessionStorage.setItem('koinnu_user_id', '{{ Auth::id() }}');
                sessionStorage.setItem('koinnu_user_role', '{{ Auth::user()?->role?->name }}');
                localStorage.setItem('koinnu_auth_active', '1');
            } catch(e) {}
        })();
    </script>
    @stack('styles')
</head>
<body class="hold-transition sidebar-mini layout-fixed">
<div class="wrapper">

    <!-- Navbar -->
    <nav class="main-header navbar navbar-expand navbar-nu navbar-dark">
        <!-- Left navbar links -->
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('dashboard') }}" class="nav-link">
                    <i class="fas fa-home mr-1"></i>
                    @if(Auth::check() && Auth::user()->isRanting())
                        Dashboard Ranting
                    @elseif(Auth::check() && Auth::user()->isMwc())
                        Dashboard MWC
                    @else
                        Dashboard PC
                    @endif
                </a>
            </li>
            <li class="nav-item d-none d-sm-inline-block">
                <a href="{{ route('transactions.create') }}" class="nav-link"><i class="fas fa-plus-circle mr-1"></i> Catat Setoran Relawan</a>
            </li>
        </ul>

        <!-- Right navbar links -->
        <ul class="navbar-nav ml-auto align-items-center">
            @auth
                <!-- User Profile Dropdown Menu (Pojok Kanan Atas) -->
                <li class="nav-item dropdown user-menu mr-2">
                    <a href="#" class="nav-link dropdown-toggle d-flex align-items-center py-1 px-2 text-white" data-toggle="dropdown" aria-expanded="false" style="border-radius: 50px; background: rgba(255, 255, 255, 0.15);">
                        <img src="{{ Auth::user()->avatar_url }}" class="user-image img-circle elevation-1 mr-2" alt="Avatar {{ Auth::user()->name }}" style="width: 28px; height: 28px; object-fit: cover;">
                        <span class="d-none d-md-inline font-weight-bold text-white mr-1 text-truncate" style="max-width: 140px;">
                            {{ Auth::user()->name }}
                        </span>
                        <i class="fas fa-chevron-down text-light small ml-1"></i>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-lg dropdown-menu-right shadow border-0" style="border-radius: 12px; overflow: hidden; min-width: 240px;">
                        <!-- Header Dropdown Profil -->
                        <li class="p-3 text-center" style="background: linear-gradient(135deg, #0a6b38 0%, #15803d 100%);">
                            <img src="{{ Auth::user()->avatar_url }}" class="img-circle elevation-2 mb-2" alt="Avatar" style="width: 65px; height: 65px; object-fit: cover; border: 3px solid #ffffff;">
                            <p class="mb-1 font-weight-bold text-white text-truncate" style="font-size: 0.95rem;">
                                {{ Auth::user()->name }}
                            </p>
                            <span class="badge badge-warning text-dark font-weight-bold">
                                {{ Auth::user()->role_display_name }}
                            </span>
                        </li>
                        <!-- Item Menu Profil & Password -->
                        <li class="p-2 bg-white">
                            <a href="{{ route('profile.edit') }}" class="dropdown-item py-2 rounded d-flex align-items-center">
                                <i class="fas fa-user-edit text-success mr-2 fa-fw"></i>
                                <span>Profil Saya</span>
                            </a>
                            <a href="{{ route('profile.edit') }}#card-password" class="dropdown-item py-2 rounded d-flex align-items-center">
                                <i class="fas fa-key text-warning mr-2 fa-fw"></i>
                                <span>Ubah Password</span>
                            </a>
                            @if(Auth::user()->isSuperAdmin())
                                <a href="{{ route('users.index') }}" class="dropdown-item py-2 rounded d-flex align-items-center">
                                    <i class="fas fa-users-cog text-info mr-2 fa-fw"></i>
                                    <span>Manajemen Akun</span>
                                </a>
                            @endif
                            <div class="dropdown-divider my-1"></div>
                            <form action="{{ route('logout') }}" method="POST" class="m-0 p-0">
                                @csrf
                                <button type="submit" class="dropdown-item py-2 rounded text-danger d-flex align-items-center w-100 border-0 bg-transparent text-left">
                                    <i class="fas fa-sign-out-alt mr-2 fa-fw"></i>
                                    <span class="font-weight-bold">Keluar (Logout)</span>
                                </button>
                            </form>
                        </li>
                    </ul>
                </li>
            @else
                <li class="nav-item mr-2">
                    <a href="{{ route('login') }}" class="btn btn-xs btn-warning text-dark font-weight-bold">
                        <i class="fas fa-sign-in-alt mr-1"></i>Masuk / Login
                    </a>
                </li>
            @endauth
            <li class="nav-item">
                <a class="nav-link" data-widget="fullscreen" href="#" role="button">
                    <i class="fas fa-expand-arrows-alt"></i>
                </a>
            </li>
        </ul>
    </nav>
    <!-- /.navbar -->

    <!-- Main Sidebar Container -->
    <aside class="main-sidebar sidebar-dark-success elevation-4">
        <!-- Brand Logo -->
        <a href="{{ route('dashboard') }}" class="brand-link nu-brand text-center py-2 px-3 d-flex align-items-center justify-content-center" style="background: #ffffff !important; border-bottom: 3px solid var(--nu-green); height: 68px;">
            <img src="{{ asset('logo_lazisnu_lmg.png') }}" alt="NU CARE - LAZISNU PCNU LAMONGAN" style="max-height: 52px; width: auto; object-fit: contain;">
        </a>

        <!-- Sidebar -->
        <div class="sidebar">
            <!-- Sidebar user panel -->
            @php
                $currentUser = Auth::user();
            @endphp
            <div class="user-panel mt-3 pb-3 mb-3 d-flex align-items-center">
                <div class="image">
                    <img src="{{ $currentUser?->avatar_url }}" class="img-circle elevation-1" alt="User Image" style="width: 38px; height: 38px; object-fit: cover;">
                </div>
                <div class="info">
                    <a href="{{ route('profile.edit') }}" class="d-block font-weight-bold text-truncate" style="max-width: 160px;" title="Buka Pengaturan Profil Saya">
                        {{ $currentUser?->name ?? 'Pengguna' }}
                    </a>
                    <small class="text-success">
                        <i class="fas fa-check-circle mr-1"></i>
                        {{ $currentUser ? $currentUser->role_display_name : 'PC Lazisnu (Cabang)' }}
                    </small>
                </div>
            </div>

            <!-- Sidebar Menu -->
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview" role="menu" data-accordion="false">
                    <!-- 1. DASHBOARD -->
                    @if(!$currentUser || $currentUser->hasMenuAccess('dashboard'))
                    <li class="nav-item">
                        <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-tachometer-alt text-warning"></i>
                            <p>Dashboard</p>
                        </a>
                    </li>
                    @endif

                    <!-- 2. MASTER DATA (DROPDOWN: MWC, RANTING, RELAWAN, DONATUR) -->
                    @if(!$currentUser || $currentUser->hasCategoryAccess('Master Data'))
                    <li class="nav-item has-treeview {{ request()->routeIs(['mwcs.*', 'rantings.*', 'relawans.*', 'donators.*', 'mustahiqs.*']) ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs(['mwcs.*', 'rantings.*', 'relawans.*', 'donators.*', 'mustahiqs.*']) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-database"></i>
                            <p>
                                Master Data
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if(!$currentUser || $currentUser->isPc())
                                @if(!$currentUser || $currentUser->hasMenuAccess('mwcs.index'))
                                <li class="nav-item">
                                    <a href="{{ route('mwcs.index') }}" class="nav-link {{ request()->routeIs('mwcs.*') ? 'active' : '' }}">
                                        <i class="fas fa-landmark nav-icon text-info"></i>
                                        <p>MWC</p>
                                    </a>
                                </li>
                                @endif
                                @if(!$currentUser || $currentUser->hasMenuAccess('rantings.index'))
                                <li class="nav-item">
                                    <a href="{{ route('rantings.index') }}" class="nav-link {{ request()->routeIs('rantings.*') ? 'active' : '' }}">
                                        <i class="fas fa-mosque nav-icon text-success"></i>
                                        <p>Ranting</p>
                                    </a>
                                </li>
                                @endif
                            @elseif($currentUser->isMwc())
                                @if(!$currentUser || $currentUser->hasMenuAccess('mwcs.index'))
                                <li class="nav-item">
                                    <a href="{{ route('mwcs.show', $currentUser->mwc_id) }}" class="nav-link {{ request()->routeIs('mwcs.*') ? 'active' : '' }}">
                                        <i class="fas fa-landmark nav-icon text-info"></i>
                                        <p>Data MWC</p>
                                    </a>
                                </li>
                                @endif
                                @if(!$currentUser || $currentUser->hasMenuAccess('rantings.index'))
                                <li class="nav-item">
                                    <a href="{{ route('rantings.index') }}" class="nav-link {{ request()->routeIs('rantings.*') ? 'active' : '' }}">
                                        <i class="fas fa-mosque nav-icon text-success"></i>
                                        <p>Ranting MWC</p>
                                    </a>
                                </li>
                                @endif
                            @elseif($currentUser->isRanting())
                                @if(!$currentUser || $currentUser->hasMenuAccess('rantings.index'))
                                <li class="nav-item">
                                    <a href="{{ route('rantings.show', $currentUser->ranting_id) }}" class="nav-link {{ request()->routeIs('rantings.*') ? 'active' : '' }}">
                                        <i class="fas fa-mosque nav-icon text-success"></i>
                                        <p>Ranting Saya</p>
                                    </a>
                                </li>
                                @endif
                            @endif

                            @if(!$currentUser || $currentUser->hasMenuAccess('relawans.index'))
                            <li class="nav-item">
                                <a href="{{ route('relawans.index') }}" class="nav-link {{ request()->routeIs('relawans.*') ? 'active' : '' }}">
                                    <i class="fas fa-users nav-icon text-primary"></i>
                                    <p>Relawan</p>
                                </a>
                            </li>
                            @endif

                            @if(!$currentUser || $currentUser->hasMenuAccess('donators.index'))
                            <li class="nav-item">
                                <a href="{{ route('donators.index') }}" class="nav-link {{ request()->routeIs('donators.index') ? 'active' : '' }}">
                                    <i class="fas fa-hand-holding-heart nav-icon text-danger"></i>
                                    <p>Donatur (Kotak)</p>
                                </a>
                            </li>
                            @endif

                            @if(!$currentUser || $currentUser->hasMenuAccess('donators.distribution'))
                            <li class="nav-item">
                                <a href="{{ route('donators.distribution') }}" class="nav-link {{ request()->routeIs('donators.distribution') ? 'active' : '' }}">
                                    <i class="fas fa-map-marked-alt nav-icon text-warning"></i>
                                    <p>Pembagian Tugas</p>
                                </a>
                            </li>
                            @endif

                            @if(!$currentUser || $currentUser->hasMenuAccess('mustahiqs.index'))
                            <li class="nav-item">
                                <a href="{{ route('mustahiqs.index') }}" class="nav-link {{ request()->routeIs('mustahiqs.*') ? 'active' : '' }}">
                                    <i class="fas fa-hand-holding-usd nav-icon text-success"></i>
                                    <p>Data Mustahiq</p>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    <!-- 3. TRANSAKSI (DROPDOWN) -->
                    @if(!$currentUser || $currentUser->hasCategoryAccess('Transaksi & Operasional'))
                    <li class="nav-item has-treeview {{ request()->routeIs(['transactions.*', 'admin.online-donations.*', 'penthongs.*']) ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs(['transactions.*', 'admin.online-donations.*', 'penthongs.*']) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-cash-register"></i>
                            <p>
                                Transaksi
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if(!$currentUser || $currentUser->hasMenuAccess('penthongs.index'))
                            <li class="nav-item">
                                <a href="{{ route('penthongs.index') }}" class="nav-link {{ request()->routeIs('penthongs.*') ? 'active' : '' }}">
                                    <i class="fas fa-drum nav-icon text-warning"></i>
                                    <p>Periode Penthong</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || $currentUser->hasMenuAccess('transactions.create'))
                            <li class="nav-item">
                                <a href="{{ route('transactions.create') }}" class="nav-link {{ request()->routeIs('transactions.create') ? 'active' : '' }}">
                                    <i class="fas fa-plus-circle nav-icon text-info"></i>
                                    <p>Input Setoran</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || $currentUser->hasMenuAccess('transactions.index'))
                            <li class="nav-item">
                                <a href="{{ route('transactions.index') }}" class="nav-link {{ request()->routeIs('transactions.index') ? 'active' : '' }}">
                                    <i class="fas fa-coins nav-icon text-success"></i>
                                    <p>Riwayat Setoran</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || ($currentUser->isPc() && $currentUser->hasMenuAccess('admin.online-donations.index')))
                                <li class="nav-item">
                                    <a href="{{ route('admin.online-donations.index') }}" class="nav-link {{ request()->routeIs('admin.online-donations.*') ? 'active' : '' }}">
                                        <i class="fas fa-receipt nav-icon text-info"></i>
                                        <p>Donasi Online</p>
                                    </a>
                                </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    <!-- PENTASYARUFAN (PENGELUARAN DANA & PROKER 5 PILAR) -->
                    @if(!$currentUser || $currentUser->hasCategoryAccess('Pentasyarufan & Penyaluran'))
                    <li class="nav-item has-treeview {{ request()->routeIs(['pilar-program.*', 'proker.*', 'pentasyarufan.*', 'bku.*', 'bku-operasional.*']) ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs(['pilar-program.*', 'proker.*', 'pentasyarufan.*', 'bku.*', 'bku-operasional.*']) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-hand-holding-heart text-danger"></i>
                            <p>
                                Pentasyarufan
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if(!$currentUser || $currentUser->hasMenuAccess('pilar-program.index'))
                            <li class="nav-item">
                                <a href="{{ route('pilar-program.index') }}" class="nav-link {{ request()->routeIs('pilar-program.*') ? 'active' : '' }}">
                                    <i class="fas fa-shapes nav-icon text-info"></i>
                                    <p>5 Pilar & Pagu</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || $currentUser->hasMenuAccess('proker.index'))
                            <li class="nav-item">
                                <a href="{{ route('proker.index') }}" class="nav-link {{ request()->routeIs('proker.*') ? 'active' : '' }}">
                                    <i class="fas fa-clipboard-list nav-icon text-warning"></i>
                                    <p>Program Kerja (RAB)</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || $currentUser->hasMenuAccess('pentasyarufan.index'))
                            <li class="nav-item">
                                <a href="{{ route('pentasyarufan.index') }}" class="nav-link {{ request()->routeIs(['pentasyarufan.index', 'pentasyarufan.create', 'pentasyarufan.show']) ? 'active' : '' }}">
                                    <i class="fas fa-money-bill-wave nav-icon text-success"></i>
                                    <p>Penyaluran & SPJ</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || $currentUser->hasMenuAccess('pentasyarufan.laporan'))
                            <li class="nav-item">
                                <a href="{{ route('pentasyarufan.laporan') }}" class="nav-link {{ request()->routeIs('pentasyarufan.laporan') ? 'active' : '' }}">
                                    <i class="fas fa-file-invoice nav-icon text-primary"></i>
                                    <p>Laporan Penyaluran</p>
                                </a>
                            </li>
                            @endif
                            <li class="nav-item">
                                <a href="{{ route('bku.index') }}" class="nav-link {{ request()->routeIs('bku.*') ? 'active' : '' }}">
                                    <i class="fas fa-book nav-icon text-warning"></i>
                                    <p>Buku Kas Umum (BKU)</p>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('bku-operasional.index') }}" class="nav-link {{ request()->routeIs('bku-operasional.*') ? 'active' : '' }}">
                                    <i class="fas fa-wallet nav-icon text-info"></i>
                                    <p>BKU Operasional (Amil)</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    <!-- 4. PORTAL PUBLIK (DROPDOWN) -->
                    @if(!$currentUser || ($currentUser->isPc() && $currentUser->hasCategoryAccess('Portal Website & Crowdfunding')))
                        <li class="nav-item has-treeview {{ request()->routeIs(['admin.categories.*', 'admin.campaigns.*', 'admin.articles.*', 'admin.sliders.*', 'admin.site-settings.*']) ? 'menu-open' : '' }}">
                            <a href="#" class="nav-link {{ request()->routeIs(['admin.categories.*', 'admin.campaigns.*', 'admin.articles.*', 'admin.sliders.*', 'admin.site-settings.*']) ? 'active' : '' }}">
                                <i class="nav-icon fas fa-globe text-info"></i>
                                <p>
                                    Portal Publik
                                    <i class="right fas fa-angle-left"></i>
                                </p>
                            </a>
                            <ul class="nav nav-treeview">
                                @if(!$currentUser || $currentUser->hasMenuAccess('admin.categories.index'))
                                <li class="nav-item">
                                    <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                                        <i class="fas fa-shapes nav-icon text-info"></i>
                                        <p>5 Pilar Program</p>
                                    </a>
                                </li>
                                @endif
                                @if(!$currentUser || $currentUser->hasMenuAccess('admin.campaigns.index'))
                                <li class="nav-item">
                                    <a href="{{ route('admin.campaigns.index') }}" class="nav-link {{ request()->routeIs('admin.campaigns.*') ? 'active' : '' }}">
                                        <i class="fas fa-bullhorn nav-icon text-danger"></i>
                                        <p>Kampanye Donasi</p>
                                    </a>
                                </li>
                                @endif
                                @if(!$currentUser || $currentUser->hasMenuAccess('admin.articles.index'))
                                <li class="nav-item">
                                    <a href="{{ route('admin.articles.index') }}" class="nav-link {{ request()->routeIs('admin.articles.*') ? 'active' : '' }}">
                                        <i class="fas fa-newspaper nav-icon text-primary"></i>
                                        <p>Berita Penyaluran</p>
                                    </a>
                                </li>
                                @endif
                                @if(!$currentUser || $currentUser->hasMenuAccess('admin.sliders.index'))
                                <li class="nav-item">
                                    <a href="{{ route('admin.sliders.index') }}" class="nav-link {{ request()->routeIs('admin.sliders.*') ? 'active' : '' }}">
                                        <i class="fas fa-images nav-icon text-warning"></i>
                                        <p>Banner Slider</p>
                                    </a>
                                </li>
                                @endif
                                @if(!$currentUser || $currentUser->hasMenuAccess('admin.site-settings.index'))
                                <li class="nav-item">
                                    <a href="{{ route('admin.site-settings.index') }}" class="nav-link {{ request()->routeIs('admin.site-settings.*') ? 'active' : '' }}">
                                        <i class="fas fa-sliders-h nav-icon text-secondary"></i>
                                        <p>Pengaturan Portal</p>
                                    </a>
                                </li>
                                @endif
                            </ul>
                        </li>
                    @endif

                    <!-- 5. LAPORAN (DROPDOWN - TERBAIK UNTUK KOIN NU) -->
                    @if(!$currentUser || $currentUser->hasCategoryAccess('Laporan & Keuangan'))
                    <li class="nav-item has-treeview {{ request()->routeIs(['reports.*', 'admin.financial-reports.*']) ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs(['reports.*', 'admin.financial-reports.*']) ? 'active' : '' }}">
                            <i class="nav-icon fas fa-chart-line text-warning"></i>
                            <p>
                                Laporan
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if(!$currentUser || $currentUser->hasMenuAccess('reports.penthong'))
                            <li class="nav-item">
                                <a href="{{ route('reports.penthong') }}" class="nav-link {{ request()->routeIs(['reports.penthong', 'penthongs.report']) ? 'active' : '' }}">
                                    <i class="fas fa-hand-holding-usd nav-icon text-warning"></i>
                                    <p>Bagi Hasil Penthong</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || $currentUser->hasMenuAccess('reports.koin'))
                            <li class="nav-item">
                                <a href="{{ route('reports.koin') }}" class="nav-link {{ request()->routeIs('reports.koin') ? 'active' : '' }}">
                                    <i class="fas fa-chart-pie nav-icon text-success"></i>
                                    <p>Rekap Perolehan</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || $currentUser->hasMenuAccess('reports.wilayah'))
                            <li class="nav-item">
                                <a href="{{ route('reports.wilayah') }}" class="nav-link {{ request()->routeIs('reports.wilayah') ? 'active' : '' }}">
                                    <i class="fas fa-landmark nav-icon text-info"></i>
                                    <p>Capaian MWC & Rtg</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || $currentUser->hasMenuAccess('reports.relawan'))
                            <li class="nav-item">
                                <a href="{{ route('reports.relawan') }}" class="nav-link {{ request()->routeIs('reports.relawan') ? 'active' : '' }}">
                                    <i class="fas fa-user-check nav-icon text-primary"></i>
                                    <p>Kinerja Relawan</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || $currentUser->hasMenuAccess('reports.donatur'))
                            <li class="nav-item">
                                <a href="{{ route('reports.donatur') }}" class="nav-link {{ request()->routeIs('reports.donatur') ? 'active' : '' }}">
                                    <i class="fas fa-boxes nav-icon text-warning"></i>
                                    <p>Distribusi Kotak</p>
                                </a>
                            </li>
                            @endif
                            @if(!$currentUser || ($currentUser->isPc() && $currentUser->hasMenuAccess('admin.financial-reports.index')))
                                <li class="nav-item">
                                    <a href="{{ route('admin.financial-reports.index') }}" class="nav-link {{ request()->routeIs('admin.financial-reports.*') ? 'active' : '' }}">
                                        <i class="fas fa-file-invoice-dollar nav-icon text-danger"></i>
                                        <p>Laporan Audit WTP</p>
                                    </a>
                                </li>
                            @endif
                            <li class="nav-item">
                                <a href="{{ route('reports.print') }}" target="_blank" class="nav-link">
                                    <i class="fas fa-print nav-icon text-secondary"></i>
                                    <p>Cetak Rekap Resmi</p>
                                </a>
                            </li>
                        </ul>
                    </li>
                    @endif

                    <!-- 6. PENGATURAN & AKUN (DROPDOWN) -->
                    @if(!$currentUser || $currentUser->hasCategoryAccess('Pengaturan Sistem'))
                    <li class="nav-item has-treeview {{ request()->routeIs('users.*') ? 'menu-open' : '' }}">
                        <a href="#" class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}">
                            <i class="nav-icon fas fa-user-cog"></i>
                            <p>
                                Pengaturan
                                <i class="right fas fa-angle-left"></i>
                            </p>
                        </a>
                        <ul class="nav nav-treeview">
                            @if(!$currentUser || $currentUser->hasMenuAccess('users.index'))
                            <li class="nav-item">
                                <a href="{{ route('users.index') }}" class="nav-link {{ request()->routeIs('users.index') ? 'active' : '' }}">
                                    <i class="fas fa-users-cog nav-icon text-warning"></i>
                                    <p>Manajemen Pengguna</p>
                                </a>
                            </li>
                            @endif
                            @auth
                                <li class="nav-item">
                                    <a href="{{ route('users.edit', Auth::id()) }}" class="nav-link {{ request()->routeIs('users.edit') ? 'active' : '' }}">
                                        <i class="fas fa-key nav-icon text-info"></i>
                                        <p>Ubah Sandi</p>
                                    </a>
                                </li>
                            @endauth
                            @if(!$currentUser || $currentUser->hasMenuAccess('buku-panduan'))
                            <li class="nav-item">
                                <a href="{{ route('buku-panduan') }}" target="_blank" class="nav-link">
                                    <i class="fas fa-book-reader nav-icon text-success"></i>
                                    <p>Buku Panduan (PDF)</p>
                                </a>
                            </li>
                            @endif
                        </ul>
                    </li>
                    @endif

                    <li class="nav-header text-uppercase font-weight-bold text-muted mt-2">Pintasan</li>

                    <!-- 7. LIHAT WEBSITE PUBLIK -->
                    <li class="nav-item">
                        <a href="{{ route('home') }}" target="_blank" class="nav-link text-warning font-weight-bold">
                            <i class="nav-icon fas fa-external-link-alt"></i>
                            <p>Lihat Web Depan</p>
                        </a>
                    </li>

                    <!-- 8. LOGOUT -->
                    @auth
                        <li class="nav-item">
                            <a href="{{ route('logout') }}" class="nav-link text-danger font-weight-bold">
                                <i class="nav-icon fas fa-sign-out-alt"></i>
                                <p>Keluar (Logout)</p>
                            </a>
                        </li>
                    @else
                        <li class="nav-item">
                            <a href="{{ route('login') }}" class="nav-link text-warning">
                                <i class="nav-icon fas fa-sign-in-alt"></i>
                                <p>Masuk (Login)</p>
                            </a>
                        </li>
                    @endauth
                </ul>
            </nav>
            <!-- /.sidebar-menu -->
        </div>
        <!-- /.sidebar -->
    </aside>

    <!-- Content Wrapper -->
    <div class="content-wrapper">
        <div class="content-header">
            <div class="container-fluid">
                <div class="row mb-2">
                    <div class="col-sm-7">
                        <h1 class="m-0 font-weight-bold text-dark">@yield('page_title', 'Dashboard PC')</h1>
                    </div>
                    <div class="col-sm-5">
                        <ol class="breadcrumb float-sm-right">
                            <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
                            @yield('breadcrumb')
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main content -->
        <section class="content">
            <div class="container-fluid">
                <!-- Flash Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                        <h5><i class="icon fas fa-check-circle"></i> Berhasil!</h5>
                        {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                        <h5><i class="icon fas fa-ban"></i> Terjadi Kesalahan!</h5>
                        {{ session('error') }}
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if(session('import_failures'))
                    <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                        <h5><i class="icon fas fa-exclamation-triangle"></i> Catatan Import (Dilewati {{ session('total_failures') }} baris):</h5>
                        <p class="mb-1 text-sm">Baris data yang tidak memenuhi validasi/duplikat NIK dilewati secara otomatis:</p>
                        <ul class="mb-0 pl-3 text-sm">
                            @foreach(session('import_failures') as $fail)
                                <li>{{ $fail }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @if($errors->any())
                    <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
                        <h5><i class="icon fas fa-exclamation-circle"></i> Periksa Input:</h5>
                        <ul class="mb-0 pl-3">
                            @foreach($errors->all() as $err)
                                <li>{{ $err }}</li>
                            @endforeach
                        </ul>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                @endif

                @yield('content')
            </div>
        </section>
    </div>

    <footer class="main-footer text-sm">
        <strong>Hak Cipta &copy; {{ date('Y') }} <a href="#" class="text-success font-weight-bold">LAZISNU Care</a>.</strong>
        Sistem Manajemen KOIN NU - Hirarki PC, 15 MWC, Ranting & Relawan RT/RW.
        <div class="float-right d-none d-sm-inline-block">
            <b>Versi</b> 2.0.0
        </div>
    </footer>
</div>

@auth
    <x-modal-cetak-laporan />
@endauth

<!-- SCRIPTS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.4/jquery.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/admin-lte@3.2/dist/js/adminlte.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<!-- FRONTEND ROUTE GUARD & SESSION EXPIRED (HTTP 401) INTERCEPTORS -->
<script>
(function() {
    let isRedirecting = false;

    // 1. Fungsi sentral penanganan Sesi Kadaluarsa / Unauthorized (HTTP 401)
    function triggerSessionExpired(reason) {
        if (isRedirecting) return;
        isRedirecting = true;

        try {
            sessionStorage.removeItem('koinnu_auth_active');
            sessionStorage.removeItem('koinnu_user_id');
            sessionStorage.removeItem('koinnu_user_role');
            localStorage.removeItem('koinnu_auth_active');
        } catch(e) {}

        console.warn('Session expired or unauthorized access detected (HTTP 401):', reason);

        if (window.toastr) {
            toastr.error('Sesi Anda telah habis, silakan login kembali.', 'Sesi Berakhir');
        } else {
            alert('Sesi Anda telah habis, silakan login kembali.');
        }

        window.location.href = "{{ route('login') }}?expired=1";
    }

    // 2. Global Route Guard: Cek auth state sebelum berpindah halaman internal via link click
    document.addEventListener('click', function(e) {
        const link = e.target.closest('a');
        if (!link || !link.href) return;

        const targetUrl = link.href;
        if (targetUrl.startsWith(window.location.origin) && !targetUrl.includes('#') && !targetUrl.includes('javascript:')) {
            const isAuthActive = sessionStorage.getItem('koinnu_auth_active') || localStorage.getItem('koinnu_auth_active');
            if (!isAuthActive && !targetUrl.includes('/login') && !targetUrl.includes('/quick-login')) {
                e.preventDefault();
                e.stopPropagation();
                triggerSessionExpired('Navigasi dicegah: Client-side auth state tidak ditemukan.');
            }
        }
    }, true);

    // 3. Interceptor Fetch API Global
    const originalFetch = window.fetch;
    window.fetch = async function(...args) {
        try {
            const response = await originalFetch.apply(this, args);
            if (response && response.status === 401) {
                triggerSessionExpired('Fetch API mengembalikan HTTP 401');
            }
            return response;
        } catch (error) {
            throw error;
        }
    };

    // 4. Interceptor jQuery AJAX Global
    if (window.jQuery) {
        $(document).ajaxError(function(event, jqXHR, settings, thrownError) {
            if (jqXHR && jqXHR.status === 401) {
                triggerSessionExpired('jQuery AJAX mengembalikan HTTP 401');
            }
        });
    }

    // 5. Interceptor XMLHttpRequest Native Global
    const originalXHROpen = window.XMLHttpRequest.prototype.open;
    window.XMLHttpRequest.prototype.open = function(method, url, ...rest) {
        this.addEventListener('load', function() {
            if (this.status === 401) {
                triggerSessionExpired('XMLHttpRequest mengembalikan HTTP 401');
            }
        });
        return originalXHROpen.apply(this, [method, url, ...rest]);
    };

    // 6. Interceptor Axios Global (jika pustaka Axios di-load)
    if (window.axios) {
        window.axios.interceptors.response.use(
            response => response,
            error => {
                if (error.response && error.response.status === 401) {
                    triggerSessionExpired('Axios mengembalikan HTTP 401');
                }
                return Promise.reject(error);
            }
        );
    }
})();
</script>

@stack('scripts')
</body>
</html>
