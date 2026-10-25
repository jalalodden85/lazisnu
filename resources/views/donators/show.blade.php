@extends('layouts.adminlte')

@section('title', 'Detail Penerima Kotak: ' . $donator->name)
@section('page_title', 'Informasi Donatur & Kotak KOIN NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('donators.index') }}">Penerima Kotak</a></li>
    <li class="breadcrumb-item active">{{ $donator->box_number }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Kolom Kiri: Detail Donatur & Kotak -->
    <div class="col-lg-7 col-md-12">
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-success">
                    <i class="fas fa-box-open mr-2"></i> Profil Pemilik Kotak Infaq
                </h3>
                <div>
                    {!! $donator->status_badge !!}
                </div>
            </div>
            <div class="card-body p-0">
                <table class="table table-striped m-0">
                    <tbody>
                        <tr>
                            <th style="width: 35%;"><i class="fas fa-barcode text-success mr-2"></i>Nomor Kotak / Kaleng</th>
                            <td>
                                <span class="badge badge-success font-weight-bold px-3 py-1" style="font-size: 1rem;">
                                    {{ $donator->box_number }}
                                </span>
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-user text-success mr-2"></i>Nama Donatur</th>
                            <td class="font-weight-bold text-dark">{{ $donator->name }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-id-card text-success mr-2"></i>NIK</th>
                            <td>{{ $donator->nik ?? '-' }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-phone-alt text-success mr-2"></i>No. HP / WhatsApp</th>
                            <td>
                                @php
                                    $donatorShowWa = \App\Services\WhatsAppHelper::generateKoinNuThankYouLink($donator->phone, $donator->name, $donator->box_number);
                                @endphp
                                @if($donatorShowWa)
                                    <a href="{{ $donatorShowWa }}" target="_blank" class="text-success font-weight-bold" title="Kirim WA Ucapan Terima Kasih">
                                        <i class="fab fa-whatsapp mr-1"></i>{{ $donator->phone }}
                                    </a>
                                @elseif($donator->phone)
                                    <span class="text-muted"><i class="fas fa-phone mr-1"></i>{{ $donator->phone }}</span>
                                @else
                                    <span class="text-muted fst-italic">-</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-mosque text-success mr-2"></i>Ranting / Desa</th>
                            <td>
                                <span class="badge badge-light border font-weight-bold">
                                    {{ $donator->ranting?->code }} - {{ $donator->ranting?->name }}
                                </span>
                                <small class="text-muted ml-1">({{ $donator->ranting?->mwc?->name }})</small>
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-map-marker-alt text-success mr-2"></i>Alamat Lengkap</th>
                            <td>
                                {{ $donator->address }}
                                @if($donator->rt || $donator->rw)
                                    <br><small class="text-muted">RT {{ $donator->rt ?? '-' }} / RW {{ $donator->rw ?? '-' }}</small>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-calendar-alt text-success mr-2"></i>Tanggal Penyerahan</th>
                            <td>{{ $donator->joined_date ? $donator->joined_date->translatedFormat('d F Y') : '-' }}</td>
                        </tr>
                        <tr>
                            <th><i class="fas fa-sticky-note text-success mr-2"></i>Catatan</th>
                            <td>{{ $donator->notes ?? '-' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                <a href="{{ route('donators.index') }}" class="btn btn-outline-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                </a>
                <div>
                    <a href="{{ route('donators.edit', $donator->id) }}" class="btn btn-warning btn-sm text-white font-weight-bold mr-1">
                        <i class="fas fa-edit mr-1"></i> Edit Data
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Petugas Pengampu Wilayah -->
    <div class="col-lg-5 col-md-12">
        <div class="card card-outline card-primary shadow-sm mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-primary">
                    <i class="fas fa-user-shield mr-2"></i> Petugas Relawan Pengampu
                </h3>
            </div>
            <div class="card-body">
                @if($donator->relawan)
                    <div class="text-center mb-3">
                        <div class="bg-light text-primary rounded-circle d-inline-flex align-items-center justify-content-center border" style="width: 70px; height: 70px; font-size: 28px;">
                            <i class="fas fa-user-check"></i>
                        </div>
                        <h5 class="font-weight-bold mt-2 mb-0">{{ $donator->relawan->nama }}</h5>
                        <p class="text-muted mb-1">Koordinator Petugas Lapangan</p>
                        <span class="badge badge-success px-3 py-1">
                            <i class="fas fa-map-pin mr-1"></i>Wilayah: {{ $donator->relawan->area_tugas }}
                        </span>
                    </div>

                    <table class="table table-sm table-borderless">
                        <tr>
                            <td class="text-muted"><i class="fas fa-id-badge mr-1"></i> NIK Petugas:</td>
                            <td class="font-weight-bold text-right">{{ $donator->relawan->nik }}</td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="fab fa-whatsapp mr-1 text-success"></i> WhatsApp:</td>
                            <td class="font-weight-bold text-right">
                                @if($donator->relawan->no_hp && ($relWaNum = \App\Services\WhatsAppHelper::formatNumber($donator->relawan->no_hp)))
                                    <a href="https://wa.me/{{ $relWaNum }}" target="_blank" class="text-success font-weight-bold">
                                        {{ $donator->relawan->no_hp }}
                                    </a>
                                @else
                                    <span class="text-muted">{{ $donator->relawan->no_hp ?: '-' }}</span>
                                @endif
                            </td>
                        </tr>
                        <tr>
                            <td class="text-muted"><i class="fas fa-boxes mr-1"></i> Total Binaan:</td>
                            <td class="font-weight-bold text-right">{{ $donator->relawan->donators()->count() }} Kotak Infaq</td>
                        </tr>
                    </table>

                    <div class="mt-3">
                        <a href="{{ route('donators.edit', $donator->id) }}" class="btn btn-outline-primary btn-block btn-sm">
                            <i class="fas fa-exchange-alt mr-1"></i> Alihkan ke Petugas Lain
                        </a>
                    </div>
                @else
                    <div class="text-center py-4">
                        <i class="fas fa-user-clock text-warning fa-3x mb-3"></i>
                        <h6 class="font-weight-bold text-dark">Kotak Infaq Belum Memiliki Petugas Pengampu</h6>
                        <p class="text-muted small">
                            Kotak ini belum ditugaskan kepada koordinator relawan RT/RW setempat.
                        </p>
                        <a href="{{ route('donators.edit', $donator->id) }}" class="btn btn-success btn-sm font-weight-bold">
                            <i class="fas fa-user-plus mr-1"></i> Tugaskan Petugas Sekarang
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Card Bantuan Petugas Wilayah -->
        <div class="card card-default shadow-sm">
            <div class="card-header bg-light py-2">
                <h6 class="card-title font-weight-bold text-muted mb-0">
                    <i class="fas fa-info-circle mr-1"></i> Peran Petugas Lapangan
                </h6>
            </div>
            <div class="card-body small text-muted">
                Petugas relawan bertanggung jawab atas:
                <ul class="pl-3 mb-0 mt-2">
                    <li>Sosialisasi dan penyerahan fisik kaleng ke rumah donatur.</li>
                    <li>Pengambilan koin infaq secara berkala setiap bulan.</li>
                    <li>Penyetoran ke Pengurus Ranting untuk diinputkan ke sistem.</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
