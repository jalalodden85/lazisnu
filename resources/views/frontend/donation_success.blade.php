@extends('layouts.frontend')

@section('title', 'Instruksi Penyaluran Infaq & Donasi | NU Care - LAZISNU')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-7">
            <div class="card border-0 shadow rounded-4 overflow-hidden">
                
                {{-- Header Status Card --}}
                @if($donation->payment_status === 'verified')
                    <div class="bg-success text-white p-4 text-center" style="background: linear-gradient(135deg, #064d27, #0a6b38) !important;">
                        <div class="rounded-circle bg-white text-success d-inline-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 65px; height: 65px; font-size: 2rem;">
                            <i class="fa-solid fa-circle-check"></i>
                        </div>
                        <h3 class="fw-bold mb-1">Pembayaran Berhasil &amp; Terverifikasi</h3>
                        <p class="text-white-50 small mb-0">No. Kuitansi Digital: <strong class="text-white">{{ $donation->donation_code }}</strong></p>
                    </div>
                @else
                    <div class="bg-success text-white p-4 text-center" style="background: linear-gradient(135deg, #064d27, #0a6b38) !important;">
                        <div class="rounded-circle bg-white text-success d-inline-flex align-items-center justify-content-center mb-2 shadow-sm" style="width: 60px; height: 60px; font-size: 1.8rem;">
                            <i class="fa-solid fa-qrcode"></i>
                        </div>
                        <h3 class="fw-bold mb-1">Selesaikan Penyaluran Donasi</h3>
                        <p class="text-white-50 small mb-0">Kode Transaksi: <strong>{{ $donation->donation_code }}</strong></p>
                    </div>
                @endif

                <div class="card-body p-4 p-md-5">
                    @if($donation->payment_status === 'verified')
                        {{-- ========================================================= --}}
                        {{-- STATE 1: SUKSES / TERVERIFIKASI (KWITANSI DIGITAL)      --}}
                        {{-- ========================================================= --}}
                        <div class="text-center mb-4">
                            <h5 class="fw-bold text-success mb-2">Alhamdulillah, Donasi Anda Telah Diterima!</h5>
                            <p class="text-muted small">
                                Jazakumullah Khairan Katsiran kepada <strong>{{ $donation->display_name }}</strong>. Donasi Anda telah diverifikasi otomatis oleh sistem dan langsung dialokasikan ke program penerima manfaat.
                            </p>
                        </div>

                        <!-- Ringkasan Kuitansi -->
                        <div class="p-4 bg-light rounded-4 border mb-4">
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <span class="text-muted small">Program Penyaluran:</span>
                                <strong class="text-dark small text-end">{{ $donation->campaign->title }}</strong>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <span class="text-muted small">Kategori Dana:</span>
                                <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $donation->kategori ?: ($donation->campaign->kategori_judul ?? 'Infaq') }}</span>
                            </div>
                            <div class="d-flex justify-content-between border-bottom pb-2 mb-2">
                                <span class="text-muted small">Tanggal Verifikasi:</span>
                                <span class="text-dark small fw-semibold">{{ $donation->verified_at ? $donation->verified_at->format('d M Y, H:i') : now()->format('d M Y, H:i') }} WIB</span>
                            </div>
                            <div class="d-flex justify-content-between align-items-center pt-1">
                                <span class="text-muted fw-bold">Total Donasi Lunas:</span>
                                <span class="fs-4 fw-extrabold text-success font-monospace">Rp {{ number_format($donation->total_amount, 0, ',', '.') }}</span>
                            </div>
                        </div>

                        <div class="d-grid gap-2">
                            <a href="{{ route('home') }}#transparansi" class="btn btn-success fw-bold py-3 rounded-pill shadow-sm" style="background: var(--nu-green);">
                                <i class="fa-solid fa-list-check me-2"></i> Lihat Transparansi Donasi Publik
                            </a>
                            <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill py-2">
                                Kembali ke Beranda
                            </a>
                        </div>

                    @else
                        {{-- ========================================================= --}}
                        {{-- STATE 2: MENUNGGU PEMBAYARAN (QRIS DINAMIS & POLLING)    --}}
                        {{-- ========================================================= --}}
                        <p class="text-muted text-center mb-3">
                            Jazakumullah Khairan Katsiran kepada Bapak/Ibu <strong>{{ $donation->display_name }}</strong>. Silakan selesaikan pembayaran donasi dengan rincian di bawah ini:
                        </p>

                        <!-- Box Nominal Terkunci -->
                        <div class="p-3 bg-light rounded-4 text-center border mb-4">
                            <small class="text-muted text-uppercase fw-bold">Nominal Terkunci Otomatis:</small>
                            <div class="fs-2 fw-extrabold text-success my-1 font-monospace" id="totalAmount">
                                Rp {{ number_format($donation->total_amount, 0, ',', '.') }}
                            </div>
                            <small class="text-muted">
                                <i class="fa-solid fa-lock text-success me-1"></i>Nominal otomatis terkunci di aplikasi perbankan saat dipindai.
                            </small>
                        </div>

                        <!-- Card Dynamic QRIS atau Transfer Bank -->
                        @if($donation->payment_method === 'qris')
                            <div class="card bg-white border border-success-subtle shadow-sm rounded-4 p-4 text-center mb-4">
                                <!-- Badge Header -->
                                <div class="d-flex justify-content-between align-items-center mb-3">
                                    <span class="badge bg-success text-white px-3 py-1 rounded-pill">
                                        <i class="fa-solid fa-bolt me-1 text-warning"></i> QRIS DINAMIS
                                    </span>
                                    <!-- Countdown Timer Box -->
                                    <div class="d-inline-flex align-items-center gap-1 text-danger fw-bold small bg-danger-subtle px-3 py-1 rounded-pill">
                                        <i class="fa-regular fa-clock"></i>
                                        <span>Batas Waktu: </span>
                                        <span id="countdownTimer" class="font-monospace">15:00</span>
                                    </div>
                                </div>

                                <!-- Gambar QR Code Dinamis -->
                                <div class="p-2 d-inline-block bg-white rounded-3 shadow-sm border mb-2" style="max-width: 280px; margin: 0 auto;">
                                    <img id="qrisImage" src="{{ $donation->qris_url ?: asset('qris_lazisnu_bri.jpg') }}" alt="QRIS Dinamis LAZISNU" class="img-fluid rounded" style="width: 100%; height: auto;">
                                </div>

                                <!-- Tombol Download QRIS Dinamis -->
                                <div class="mb-3">
                                    <a href="{{ route('donation.download-qris', $donation->donation_code) }}" class="btn btn-outline-success fw-bold px-4 py-2 rounded-pill shadow-sm" id="btnDownloadQris">
                                        <i class="fa-solid fa-cloud-arrow-down me-1"></i> Download QRIS
                                    </a>
                                </div>

                                <div class="small fw-bold text-dark font-monospace mb-1">LAZISNU LAMONGAN (NMID: ID1026559758341)</div>
                                <p class="text-muted small mb-2">
                                    Buka aplikasi Mobile Banking (BRImo, Livin', BCA Mobile, BSI Mobile) atau E-Wallet (GoPay, OVO, DANA, ShopeePay) lalu <strong>Pindai QR</strong> di atas.
                                </p>

                                <!-- Status Detektor Real-Time -->
                                <div class="p-2.5 rounded-3 bg-light border d-flex align-items-center justify-content-center gap-2 mb-2">
                                    <div class="spinner-grow text-success spinner-grow-sm" role="status"></div>
                                    <span class="small fw-bold text-secondary" id="pollingStatusText">
                                        Mendeteksi pembayaran otomatis dari server...
                                    </span>
                                </div>
                            </div>

                            <!-- ========================================================= -->
                            <!-- PANDUAN PEMBAYARAN MENGGUNAKAN APLIKASI MOBILE BANKING    -->
                            <!-- ========================================================= -->
                            <div class="card bg-white border border-secondary-subtle shadow-sm rounded-4 p-4 text-start mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 34px; height: 34px; min-width: 34px;">
                                        <i class="fa-solid fa-mobile-screen-button"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Panduan Pembayaran</h6>
                                        <small class="text-muted fw-semibold">Menggunakan Aplikasi Mobile Banking</small>
                                    </div>
                                </div>

                                <ol class="ps-3 mb-0 text-secondary small" style="line-height: 1.85;">
                                    <li class="mb-2">
                                        Pilih tombol <strong>"Download QRIS"</strong> untuk menyimpan QR Code-nya
                                    </li>
                                    <li class="mb-2">
                                        Buka Aplikasi <strong>Mobile Banking</strong> yang dimiliki dan mendukung fasilitas QRIS
                                    </li>
                                    <li class="mb-2">
                                        Buka menu <strong>"Scan/Bayar"</strong> kemudian pilih metode/tekan ikon <strong>"Upload Gambar"</strong>. Kemudian pilih gambar QR Code dari QRIS yang sudah didownload sebelumnya
                                    </li>
                                    <li class="mb-2">
                                        Periksa kembali detail pembayaran anda
                                    </li>
                                    <li class="mb-2">
                                        Jika sudah sesuai, konfirmasi dan bayar
                                    </li>
                                    <li class="mb-2">
                                        <span class="text-success fw-bold"><i class="fa-solid fa-circle-check me-1"></i>Transaksi Berhasil</span>
                                    </li>
                                    <li class="mb-0">
                                        Buka <strong>Aplikasi NU Online</strong> atau email yang terhubung dengan e-wallet Anda dan cek kembali transaksi.
                                    </li>
                                </ol>
                            </div>
                        @else
                            {{-- Transfer Bank Manual --}}
                            <div class="card bg-white border rounded-3 p-3 mb-4">
                                <small class="text-muted fw-bold mb-2 d-block">Rekening Tujuan Penyaluran:</small>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                    <div>
                                        <div class="fw-bold fs-5 text-dark">Bank Syariah Indonesia (BSI)</div>
                                        <div class="text-muted small">No. Rekening: <strong class="fs-5 text-dark font-monospace" id="rekNo">{{ $settings['bank_bsi_zakat'] ?? '2026070708' }}</strong></div>
                                        <small class="text-muted">Atas Nama: <strong>{{ $settings['bank_bsi_zakat_name'] ?? 'Lazisnu Lamongan' }}</strong></small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-success rounded-pill fw-bold" onclick="copyToClipboard('{{ $settings['bank_bsi_zakat'] ?? '2026070708' }}', 'btnCopyRek')" id="btnCopyRek">
                                        <i class="fa-regular fa-copy me-1"></i> Salin
                                    </button>
                                </div>
                            </div>
                        @endif

                        <!-- Informasi Program -->
                        <div class="mb-4 small text-muted">
                            <div>Program Kebaikan: <strong>{{ $donation->campaign->title }}</strong></div>
                            <div>Status Pembayaran: <span class="badge bg-warning text-dark" id="paymentStatusBadge">Menunggu Transfer</span></div>
                        </div>

                        <!-- Tombol Bantuan / WA -->
                        @php
                            $waText = urlencode("Assalamu'alaikum LAZISNU, saya sedang melakukan donasi online:\n\nKode: {$donation->donation_code}\nNama: {$donation->donor_name}\nNominal: Rp " . number_format($donation->total_amount, 0, ',', '.') . "\nProgram: {$donation->campaign->title}");
                            $cleanWa = preg_replace('/[^0-9]/', '', $settings['phone_wa_hotline'] ?? '6285188991926');
                            if (str_starts_with($cleanWa, '0')) {
                                $cleanWa = '62' . substr($cleanWa, 1);
                            }
                        @endphp
                        <div class="d-grid gap-2">
                            <a href="https://wa.me/{{ $cleanWa }}?text={{ $waText }}" target="_blank" class="btn btn-outline-success fw-bold py-2 rounded-pill">
                                <i class="fa-brands fa-whatsapp fs-5 me-1"></i> Butuh Bantuan? Hubungi Admin
                            </a>

                            <a href="{{ route('home') }}" class="btn btn-link text-muted btn-sm text-decoration-none mt-2">
                                <i class="fa-solid fa-arrow-left me-1"></i> Batalkan &amp; Kembali ke Beranda
                            </a>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
@if($donation->payment_status !== 'verified')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const donationCode = "{{ $donation->donation_code }}";
        const checkStatusUrl = "{{ route('api.donation.check-status', $donation->donation_code) }}";
        const pollingText = document.getElementById('pollingStatusText');
        const countdownEl = document.getElementById('countdownTimer');
        const statusBadge = document.getElementById('paymentStatusBadge');

        // =========================================================================
        // 1. TIMER KADALUARSA 15 MENIT
        // =========================================================================
        let durationSeconds = 15 * 60; // 15 menit
        let timerInterval = setInterval(function () {
            durationSeconds--;
            if (durationSeconds <= 0) {
                clearInterval(timerInterval);
                clearInterval(pollingInterval);
                if (countdownEl) countdownEl.textContent = "00:00 (Kadaluarsa)";
                if (pollingText) pollingText.innerHTML = '<span class="text-danger fw-bold"><i class="fa-solid fa-triangle-exclamation me-1"></i>Waktu pembayaran telah habis. Silakan buat donasi baru.</span>';
                if (statusBadge) {
                    statusBadge.className = 'badge bg-danger';
                    statusBadge.textContent = 'Kadaluarsa';
                }
                return;
            }

            let minutes = Math.floor(durationSeconds / 60);
            let seconds = durationSeconds % 60;
            if (countdownEl) {
                countdownEl.textContent = 
                    (minutes < 10 ? "0" + minutes : minutes) + ":" + 
                    (seconds < 10 ? "0" + seconds : seconds);
            }
        }, 1000);

        // =========================================================================
        // 2. AJAX POLLING RINGAN (CEK STATUS TIAP 3 DETIK)
        // =========================================================================
        let isChecking = false;
        let pollingInterval = setInterval(function () {
            if (isChecking) return;
            isChecking = true;

            fetch(checkStatusUrl)
                .then(res => res.json())
                .then(data => {
                    isChecking = false;

                    // JIKA STATUS BERUBAH MENJADI TERVERIFIKASI (Trigger dari Webhook Backend)
                    if (data.is_verified) {
                        clearInterval(pollingInterval);
                        clearInterval(timerInterval);

                        if (pollingText) {
                            pollingText.innerHTML = '<span class="text-success fw-bold"><i class="fa-solid fa-check-circle me-1"></i>Pembayaran Terdeteksi! Mengalihkan ke kuitansi...</span>';
                        }
                        if (statusBadge) {
                            statusBadge.className = 'badge bg-success';
                            statusBadge.textContent = 'Sah / Terverifikasi';
                        }

                        // Efek transisi halus: redirect otomatis ke kuitansi dalam 1 detik
                        setTimeout(() => {
                            window.location.href = data.redirect_url || window.location.href;
                        }, 1200);
                    }
                })
                .catch(err => {
                    isChecking = false;
                    console.log("Polling error:", err);
                });
        }, 3000);
    });

    // Helper fungsi simulasi pembayaran (Developer Test)
    function simulatePaymentSuccess() {
        const btn = document.getElementById('btnSimulate');
        if (btn) btn.disabled = true;

        fetch("{{ route('api.donation.simulate', $donation->donation_code) }}", {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(res => res.json())
        .then(res => {
            console.log("Simulated webhook executed:", res);
        })
        .catch(err => alert("Gagal simulasi: " + err));
    }
</script>
@endif
@endpush

