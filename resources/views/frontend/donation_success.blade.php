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
                                <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $donation->kategori_label }}</span>
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

                        <!-- Box Nominal Transfer -->
                        <!-- Box Nominal Transfer -->
                        <div class="p-3 bg-light rounded-4 text-center border mb-4">
                            <small class="text-muted text-uppercase fw-bold">Total Nominal Donasi / Transfer:</small>
                            <div class="d-flex align-items-center justify-content-center gap-2 my-1">
                                <span class="fs-2 fw-extrabold text-success font-monospace" id="totalAmount">
                                    Rp {{ number_format($donation->total_amount, 0, ',', '.') }}
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 fw-bold" onclick="copyToClipboard('{{ (int)$donation->total_amount }}', 'btnCopyNominal')" id="btnCopyNominal" title="Salin Nominal Transfer">
                                    <i class="fa-regular fa-copy me-1"></i> Salin
                                </button>
                            </div>
                            <small class="text-danger fw-bold">
                                *Termasuk kode unik Rp {{ $donation->unique_code }} untuk memudahkan verifikasi pencocokan mutasi.
                            </small>
                        </div>

                        <!-- Card QRIS Statis Bank BRI / BSI Sesuai Kategori -->
                        @if(in_array($donation->payment_method, ['qris', 'qris_bri', 'qris_bni', 'qris_bsi']))
                            @php
                                $isQrisBsi = $donation->payment_method === 'qris_bsi' || preg_match('/zakat/i', $donation->kategori ?? '');
                            @endphp
                            <div class="card bg-white border {{ $isQrisBsi ? 'border-primary-subtle' : 'border-success-subtle' }} shadow-sm rounded-4 p-4 text-center mb-4">
                                <!-- Badge Header -->
                                <div class="mb-3">
                                    <span class="badge {{ $isQrisBsi ? 'bg-primary' : 'bg-success' }} text-white px-3 py-1 rounded-pill fw-bold">
                                        <i class="fa-solid fa-qrcode me-1"></i> {{ $isQrisBsi ? 'QRIS Resmi Bank BSI (Khusus Zakat Mal & Profesi)' : 'QRIS Resmi Bank BRI (Semua E-Wallet & M-Banking)' }}
                                    </span>
                                </div>

                                <!-- Gambar QR Code Statis BRI / BSI -->
                                <div class="p-2 d-inline-block bg-white rounded-3 shadow-sm border mb-2" style="max-width: 280px; margin: 0 auto;">
                                    <img src="{{ asset($isQrisBsi ? 'qris_lazisnu_bsi.jpg' : 'qris_lazisnu_bri.jpg') }}" alt="QRIS {{ $isQrisBsi ? 'Bank BSI' : 'Bank BRI' }} LAZISNU Lamongan" class="img-fluid rounded" style="width: 100%; height: auto;">
                                </div>

                                <div class="small fw-bold text-dark font-monospace mb-1">
                                    {{ $isQrisBsi ? 'LAZISNU LAMONGAN (BSI - KHUSUS ZAKAT)' : 'LAZISNU LAMONGAN (NMID: ID1026559758341)' }}
                                </div>
                                <p class="text-muted small mb-3">
                                    Pindai menggunakan aplikasi <strong>Mobile Banking</strong> (BRImo, Livin' by Mandiri, BCA Mobile, BSI Mobile) atau <strong>E-Wallet</strong> (GoPay, OVO, DANA, ShopeePay, LinkAja).
                                </p>

                                <!-- Tombol Download QRIS Statis -->
                                <div class="mb-2">
                                    <a href="{{ route('donation.download-qris', $donation->donation_code) }}" class="btn {{ $isQrisBsi ? 'btn-outline-primary' : 'btn-outline-success' }} fw-bold px-4 py-2 rounded-pill shadow-sm" id="btnDownloadQris">
                                        <i class="fa-solid fa-cloud-arrow-down me-1"></i> Unduh Gambar QRIS {{ $isQrisBsi ? 'BSI' : 'BRI' }}
                                    </a>
                                </div>
                            </div>

                            <!-- ========================================================= -->
                            <!-- PANDUAN PEMBAYARAN QRIS STATIS                           -->
                            <!-- ========================================================= -->
                            <div class="card bg-white border border-secondary-subtle shadow-sm rounded-4 p-4 text-start mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 34px; height: 34px; min-width: 34px;">
                                        <i class="fa-solid fa-list-ol"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Panduan Pembayaran QRIS</h6>
                                        <small class="text-muted fw-semibold">Penyaluran Cepat &amp; Amanah</small>
                                    </div>
                                </div>

                                <ol class="ps-3 mb-0 text-secondary small" style="line-height: 1.85;">
                                    <li class="mb-2">
                                        Unduh gambar QRIS di atas atau langsung pindai barcode dari layar.
                                    </li>
                                    <li class="mb-2">
                                        Buka aplikasi <strong>Mobile Banking</strong> atau <strong>E-Wallet</strong> yang Anda miliki, lalu buka menu <strong>"Scan / Bayar QR"</strong>.
                                    </li>
                                    <li class="mb-2">
                                        Pastikan nama merchant penerima tertera: <strong>LAZISNU LAMONGAN</strong>.
                                    </li>
                                    <li class="mb-2">
                                        Masukkan nominal transfer tepat sejumlah: <strong class="text-success font-monospace">Rp {{ number_format($donation->total_amount, 0, ',', '.') }}</strong> (sesuai total di atas).
                                    </li>
                                    <li class="mb-0">
                                        Setelah transaksi berhasil, silakan klik tombol <strong>"Konfirmasi Donasi via WhatsApp"</strong> di bawah untuk mengirimkan bukti transfer kepada admin LAZISNU.
                                    </li>
                                </ol>
                            </div>
                        @elseif($donation->payment_method === 'transfer_bri')
                            @php
                                $targetBank = 'Bank BRI';
                                $targetRek = $settings['bank_bri_infaq'] ?? '004101001448562';
                                $targetName = $settings['bank_bri_infaq_name'] ?? 'LAZISNU LAMONGAN';
                            @endphp
                            {{-- Transfer Bank BRI --}}
                            <div class="card bg-white border border-success-subtle shadow-sm rounded-4 p-4 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-success-subtle text-success border border-success-subtle rounded-pill px-3 py-1 fw-bold">
                                        <i class="fa-solid fa-building-columns me-1"></i> Rekening Resmi Bank BRI
                                    </span>
                                    <span class="small text-muted fw-semibold">Selain Zakat (Infaq, Sedekah, dll)</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 bg-light rounded-3 border">
                                    <div>
                                        <div class="fw-bold fs-5 text-dark mb-1">
                                            <i class="fa-solid fa-money-check-dollar text-success me-1"></i> Bank BRI
                                        </div>
                                        <div class="text-muted small">No. Rekening: <strong class="fs-5 text-dark font-monospace text-primary" id="rekNo">{{ $targetRek }}</strong></div>
                                        <small class="text-muted">Atas Nama: <strong class="text-dark">{{ $targetName }}</strong></small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-success rounded-pill fw-bold px-3 py-2" onclick="copyToClipboard('{{ $targetRek }}', 'btnCopyRek')" id="btnCopyRek">
                                        <i class="fa-regular fa-copy me-1"></i> Salin
                                    </button>
                                </div>
                            </div>

                            <!-- ========================================================= -->
                            <!-- PANDUAN PEMBAYARAN TRANSFER (TF)                         -->
                            <!-- ========================================================= -->
                            <div class="card bg-white border border-secondary-subtle shadow-sm rounded-4 p-4 text-start mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-success text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; min-width: 36px;">
                                        <i class="fa-solid fa-list-ol"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Panduan Pembayaran</h6>
                                        <small class="text-success fw-bold">Jika Lewat Transfer (TF)</small>
                                    </div>
                                </div>

                                <ol class="ps-3 mb-0 text-secondary small" style="line-height: 1.85;">
                                    <li class="mb-2">
                                        Pilih tombol <strong>"Salin"</strong> untuk menyimpan Nomor Rekening LAZISNU Lamongan.
                                    </li>
                                    <li class="mb-2">
                                        Buka Aplikasi <strong>Mobile Banking</strong> yang dimiliki kemudian Pilih menu <strong>Transfer ke {{ $targetBank }}</strong> di Nomor Rekening <strong class="text-dark font-monospace">{{ $targetRek }}</strong>.
                                    </li>
                                    <li class="mb-2">
                                        Pastikan nama merchant penerima tertera: <strong>{{ $targetName }}</strong>.
                                    </li>
                                    <li class="mb-2">
                                        Masukkan nominal transfer tepat sejumlah: <strong class="text-success font-monospace fs-6">Rp {{ number_format($donation->total_amount, 0, ',', '.') }}</strong> (sesuai total di atas).
                                    </li>
                                    <li class="mb-2">
                                        Setelah transaksi berhasil, silakan klik tombol <strong>"Konfirmasi Donasi via WhatsApp"</strong> di bawah untuk mengirimkan bukti transfer kepada admin LAZISNU. Jika sudah sesuai, konfirmasi dan bayar.
                                    </li>
                                    <li class="mb-0">
                                        Transaksi Berhasil.
                                    </li>
                                </ol>
                            </div>
                        @else
                            @php
                                $targetBank = 'Bank Syariah Indonesia (BSI)';
                                $targetRek = $settings['bank_bsi_zakat'] ?? '2026070708';
                                $targetName = $settings['bank_bsi_zakat_name'] ?? 'LAZISNU LAMONGAN';
                            @endphp
                            {{-- Transfer BSI atau Khusus Zakat --}}
                            <div class="card bg-white border border-primary-subtle shadow-sm rounded-4 p-4 mb-4">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle rounded-pill px-3 py-1 fw-bold">
                                        <i class="fa-solid fa-building-columns me-1"></i> Rekening Resmi Bank BSI
                                    </span>
                                    <span class="small text-muted fw-semibold">Khusus Zakat Mal &amp; Profesi</span>
                                </div>
                                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 bg-light rounded-3 border">
                                    <div>
                                        <div class="fw-bold fs-5 text-dark mb-1">
                                            <i class="fa-solid fa-money-check-dollar text-primary me-1"></i> Bank Syariah Indonesia (BSI)
                                        </div>
                                        <div class="text-muted small">No. Rekening: <strong class="fs-5 text-dark font-monospace text-primary" id="rekNo">{{ $targetRek }}</strong></div>
                                        <small class="text-muted">Atas Nama: <strong class="text-dark">{{ $targetName }}</strong></small>
                                    </div>
                                    <button class="btn btn-sm btn-outline-primary rounded-pill fw-bold px-3 py-2" onclick="copyToClipboard('{{ $targetRek }}', 'btnCopyRek')" id="btnCopyRek">
                                        <i class="fa-regular fa-copy me-1"></i> Salin
                                    </button>
                                </div>
                            </div>

                            <!-- ========================================================= -->
                            <!-- PANDUAN PEMBAYARAN TRANSFER (TF)                         -->
                            <!-- ========================================================= -->
                            <div class="card bg-white border border-secondary-subtle shadow-sm rounded-4 p-4 text-start mb-4">
                                <div class="d-flex align-items-center mb-3">
                                    <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 36px; height: 36px; min-width: 36px;">
                                        <i class="fa-solid fa-list-ol"></i>
                                    </div>
                                    <div>
                                        <h6 class="fw-bold mb-0 text-dark">Panduan Pembayaran</h6>
                                        <small class="text-primary fw-bold">Jika Lewat Transfer (TF)</small>
                                    </div>
                                </div>

                                <ol class="ps-3 mb-0 text-secondary small" style="line-height: 1.85;">
                                    <li class="mb-2">
                                        Pilih tombol <strong>"Salin"</strong> untuk menyimpan Nomor Rekening LAZISNU Lamongan.
                                    </li>
                                    <li class="mb-2">
                                        Buka Aplikasi <strong>Mobile Banking</strong> yang dimiliki kemudian Pilih menu <strong>Transfer ke {{ $targetBank }}</strong> di Nomor Rekening <strong class="text-dark font-monospace">{{ $targetRek }}</strong>.
                                    </li>
                                    <li class="mb-2">
                                        Pastikan nama merchant penerima tertera: <strong>{{ $targetName }}</strong>.
                                    </li>
                                    <li class="mb-2">
                                        Masukkan nominal transfer tepat sejumlah: <strong class="text-primary font-monospace fs-6">Rp {{ number_format($donation->total_amount, 0, ',', '.') }}</strong> (sesuai total di atas).
                                    </li>
                                    <li class="mb-2">
                                        Setelah transaksi berhasil, silakan klik tombol <strong>"Konfirmasi Donasi via WhatsApp"</strong> di bawah untuk mengirimkan bukti transfer kepada admin LAZISNU. Jika sudah sesuai, konfirmasi dan bayar.
                                    </li>
                                    <li class="mb-0">
                                        Transaksi Berhasil.
                                    </li>
                                </ol>
                            </div>
                        @endif

                        <!-- Informasi Program -->
                        <div class="mb-4 small text-muted">
                            <div>Program Kebaikan: <strong>{{ $donation->campaign->title }}</strong></div>
                            <div>Kategori: <span class="badge bg-success-subtle text-success border border-success-subtle">{{ $donation->kategori_label }}</span></div>
                            <div>Status Pembayaran: <span class="badge bg-warning text-dark" id="paymentStatusBadge">Menunggu Pembayaran</span></div>
                        </div>

                        <!-- Tombol Bantuan / WA Konfirmasi -->
                        @php
                            $waMethodLabel = match($donation->payment_method) {
                                'qris', 'qris_bri', 'qris_bni' => 'QRIS Bank BRI',
                                'qris_bsi' => 'QRIS Bank BSI (Khusus Zakat)',
                                'transfer_bri' => 'Transfer Bank BRI',
                                'transfer_bsi' => 'Transfer Bank BSI',
                                default => 'Transfer Bank',
                            };
                            $waText = urlencode("Assalamu'alaikum LAZISNU, saya ingin konfirmasi transfer donasi online:\n\nKode Transaksi: {$donation->donation_code}\nNama Donatur: {$donation->donor_name}\nNominal: Rp " . number_format($donation->total_amount, 0, ',', '.') . "\nMetode: {$waMethodLabel}\nProgram: {$donation->campaign->title}\n\nMohon diverifikasi, terima kasih.");
                            $cleanWa = \App\Services\WhatsAppHelper::formatNumber($settings['phone_wa_hotline'] ?? '6285188991926') ?? '6285188991926';
                        @endphp
                        <div class="d-grid gap-2">
                            <a href="https://wa.me/{{ $cleanWa }}?text={{ $waText }}" target="_blank" class="btn btn-success fw-bold py-3 rounded-pill shadow-sm" style="background: #25d366; border: none;">
                                <i class="fa-brands fa-whatsapp fs-5 me-2"></i> Konfirmasi Donasi via WhatsApp
                            </a>

                            <a href="{{ route('home') }}" class="btn btn-outline-secondary rounded-pill py-2">
                                <i class="fa-solid fa-arrow-left me-1"></i> Kembali ke Beranda
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
<script>
    function copyToClipboard(text, btnId) {
        if (navigator.clipboard) {
            navigator.clipboard.writeText(text).then(function() {
                showCopiedFeedback(btnId);
            }).catch(function() {
                fallbackCopy(text, btnId);
            });
        } else {
            fallbackCopy(text, btnId);
        }
    }

    function fallbackCopy(text, btnId) {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        document.body.appendChild(textarea);
        textarea.select();
        document.execCommand('copy');
        document.body.removeChild(textarea);
        showCopiedFeedback(btnId);
    }

    function showCopiedFeedback(btnId) {
        const btn = document.getElementById(btnId);
        if (btn) {
            const originalHtml = btn.innerHTML;
            btn.innerHTML = '<i class="fa-solid fa-check me-1"></i> Tersalin!';
            btn.classList.remove('btn-outline-success');
            btn.classList.add('btn-success', 'text-white');
            setTimeout(function() {
                btn.innerHTML = originalHtml;
                btn.classList.remove('btn-success', 'text-white');
                btn.classList.add('btn-outline-success');
            }, 2000);
        }
    }

    @if($donation->payment_status !== 'verified')
    document.addEventListener('DOMContentLoaded', function () {
        const checkStatusUrl = "{{ route('api.donation.check-status', $donation->donation_code) }}";
        const statusBadge = document.getElementById('paymentStatusBadge');

        // Pengecekan status otomatis saat admin memverifikasi di backend
        let isChecking = false;
        let pollingInterval = setInterval(function () {
            if (isChecking) return;
            isChecking = true;

            fetch(checkStatusUrl)
                .then(res => res.json())
                .then(data => {
                    isChecking = false;
                    if (data.is_verified) {
                        clearInterval(pollingInterval);
                        if (statusBadge) {
                            statusBadge.className = 'badge bg-success';
                            statusBadge.textContent = 'Sah / Terverifikasi';
                        }
                        setTimeout(() => {
                            window.location.href = data.redirect_url || window.location.href;
                        }, 1200);
                    }
                })
                .catch(() => {
                    isChecking = false;
                });
        }, 5000);
    });
    @endif
</script>
@endpush

