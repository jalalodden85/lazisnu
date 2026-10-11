@extends('layouts.adminlte')

@section('title', 'Detail Pentasyarufan: ' . $pentasyarufan->nomor_transaksi)
@section('page_title', 'Detail Pentasyarufan & SPJ')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pentasyarufan.index') }}">Pentasyarufan</a></li>
    <li class="breadcrumb-item active">{{ $pentasyarufan->nomor_transaksi }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Detail Transaksi & Penyaluran -->
    <div class="col-lg-7">
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-receipt text-success mr-2"></i>Transaksi Pentasyarufan: {{ $pentasyarufan->nomor_transaksi }}
                </h3>
                {!! $pentasyarufan->spj_badge !!}
            </div>
            <div class="card-body">
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                        <button type="button" class="close" data-dismiss="alert">&times;</button>
                    </div>
                @endif

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Nomor Transaksi</small>
                        <code class="text-primary font-weight-bold" style="font-size: 1.1rem;">{{ $pentasyarufan->nomor_transaksi }}</code>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Tanggal Penyaluran</small>
                        <strong class="text-dark"><i class="fas fa-calendar-alt mr-1"></i>{{ $pentasyarufan->tanggal_penyaluran->format('d F Y') }}</strong>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Program Kerja Induk</small>
                        <a href="{{ route('proker.show', $pentasyarufan->program_kerja_id) }}" class="font-weight-bold text-success">
                            {{ $pentasyarufan->programKerja?->nama_kegiatan }}
                        </a>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Pilar Program NU Care</small>
                        <span class="badge text-white px-2 py-1 font-weight-bold" style="background: {{ $pentasyarufan->pilarProgram?->warna ?? '#0a6b38' }};">
                            <i class="{{ $pentasyarufan->pilarProgram?->icon ?? 'fas fa-circle' }} mr-1"></i>
                            {{ $pentasyarufan->pilarProgram?->nama_pilar }}
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Sumber Dana (Periode Penthong)</small>
                        <strong class="text-dark">
                            <i class="fas fa-coins text-warning mr-1"></i>
                            {{ $pentasyarufan->penthong?->nama_periode ?? 'Periode Global' }}
                        </strong>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Potongan Hak Amil / Operasional</small>
                        <span class="badge badge-info font-weight-bold">
                            {{ number_format($pentasyarufan->potongan_operasional_pct ?? 20, 1) }}%
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Penerima Manfaat</small>
                        <strong class="text-dark">{{ $pentasyarufan->penerima_manfaat }}</strong>
                        <span class="badge badge-light border ml-1">{{ $pentasyarufan->jumlah_penerima }} Jiwa</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Wilayah Pelaksana</small>
                        <span class="font-weight-bold text-dark">{{ $pentasyarufan->wilayah_label }}</span>
                        <span class="badge badge-light border ml-1">{{ strtoupper($pentasyarufan->tingkat) }}</span>
                    </div>
                </div>

                <div class="p-3 bg-light rounded border mb-3">
                    <small class="text-muted d-block font-weight-bold">Nominal Dana Dicairkan:</small>
                    <h3 class="font-weight-bold text-danger mb-0">
                        Rp {{ number_format($pentasyarufan->nominal_pencairan, 0, ',', '.') }}
                    </h3>
                </div>

                @if($pentasyarufan->keterangan)
                    <div class="mb-3">
                        <small class="text-muted d-block font-weight-bold mb-1">Keterangan / Catatan:</small>
                        <div class="p-2 rounded bg-light border small text-dark">
                            {{ $pentasyarufan->keterangan }}
                        </div>
                    </div>
                @endif

                <div class="small text-muted border-top pt-2">
                    <i class="fas fa-user-edit mr-1"></i> Dicatat oleh: <strong>{{ $pentasyarufan->user?->name }}</strong> pada {{ $pentasyarufan->created_at->format('d M Y H:i') }}
                </div>
            </div>

            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                <a href="{{ route('pentasyarufan.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>

        <!-- Bukti Penyaluran Lapangan (Foto / Kwitansi) -->
        <div class="card card-outline card-info shadow-sm">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-camera text-info mr-2"></i>Foto / Bukti Penyaluran Lapangan
                </h3>
            </div>
            <div class="card-body text-center">
                @if($pentasyarufan->bukti_penyaluran)
                    @php
                        $ext = pathinfo($pentasyarufan->bukti_penyaluran, PATHINFO_EXTENSION);
                    @endphp
                    @if(in_array(strtolower($ext), ['jpg', 'jpeg', 'png', 'webp']))
                        <img src="{{ asset('storage/' . $pentasyarufan->bukti_penyaluran) }}" alt="Bukti Penyaluran" class="img-fluid rounded border shadow-sm" style="max-height: 380px;">
                    @else
                        <a href="{{ asset('storage/' . $pentasyarufan->bukti_penyaluran) }}" target="_blank" class="btn btn-outline-info font-weight-bold">
                            <i class="fas fa-file-download mr-1"></i> Unduh Berkas Bukti ({{ strtoupper($ext) }})
                        </a>
                    @endif
                @else
                    <div class="py-4 text-muted">
                        <i class="fas fa-image fa-3x mb-2 d-block text-secondary"></i>
                        Tidak ada foto dokumentasi penyerahan yang dilampirkan.
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Status SPJ & Verifikasi Pertanggungjawaban -->
    <div class="col-lg-5">
        <div class="card card-outline card-warning shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-file-invoice-dollar text-warning mr-2"></i>Dokumen Pertanggungjawaban (SPJ)
                </h3>
            </div>
            <div class="card-body">
                <div class="text-center mb-3">
                    <div class="mb-2">{!! $pentasyarufan->spj_badge !!}</div>
                    @if($pentasyarufan->spj_verified_by)
                        <small class="text-muted d-block">
                            Diverifikasi oleh: <strong>{{ $pentasyarufan->spjVerifier?->name }}</strong>
                            <br>{{ $pentasyarufan->spj_verified_at?->format('d M Y H:i') }}
                        </small>
                        @if($pentasyarufan->spj_notes)
                            <div class="alert alert-light border mt-2 small text-left">
                                <strong>Catatan Verifikator:</strong> {{ $pentasyarufan->spj_notes }}
                            </div>
                        @endif
                    @endif
                </div>

                @if($pentasyarufan->berkas_spj)
                    <div class="p-3 bg-light rounded border text-center mb-3">
                        <i class="fas fa-file-pdf fa-3x text-danger mb-2"></i>
                        <div class="font-weight-bold text-dark">Berkas SPJ Telah Diunggah</div>
                        <a href="{{ asset('storage/' . $pentasyarufan->berkas_spj) }}" target="_blank" class="btn btn-sm btn-outline-danger font-weight-bold mt-2">
                            <i class="fas fa-eye mr-1"></i> Buka / Unduh Dokumen SPJ
                        </a>
                    </div>
                @else
                    <div class="alert alert-warning small mb-3">
                        <i class="fas fa-exclamation-circle mr-1"></i>
                        Pencairan dana ini belum dilengkapi dokumen SPJ (Surat Pertanggungjawaban, nota belanja, daftar tanda tangan mustahiq).
                    </div>
                @endif

                <!-- Form Upload / Re-upload SPJ -->
                <form action="{{ route('pentasyarufan.upload-spj', $pentasyarufan->id) }}" method="POST" enctype="multipart/form-data" class="border-top pt-3">
                    @csrf
                    <div class="form-group mb-2">
                        <label class="small font-weight-bold text-dark">
                            {{ $pentasyarufan->berkas_spj ? 'Perbarui / Unggah Ulang Berkas SPJ:' : 'Unggah Dokumen SPJ Lengkap:' }}
                        </label>
                        <input type="file" name="berkas_spj" class="form-control-file border rounded p-1" accept="application/pdf,image/*,.zip" required>
                        <small class="text-muted">Format: PDF, Gambar, atau ZIP (Maks. 10 MB).</small>
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary font-weight-bold btn-block mt-2">
                        <i class="fas fa-upload mr-1"></i> Upload Dokumen SPJ
                    </button>
                </form>

                <!-- Tombol Verifikasi Khusus Bendahara / Super Admin / Ketua -->
                @php
                    $u = auth()->user();
                    $canVerify = $u && ($u->isSuperAdmin() || $u->isBendahara() || $u->isKetua());
                @endphp
                @if($canVerify && $pentasyarufan->berkas_spj)
                    <div class="border-top mt-3 pt-3">
                        <h6 class="font-weight-bold text-dark mb-2">Otoritas Verifikasi Keuangan:</h6>
                        <div class="d-flex">
                            <button type="button" class="btn btn-sm btn-success font-weight-bold flex-grow-1 mr-1" data-toggle="modal" data-target="#modalApproveSpj">
                                <i class="fas fa-check-double mr-1"></i> Sahkan SPJ
                            </button>
                            <button type="button" class="btn btn-sm btn-danger font-weight-bold flex-grow-1" data-toggle="modal" data-target="#modalRejectSpj">
                                <i class="fas fa-times-circle mr-1"></i> Tolak / Revisi
                            </button>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

@if($canVerify ?? false)
<!-- Modal Sahkan SPJ -->
<div class="modal fade" id="modalApproveSpj" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('pentasyarufan.verify-spj', $pentasyarufan->id) }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="status" value="diterima">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> Pengesahan Dokumen SPJ
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda telah memeriksa fisik/digital dokumen SPJ transaksi <strong>{{ $pentasyarufan->nomor_transaksi }}</strong> dan menyatakan bahwa pengeluaran dana sebesar <strong>Rp {{ number_format($pentasyarufan->nominal_pencairan, 0, ',', '.') }}</strong> telah sah dan dapat dipertanggungjawabkan?</p>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold text-dark">Catatan Verifikasi (Opsional)</label>
                    <textarea name="spj_notes" rows="2" class="form-control" placeholder="Contoh: Dokumen lengkap, kwitansi dan tanda terima mustahiq telah diverifikasi sah."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success font-weight-bold">
                    <i class="fas fa-check-double mr-1"></i> Sahkan SPJ Ini
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tolak SPJ -->
<div class="modal fade" id="modalRejectSpj" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('pentasyarufan.verify-spj', $pentasyarufan->id) }}" method="POST" class="modal-content">
            @csrf
            <input type="hidden" name="status" value="ditolak">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-times-circle mr-1"></i> Penolakan / Permintaan Revisi SPJ
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Berikan catatan bagian berkas SPJ yang kurang lengkap atau memerlukan perbaikan:</p>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold text-dark">Catatan Revisi SPJ <span class="text-danger">*</span></label>
                    <textarea name="spj_notes" rows="3" class="form-control" required placeholder="Contoh: Lampiran kwitansi bermaterai dan foto penyerahan belum disertakan."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger font-weight-bold">
                    <i class="fas fa-ban mr-1"></i> Kirim Penolakan / Revisi
                </button>
            </div>
        </form>
    </div>
</div>
@endif
@endsection
