@extends('layouts.adminlte')

@section('title', 'Pengaturan Profil Saya')
@section('page_title', 'Profil Saya & Pengaturan Keamanan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item active">Profil Saya</li>
@endsection

@push('styles')
<style>
    .avatar-wrapper {
        position: relative;
        width: 140px;
        height: 140px;
        margin: 0 auto 1.25rem;
    }
    .avatar-img-preview {
        width: 140px;
        height: 140px;
        object-fit: cover;
        border-radius: 50%;
        border: 4px solid #ffffff;
        box-shadow: 0 8px 24px rgba(10, 107, 56, 0.22);
        background-color: #f8fafc;
        transition: all 0.3s ease;
    }
    .avatar-upload-btn {
        position: absolute;
        bottom: 4px;
        right: 4px;
        background: #0a6b38;
        color: #ffffff;
        border: 2px solid #ffffff;
        border-radius: 50%;
        width: 38px;
        height: 38px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
        transition: transform 0.2s ease, background-color 0.2s ease;
    }
    .avatar-upload-btn:hover {
        background: #064d27;
        color: #ffffff;
        transform: scale(1.1);
    }
    .password-toggle-addon {
        cursor: pointer;
        background-color: #f8fafc;
        border-left: none;
    }
    .password-toggle-addon:hover {
        background-color: #e2e8f0;
    }
</style>
@endpush

@section('content')
<div class="container-fluid">

    {{-- ALERT MESSAGES PROFIL --}}
    @if(session('profile_success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-check-circle mr-2"></i> {{ session('profile_success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    {{-- ALERT MESSAGES PASSWORD --}}
    @if(session('password_success'))
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-key mr-2"></i> {{ session('password_success') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    @if(session('password_error'))
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fas fa-exclamation-triangle mr-2"></i> {{ session('password_error') }}
            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                <span aria-hidden="true">&times;</span>
            </button>
        </div>
    @endif

    <div class="row">
        <!-- ============================================================= -->
        <!-- CARD 1: INFORMASI PROFIL & FOTO AVATAR (KIRI / ATAS)           -->
        <!-- ============================================================= -->
        <div class="col-lg-6 mb-4">
            <div class="card card-outline card-success shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title font-weight-bold text-success mb-0">
                        <i class="fas fa-user-circle mr-2"></i> Informasi Akun & Foto Profil
                    </h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('profile.update') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <!-- Real-time Image Preview Avatar -->
                        <div class="text-center mb-3">
                            <div class="avatar-wrapper">
                                <img id="avatarPreview" 
                                     src="{{ $user->avatar_url }}" 
                                     alt="Avatar {{ $user->name }}" 
                                     class="avatar-img-preview">
                                <label for="avatarInput" class="avatar-upload-btn" title="Pilih Foto Baru">
                                    <i class="fas fa-camera"></i>
                                </label>
                            </div>

                            <input type="file" 
                                   id="avatarInput" 
                                   name="avatar" 
                                   class="d-none @error('avatar') is-invalid @enderror" 
                                   accept="image/jpeg,image/png,image/jpg,image/webp" 
                                   onchange="previewProfileImage(this)">

                            <div class="small text-muted mt-1">
                                Klik ikon kamera untuk memilih foto baru. <br>
                                <span class="badge badge-light border">JPG, PNG, WEBP (Maks. 2MB)</span>
                            </div>

                            <div id="previewBadge" class="mt-2 d-none">
                                <span class="badge badge-info">
                                    <i class="fas fa-eye mr-1"></i> Pratinjau Foto Baru Aktif
                                </span>
                                <button type="button" class="btn btn-xs btn-outline-danger ml-2" onclick="resetImagePreview('{{ $user->avatar_url }}')">
                                    <i class="fas fa-undo mr-1"></i> Batal
                                </button>
                            </div>

                            @error('avatar')
                                <div class="text-danger small mt-2">
                                    <strong>{{ $message }}</strong>
                                </div>
                            @enderror
                        </div>

                        <hr class="my-4">

                        <!-- Badge Metadata Akun (Read-only) -->
                        <div class="bg-light p-3 rounded mb-3 border">
                            <div class="row">
                                <div class="col-6 mb-2">
                                    <small class="text-muted d-block">Username / Kode Wilayah</small>
                                    <strong class="text-dark"><i class="fas fa-id-badge text-success mr-1"></i>{{ $user->username }}</strong>
                                </div>
                                <div class="col-6 mb-2">
                                    <small class="text-muted d-block">Hak Akses (Role)</small>
                                    {!! $user->role_badge !!}
                                </div>
                                <div class="col-12">
                                    <small class="text-muted d-block">Cakupan Wilayah Tugas</small>
                                    <span class="text-secondary font-weight-bold">{{ $user->wilayah_name }}</span>
                                </div>
                            </div>
                        </div>

                        <!-- Nama Lengkap -->
                        <div class="form-group">
                            <label class="font-weight-bold" for="nameInput">
                                Nama Lengkap <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user text-muted"></i></span>
                                </div>
                                <input type="text" 
                                       id="nameInput" 
                                       name="name" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       value="{{ old('name', $user->name) }}" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Alamat Email -->
                        <div class="form-group">
                            <label class="font-weight-bold" for="emailInput">
                                Alamat Email Resmi <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-envelope text-muted"></i></span>
                                </div>
                                <input type="email" 
                                       id="emailInput" 
                                       name="email" 
                                       class="form-control @error('email') is-invalid @enderror" 
                                       value="{{ old('email', $user->email) }}" 
                                       required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Email ini dapat digunakan sebagai alternatif username saat login.</small>
                        </div>

                        <!-- Nomor Telepon / WhatsApp -->
                        <div class="form-group">
                            <label class="font-weight-bold" for="phoneInput">Nomor WhatsApp / HP</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-phone text-muted"></i></span>
                                </div>
                                <input type="text" 
                                       id="phoneInput" 
                                       name="phone" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       value="{{ old('phone', $user->phone) }}" 
                                       placeholder="0812xxxxxxxx">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mt-4 pt-2 text-right">
                            <button type="submit" class="btn btn-success font-weight-bold px-4">
                                <i class="fas fa-save mr-1"></i> Simpan Perubahan Profil
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>

        <!-- ============================================================= -->
        <!-- CARD 2: UBAH KATA SANDI (KANAN / BAWAH)                       -->
        <!-- ============================================================= -->
        <div class="col-lg-6 mb-4">
            <div class="card card-outline card-warning shadow-sm h-100" id="card-password">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-shield-alt text-warning mr-2"></i> Keamanan Akun & Ubah Password
                    </h5>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border small text-muted mb-4">
                        <i class="fas fa-info-circle text-info mr-1"></i>
                        Untuk menjaga keamanan akun Anda, pastikan kata sandi minimal 6 karakter dan tidak dibagikan kepada pihak yang tidak berwenang.
                    </div>

                    <form action="{{ route('profile.password.update') }}" method="POST">
                        @csrf
                        @method('PUT')

                        <!-- Password Saat Ini -->
                        <div class="form-group mb-3">
                            <label class="font-weight-bold" for="currentPasswordInput">
                                Password Saat Ini <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-lock text-muted"></i></span>
                                </div>
                                <input type="password" 
                                       id="currentPasswordInput" 
                                       name="current_password" 
                                       class="form-control @error('current_password') is-invalid @enderror" 
                                       placeholder="Masukkan kata sandi lama Anda" 
                                       required autocomplete="current-password">
                                <div class="input-group-append">
                                    <span class="input-group-text password-toggle-addon" onclick="togglePasswordVisibility('currentPasswordInput', 'iconCurrentPass')" title="Lihat/Sembunyikan">
                                        <i class="fas fa-eye text-muted" id="iconCurrentPass"></i>
                                    </span>
                                </div>
                                @error('current_password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Password Baru -->
                        <div class="form-group mb-3">
                            <label class="font-weight-bold" for="newPasswordInput">
                                Password Baru <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-key text-muted"></i></span>
                                </div>
                                <input type="password" 
                                       id="newPasswordInput" 
                                       name="password" 
                                       class="form-control @error('password') is-invalid @enderror" 
                                       placeholder="Minimal 6 karakter" 
                                       required autocomplete="new-password">
                                <div class="input-group-append">
                                    <span class="input-group-text password-toggle-addon" onclick="togglePasswordVisibility('newPasswordInput', 'iconNewPass')" title="Lihat/Sembunyikan">
                                        <i class="fas fa-eye text-muted" id="iconNewPass"></i>
                                    </span>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <!-- Konfirmasi Password Baru -->
                        <div class="form-group mb-4">
                            <label class="font-weight-bold" for="confirmPasswordInput">
                                Konfirmasi Password Baru <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-check-double text-muted"></i></span>
                                </div>
                                <input type="password" 
                                       id="confirmPasswordInput" 
                                       name="password_confirmation" 
                                       class="form-control" 
                                       placeholder="Ketik ulang kata sandi baru" 
                                       required autocomplete="new-password">
                                <div class="input-group-append">
                                    <span class="input-group-text password-toggle-addon" onclick="togglePasswordVisibility('confirmPasswordInput', 'iconConfirmPass')" title="Lihat/Sembunyikan">
                                        <i class="fas fa-eye text-muted" id="iconConfirmPass"></i>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="p-3 bg-light rounded border mb-4">
                            <h6 class="font-weight-bold small text-dark mb-2">Panduan Keamanan Kata Sandi:</h6>
                            <ul class="small text-muted pl-3 mb-0">
                                <li>Panjang minimal 6 karakter.</li>
                                <li>Gunakan kombinasi huruf kapital, huruf kecil, dan angka.</li>
                                <li>Hindari menggunakan tanggal lahir atau kode wilayah yang mudah ditebak.</li>
                            </ul>
                        </div>

                        <div class="text-right">
                            <button type="submit" class="btn btn-warning font-weight-bold px-4 text-dark shadow-sm">
                                <i class="fas fa-lock mr-1"></i> Perbarui Kata Sandi
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    /**
     * Real-time Image Preview menggunakan FileReader API
     */
    function previewProfileImage(input) {
        if (input.files && input.files[0]) {
            const file = input.files[0];
            
            // Validasi ukuran sisi klien (maks 2MB = 2097152 bytes)
            if (file.size > 2097152) {
                alert('Peringatan: Ukuran file melebihi 2MB. Silakan pilih foto dengan resolusi lebih kecil.');
                input.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = function(e) {
                const previewEl = document.getElementById('avatarPreview');
                if (previewEl) {
                    previewEl.src = e.target.result;
                }
                const badgeEl = document.getElementById('previewBadge');
                if (badgeEl) {
                    badgeEl.classList.remove('d-none');
                }
            };
            reader.readAsDataURL(file);
        }
    }

    /**
     * Mengembalikan pratinjau avatar ke foto tersimpan semula
     */
    function resetImagePreview(originalUrl) {
        const input = document.getElementById('avatarInput');
        const previewEl = document.getElementById('avatarPreview');
        const badgeEl = document.getElementById('previewBadge');

        if (input) input.value = '';
        if (previewEl) previewEl.src = originalUrl;
        if (badgeEl) badgeEl.classList.add('d-none');
    }

    /**
     * Toggle Show/Hide Password dengan Icon Eye
     */
    function togglePasswordVisibility(inputId, iconId) {
        const input = document.getElementById(inputId);
        const icon = document.getElementById(iconId);

        if (!input || !icon) return;

        if (input.type === 'password') {
            input.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            input.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }
</script>
@endpush
