@extends('layouts.adminlte')

@section('title', 'Pengaturan Portal & Rekening Donasi')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-sliders-h text-warning mr-2"></i>Pengaturan Portal Publik & Rekening Donasi</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('home') }}" target="_blank" class="btn btn-outline-success font-weight-bold btn-sm">
                    <i class="fas fa-external-link-alt mr-1"></i> Buka Website Publik
                </a>
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

        <div class="card card-outline card-success shadow-sm">
            <form action="{{ route('admin.site-settings.update') }}" method="POST">
                @csrf
                <div class="card-header p-2">
                    <ul class="nav nav-pills" id="settingsTab" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active font-weight-bold" id="general-tab" data-toggle="pill" href="#tab-general" role="tab">
                                <i class="fas fa-building mr-1"></i> Profil Lembaga & Legalitas
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="bank-tab" data-toggle="pill" href="#tab-bank" role="tab">
                                <i class="fas fa-credit-card mr-1"></i> Rekening Bank Resmi
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="contact-tab" data-toggle="pill" href="#tab-contact" role="tab">
                                <i class="fas fa-headset mr-1"></i> Kontak & Hotline WA
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link font-weight-bold" id="zakat-tab" data-toggle="pill" href="#tab-zakat" role="tab">
                                <i class="fas fa-calculator mr-1"></i> Parameter Nisab Zakat
                            </a>
                        </li>
                    </ul>
                </div>

                <div class="card-body">
                    <div class="tab-content" id="settingsTabContent">
                        <!-- TAB 1: IDENTITAS & LEGALITAS -->
                        <div class="tab-pane fade show active" id="tab-general" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Nama Lembaga Utama</label>
                                    <input type="text" name="site_name" class="form-control" value="{{ $settings['site_name'] ?? 'NU Care - LAZISNU' }}" required>
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Nama Cabang / PC</label>
                                    <input type="text" name="branch_name" class="form-control" value="{{ $settings['branch_name'] ?? 'Pengurus Cabang Nahdlatul Ulama' }}" required>
                                </div>
                            </div>

                            <div class="form-group">
                                <label>Motto / Slogan (Tagline)</label>
                                <input type="text" name="site_tagline" class="form-control" value="{{ $settings['site_tagline'] ?? 'Berkhidmat untuk Kemandirian Umat, Mantap Melayani Dhuafa' }}">
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Legalitas SK Kemenag & Izin BAZNAS</label>
                                    <input type="text" name="legal_kemenag" class="form-control" value="{{ $settings['legal_kemenag'] ?? 'Izin Operasional SK Kemenag RI No. 255 Tahun 2021 | Terdaftar Resmi BAZNAS' }}">
                                </div>
                                <div class="col-md-6 form-group">
                                    <label>Opini Hasil Audit Keuangan</label>
                                    <input type="text" name="audit_opinion" class="form-control" value="{{ $settings['audit_opinion'] ?? 'Wajar Tanpa Pengecualian (WTP)' }}">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Alamat Lengkap Kantor Sekretariat</label>
                                    <input type="text" name="office_address" class="form-control" value="{{ $settings['office_address'] ?? 'Jl. Kyai Amin No 9 Lamongan 62213' }}">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label>Website Resmi</label>
                                    <input type="text" name="website_url" class="form-control" value="{{ $settings['website_url'] ?? 'https://lazispcnulamongan.com' }}">
                                </div>
                                <div class="col-md-3 form-group">
                                    <label>Jam Kerja Operasional</label>
                                    <input type="text" name="office_hours" class="form-control" value="{{ $settings['office_hours'] ?? 'Senin - Sabtu: 08.00 - 16.00 WIB (Layanan & Ambulans 24 Jam)' }}">
                                </div>
                            </div>
                        </div>

                        <!-- TAB 2: REKENING BANK RESMI -->
                        <div class="tab-pane fade" id="tab-bank" role="tabpanel">
                            <div class="alert alert-info small mb-3">
                                <i class="fas fa-info-circle mr-1"></i> Rekening di bawah ini dipisahkan berdasarkan kategori penerimaan donasi dan tampil pada portal publik dan form donasi.
                            </div>

                            <div class="row">
                                <!-- BSI Khusus Zakat -->
                                <div class="col-md-6 form-group border p-3 rounded mb-3 bg-light border-primary">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="text-primary font-weight-bold mb-0"><i class="fas fa-building-columns mr-1"></i> Rekening Khusus Zakat (BSI)</label>
                                        <span class="badge badge-primary">Khusus Zakat</span>
                                    </div>
                                    <div class="mb-2">
                                        <label class="small text-muted mb-0">Nomor Rekening BSI:</label>
                                        <input type="text" name="bank_bsi_zakat" class="form-control font-weight-bold font-monospace" value="{{ $settings['bank_bsi_zakat'] ?? '2026070708' }}">
                                    </div>
                                    <div>
                                        <label class="small text-muted mb-0">Atas Nama Rekening:</label>
                                        <input type="text" name="bank_bsi_zakat_name" class="form-control" value="{{ $settings['bank_bsi_zakat_name'] ?? 'Lazisnu Lamongan' }}">
                                    </div>
                                </div>

                                <!-- BRI Selain Zakat -->
                                <div class="col-md-6 form-group border p-3 rounded mb-3 bg-light border-success">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="text-success font-weight-bold mb-0"><i class="fas fa-building-columns mr-1"></i> Rekening Selain Zakat (BRI)</label>
                                        <span class="badge badge-success">Infaq, Shadaqah & Koin NU</span>
                                    </div>
                                    <div class="mb-2">
                                        <label class="small text-muted mb-0">Nomor Rekening BRI:</label>
                                        <input type="text" name="bank_bri_infaq" class="form-control font-weight-bold font-monospace" value="{{ $settings['bank_bri_infaq'] ?? '00411001448562' }}">
                                    </div>
                                    <div>
                                        <label class="small text-muted mb-0">Atas Nama Rekening:</label>
                                        <input type="text" name="bank_bri_infaq_name" class="form-control" value="{{ $settings['bank_bri_infaq_name'] ?? 'LAZISNU LAMONGAN' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 3: KONTAK & WHATSAPP -->
                        <div class="tab-pane fade" id="tab-contact" role="tabpanel">
                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label>Nomor WhatsApp Hotline Layanan Donatur & Ambulans</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span></div>
                                        <input type="text" name="phone_wa_hotline" class="form-control font-weight-bold" value="{{ $settings['phone_wa_hotline'] ?? '0812-3456-7890' }}" placeholder="0812-3456-7890">
                                    </div>
                                    <small class="text-muted">Nomor ini terhubung dengan tombol floating WhatsApp di website publik.</small>
                                </div>

                                <div class="col-md-6 form-group">
                                    <label>Alamat Email Resmi</label>
                                    <div class="input-group">
                                        <div class="input-group-prepend"><span class="input-group-text"><i class="fas fa-envelope"></i></span></div>
                                        <input type="email" name="email_support" class="form-control" value="{{ $settings['email_support'] ?? 'lazisnu.center@gmail.com' }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- TAB 4: PARAMETER ZAKAT -->
                        <div class="tab-pane fade" id="tab-zakat" role="tabpanel">
                            <div class="form-group" style="max-width: 400px;">
                                <label>Harga Emas Murni Terkini (Rp / gram)</label>
                                <div class="input-group">
                                    <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                                    <input type="number" name="gold_price_per_gram" class="form-control font-weight-bold text-success" value="{{ $settings['gold_price_per_gram'] ?? 1450000 }}" step="10000">
                                </div>
                                <small class="text-muted">Digunakan secara otomatis untuk menghitung batas nisab zakat (85 gram emas) pada Kalkulator Zakat Online.</small>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-success font-weight-bold px-4 py-2">
                        <i class="fas fa-save mr-1"></i> Simpan Seluruh Pengaturan
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
