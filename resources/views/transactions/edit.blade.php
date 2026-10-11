@extends('layouts.adminlte')

@section('title', 'Edit Transaksi Setoran Koin: ' . $transaction->invoice_code)
@section('page_title', 'Perbarui Data Transaksi Setoran Koin')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('transactions.index') }}">Riwayat Setoran</a></li>
    <li class="breadcrumb-item active">Edit {{ $transaction->invoice_code }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-8 col-md-10 col-12">

        <!-- Status Periode Pentong Info Callout -->
        <div class="callout callout-success bg-white shadow-sm mb-3">
            <div class="d-flex justify-content-between align-items-center flex-wrap">
                <div>
                    <i class="fas fa-drum text-success mr-2"></i>
                    <strong>Periode Terkait:</strong> {{ $transaction->penthong->nama_periode ?? '-' }}
                    <span class="badge badge-success ml-2"><i class="fas fa-check-circle mr-1"></i>Periode Aktif (Open)</span>
                </div>
                <small class="text-muted">
                    Transaksi dapat diedit selama periode pentong belum ditutup oleh PC Lazisnu.
                </small>
            </div>
        </div>

        <div class="card card-outline card-success shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold">
                    <i class="fas fa-edit mr-1 text-success"></i> Formulir Edit Transaksi {{ $transaction->invoice_code }}
                </h3>
                <div class="card-tools">
                    <a href="{{ route('transactions.show', $transaction->id) }}" class="btn btn-xs btn-outline-info">
                        <i class="fas fa-receipt mr-1"></i> Lihat Kuitansi
                    </a>
                </div>
            </div>

            <form action="{{ route('transactions.update', $transaction->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')

                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <i class="fas fa-exclamation-circle mr-1"></i> <strong>Terdapat kesalahan pada isian formulir:</strong>
                            <ul class="mb-0 mt-1 pl-3">
                                @foreach($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                <span aria-hidden="true">&times;</span>
                            </button>
                        </div>
                    @endif

                    <!-- Detail Terikat (Read Only Multi-Tenancy) -->
                    <div class="p-3 bg-light rounded border mb-4">
                        <div class="row">
                            <div class="col-md-6 col-12 mb-2 mb-md-0">
                                <small class="text-muted d-block font-weight-bold">Relawan / Koordinator Penyetor:</small>
                                <span class="font-weight-bold text-dark fs-6">{{ $transaction->relawan->nama ?? '-' }}</span>
                                <small class="d-block text-muted">Area Tugas: {{ $transaction->relawan->area_tugas ?? '-' }}</small>
                            </div>
                            <div class="col-md-6 col-12">
                                <small class="text-muted d-block font-weight-bold">Wilayah Ranting & MWC:</small>
                                <span class="text-dark font-weight-bold">{{ $transaction->ranting->name ?? '-' }}</span>
                                <span class="text-muted">({{ $transaction->mwc->name ?? '-' }})</span>
                            </div>
                        </div>
                    </div>

                    <!-- Input 1: Nominal Setoran (Rupiah) -->
                    <div class="form-group mb-3">
                        <label for="amount" class="font-weight-bold">
                            Nominal Setoran Koin (Rp) <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold bg-success text-white">Rp</span>
                            </div>
                            <input type="text"
                                   name="amount"
                                   id="amount"
                                   class="form-control form-control-lg font-weight-bold @error('amount') is-invalid @enderror"
                                   value="{{ old('amount', number_format($transaction->amount, 0, ',', '.')) }}"
                                   placeholder="Contoh: 250.000"
                                   required>
                        </div>
                        <small class="form-text text-muted">Ketik angka murni atau gunakan titik sebagai pemisah ribuan.</small>
                        @error('amount')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Input 2: Tanggal Serah Terima -->
                    <div class="form-group mb-3">
                        <label for="collection_date" class="font-weight-bold">
                            Tanggal Serah Terima Setoran <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text"><i class="fas fa-calendar-alt"></i></span>
                            </div>
                            <input type="date"
                                   name="collection_date"
                                   id="collection_date"
                                   class="form-control @error('collection_date') is-invalid @enderror"
                                   value="{{ old('collection_date', $transaction->collection_date ? \Carbon\Carbon::parse($transaction->collection_date)->format('Y-m-d') : '') }}"
                                   max="{{ date('Y-m-d') }}"
                                   required>
                        </div>
                        @error('collection_date')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Input 3: Catatan Tambahan -->
                    <div class="form-group mb-3">
                        <label for="notes" class="font-weight-bold">Keterangan / Catatan Tambahan:</label>
                        <textarea name="notes"
                                  id="notes"
                                  rows="3"
                                  class="form-control @error('notes') is-invalid @enderror"
                                  placeholder="Contoh: Perolehan pembukaan kotak kaleng RT 01 s/d RT 03">{{ old('notes', $transaction->notes) }}</textarea>
                        @error('notes')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>

                    <!-- Input 4: Bukti Foto / Dokumentasi Penyerahan -->
                    <div class="form-group mb-2">
                        <label for="proof_image" class="font-weight-bold">Foto Bukti / Kuitansi Fisik (Opsional):</label>
                        @if($transaction->proof_image)
                            <div class="mb-2">
                                <a href="{{ asset('storage/' . $transaction->proof_image) }}" target="_blank" class="btn btn-xs btn-outline-secondary">
                                    <i class="fas fa-image mr-1"></i> Lihat Foto Terpasang Saat Ini
                                </a>
                            </div>
                        @endif
                        <input type="file"
                               name="proof_image"
                               id="proof_image"
                               class="form-control-file @error('proof_image') is-invalid @enderror"
                               accept="image/jpeg,image/png,image/jpg,image/webp">
                        <small class="form-text text-muted">Format: JPG, JPEG, PNG, atau WEBP. Maksimal 2MB. Kosongkan jika tidak ingin mengganti foto.</small>
                        @error('proof_image')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @enderror
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('transactions.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>

    </div>
</div>
@endsection
