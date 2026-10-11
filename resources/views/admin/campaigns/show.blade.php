@extends('layouts.adminlte')

@section('title', 'Detail Kampanye: ' . $campaign->title)

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">
                    <i class="fas fa-hand-holding-heart text-success mr-2"></i>{{ Str::limit($campaign->title, 45) }}
                </h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.campaigns.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                </a>
                <a href="{{ route('programs.detail', $campaign->slug) }}" target="_blank" class="btn btn-info btn-sm">
                    <i class="fas fa-external-link-alt mr-1"></i> Buka Halaman Publik
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <!-- Toast / Dynamic Alert Container -->
        <div id="alertContainer">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show">
                    <i class="fas fa-check-circle mr-1"></i> {{ session('success') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
                    <button type="button" class="close" data-dismiss="alert">&times;</button>
                </div>
            @endif
        </div>

        <!-- Metric Cards -->
        <div class="row">
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-info"><i class="fas fa-bullseye"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Target Penggalangan</span>
                        <span class="info-box-number">Rp {{ number_format($campaign->target_amount, 0, ',', '.') }}</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-success"><i class="fas fa-donate"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Terkumpul Terverifikasi</span>
                        <span class="info-box-number text-success" id="statCollectedAmount">
                            Rp {{ number_format($campaign->collected_amount, 0, ',', '.') }}
                        </span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-warning"><i class="fas fa-chart-line"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Pencapaian Donasi</span>
                        <span class="info-box-number" id="statProgressPct">{{ $campaign->progress_percentage }}%</span>
                    </div>
                </div>
            </div>
            <div class="col-md-3 col-sm-6 col-12">
                <div class="info-box shadow-sm">
                    <span class="info-box-icon bg-secondary"><i class="fas fa-users"></i></span>
                    <div class="info-box-content">
                        <span class="info-box-text">Total Donatur Masuk</span>
                        <span class="info-box-number">{{ $campaign->donations->count() }} Transaksi</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Daftar Donasi Kampanye -->
        <div class="card card-outline card-success shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-list-alt text-success mr-2"></i>Daftar Transaksi Donasi Masuk (Terkait Kampanye Ini)
                </h3>
                <span class="badge badge-light border text-muted">Sinkronisasi Realtime dengan Modul Donasi Online</span>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0" id="campaignDonationsTable">
                    <thead class="bg-light">
                        <tr>
                            <th>Kode Donasi</th>
                            <th>Nama Donatur</th>
                            <th>Nominal Pokok</th>
                            <th>Total Transfer</th>
                            <th>Metode</th>
                            <th class="text-center">Status</th>
                            <th>Waktu Verifikasi</th>
                            <th class="text-center" style="width: 170px;">Aksi Verifikasi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaign->donations as $don)
                            <tr id="row-{{ $don->donation_code }}">
                                <td>
                                    <strong class="text-dark font-monospace">{{ $don->donation_code }}</strong>
                                </td>
                                <td>
                                    <strong>{{ $don->display_name }}</strong>
                                    <div class="small text-muted">{{ $don->donor_phone }}</div>
                                </td>
                                <td>Rp {{ number_format($don->amount, 0, ',', '.') }}</td>
                                <td class="font-weight-bold text-success">
                                    Rp {{ number_format($don->total_amount, 0, ',', '.') }}
                                    @if($don->unique_code > 0)
                                        <small class="text-muted d-block">(+{{ $don->unique_code }})</small>
                                    @endif
                                </td>
                                <td><span class="badge badge-light border text-uppercase">{{ str_replace('_', ' ', $don->payment_method) }}</span></td>
                                <td class="text-center" id="status-badge-{{ $don->donation_code }}">
                                    @if($don->payment_status === 'verified')
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i>Verified</span>
                                    @elseif($don->payment_status === 'pending')
                                        <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-clock mr-1"></i>Pending</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1">{{ $don->payment_status }}</span>
                                    @endif
                                </td>
                                <td id="verified-time-{{ $don->donation_code }}">
                                    <small class="text-muted">
                                        {{ $don->verified_at ? $don->verified_at->format('d/m/Y H:i') : '-' }}
                                    </small>
                                </td>
                                <td class="text-center" id="action-cell-{{ $don->donation_code }}">
                                    @if($don->payment_status !== 'verified')
                                        <button type="button" 
                                                class="btn btn-sm btn-success font-weight-bold"
                                                onclick="verifyDonationSync('{{ $don->donation_code }}', {{ $don->amount }})"
                                                id="btn-verify-{{ $don->donation_code }}">
                                            <i class="fas fa-check mr-1"></i> Verifikasi
                                        </button>
                                    @else
                                        <button type="button" 
                                                class="btn btn-sm btn-outline-warning"
                                                onclick="cancelDonationSync('{{ $don->donation_code }}')"
                                                id="btn-cancel-{{ $don->donation_code }}">
                                            <i class="fas fa-undo mr-1"></i> Batalkan
                                        </button>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center text-muted py-4">Belum ada donasi masuk pada kampanye ini.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
@endsection

@push('scripts')
<script>
    const csrfToken = '{{ csrf_token() }}';
    const verifyRoute = '{{ route('admin.campaigns.verify-donation') }}';
    const cancelRoute = '{{ route('admin.campaigns.cancel-donation') }}';

    function showAlert(type, message) {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                <i class="fas fa-${type === 'success' ? 'check-circle' : 'exclamation-circle'} mr-1"></i> ${message}
                <button type="button" class="close" data-dismiss="alert">&times;</button>
            </div>
        `;
        document.getElementById('alertContainer').innerHTML = alertHtml;
    }

    function formatRupiah(number) {
        return 'Rp ' + new Intl.NumberFormat('id-ID').format(number);
    }

    // Aksi Verifikasi Donasi Realtime via AJAX (Atomic Transaction)
    function verifyDonationSync(kodeDonasi, defaultNominal) {
        if (!confirm(`Konfirmasi verifikasi donasi dengan kode [${kodeDonasi}]?`)) {
            return;
        }

        const btn = document.getElementById(`btn-verify-${kodeDonasi}`);
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Memproses...';
        }

        fetch(verifyRoute, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({
                kode_donasi: kodeDonasi,
                nominal_terverifikasi: defaultNominal
            })
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(({ status, body }) => {
            if (status === 200 && body.success) {
                showAlert('success', body.message);

                // 1. Update State Badge
                const statusBadge = document.getElementById(`status-badge-${kodeDonasi}`);
                if (statusBadge) {
                    statusBadge.innerHTML = '<span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i>Verified</span>';
                }

                // 2. Update Waktu Verifikasi
                const verifiedTime = document.getElementById(`verified-time-${kodeDonasi}`);
                if (verifiedTime) {
                    verifiedTime.innerHTML = '<small class="text-muted">Baru saja</small>';
                }

                // 3. Update Tombol Aksi
                const actionCell = document.getElementById(`action-cell-${kodeDonasi}`);
                if (actionCell) {
                    actionCell.innerHTML = `
                        <button type="button" class="btn btn-sm btn-outline-warning" onclick="cancelDonationSync('${kodeDonasi}')" id="btn-cancel-${kodeDonasi}">
                            <i class="fas fa-undo mr-1"></i> Batalkan
                        </button>
                    `;
                }

                // 4. Update Angka Statistik Akumulasi Kampanye
                if (body.collected_amount !== undefined) {
                    document.getElementById('statCollectedAmount').innerText = formatRupiah(body.collected_amount);
                }
                if (body.progress_percentage !== undefined) {
                    document.getElementById('statProgressPct').innerText = body.progress_percentage + '%';
                }
            } else {
                showAlert('danger', body.message || 'Gagal memverifikasi donasi.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-check mr-1"></i> Verifikasi';
                }
            }
        })
        .catch(err => {
            showAlert('danger', 'Terjadi kesalahan jaringan atau server: ' + err.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-check mr-1"></i> Verifikasi';
            }
        });
    }

    // Aksi Pembatalan Verifikasi Realtime
    function cancelDonationSync(kodeDonasi) {
        if (!confirm(`Batalkan verifikasi donasi dengan kode [${kodeDonasi}]?`)) {
            return;
        }

        const btn = document.getElementById(`btn-cancel-${kodeDonasi}`);
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i> Membatalkan...';
        }

        fetch(cancelRoute, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken,
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ kode_donasi: kodeDonasi })
        })
        .then(res => res.json().then(data => ({ status: res.status, body: data })))
        .then(({ status, body }) => {
            if (status === 200 && body.success) {
                showAlert('success', body.message);

                const statusBadge = document.getElementById(`status-badge-${kodeDonasi}`);
                if (statusBadge) {
                    statusBadge.innerHTML = '<span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-clock mr-1"></i>Pending</span>';
                }

                const verifiedTime = document.getElementById(`verified-time-${kodeDonasi}`);
                if (verifiedTime) {
                    verifiedTime.innerHTML = '<small class="text-muted">-</small>';
                }

                const actionCell = document.getElementById(`action-cell-${kodeDonasi}`);
                if (actionCell) {
                    actionCell.innerHTML = `
                        <button type="button" class="btn btn-sm btn-success font-weight-bold" onclick="verifyDonationSync('${kodeDonasi}', 0)" id="btn-verify-${kodeDonasi}">
                            <i class="fas fa-check mr-1"></i> Verifikasi
                        </button>
                    `;
                }

                if (body.collected_amount !== undefined) {
                    document.getElementById('statCollectedAmount').innerText = formatRupiah(body.collected_amount);
                }
                if (body.progress_percentage !== undefined) {
                    document.getElementById('statProgressPct').innerText = body.progress_percentage + '%';
                }
            } else {
                showAlert('danger', body.message || 'Gagal membatalkan verifikasi.');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-undo mr-1"></i> Batalkan';
                }
            }
        })
        .catch(err => {
            showAlert('danger', 'Terjadi kesalahan jaringan atau server: ' + err.message);
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<i class="fas fa-undo mr-1"></i> Batalkan';
            }
        });
    }
</script>
@endpush
