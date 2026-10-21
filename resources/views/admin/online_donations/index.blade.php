@extends('layouts.adminlte')

@section('title', 'Donasi Online Masuk & Verifikasi')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-receipt text-warning mr-2"></i>Donasi Online Masuk & Verifikasi</h1>
            </div>
            <div class="col-sm-6 text-right">
                <span class="text-muted small">Total Donasi Terverifikasi: <strong class="text-success fs-5">Rp {{ number_format($stats['total_verified'], 0, ',', '.') }}</strong></span>
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
            <div class="card-body p-3">
                <form action="{{ route('admin.online-donations.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari kode transaksi, nama donatur, no. hp..." value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="payment_status" class="form-control form-control-sm">
                            <option value="">-- Semua Status Pembayaran --</option>
                            <option value="pending" {{ request('payment_status') == 'pending' ? 'selected' : '' }}>Pending (Menunggu Transfer)</option>
                            <option value="verified" {{ request('payment_status') == 'verified' ? 'selected' : '' }}>Verified (Sah / Lunas)</option>
                            <option value="cancelled" {{ request('payment_status') == 'cancelled' ? 'selected' : '' }}>Dibatalkan</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="campaign_id" class="form-control form-control-sm">
                            <option value="">-- Semua Kategori Judul --</option>
                            @foreach($campaigns as $camp)
                                <option value="{{ $camp->id }}" {{ request('campaign_id') == $camp->id ? 'selected' : '' }}>
                                    {{ $camp->kategori_judul ?: $camp->title }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter mr-1"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tabel Donasi -->
        <div class="card card-outline card-success shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Kode Donasi</th>
                            <th>Donatur & Kontak</th>
                            <th>Alamat Donatur</th>
                            <th>Kategori Judul</th>
                            <th>Nominal Transfer (Rp)</th>
                            <th>Metode Bayar</th>
                            <th class="text-center">Status</th>
                            <th>Tanggal Masuk</th>
                            <th style="width: 150px;" class="text-center">Aksi & Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($donations as $don)
                            <tr>
                                <td>
                                    <strong class="text-dark">{{ $don->donation_code }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $don->display_name }}</strong>
                                    <div class="small text-muted">
                                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $don->donor_phone) }}" target="_blank" class="text-success">
                                            <i class="fab fa-whatsapp mr-1"></i>{{ $don->donor_phone }}
                                        </a>
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
                                    <span class="badge badge-info px-2 py-1 font-weight-bold" style="font-size: 0.85rem;">
                                        {{ $don->campaign->kategori_judul ?? $don->campaign->kategori_judul_display }}
                                    </span>
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
                                        <span class="badge badge-secondary px-2 py-1">{{ $don->payment_status }}</span>
                                    @endif
                                </td>
                                <td><small class="text-muted">{{ $don->created_at->format('d/m/Y H:i') }}</small></td>
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
                                <td colspan="9" class="text-center text-muted py-4">Belum ada donasi online masuk.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($donations->hasPages())
                <div class="card-footer bg-white">
                    {{ $donations->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
