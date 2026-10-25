@extends('layouts.adminlte')

@section('title', 'Donasi Online Masuk & Verifikasi')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">
                    <i class="fas fa-receipt text-warning mr-2"></i>Donasi Online Masuk & Verifikasi
                </h1>
            </div>
            <div class="col-sm-6 text-right d-flex justify-content-end align-items-center flex-wrap gap-2">
                <div class="mr-3">
                    <span class="text-muted small">Total Donasi Terverifikasi: <strong class="text-success fs-5">Rp {{ number_format($stats['total_verified'], 0, ',', '.') }}</strong></span>
                </div>
                <!-- Dropdown Menu Export -->
                <div class="btn-group">
                    <button type="button" class="btn btn-success btn-sm dropdown-toggle shadow-sm" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <i class="fas fa-download mr-1"></i> Export Data
                    </button>
                    <div class="dropdown-menu dropdown-menu-right shadow">
                        <h6 class="dropdown-header text-uppercase font-weight-bold text-muted small">Export Data Terfilter</h6>
                        <a class="dropdown-item text-success" href="{{ route('admin.online-donations.export.excel', request()->query()) }}">
                            <i class="fas fa-file-excel mr-2"></i> Export ke Excel (.xlsx)
                        </a>
                        <a class="dropdown-item text-danger" href="{{ route('admin.online-donations.export.pdf', request()->query()) }}" target="_blank">
                            <i class="fas fa-file-pdf mr-2"></i> Export / Cetak PDF Resmi
                        </a>
                        <div class="dropdown-divider"></div>
                        <h6 class="dropdown-header text-uppercase font-weight-bold text-muted small">Export Seluruh Data</h6>
                        <a class="dropdown-item text-success font-weight-bold" href="{{ route('admin.online-donations.export.excel') }}">
                            <i class="fas fa-database mr-2"></i> Export Semua Data ke Excel
                        </a>
                        <a class="dropdown-item text-danger font-weight-bold" href="{{ route('admin.online-donations.export.pdf') }}" target="_blank">
                            <i class="fas fa-file-pdf mr-2"></i> Export Semua Data ke PDF
                        </a>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item text-dark" href="#" data-toggle="modal" data-target="#modalKustomExport">
                            <i class="fas fa-sliders-h mr-2"></i> Kustom Filter Export...
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">
                <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif
        @if(session('info'))
            <div class="alert alert-info alert-dismissible fade show">
                <i class="fas fa-info-circle mr-1"></i> {{ session('info') }}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        @endif

        <!-- Quick Stat Boxes -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-check-double"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Donasi Terverifikasi</span>
                        <span class="info-box-number text-success">{{ $stats['count_verified'] }} Transaksi</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-clock"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Menunggu Konfirmasi</span>
                        <span class="info-box-number text-warning">{{ $stats['count_pending'] }} Transaksi</span>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-primary"><i class="fas fa-hand-holding-usd"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Dana Terhimpun</span>
                        <span class="info-box-number">Rp {{ number_format($stats['total_verified'], 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Card -->
        <div class="card card-outline card-secondary shadow-sm mb-3">
            <div class="card-header py-2 bg-light">
                <h3 class="card-title text-muted small font-weight-bold">
                    <i class="fas fa-filter text-primary mr-1"></i> Filter Pencarian, Jenis Donasi & Periode Bulan
                </h3>
                <div class="card-tools">
                    <span class="badge badge-light border">
                        Ditemukan: <strong>{{ $donations->total() }}</strong> donasi
                    </span>
                </div>
            </div>
            <div class="card-body p-3">
                <form action="{{ route('admin.online-donations.index') }}" method="GET" id="formFilterDonasi">
                    <div class="row g-2">
                        <!-- Baris 1: Pencarian, Jenis Donasi, Bulan, Status -->
                        <div class="col-md-3 mb-2">
                            <label class="small text-muted mb-1">Cari Kata Kunci</label>
                            <input type="text" name="q" class="form-control form-control-sm" placeholder="Kode donasi, nama, nomor WA, alamat..." value="{{ request('q') }}">
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small text-muted mb-1">Jenis / Kategori Donasi</label>
                            <select name="kategori" class="form-control form-control-sm">
                                <option value="">-- Semua Jenis Donasi --</option>
                                @foreach($availableCategories as $kat)
                                    <option value="{{ $kat }}" {{ strtolower(request('kategori') ?? '') === strtolower($kat) ? 'selected' : '' }}>
                                        {{ $kat }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small text-muted mb-1">Periode Bulan</label>
                            <select name="month" class="form-control form-control-sm">
                                <option value="">-- Semua Bulan (Akumulasi) --</option>
                                @foreach($availableMonths as $val => $label)
                                    <option value="{{ $val }}" {{ request('month') == $val ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small text-muted mb-1">Status Pembayaran</label>
                            <select name="payment_status" class="form-control form-control-sm">
                                <option value="">-- Semua Status Pembayaran --</option>
                                <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending (Menunggu Transfer)</option>
                                <option value="verified" {{ request('payment_status') == 'verified' ? 'selected' : '' }}>Verified (Sah / Lunas)</option>
                                <option value="cancelled" {{ request('payment_status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                            </select>
                        </div>

                        <!-- Baris 2: Kampanye Program, Paging, dan Tombol Aksi -->
                        <div class="col-md-4 mb-2">
                            <label class="small text-muted mb-1">Program Kampanye Spesifik</label>
                            <select name="campaign_id" class="form-control form-control-sm">
                                <option value="">-- Semua Program Kampanye --</option>
                                @foreach($campaigns as $camp)
                                    <option value="{{ $camp->id }}" {{ request('campaign_id') == $camp->id ? 'selected' : '' }}>
                                        {{ $camp->kategori_judul ?: $camp->title }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3 mb-2">
                            <label class="small text-muted mb-1">Jumlah Baris per Halaman (Paging)</label>
                            <select name="per_page" class="form-control form-control-sm">
                                <option value="10" {{ request('per_page', '10') == '10' ? 'selected' : '' }}>10 baris</option>
                                <option value="50" {{ request('per_page') == '50' ? 'selected' : '' }}>50 baris</option>
                                <option value="all" {{ request('per_page') == 'all' ? 'selected' : '' }}>Seluruh baris (Semua)</option>
                            </select>
                        </div>
                        <div class="col-md-5 mb-2 d-flex align-items-end justify-content-end gap-2">
                            <button type="submit" class="btn btn-primary btn-sm px-3 shadow-sm mr-1">
                                <i class="fas fa-filter mr-1"></i> Terapkan Filter
                            </button>
                            <a href="{{ route('admin.online-donations.index') }}" class="btn btn-outline-secondary btn-sm px-3 mr-1" title="Reset filter ke kondisi awal">
                                <i class="fas fa-undo mr-1"></i> Reset
                            </a>
                            <!-- Tombol Cepat Export Sesuai Filter -->
                            <a href="{{ route('admin.online-donations.export.excel', request()->query()) }}" class="btn btn-outline-success btn-sm px-2 mr-1" title="Download Excel data terfilter">
                                <i class="fas fa-file-excel mr-1"></i> Excel
                            </a>
                            <a href="{{ route('admin.online-donations.export.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger btn-sm px-2" title="Buka Dokumen PDF data terfilter">
                                <i class="fas fa-file-pdf mr-1"></i> PDF
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Donasi -->
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header py-2 d-flex justify-content-between align-items-center bg-white flex-wrap">
                <div>
                    <h3 class="card-title font-weight-bold text-dark">
                        <i class="fas fa-list-ul text-success mr-1"></i> Daftar Transaksi Donasi Masuk
                    </h3>
                    <span class="text-muted small ml-2">
                        (Menampilkan {{ $donations->firstItem() ?? 0 }} - {{ $donations->lastItem() ?? 0 }} dari total <strong>{{ $donations->total() }}</strong> transaksi)
                    </span>
                </div>
                <div class="d-flex align-items-center">
                    <span class="small text-muted mr-2">Paging Cepat:</span>
                    <div class="btn-group btn-group-sm">
                        <a href="{{ request()->fullUrlWithQuery(['per_page' => 10]) }}" class="btn {{ request('per_page', '10') == '10' ? 'btn-success font-weight-bold' : 'btn-outline-secondary' }}">10 baris</a>
                        <a href="{{ request()->fullUrlWithQuery(['per_page' => 50]) }}" class="btn {{ request('per_page') == '50' ? 'btn-success font-weight-bold' : 'btn-outline-secondary' }}">50 baris</a>
                        <a href="{{ request()->fullUrlWithQuery(['per_page' => 'all']) }}" class="btn {{ request('per_page') == 'all' ? 'btn-success font-weight-bold' : 'btn-outline-secondary' }}">Seluruh baris</a>
                    </div>
                </div>
            </div>

            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 50px;" class="text-center">No</th>
                            <th style="width: 140px;">Kode Donasi</th>
                            <th>Donatur & Kontak</th>
                            <th>Alamat Donatur</th>
                            <th>Jenis / Kategori</th>
                            <th>Nominal Transfer (Rp)</th>
                            <th>Metode Bayar</th>
                            <th class="text-center">Status</th>
                            <th>Tanggal Masuk</th>
                            <th style="width: 150px;" class="text-center">Aksi & Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($donations as $index => $don)
                            <tr>
                                <td class="text-center text-muted font-weight-bold">
                                    {{ ($donations->firstItem() ?? 1) + $index }}
                                </td>
                                <td>
                                    <strong class="text-dark">{{ $don->donation_code }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $don->display_name }}</strong>
                                    <div class="small text-muted">
                                        @php
                                            $waLink = \App\Services\WhatsAppHelper::generateDonationThankYouLink($don->donor_phone, $don->display_name, $don->donation_code, (float) $don->total_amount);
                                        @endphp
                                        @if($waLink)
                                            <a href="{{ $waLink }}" target="_blank" class="text-success font-weight-bold" title="Kirim WA Ucapan Terima Kasih">
                                                <i class="fab fa-whatsapp mr-1"></i>{{ $don->donor_phone }}
                                            </a>
                                        @elseif($don->donor_phone)
                                            <span class="text-muted"><i class="fas fa-phone mr-1"></i>{{ $don->donor_phone }}</span>
                                        @else
                                            <span class="text-muted fst-italic"><i class="fas fa-phone-slash mr-1"></i>Tanpa WA</span>
                                        @endif
                                    </div>
                                    @if($don->prayer_message)
                                        <small class="text-secondary d-block fst-italic mt-1" style="max-width: 250px;">
                                            "{{ Str::limit($don->prayer_message, 60) }}"
                                        </small>
                                    @endif
                                </td>
                                <td>
                                    @if($don->alamat)
                                        <div class="small text-dark font-weight-bold">
                                            <i class="fas fa-map-marker-alt text-danger mr-1"></i>{{ $don->alamat }}
                                        </div>
                                    @else
                                        <span class="text-muted small fst-italic"><i class="fas fa-map-marker-alt text-secondary mr-1"></i>-</span>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge {{ strtolower($don->kategori ?? '') === 'zakat' ? 'badge-primary' : 'badge-info' }} px-2 py-1 font-weight-bold" style="font-size: 0.85rem;">
                                        {{ $don->kategori_label }}
                                    </span>
                                    @if($don->campaign && $don->campaign->title !== $don->kategori_label)
                                        <div class="small text-muted mt-1" style="max-width: 200px;">
                                            <i class="fas fa-bullhorn mr-1 text-secondary"></i>{{ Str::limit($don->campaign->title, 40) }}
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <strong class="text-success">Rp {{ number_format($don->total_amount, 0, ',', '.') }}</strong>
                                    @if($don->unique_code > 0)
                                        <div class="small text-muted">(Termasuk unik: {{ $don->unique_code }})</div>
                                    @endif
                                </td>
                                <td>
                                    <span class="badge badge-light border text-uppercase">{{ str_replace('_', ' ', $don->payment_method) }}</span>
                                </td>
                                <td class="text-center">
                                    @if($don->payment_status == 'verified')
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i>Sah / Terverifikasi</span>
                                    @elseif($don->payment_status == 'pending')
                                        <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-clock mr-1"></i>Pending</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1">{{ ucfirst($don->payment_status) }}</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $don->created_at ? $don->created_at->format('d/m/Y H:i') : '-' }}</small></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        @if($don->payment_status !== 'verified')
                                            <form action="{{ route('admin.online-donations.verify', $don->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Verifikasi donasi sah dari {{ $don->display_name }} senilai Rp {{ number_format($don->total_amount, 0, ',', '.') }}?')">
                                                @csrf
                                                <button type="submit" class="btn btn-success" title="Verifikasi Lunas (1-Klik)">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                            </form>
                                        @else
                                            <form action="{{ route('admin.online-donations.cancel', $don->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Batalkan verifikasi donasi ini?')">
                                                @csrf
                                                <button type="submit" class="btn btn-warning" title="Batalkan Verifikasi">
                                                    <i class="fas fa-undo"></i>
                                                </button>
                                            </form>
                                        @endif
                                        @if($waLink)
                                            <a href="{{ $waLink }}" target="_blank" class="btn btn-success" title="Kirim WA Ucapan Terima Kasih">
                                                <i class="fab fa-whatsapp"></i>
                                            </a>
                                        @else
                                            <button type="button" class="btn btn-secondary" disabled title="Nomor WhatsApp tidak tersedia">
                                                <i class="fab fa-whatsapp"></i>
                                            </button>
                                        @endif
                                        <a href="{{ route('admin.online-donations.show', $don->id) }}" class="btn btn-info" title="Detail">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <form action="{{ route('admin.online-donations.destroy', $don->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus donasi {{ $don->donation_code }}?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="fas fa-inbox fa-2x mb-2 text-secondary d-block"></i>
                                    Tidak ada data donasi online yang sesuai dengan filter yang dipilih.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <!-- Footer Paging & Info -->
            <div class="card-footer bg-white clearfix py-2">
                <div class="float-left text-muted small pt-2">
                    Menampilkan <strong>{{ $donations->firstItem() ?? 0 }}</strong> sampai <strong>{{ $donations->lastItem() ?? 0 }}</strong> dari total <strong>{{ $donations->total() }}</strong> donasi masuk.
                </div>
                <div class="float-right m-0">
                    {{ $donations->withQueryString()->links('pagination::bootstrap-4') }}
                </div>
            </div>
        </div>
    </div>
</section>

<!-- MODAL KUSTOM FILTER EXPORT -->
<div class="modal fade" id="modalKustomExport" tabindex="-1" role="dialog" aria-labelledby="modalKustomExportLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold" id="modalKustomExportLabel">
                    <i class="fas fa-sliders-h mr-2"></i> Kustom Ekspor Donasi Online
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="formKustomExport" method="GET" target="_blank">
                <div class="modal-body">
                    <p class="text-muted small">Pilih parameter filter di bawah ini untuk mengekspor data donasi online sesuai kebutuhan.</p>
                    
                    <div class="form-group">
                        <label class="font-weight-bold small text-dark">Jenis / Kategori Donasi</label>
                        <select name="kategori" class="form-control form-control-sm">
                            <option value="">-- Semua Jenis Donasi --</option>
                            @foreach($availableCategories as $kat)
                                <option value="{{ $kat }}" {{ strtolower(request('kategori') ?? '') === strtolower($kat) ? 'selected' : '' }}>
                                    {{ $kat }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small text-dark">Periode Bulan</label>
                        <select name="month" class="form-control form-control-sm">
                            <option value="">-- Semua Bulan (Akumulasi Seluruh Data) --</option>
                            @foreach($availableMonths as $val => $label)
                                <option value="{{ $val }}" {{ request('month') == $val ? 'selected' : '' }}>
                                    {{ $label }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    <div class="form-group">
                        <label class="font-weight-bold small text-dark">Status Pembayaran</label>
                        <select name="payment_status" class="form-control form-control-sm">
                            <option value="">-- Semua Status Pembayaran --</option>
                            <option value="verified" {{ request('payment_status') == 'verified' ? 'selected' : '' }}>Verified (Sah / Lunas)</option>
                            <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending (Menunggu Transfer)</option>
                            <option value="cancelled" {{ request('payment_status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>

                    <div class="form-group mb-0">
                        <label class="font-weight-bold small text-dark">Program Kampanye</label>
                        <select name="campaign_id" class="form-control form-control-sm">
                            <option value="">-- Semua Program Kampanye --</option>
                            @foreach($campaigns as $camp)
                                <option value="{{ $camp->id }}" {{ request('campaign_id') == $camp->id ? 'selected' : '' }}>
                                    {{ $camp->kategori_judul ?: $camp->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer bg-light d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <div>
                        <button type="button" class="btn btn-success btn-sm shadow-sm mr-1" onclick="submitExportModal('{{ route('admin.online-donations.export.excel') }}')">
                            <i class="fas fa-file-excel mr-1"></i> Download Excel (.xlsx)
                        </button>
                        <button type="button" class="btn btn-danger btn-sm shadow-sm" onclick="submitExportModal('{{ route('admin.online-donations.export.pdf') }}')">
                            <i class="fas fa-file-pdf mr-1"></i> Buka Dokumen PDF
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function submitExportModal(actionUrl) {
    var form = document.getElementById('formKustomExport');
    form.action = actionUrl;
    form.submit();
}
</script>
@endsection
