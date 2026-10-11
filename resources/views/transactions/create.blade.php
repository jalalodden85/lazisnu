@extends('layouts.adminlte')

@section('title', 'Input Setoran Relawan')
@section('page_title', 'Form Input Setoran Koin Infaq dari Relawan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transactions.index') }}">Transaksi</a></li>
    <li class="breadcrumb-item active">Input Setoran</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9 col-12">

        <!-- 1. Penanda Status Periode Pentong Aktif / Peringatan Beku -->
        @if($activePenthong && $activePenthong->isOpen())
            <div class="card card-outline card-success shadow-sm mb-4">
                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div class="d-flex align-items-center">
                            <div class="bg-success text-white rounded p-3 mr-3 shadow-sm">
                                <i class="fas fa-drum fa-2x"></i>
                            </div>
                            <div>
                                <span class="badge badge-success px-2 py-1 font-weight-bold text-uppercase">
                                    <i class="fas fa-check-circle mr-1"></i> Periode Terbuka (Open)
                                </span>
                                <h5 class="mb-0 mt-1 font-weight-bold text-success">
                                    Periode Pentong Aktif: {{ $activePenthong->nama_periode }}
                                </h5>
                                <small class="text-muted">
                                    Bulan {{ $activePenthong->bulan_nama }} {{ $activePenthong->tahun }} &bull; Transaksi setoran koin otomatis terikat ke ID Periode: #{{ $activePenthong->id }}
                                </small>
                            </div>
                        </div>
                        <div class="text-md-right mt-2 mt-md-0">
                            <span class="badge badge-light border border-success text-success px-3 py-2 font-weight-bold">
                                <i class="fas fa-calendar-check mr-1"></i> Siap Terima Setoran
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        @else
            <div class="alert alert-danger shadow-sm mb-4 p-4 text-center">
                <div class="mb-2">
                    <i class="fas fa-lock fa-3x text-danger"></i>
                </div>
                <h4 class="font-weight-bold text-danger mb-1">
                    Transaksi Setoran Dibekukan Sementara
                </h4>
                <p class="mb-2 text-dark font-weight-bold">
                    Tidak dapat menginput setoran. Periode pentong saat ini sedang ditutup atau belum dibuka oleh admin.
                </p>
                <p class="text-muted text-sm mb-0">
                    Sistem mengunci formulir setoran demi ketertiban administrasi dan audit bagi hasil. Silakan hubungi Administrator PC Lazisnu untuk membuka periode Penthong baru.
                </p>
            </div>
        @endif

        @php
            $isPeriodeOpen = ($activePenthong && $activePenthong->isOpen());
            $isRantingSuspended = Auth::user()?->isRanting() && Auth::user()?->ranting && !Auth::user()->ranting->is_active;
            $isInputDisabled = !$isPeriodeOpen || $isRantingSuspended;
        @endphp

        <!-- 2. Formulir Input Setoran -->
        <div class="card card-outline {{ $isPeriodeOpen ? 'card-success' : 'card-secondary' }} shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold {{ $isPeriodeOpen ? 'text-success' : 'text-muted' }}">
                    <i class="fas fa-coins mr-2"></i> Pencatatan Setoran Koin Infaq Per Relawan
                </h3>
                @if(!$isPeriodeOpen)
                    <span class="badge badge-danger px-2 py-1"><i class="fas fa-ban mr-1"></i>Form Terkunci</span>
                @endif
            </div>

            <form action="{{ route('transactions.store') }}" method="POST" enctype="multipart/form-data" id="formInputSetoran">
                @csrf
                <input type="hidden" name="periode_pentong_id" value="{{ $activePenthong?->id }}">
                <input type="hidden" name="penthong_id" value="{{ $activePenthong?->id }}">

                <div class="card-body">
                    <div class="callout callout-info mb-4 py-2 bg-light">
                        <h6 class="font-weight-bold text-info mb-1">
                            <i class="fas fa-info-circle mr-1"></i> Ketentuan SOP Setoran Ranting:
                        </h6>
                        <p class="text-xs mb-0 text-muted">
                            Nominal setoran dicatat berdasarkan akumulasi total uang koin yang diserahkan oleh masing-masing Relawan RT/RW setiap bulannya. Pastikan fisik uang telah dihitung dan diverifikasi bersama relawan penyetor.
                        </p>
                    </div>

                    <div class="row">
                        <!-- Dropdown Relawan -->
                        <div class="col-md-6 form-group">
                            <label for="relawan_id" class="font-weight-bold">
                                Relawan Penyetor (Kord. RT/RW) <span class="text-danger">*</span>
                            </label>
                            <select name="relawan_id" id="relawan_id" class="form-control @error('relawan_id') is-invalid @enderror" {{ $isInputDisabled ? 'disabled' : '' }} required>
                                <option value="">-- Pilih Relawan --</option>
                                @foreach($relawans as $r)
                                    <option value="{{ $r->id }}" data-nama="{{ $r->nama }}" data-area="{{ $r->area_tugas }}" {{ (old('relawan_id', request('relawan_id')) == $r->id) ? 'selected' : '' }}>
                                        {{ $r->nama }} — [{{ $r->area_tugas }}] ({{ $r->ranting->name ?? 'Ranting' }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Hanya menampilkan relawan pada wilayah Ranting Anda.</small>
                            @error('relawan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Tanggal Serah Terima -->
                        <div class="col-md-6 form-group">
                            <label for="collection_date" class="font-weight-bold">
                                Tanggal Penyerahan Koin <span class="text-danger">*</span>
                            </label>
                            <input type="date" 
                                   name="collection_date" 
                                   id="collection_date" 
                                   class="form-control @error('collection_date') is-invalid @enderror" 
                                   value="{{ old('collection_date', date('Y-m-d')) }}" 
                                   max="{{ date('Y-m-d') }}"
                                   {{ $isInputDisabled ? 'disabled' : '' }}
                                   required>
                            <small class="form-text text-muted">Tanggal fisik uang koin diserahkan ke pengurus.</small>
                            @error('collection_date')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <div class="row">
                        <!-- Nominal Uang yang Dihimpun -->
                        <div class="col-md-6 form-group">
                            <label for="amount" class="font-weight-bold">
                                Total Nominal Uang Infaq <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold text-success">Rp</span>
                                </div>
                                <input type="number" 
                                       step="500" 
                                       min="500" 
                                       name="amount" 
                                       id="amount" 
                                       class="form-control form-control-lg font-weight-bold @error('amount') is-invalid @enderror" 
                                       placeholder="Contoh: 1250000" 
                                       value="{{ old('amount') }}" 
                                       {{ $isInputDisabled ? 'disabled' : '' }}
                                       required>
                                @error('amount')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted" id="amountFormattedPreview">
                                Masukkan nominal total tanpa titik atau koma.
                            </small>
                        </div>

                        <!-- Upload Foto Bukti -->
                        <div class="col-md-6 form-group">
                            <label for="proof_image" class="font-weight-bold">Foto Dokumentasi / Nota (Opsional)</label>
                            <div class="custom-file">
                                <input type="file" 
                                       name="proof_image" 
                                       class="custom-file-input @error('proof_image') is-invalid @enderror" 
                                       id="proof_image" 
                                       accept="image/*"
                                       {{ $isInputDisabled ? 'disabled' : '' }}>
                                <label class="custom-file-label" for="proof_image">Pilih foto penyerahan...</label>
                                @error('proof_image')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">Format: JPG, PNG, atau WebP (Maks 2MB).</small>
                        </div>
                    </div>

                    <!-- Catatan Tambahan -->
                    <div class="form-group">
                        <label for="notes" class="font-weight-bold">Catatan (Opsional)</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control @error('notes') is-invalid @enderror" placeholder="Contoh: Setoran koin periode bulan ini, pecahan 1.000 dan 500, titipan donatur RT 01." {{ $isInputDisabled ? 'disabled' : '' }}>{{ old('notes') }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal / Kembali
                    </a>
                    
                    @if($isInputDisabled)
                        <button type="button" class="btn btn-secondary font-weight-bold px-4" disabled>
                            <i class="fas fa-lock mr-1"></i> Setoran Dikunci (Periode Tertutup)
                        </button>
                    @else
                        <button type="button" class="btn btn-success font-weight-bold px-4 shadow-sm" id="btnBukaKonfirmasi">
                            <i class="fas fa-save mr-1"></i> Simpan Setoran Relawan
                        </button>
                    @endif
                </div>
            </form>
        </div>

    </div>
</div>

<!-- Modal Konfirmasi Setoran Sebelum Simpan -->
@if($isPeriodeOpen)
<div class="modal fade" id="modalKonfirmasiSetoran" tabindex="-1" role="dialog" aria-labelledby="modalKonfirmasiSetoranLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold" id="modalKonfirmasiSetoranLabel">
                    <i class="fas fa-check-circle mr-2"></i> Konfirmasi Pencatatan Setoran Koin
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <div class="text-center mb-3">
                    <span class="badge badge-success px-3 py-2 text-uppercase mb-2">
                        <i class="fas fa-drum mr-1"></i> Periode Pentong Terikat
                    </span>
                    <h5 class="font-weight-bold text-success mb-0" id="confPeriodeName">
                        {{ $activePenthong->nama_periode }}
                    </h5>
                    <small class="text-muted">Bulan {{ $activePenthong->bulan_nama }} {{ $activePenthong->tahun }} (ID: #{{ $activePenthong->id }})</small>
                </div>

                <div class="table-responsive bg-light p-3 rounded border">
                    <table class="table table-sm table-borderless mb-0">
                        <tr>
                            <td class="text-muted" style="width: 40%;">Relawan Penyetor:</td>
                            <td class="font-weight-bold text-dark" id="confRelawanNama">-</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Area Tugas RT/RW:</td>
                            <td class="font-weight-bold text-dark" id="confRelawanArea">-</td>
                        </tr>
                        <tr>
                            <td class="text-muted">Tanggal Setoran:</td>
                            <td class="font-weight-bold text-dark" id="confTanggal">-</td>
                        </tr>
                        <tr class="border-top">
                            <td class="text-muted align-middle pt-2">Nominal Setoran:</td>
                            <td class="pt-2">
                                <span class="h4 font-weight-bold text-success mb-0" id="confNominal">Rp 0</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <div class="alert alert-warning py-2 px-3 mt-3 mb-0 small">
                    <i class="fas fa-exclamation-triangle mr-1"></i>
                    Pastikan nominal dan relawan telah sesuai. Data yang disimpan akan langsung masuk ke pembukuan periode aktif ini.
                </div>
            </div>
            <div class="modal-footer bg-light justify-content-between">
                <button type="button" class="btn btn-outline-secondary" data-dismiss="modal">
                    <i class="fas fa-edit mr-1"></i> Koreksi Data
                </button>
                <button type="button" class="btn btn-success font-weight-bold px-4" id="btnSubmitFinal">
                    <i class="fas fa-check mr-1"></i> Ya, Simpan Setoran
                </button>
            </div>
        </div>
    </div>
</div>
@endif

@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const fileInput = document.getElementById('proof_image');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            let fileName = e.target.files[0] ? e.target.files[0].name : "Pilih foto penyerahan...";
            let label = e.target.nextElementSibling;
            label.innerText = fileName;
        });
    }

    const amountInput = document.getElementById('amount');
    const amountPreview = document.getElementById('amountFormattedPreview');
    if (amountInput && amountPreview) {
        amountInput.addEventListener('input', function() {
            let val = parseInt(this.value, 10);
            if (!isNaN(val) && val > 0) {
                amountPreview.innerHTML = 'Terbaca: <strong class="text-success font-weight-bold">Rp ' + val.toLocaleString('id-ID') + '</strong>';
            } else {
                amountPreview.innerText = 'Masukkan nominal total tanpa titik atau koma.';
            }
        });
    }

    // Modal Konfirmasi Trigger
    const btnBukaKonfirmasi = document.getElementById('btnBukaKonfirmasi');
    const formInput = document.getElementById('formInputSetoran');
    const modalEl = $('#modalKonfirmasiSetoran');

    if (btnBukaKonfirmasi && formInput) {
        btnBukaKonfirmasi.addEventListener('click', function(e) {
            e.preventDefault();

            // Cek validasi HTML5 bawaan
            if (!formInput.checkValidity()) {
                formInput.reportValidity();
                return;
            }

            const relawanSelect = document.getElementById('relawan_id');
            const selectedOpt = relawanSelect.options[relawanSelect.selectedIndex];
            const relawanNama = selectedOpt ? selectedOpt.getAttribute('data-nama') : '-';
            const relawanArea = selectedOpt ? selectedOpt.getAttribute('data-area') : '-';
            
            const tanggalInput = document.getElementById('collection_date').value;
            const nominalVal = parseInt(amountInput.value, 10) || 0;

            document.getElementById('confRelawanNama').innerText = relawanNama;
            document.getElementById('confRelawanArea').innerText = relawanArea || '-';
            document.getElementById('confTanggal').innerText = tanggalInput;
            document.getElementById('confNominal').innerText = 'Rp ' + nominalVal.toLocaleString('id-ID');

            modalEl.modal('show');
        });

        const btnSubmitFinal = document.getElementById('btnSubmitFinal');
        if (btnSubmitFinal) {
            btnSubmitFinal.addEventListener('click', function() {
                this.disabled = true;
                this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Menyimpan...';
                formInput.submit();
            });
        }
    }
});
</script>
@endpush
