@extends('layouts.adminlte')

@section('title', 'Tambah Akun Pengguna Baru')
@section('page_title', 'Tambah Akun Pengguna Baru')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Akun Pengguna</a></li>
    <li class="breadcrumb-item active">Tambah Baru</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11 col-12">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-user-plus text-success mr-2"></i>Formulir Pendaftaran Akun & Konfigurasi Menu
                </h3>
                <span class="badge badge-success px-3 py-1">Super Admin Role Builder</span>
            </div>

            <form action="{{ route('users.store') }}" method="POST">
                @csrf

                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show">
                            <h5><i class="icon fas fa-ban"></i> Terjadi Kesalahan</h5>
                            <ul class="mb-0 pl-3">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <!-- SECTION 1: PROFIL & KREDENSIAL -->
                    <h5 class="font-weight-bold text-dark mb-3 border-bottom pb-2">
                        <i class="fas fa-id-card text-success mr-2"></i>1. Identitas Akun & Kredensial Login
                    </h5>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Nama Lengkap Pejabat / Petugas <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name') }}" placeholder="Contoh: H. Ahmad Subagio, M.Si" required>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Username / Kode Wilayah <span class="text-danger">*</span></label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" 
                                   value="{{ old('username') }}" placeholder="Contoh: bendahara_lazisnu / admin_online" required>
                            <small class="form-text text-muted">Digunakan untuk login (huruf kecil, angka, atau underscore tanpa spasi).</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Alamat Email Resmi <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email') }}" placeholder="petugas.pc@lazisnu.or.id" required>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Nomor WhatsApp / HP</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                   value="{{ old('phone') }}" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Role / Peran Utama <span class="text-danger">*</span></label>
                            <select name="role_id" class="form-control" required>
                                @foreach($roles as $r)
                                    <option value="{{ $r->id }}" {{ old('role_id') == $r->id ? 'selected' : '' }}>
                                        {{ $r->label }} ({{ strtoupper($r->name) }})
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Wilayah MWC (Opsional jika Staf MWC)</label>
                            <select name="mwc_id" class="form-control">
                                <option value="">-- Pimpinan Cabang (Global/Semua Wilayah) --</option>
                                @foreach($mwcs as $mwc)
                                    <option value="{{ $mwc->id }}" {{ old('mwc_id') == $mwc->id ? 'selected' : '' }}>
                                        [{{ $mwc->code }}] {{ $mwc->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-4 form-group">
                            <label class="font-weight-bold">Status Akun</label>
                            <select name="is_active" class="form-control">
                                <option value="1" {{ old('is_active', 1) == 1 ? 'selected' : '' }}>Aktif (Dapat Login)</option>
                                <option value="0" {{ old('is_active') === 0 ? 'selected' : '' }}>Non-aktif (Diblokir)</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold">Kata Sandi (Password Awal) <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" id="createPassword" name="password" 
                                   class="form-control @error('password') is-invalid @enderror" 
                                   value="password" placeholder="Minimal 6 karakter" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-secondary" type="button" onclick="togglePass()">
                                    <i class="fas fa-eye" id="passIcon"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">Password awal standar: <code>password</code></small>
                    </div>

                    <hr class="my-4">

                    <!-- SECTION 2: DYNAMIC MENU PERMISSIONS (TOGGLE SWITCHES) -->
                    <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                        <div>
                            <h5 class="font-weight-bold text-dark mb-0">
                                <i class="fas fa-toggle-on text-primary mr-2"></i>2. Kontrol Akses Menu & Modul Dinamis
                            </h5>
                            <small class="text-muted">Aktifkan (ON) atau matikan (OFF) menu-menu yang berhak diakses dan ditampilkan di sidebar user ini.</small>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="selectAllMenus(true)">
                                <i class="fas fa-check-double mr-1"></i> Centang Semua (ON)
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="selectAllMenus(false)">
                                <i class="fas fa-times mr-1"></i> Kosongkan Semua (OFF)
                            </button>
                        </div>
                    </div>

                    <div class="row">
                        @foreach($groupedMenus as $categoryName => $menus)
                            <div class="col-md-6 col-12 mb-3">
                                <div class="card h-100 border shadow-none bg-light">
                                    <div class="card-header py-2 px-3 bg-white border-bottom d-flex justify-content-between align-items-center">
                                        <strong class="text-success font-weight-bold">
                                            <i class="fas fa-folder-open mr-1"></i> {{ $categoryName }}
                                        </strong>
                                        <span class="badge badge-light border">{{ $menus->count() }} Menu</span>
                                    </div>
                                    <div class="card-body p-3">
                                        @foreach($menus as $menu)
                                            <div class="d-flex justify-content-between align-items-center py-2 px-2 border-bottom bg-white rounded mb-2">
                                                <div>
                                                    <i class="{{ $menu->icon }} text-secondary mr-2" style="width: 20px;"></i>
                                                    <strong class="text-dark">{{ $menu->name }}</strong>
                                                    <div class="small text-muted font-monospace pl-4" style="font-size: 0.75rem;">
                                                        Route: {{ $menu->route_name ?? $menu->url_pattern }}
                                                    </div>
                                                </div>
                                                <div class="custom-control custom-switch custom-switch-lg">
                                                    <input type="checkbox" 
                                                           class="custom-control-input menu-toggle-checkbox" 
                                                           id="menu_{{ $menu->id }}" 
                                                           name="menus[]" 
                                                           value="{{ $menu->id }}"
                                                           {{ in_array($menu->id, old('menus', [1])) ? 'checked' : '' }}>
                                                    <label class="custom-control-label cursor-pointer" for="menu_{{ $menu->id }}"></label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                </div>

                <div class="card-footer bg-white d-flex justify-content-between py-3">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success px-4 font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan & Buat Akun Pengguna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    function togglePass() {
        var input = document.getElementById("createPassword");
        var icon = document.getElementById("passIcon");
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

    function selectAllMenus(status) {
        var checkboxes = document.querySelectorAll('.menu-toggle-checkbox');
        checkboxes.forEach(function(cb) {
            cb.checked = status;
        });
    }
</script>
@endpush
