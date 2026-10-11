@extends('layouts.frontend')

@section('title', 'Laporan Akuntabilitas & Transparansi | NU Care - LAZISNU')

@section('content')
<div class="bg-success text-white py-5" style="background: linear-gradient(135deg, #064d27, #0a6b38) !important;">
    <div class="container text-center">
        <h1 class="fw-bold mb-2"><i class="fa-solid fa-file-shield text-warning me-2"></i> Transparansi & Akuntabilitas Publik</h1>
        <p class="text-white-50 max-w-700 mx-auto mb-0">
            Bukti nyata komitmen LAZISNU dalam mengelola dana amanah umat secara profesional, terbuka, dan terverifikasi audit akuntan independen.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="card border-0 shadow-sm rounded-4 p-4 mb-4 bg-light">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h4 class="fw-bold text-dark mb-1">Opini Audit Wajar Tanpa Pengecualian (WTP)</h4>
                <p class="text-muted small mb-0">
                    Laporan keuangan NU Care - LAZISNU diaudit secara periodik sesuai Standar Akuntansi Keuangan Entitas Tanpa Akuntabilitas Publik (SAK ETAP) dan PSAK 109 tentang Akuntansi Zakat dan Infak/Sedekah.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <span class="badge bg-success p-2 fs-6"><i class="fa-solid fa-check-double me-1"></i> Terakreditasi Baik Sekali</span>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @forelse($reports as $rep)
            <div class="col-lg-4 col-md-6">
                <div class="card h-100 border-0 shadow-sm rounded-4 p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="badge bg-success bg-opacity-10 text-success fw-bold">{{ $rep->report_year }}</span>
                        <span class="badge bg-warning bg-opacity-25 text-dark small">{{ $rep->report_period }}</span>
                    </div>
                    <h5 class="fw-bold mb-3 text-dark">{{ $rep->title }}</h5>
                    <div class="bg-light p-3 rounded-3 mb-3 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Penghimpunan:</span>
                            <strong class="text-success">Rp {{ number_format($rep->total_inflow, 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted">Total Penyaluran:</span>
                            <strong class="text-danger">Rp {{ number_format($rep->total_outflow, 0, ',', '.') }}</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span class="text-muted">Status Audit:</span>
                            <strong class="text-dark">{{ $rep->audit_status }}</strong>
                        </div>
                    </div>
                    @php
                        $cleanRepWa = preg_replace('/[^0-9]/', '', $settings['phone_wa_hotline'] ?? '6285188991926');
                        if (str_starts_with($cleanRepWa, '0')) {
                            $cleanRepWa = '62' . substr($cleanRepWa, 1);
                        }
                    @endphp
                    <a href="https://wa.me/{{ $cleanRepWa }}?text=Assalamu%27alaikum,%20mohon%20info%20salinan%20dokumen%20{{ urlencode($rep->title) }}" target="_blank" class="btn btn-outline-success btn-sm rounded-pill fw-bold mt-auto">
                        <i class="fa-solid fa-file-pdf me-1"></i> Minta Salinan Laporan
                    </a>
                </div>
            </div>
        @empty
            <div class="col-12 text-center py-5">
                <p class="text-muted">Belum ada laporan terpublikasi.</p>
            </div>
        @endforelse
    </div>
</div>
@endsection
