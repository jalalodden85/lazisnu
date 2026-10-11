@extends('layouts.adminlte')

@section('title', 'Ubah Akun & Kata Sandi')
@section('page_title', 'Ubah Data Akun & Kata Sandi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('users.index') }}">Akun Pengguna</a></li>
    <li class="breadcrumb-item active">Ubah Akun</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-user-edit text-success mr-2"></i>Ubah Akun: {{ $user->name }}
                </h3>
                <div class="card-tools">
                    {!! $user->role_badge !!}
                </div>
            </div>

            <form action="{{ route('users.update', $user->id) }}" method="POST">
                @csrf
                @method('PUT')

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

                    <div class="alert alert-light border d-flex align-items-center mb-4">
                        <i class="fas fa-shield-alt text-success fa-2x mr-3"></i>
                        <div>
                            <div class="font-weight-bold">Tingkat Wilayah: {{ $user->wilayah_name }}</div>
                            <small class="text-muted">Role: <strong>{{ strtoupper($user->role?->name ?? '-') }}</strong></small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Nama Petugas / Pengguna <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   value="{{ old('name', $user->name) }}" required>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Username / Kode Wilayah</label>
                            <input type="text" name="username" class="form-control @error('username') is-invalid @enderror" 
                                   value="{{ old('username', $user->username) }}" placeholder="Contoh: M01 / M01R01">
                            <small class="form-text text-muted">Dapat digunakan sebagai username saat login.</small>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Alamat Email Resmi <span class="text-danger">*</span></label>
                            <input type="email" name="email" class="form-control @error('email') is-invalid @enderror" 
                                   value="{{ old('email', $user->email) }}" required>
                        </div>

                        <div class="col-md-6 form-group">
                            <label class="font-weight-bold">Nomor Telepon / WhatsApp</label>
                            <input type="text" name="phone" class="form-control @error('phone') is-invalid @enderror" 
                                   value="{{ old('phone', $user->phone) }}" placeholder="08xxxxxxxxxx">
                        </div>
                    </div>

                    @if(Auth::user()->isSuperAdmin())
                        <div class="row">
                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">Role / Peran Pengguna <span class="text-danger">*</span></label>
                                <select name="role_id" class="form-control" required>
                                    @foreach($roles as $r)
                                        <option value="{{ $r->id }}" {{ old('role_id', $user->role_id) == $r->id ? 'selected' : '' }}>
                                            {{ $r->label }} ({{ strtoupper($r->name) }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="col-md-6 form-group">
                                <label class="font-weight-bold">Wilayah MWC (Jika Staf MWC)</label>
                                <select name="mwc_id" class="form-control">
                                    <option value="">-- Pimpinan Cabang (Global/Semua Wilayah) --</option>
                                    @foreach($mwcs as $mwc)
                                        <option value="{{ $mwc->id }}" {{ old('mwc_id', $user->mwc_id) == $mwc->id ? 'selected' : '' }}>
                                            [{{ $mwc->code }}] {{ $mwc->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    <div class="form-group">
                        <label class="font-weight-bold">Status Akun</label>
                        <select name="is_active" class="form-control">
                            <option value="1" {{ old('is_active', $user->is_active) ? 'selected' : '' }}>Aktif (Dapat Login)</option>
                            <option value="0" {{ !old('is_active', $user->is_active) ? 'selected' : '' }}>Non-aktif (Diblokir)</option>
                        </select>
                    </div>

                    @if(Auth::user()->isSuperAdmin() && isset($groupedMenus))
                        <hr class="my-4">

                        <!-- SECTION: DYNAMIC MENU PERMISSIONS TOGGLE SWITCHES -->
                        <div class="d-flex justify-content-between align-items-center mb-3 border-bottom pb-2">
                            <div>
                                <h5 class="font-weight-bold text-dark mb-0">
                                    <i class="fas fa-toggle-on text-primary mr-2"></i>Konfigurasi Hak Akses Menu Dinamis
                                </h5>
                                <small class="text-muted">Aktifkan (ON) atau nonaktifkan (OFF) menu-menu yang dapat dibuka oleh akun ini.</small>
                            </div>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-primary" onclick="selectAllMenus(true)">
                                    <i class="fas fa-check-double mr-1"></i> Centang Semua (ON)
                                </button>
                                <button type="button" class="btn btn-outline-secondary" onclick="selectAllMenus(false)">
                                    <i class="fas fa-times mr-1"></i> Matikan Semua (OFF)
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
                                                               id="edit_menu_{{ $menu->id }}" 
                                                               name="menus[]" 
                                                               value="{{ $menu->id }}"
                                                               {{ in_array($menu->id, old('menus', $userMenuIds ?? [])) ? 'checked' : '' }}>
                                                        <label class="custom-control-label cursor-pointer" for="edit_menu_{{ $menu->id }}"></label>
                                                    </div>
                                                </div>
                                            @endforeach
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    <hr class="my-4">

                    <!-- Section Ganti Kata Sandi -->
                    <div class="bg-light p-3 rounded border">
                        <h5 class="font-weight-bold text-dark mb-2">
                            <i class="fas fa-key text-warning mr-2"></i>Ubah Kata Sandi (Password)
                        </h5>
                        <p class="small text-muted mb-3">
                            Kosongkan kolom password di bawah ini jika tidak ingin mengubah password yang lama.
                        </p>

                        <div class="form-group mb-0">
                            <label class="font-weight-bold">Password Baru</label>
                            <div class="input-group">
                                <input type="password" id="newPassword" name="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       placeholder="Masukkan kata sandi baru (minimal 6 karakter)">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button" onclick="togglePass()">
                                        <i class="fas fa-eye" id="passIcon"></i>
                                    </button>
                                </div>
                            </div>
                            <small class="form-text text-muted">Password bawaan sistem sebelumnya adalah: <code>password</code></small>
                        </div>
                    </div>

                </div>

                <div class="card-footer bg-white d-flex justify-content-between">
                    <a href="{{ route('users.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Akun
                    </a>
                    <button type="submit" class="btn btn-success px-4 font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan Akun
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
        var input = document.getElementById("newPassword");
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
</script>
@endpush
