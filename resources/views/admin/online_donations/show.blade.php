@extends('layouts.adminlte')

@section('title', 'Detail Donasi Online')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">Detail Donasi: {{ $donation->donation_code }}</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.online-donations.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-7">
                <div class="card card-outline card-success shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold">Informasi Pembayaran Donasi</h3>
                    </div>
                    <div class="card-body p-0">
                        <table class="table table-striped mb-0">
                            <tr>
                                <th style="width: 200px;">Kode Donasi:</th>
                                <td><span class="badge badge-light border font-weight-bold fs-6">{{ $donation->donation_code }}</span></td>
                            </tr>
                            <tr>
                                <th>Program Kebaikan:</th>
                                <td><strong>{{ $donation->campaign->title }}</strong></td>
                            </tr>
                            <tr>
                                <th>Nama Donatur:</th>
                                <td>
                                    {{ $donation->donor_name }}
                                    @if($donation->is_anonymous)
                                        <span class="badge badge-warning ml-1">Tampil Publik: Hamba Allah</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <th>No. Handphone / WA:</th>
                                <td>
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $donation->donor_phone) }}" target="_blank" class="btn btn-xs btn-success">
                                        <i class="fab fa-whatsapp mr-1"></i> {{ $donation->donor_phone }}
                                    </a>
                                </td>
                            </tr>
                            <tr>
                                <th>Email:</th>
                                <td>{{ $donation->donor_email ?: '-' }}</td>
                            </tr>
                            <tr>
                                <th>Nominal Donasi Murni:</th>
                                <td>Rp {{ number_format($donation->amount, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <th>Kode Unik Transfer:</th>
                                <td><strong class="text-danger">+ Rp {{ $donation->unique_code }}</strong></td>
                            </tr>
                            <tr>
                                <th>Total Harus Ditransfer:</th>
                                <td><strong class="text-success fs-5">Rp {{ number_format($donation->total_amount, 0, ',', '.') }}</strong></td>
                            </tr>
                            <tr>
                                <th>Metode Pembayaran:</th>
                                <td class="text-uppercase font-weight-bold">{{ str_replace('_', ' ', $donation->payment_method) }}</td>
                            </tr>
                            <tr>
                                <th>Status Pembayaran:</th>
                                <td>
                                    @if($donation->payment_status == 'verified')
                                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check mr-1"></i>Sah / Terverifikasi</span>
                                    @elseif($donation->payment_status == 'pending')
                                        <span class="badge badge-warning text-dark px-2 py-1"><i class="fas fa-clock mr-1"></i>Menunggu Transfer</span>
                                    @else
                                        <span class="badge badge-secondary px-2 py-1">{{ $donation->payment_status }}</span>
                                    @endif
                                </td>
                            </tr>
                            @if($donation->verified_at)
                                <tr>
                                    <th>Diverifikasi Pada:</th>
                                    <td>{{ $donation->verified_at->format('d/m/Y H:i') }} (Oleh: {{ $donation->verifier->name ?? 'Admin' }})</td>
                                </tr>
                            @endif
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-5">
                <!-- Doa / Catatan Donatur -->
                <div class="card card-outline card-warning shadow-sm mb-3">
                    <div class="card-header">
                        <h3 class="card-title font-weight-bold"><i class="fas fa-praying-hands mr-1"></i> Doa / Harapan Donatur</h3>
                    </div>
                    <div class="card-body">
                        @if($donation->prayer_message)
                            <blockquote class="blockquote small text-secondary">
                                <p class="mb-0">"{{ $donation->prayer_message }}"</p>
                                <footer class="blockquote-footer mt-2">{{ $donation->display_name }}</footer>
                            </blockquote>
                        @else
                            <p class="text-muted small mb-0">Donatur tidak meninggalkan pesan doa khusus.</p>
                        @endif
                    </div>
                </div>

                <!-- Tombol Aksi Verifikasi -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        @if($donation->payment_status !== 'verified')
                            <form action="{{ route('admin.online-donations.verify', $donation->id) }}" method="POST" onsubmit="return confirm('Verifikasi donasi ini?')">
                                @csrf
                                <button type="submit" class="btn btn-success btn-block font-weight-bold mb-2 py-2">
                                    <i class="fas fa-check-double mr-1"></i> Verifikasi Donasi Sah
                                </button>
                            </form>
                        @else
                            <form action="{{ route('admin.online-donations.cancel', $donation->id) }}" method="POST" onsubmit="return confirm('Batalkan verifikasi donasi?')">
                                @csrf
                                <button type="submit" class="btn btn-warning btn-block font-weight-bold mb-2">
                                    <i class="fas fa-undo mr-1"></i> Batalkan Status Verifikasi
                                </button>
                            </form>
                        @endif

                        <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $donation->donor_phone) }}?text=Assalamu%27alaikum%20Bpk%2FIbu%20{{ urlencode($donation->donor_name) }},%20terima%20kasih%20telah%20berinfaq%20melalui%20LAZISNU%20dengan%20kode%20{{ $donation->donation_code }}." target="_blank" class="btn btn-outline-success btn-block font-weight-bold">
                            <i class="fab fa-whatsapp mr-1"></i> Hubungi / Kirim Resi via WA
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
