@extends('layouts.adminlte')

@section('title', 'Manajemen Akun Pengguna')
@section('page_title', 'Manajemen Akun Pengguna')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item active">Akun Pengguna</li>
@endsection

@section('content')
<!-- KPI Kartu Akun -->
<div class="row">
    @if(!auth()->check() || auth()->user()->isPc())
        <div class="col-lg-3 col-6">
            <div class="small-box bg-danger">
                <div class="inner">
                    <h3>{{ $countPc }}</h3>
                    <p>Akun Pengurus Cabang (PC)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-landmark"></i>
                </div>
                <a href="{{ route('users.index', ['role_group' => 'pc']) }}" class="small-box-footer">
                    Lihat {{ $countPc }} Akun PC <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>{{ $countMwc }}</h3>
                    <p>Akun Admin MWC (Kecamatan)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-building"></i>
                </div>
                <a href="{{ route('users.index', ['role_id' => 2]) }}" class="small-box-footer">
                    Lihat 15 MWC <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $countRanting }}</h3>
                    <p>Akun Admin Ranting (Desa)</p>
                </div>
                <div class="icon">
                    <i class="fas fa-mosque"></i>
                </div>
                <a href="{{ route('users.index', ['role_id' => 3]) }}" class="small-box-footer">
                    Lihat 258 Ranting <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>

        <div class="col-lg-3 col-6">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ $countPc + $countMwc + $countRanting }}</h3>
                    <p>Total Akun Terdaftar</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users-cog"></i>
                </div>
                <a href="{{ route('users.index') }}" class="small-box-footer">
                    Tampilkan Semua <i class="fas fa-arrow-circle-right"></i>
                </a>
            </div>
        </div>
    @elseif(auth()->user()->isMwc())
        <div class="col-lg-4 col-6">
            <div class="small-box bg-primary">
                <div class="inner">
                    <h3>1</h3>
                    <p>Akun Admin MWC Ini</p>
                </div>
                <div class="icon">
                    <i class="fas fa-building"></i>
                </div>
                <span class="small-box-footer">{{ auth()->user()->mwc?->name }}</span>
            </div>
        </div>

        <div class="col-lg-4 col-6">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ $countRanting }}</h3>
                    <p>Akun Ranting Wilayah MWC</p>
                </div>
                <div class="icon">
                    <i class="fas fa-mosque"></i>
                </div>
                <span class="small-box-footer">Desa di Kec. {{ auth()->user()->mwc?->kecamatan }}</span>
            </div>
        </div>

        <div class="col-lg-4 col-12">
            <div class="small-box bg-warning">
                <div class="inner">
                    <h3>{{ 1 + $countRanting }}</h3>
                    <p>Total Akun Terkelola di MWC</p>
                </div>
                <div class="icon">
                    <i class="fas fa-users"></i>
                </div>
                <span class="small-box-footer">Wilayah MWC {{ auth()->user()->mwc?->code }}</span>
            </div>
        </div>
    @elseif(auth()->user()->isRanting())
        <div class="col-12">
            <div class="small-box bg-success">
                <div class="inner">
                    <h3>{{ auth()->user()->name }}</h3>
                    <p>Akun Operator Ranting NU: {{ auth()->user()->ranting?->name }} ({{ auth()->user()->username }})</p>
                </div>
                <div class="icon">
                    <i class="fas fa-mosque"></i>
                </div>
                <span class="small-box-footer">Wilayah Desa {{ auth()->user()->ranting?->desa }}</span>
            </div>
        </div>
    @endif
</div>

<!-- Info Alert Panduan Login -->
<div class="alert alert-info alert-dismissible fade show shadow-sm">
    <h5><i class="icon fas fa-info-circle"></i> Standar Akun & Kata Sandi (Password)</h5>
    <ul class="mb-0 pl-3">
        <li><strong>Password Default:</strong> Seluruh akun disiapkan dengan password seragam: <code class="bg-white px-2 py-1 rounded text-dark font-weight-bold">password</code></li>
        @if(!auth()->check() || auth()->user()->isPc())
            <li><strong>Username Login:</strong> Dapat menggunakan <strong>Kode Wilayah</strong> (misal: <code class="bg-white px-1 text-dark">adminpc</code> untuk PC, <code class="bg-white px-1 text-dark">M01</code> s/d <code class="bg-white px-1 text-dark">M15</code> untuk MWC, dan <code class="bg-white px-1 text-dark">M01R01</code> s/d <code class="bg-white px-1 text-dark">M15R19</code> untuk Ranting) ATAU alamat <strong>Email</strong> resmi.</li>
        @elseif(auth()->user()->isMwc())
            <li><strong>Username Login:</strong> Dapat menggunakan <strong>Kode Wilayah</strong> (misal: <code class="bg-white px-1 text-dark">{{ auth()->user()->username }}</code> atau kode ranting di wilayah ini) ATAU alamat <strong>Email</strong> resmi.</li>
        @else
            <li><strong>Username Login:</strong> Dapat menggunakan <strong>Kode Ranting</strong> (<code class="bg-white px-1 text-dark">{{ auth()->user()->username }}</code>) ATAU alamat <strong>Email</strong> resmi.</li>
        @endif
        <li><strong>Ubah Password:</strong> Klik tombol <span class="badge badge-warning text-dark"><i class="fas fa-key mr-1"></i>Edit / Ubah Sandi</span> pada baris akun yang diinginkan.</li>
    </ul>
    <button type="button" class="close" data-dismiss="alert">&times;</button>
</div>

@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

@if((!auth()->check() || auth()->user()->isPc()) && $pcUsersList->isNotEmpty())
    <!-- 5 Akun Pengguna Khusus Tingkat Pengurus Cabang (PC LAZISNU) -->
    <div class="card card-outline card-danger shadow-sm mb-4">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <h3 class="card-title font-weight-bold text-dark mb-0">
                <i class="fas fa-landmark text-danger mr-2"></i>Akun Khusus Tingkat Pengurus Cabang (PC LAZISNU Lamongan)
            </h3>
            <span class="badge badge-danger px-3 py-1">Role-Based Access Control (RBAC)</span>
        </div>
        <div class="card-body p-0 table-responsive">
            <table class="table table-hover table-striped align-middle mb-0">
                <thead class="bg-light">
                    <tr>
                        <th style="width: 220px;">Peran / Hak Akses</th>
                        <th>Nama Pejabat / Pengguna</th>
                        <th>Username</th>
                        <th>Email Resmi</th>
                        <th>Wewenang Spesifik</th>
                        <th class="text-center" style="width: 170px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($pcUsersList as $pcUser)
                        <tr>
                            <td>{!! $pcUser->role_badge !!}</td>
                            <td>
                                <strong>{{ $pcUser->name }}</strong>
                                @if(Auth::id() === $pcUser->id)
                                    <span class="badge badge-primary ml-1"><i class="fas fa-user-check mr-1"></i>Anda</span>
                                @endif
                            </td>
                            <td>
                                <code class="text-danger font-weight-bold" style="font-size: 0.95rem;">
                                    {{ $pcUser->username }}
                                </code>
                            </td>
                            <td><small class="text-muted"><i class="fas fa-envelope mr-1"></i>{{ $pcUser->email }}</small></td>
                            <td>
                                <small class="text-dark font-weight-normal">
                                    @if($pcUser->role?->name === 'pc_superadmin' || $pcUser->role?->name === 'pc')
                                        <span class="text-danger font-weight-bold"><i class="fas fa-check-circle mr-1"></i>Akses Penuh:</span> Manajemen User & Seluruh Modul
                                    @elseif($pcUser->role?->name === 'pc_ketua')
                                        <span style="color: #6f42c1;" class="font-weight-bold"><i class="fas fa-eye mr-1"></i>Eksekutif:</span> Monitoring Dashboard & Laporan Real-Time (Read-Only)
                                    @elseif($pcUser->role?->name === 'pc_sekretaris')
                                        <span style="color: #20c997;" class="font-weight-bold"><i class="fas fa-file-signature mr-1"></i>Sekretariat:</span> Master Data Wilayah & Relawan, Berita Publikasi, & Arsip Pelaporan
                                    @elseif($pcUser->role?->name === 'pc_bendahara')
                                        <span class="text-warning font-weight-bold"><i class="fas fa-calculator mr-1"></i>Keuangan:</span> Rekonsiliasi Kas, Cetak LPJ, & Approval Pencairan Dana
                                    @elseif($pcUser->role?->name === 'pc_admin_koin')
                                        <span class="text-success font-weight-bold"><i class="fas fa-coins mr-1"></i>KOIN NU:</span> Periode Penthong & Verifikasi Setoran MWC/Ranting
                                    @elseif($pcUser->role?->name === 'pc_admin_online')
                                        <span class="text-info font-weight-bold"><i class="fas fa-globe mr-1"></i>Portal & Donasi:</span> Kampanye Crowdfunding, Berita, & Verifikasi Transfer
                                    @else
                                        {{ $pcUser->role?->description ?? '-' }}
                                    @endif
                                </small>
                            </td>
                            <td class="text-center">
                                <div class="btn-group btn-group-sm">
                                    <a href="{{ route('quick-login', $pcUser->id) }}" class="btn btn-outline-success btn-xs" title="Login Cepat">
                                        <i class="fas fa-sign-in-alt mr-1"></i>Login
                                    </a>
                                    <a href="{{ route('users.edit', $pcUser->id) }}" class="btn btn-outline-warning btn-xs" title="Edit Data & Hak Akses">
                                        <i class="fas fa-sliders-h mr-1"></i>Akses & Sandi
                                    </a>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endif

<!-- Tabel Daftar Pengguna -->
<div class="card card-outline card-success shadow-sm">
    <div class="card-header bg-white">
        <h3 class="card-title font-weight-bold text-dark pt-1">
            <i class="fas fa-id-card-alt text-success mr-2"></i>Daftar Akun Pengguna & Hak Akses
        </h3>
        <div class="card-tools d-flex align-items-center">
            <span class="badge badge-info px-2 py-1 mr-2">Menampilkan {{ $users->firstItem() ?? 0 }} - {{ $users->lastItem() ?? 0 }} dari {{ $users->total() }} akun</span>
            @if(!auth()->check() || auth()->user()->isSuperAdmin())
                <a href="{{ route('users.create') }}" class="btn btn-sm btn-success font-weight-bold shadow-sm">
                    <i class="fas fa-user-plus mr-1"></i> Tambah Akun Baru
                </a>
            @endif
        </div>
    </div>

    <!-- Filter Form -->
    <div class="card-body border-bottom bg-light py-3">
        <form action="{{ route('users.index') }}" method="GET" class="row">
            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Tingkat / Role</label>
                <select name="role_id" class="form-control form-control-sm">
                    <option value="">-- Semua Role --</option>
                    @foreach($roles as $role)
                        <option value="{{ $role->id }}" {{ request('role_id') == $role->id ? 'selected' : '' }}>
                            {{ strtoupper($role->name) }} - {{ $role->description }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-3 form-group mb-2">
                <label class="small font-weight-bold text-muted">Filter MWC NU</label>
                <select name="mwc_id" class="form-control form-control-sm">
                    <option value="">-- Semua MWC NU --</option>
                    @foreach($mwcs as $mwc)
                        <option value="{{ $mwc->id }}" {{ request('mwc_id') == $mwc->id ? 'selected' : '' }}>
                            [{{ $mwc->code }}] {{ $mwc->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="col-md-4 form-group mb-2">
                <label class="small font-weight-bold text-muted">Pencarian Cepat</label>
                <input type="text" name="search" class="form-control form-control-sm" 
                       placeholder="Cari Nama, Username/Kode, Email..." value="{{ request('search') }}">
            </div>

            <div class="col-md-2 form-group mb-2 d-flex align-items-end">
                <button type="submit" class="btn btn-sm btn-success btn-block mr-1">
                    <i class="fas fa-search mr-1"></i> Filter
                </button>
                @if(request()->anyFilled(['role_id', 'mwc_id', 'search']))
                    <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-secondary" title="Reset">
                        <i class="fas fa-undo"></i>
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Table -->
    <div class="card-body table-responsive p-0">
        <table class="table table-hover table-striped align-middle m-0">
            <thead class="thead-light">
                <tr>
                    <th style="width: 50px;">No</th>
                    <th>Nama Pengguna / PJ</th>
                    <th>Username / Kode</th>
                    <th>Email Resmi</th>
                    <th>Role / Tingkat</th>
                    <th>Wilayah Naungan</th>
                    <th>No. Telepon / HP</th>
                    <th>Status</th>
                    <th class="text-center" style="width: 170px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($users as $index => $u)
                    <tr>
                        <td>{{ $users->firstItem() + $index }}</td>
                        <td>
                            <strong>{{ $u->name }}</strong>
                            @if(Auth::id() === $u->id)
                                <span class="badge badge-primary ml-1"><i class="fas fa-user-check mr-1"></i>Sedang Login</span>
                            @endif
                        </td>
                        <td>
                            <code class="text-primary font-weight-bold" style="font-size: 0.95rem;">
                                {{ $u->username ?? '-' }}
                            </code>
                        </td>
                        <td>
                            <small class="text-muted"><i class="fas fa-envelope mr-1"></i>{{ $u->email }}</small>
                        </td>
                        <td>
                            {!! $u->role_badge !!}
                        </td>
                        <td>
                            <small class="font-weight-bold text-dark">
                                {{ $u->wilayah_name }}
                            </small>
                        </td>
                        <td>
                            <small class="text-muted">{{ $u->phone ?? '-' }}</small>
                        </td>
                        <td>
                            @if($u->is_active)
                                <span class="badge badge-success"><i class="fas fa-check mr-1"></i>Aktif</span>
                            @else
                                <span class="badge badge-danger"><i class="fas fa-times mr-1"></i>Non-aktif</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('quick-login', $u->id) }}" class="btn btn-outline-success btn-xs" title="Login Cepat sebagai akun ini">
                                    <i class="fas fa-sign-in-alt mr-1"></i>Login
                                </a>
                                <a href="{{ route('users.edit', $u->id) }}" class="btn btn-outline-primary btn-xs" title="Edit Data, Hak Akses & Password">
                                    <i class="fas fa-user-cog mr-1"></i>Akses
                                </a>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="fas fa-users-slash fa-2x mb-2 d-block text-secondary"></i>
                            Tidak ada data akun pengguna yang sesuai dengan filter pencarian.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($users->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-3">
            <small class="text-muted">
                Halaman {{ $users->currentPage() }} dari {{ $users->lastPage() }}
            </small>
            <div>
                {{ $users->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>
@endsection
