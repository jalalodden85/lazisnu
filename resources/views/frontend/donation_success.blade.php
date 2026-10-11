@extends('layouts.frontend')

@section('title', 'Instruksi Penyaluran Infaq & Donasi | NU Care - LAZISNU')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow rounded-4 overflow-hidden">
                <div class="bg-success text-white p-4 text-center" style="background: linear-gradient(135deg, #064d27, #0a6b38) !important;">
                    <div class="rounded-circle bg-white text-success d-inline-flex align-items-center justify-content-center mb-2" style="width: 60px; height: 60px; font-size: 1.8rem;">
                        <i class="fa-solid fa-check"></i>
                    </div>
                    <h3 class="fw-bold mb-1">Niat Donasi Telah Tercatat</h3>
                    <p class="text-white-50 small mb-0">Kode Transaksi: <strong>{{ $donation->donation_code }}</strong></p>
                </div>

                <div class="card-body p-4 p-md-5">
                    <p class="text-muted text-center mb-4">
                        Jazakumullah Khairan Katsiran kepada Bapak/Ibu <strong>{{ $donation->donor_name }}</strong>. Silakan selesaikan transfer sejumlah nominal berikut tepat hingga 3 digit terakhir:
                    </p>

                    <!-- Nominal Transfer Box -->
                    <div class="p-3 bg-light rounded-4 text-center border mb-4">
                        <small class="text-muted text-uppercase fw-bold">Total Nominal Transfer:</small>
                        <div class="fs-2 fw-extrabold text-success my-1" id="totalAmount">
                            Rp {{ number_format($donation->total_amount, 0, ',', '.') }}
                        </div>
                        <small class="text-danger fw-bold">*Termasuk kode unik Rp {{ $donation->unique_code }} untuk verifikasi otomatis.</small>
                    </div>

                    <!-- Rekening Tujuan Transfer / QRIS -->
                    <div class="card bg-white border rounded-3 p-3 mb-4">
                        <small class="text-muted fw-bold mb-2 d-block">Tujuan Penyaluran Donasi:</small>
                        @if($donation->payment_method === 'transfer_bsi')
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill mb-1">Khusus Zakat</span>
                                    <div class="fw-bold fs-5 text-dark">Bank Syariah Indonesia (BSI)</div>
                                    <div class="text-muted small">No. Rekening: <strong class="fs-5 text-dark font-monospace" id="rekNo">{{ $settings['bank_bsi_zakat'] ?? '2026070708' }}</strong></div>
                                    <small class="text-muted">Atas Nama: <strong>{{ $settings['bank_bsi_zakat_name'] ?? 'Lazisnu Lamongan' }}</strong></small>
                                </div>
                                <button class="btn btn-sm btn-outline-success rounded-pill fw-bold" onclick="copyToClipboard('{{ $settings['bank_bsi_zakat'] ?? '2026070708' }}', 'btnCopyRek')" id="btnCopyRek">
                                    <i class="fa-regular fa-copy me-1"></i> Salin Rekening
                                </button>
                            </div>
                        @elseif($donation->payment_method === 'transfer_bri')
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill mb-1">Selain Zakat (Infaq, Shadaqah, dll)</span>
                                    <div class="fw-bold fs-5 text-dark">Bank BRI</div>
                                    <div class="text-muted small">No. Rekening: <strong class="fs-5 text-dark font-monospace" id="rekNo">{{ $settings['bank_bri_infaq'] ?? '00411001448562' }}</strong></div>
                                    <small class="text-muted">Atas Nama: <strong>{{ $settings['bank_bri_infaq_name'] ?? 'LAZISNU LAMONGAN' }}</strong></small>
                                </div>
                                <button class="btn btn-sm btn-outline-success rounded-pill fw-bold" onclick="copyToClipboard('{{ $settings['bank_bri_infaq'] ?? '00411001448562' }}', 'btnCopyRek')" id="btnCopyRek">
                                    <i class="fa-regular fa-copy me-1"></i> Salin Rekening
                                </button>
                            </div>
                        @elseif($donation->payment_method === 'qris')
                            <div class="text-center py-2">
                                <span class="badge bg-danger-subtle text-danger border border-danger-subtle rounded-pill mb-2">QRIS Bank BRI (Semua E-Wallet & M-Banking)</span>
                                <div class="bg-white p-2 d-inline-block rounded-3 shadow-sm border mb-2">
                                    <img src="{{ asset('qris_lazisnu_bri.jpg') }}" alt="QRIS LAZISNU PCNU Lamongan" style="max-width: 280px; width: 100%; height: auto;">
                                </div>
                                <div class="small fw-bold text-dark font-monospace mb-1">NMID: ID1026559758341</div>
                                <p class="text-muted small mb-1">Pindai menggunakan GoPay, OVO, DANA, ShopeePay, LinkAja, atau Mobile Banking apa pun.</p>
                                <a href="{{ asset('qris_lazisnu_bri.jpg') }}" download="QRIS_LAZISNU_BRI_LAMONGAN.jpg" class="btn btn-sm btn-outline-danger rounded-pill mt-1 fw-bold">
                                    <i class="fa-solid fa-download me-1"></i> Unduh Gambar QRIS
                                </a>
                            </div>
                        @else
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <div>
                                    <span class="badge bg-secondary-subtle text-secondary rounded-pill mb-1">Transfer Bank</span>
                                    <div class="fw-bold fs-5 text-dark">Bank BRI (Infaq / Shadaqah)</div>
                                    <div class="text-muted small">No. Rekening: <strong class="fs-5 text-dark font-monospace" id="rekNo">{{ $settings['bank_bri_infaq'] ?? '00411001448562' }}</strong></div>
                                    <small class="text-muted">Atas Nama: <strong>{{ $settings['bank_bri_infaq_name'] ?? 'LAZISNU LAMONGAN' }}</strong></small>
                                </div>
                                <button class="btn btn-sm btn-outline-success rounded-pill fw-bold" onclick="copyToClipboard('{{ $settings['bank_bri_infaq'] ?? '00411001448562' }}', 'btnCopyRek')" id="btnCopyRek">
                                    <i class="fa-regular fa-copy me-1"></i> Salin Rekening
                                </button>
                            </div>
                        @endif
                    </div>

                    <!-- Program Yang Dituju -->
                    <div class="mb-4 small text-muted">
                        <div>Program Kebaikan: <strong>{{ $donation->campaign->title }}</strong></div>
                        <div>Status Pembayaran: <span class="badge bg-warning text-dark">Menunggu Transfer</span></div>
                    </div>

                    <!-- Tombol Konfirmasi WA -->
                    @php
                        $waText = urlencode("Assalamu'alaikum LAZISNU, saya ingin konfirmasi transfer donasi:\n\nKode: {$donation->donation_code}\nNama: {$donation->donor_name}\nNominal: Rp " . number_format($donation->total_amount, 0, ',', '.') . "\nProgram: {$donation->campaign->title}\n\nMohon dicek, terima kasih.");
                        $cleanWa = preg_replace('/[^0-9]/', '', $settings['phone_wa_hotline'] ?? '6285188991926');
                        if (str_starts_with($cleanWa, '0')) {
                            $cleanWa = '62' . substr($cleanWa, 1);
                        }
                    @endphp
                    <div class="d-grid gap-2">
                        <a href="https://wa.me/{{ $cleanWa }}?text={{ $waText }}" target="_blank" class="btn btn-success fw-bold py-3 rounded-pill shadow-sm" style="background: #25d366; border: none;">
                            <i class="fa-brands fa-whatsapp fs-5 me-2"></i> Konfirmasi Transfer via WhatsApp
                        </a>
                        <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill py-2">
                            Kembali ke Beranda
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
