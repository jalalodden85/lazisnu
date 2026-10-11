@extends('layouts.adminlte')

@section('title', 'Setoran Kontribusi Struktur (MWC/PC/PW/PB)')
@section('page_title', 'Setoran Kontribusi Struktur')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pentasyarufan.index') }}">Pentasyarufan</a></li>
    <li class="breadcrumb-item"><a href="{{ route('bku.index') }}">Buku Kas Umum</a></li>
    <li class="breadcrumb-item active">Setoran Kontribusi Struktur</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-11">
        <form action="{{ route('pentasyarufan.kontribusi.store') }}" method="POST" enctype="multipart/form-data" id="formKontribusi">
            @csrf
            
            <div class="card card-outline card-success shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h3 class="card-title font-weight-bold text-dark mb-0">
                        <i class="fas fa-hand-holding-usd text-success mr-2"></i>Formulir Setoran Kontribusi / Pembagian Hasil Struktur
                    </h3>
                    <span class="badge badge-success px-3 py-2">
                        <i class="fas fa-shield-alt mr-1"></i> Terkunci pada Periode Pentong & Terintegrasi BKU
                    </span>
                </div>
                <div class="card-body">
                    @if($errors->any())
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-triangle mr-1"></i> Terdapat Kesalahan Input:</h6>
                            <ul class="mb-0 pl-3 small">
                                @foreach($errors->all() as $err)
                                    <li>{{ $err }}</li>
                                endforeach
                            </ul>
                            <button type="button" class="close" data-dismiss="alert">&times;</button>
                        </div>
                    @endif

                    <!-- Informasi Alur Akuntansi BKU -->
                    <div class="alert alert-light border-left-success py-3 px-3 small mb-4 text-dark shadow-xs" style="border-left: 4px solid #28a745; background-color: #f8fbf9;">
                        <div class="d-flex align-items-center">
                            <i class="fas fa-book-open text-success fa-2x mr-3"></i>
                            <div>
                                <strong class="text-success" style="font-size: 0.95rem;">Standar Akuntansi Buku Kas Umum (BKU) KOIN NU:</strong>
                                <span class="text-muted d-block mt-1">
                                    Setoran bagi hasil ke struktur atas ini akan secara otomatis dicatat pada <strong>Buku Kas Umum (BKU) Ranting</strong> di kolom <strong>PENGELUARAN (Sisi Kanan / Kredit)</strong> dengan kode akun <strong><code>NU-KONTRIBUSI</code></strong>. Transaksi ini mengunci saldo per Periode Pentong dan mengurangi saldo akhir buku kas kasir secara real-time.
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- =============================================================== -->
                    <!-- LANGKAH 1: IDENTIFIKASI PERIODE PENTONG & SCOPE RANTING         -->
                    <!-- =============================================================== -->
                    <div class="card bg-light border mb-4">
                        <div class="card-header bg-white py-2">
                            <span class="badge badge-dark mr-2" style="font-size: 0.9rem;">Langkah 1</span>
                            <strong class="text-dark"><i class="fas fa-calendar-alt text-primary mr-1"></i> Sumber Dana & Entitas Wilayah</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <!-- Pilihan Periode Pentong -->
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold">
                                        Periode Pentong <span class="text-danger">*</span>
                                    </label>
                                    <select name="periode_pentong_id" id="selectPenthong" class="form-control select2bs4" required onchange="window.location.href='{{ route('pentasyarufan.kontribusi.create') }}?penthong_id=' + this.value + '{{ $rantingId ? '&ranting_id='.$rantingId : '' }}'">
                                        @foreach($penthongs as $pt)
                                            <option value="{{ $pt->id }}" {{ $selectedPenthongId == $pt->id ? 'selected' : '' }}>
                                                {{ $pt->nama_periode }} ({{ $pt->bulan_nama }} {{ $pt->tahun }}) 
                                                — [{{ $pt->isOpen() ? 'AKTIF / OPEN' : 'TUTUP' }}]
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="form-text text-muted">
                                        Pilihlah periode penthong yang aktif untuk mencairkan kewajiban bagi hasil struktur.
                                    </small>
                                </div>

                                <!-- Ranting Penyetor (Data Scoping) -->
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold">
                                        Ranting Penyetor <span class="text-danger">*</span>
                                    </label>
                                    @if(auth()->user() && auth()->user()->isRanting())
                                        <input type="hidden" name="ranting_id" value="{{ $rantingId }}">
                                        <input type="text" class="form-control bg-white font-weight-bold text-success" 
                                               value="Ranting {{ $ranting?->name }} ({{ $ranting?->code }}) — MWC {{ $ranting?->mwc?->name }}" readonly>
                                        <small class="text-muted"><i class="fas fa-lock text-success mr-1"></i> Data Scoping: Terkunci otomatis pada Ranting Anda</small>
                                    @else
                                        <select name="ranting_id" id="selectRanting" class="form-control select2bs4" required onchange="window.location.href='{{ route('pentasyarufan.kontribusi.create') }}?penthong_id={{ $selectedPenthongId }}&ranting_id=' + this.value">
                                            <option value="">-- Pilih Ranting --</option>
                                            @foreach($allRantings as $r)
                                                <option value="{{ $r->id }}" {{ $rantingId == $r->id ? 'selected' : '' }}>
                                                    {{ $r->name }} (MWC {{ $r->mwc?->name ?? '-' }})
                                                </option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted">Pilih ranting yang akan menyetorkan kontribusi bagi hasil</small>
                                    @endif
                                </div>
                            </div>

                            <!-- Notifikasi Estimasi Koin Terhimpun -->
                            @if($rantingId && $selectedPenthong)
                            <div class="bg-white border rounded p-3 mt-2">
                                <div class="d-flex justify-content-between align-items-center flex-wrap">
                                    <div>
                                        <span class="text-muted small d-block">Total Perolehan Koin Terkonfirmasi Ranting pada Periode Ini:</span>
                                        <h4 class="font-weight-bold text-success mb-0">
                                            Rp {{ number_format($estimasiKoinBruto, 0, ',', '.') }}
                                        </h4>
                                    </div>
                                    @if($estimasiKoinBruto > 0)
                                    <div>
                                        <button type="button" class="btn btn-outline-success btn-sm font-weight-bold" onclick="setNominalBruto({{ $estimasiKoinBruto }})">
                                            <i class="fas fa-magic mr-1"></i> Gunakan Nilai Perolehan Ini
                                        </button>
                                    </div>
                                    @endif
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>

                    <!-- =============================================================== -->
                    <!-- LANGKAH 2: KALKULATOR PEMBAGIAN HASIL STRUKTUR OTOMATIS         -->
                    <!-- =============================================================== -->
                    <div class="card bg-light border mb-4">
                        <div class="card-header bg-white py-2">
                            <span class="badge badge-dark mr-2" style="font-size: 0.9rem;">Langkah 2</span>
                            <strong class="text-dark"><i class="fas fa-calculator text-success mr-1"></i> Perhitungan Alokasi Bagi Hasil (Berdasarkan Aturan KOIN NU)</strong>
                        </div>
                        <div class="card-body">
                            <!-- Input Nominal Bruto -->
                            <div class="form-group mb-4">
                                <label class="font-weight-bold" style="font-size: 1.05rem;">
                                    Nominal Koin Bruto yang Dihimpun (Rp) <span class="text-danger">*</span>
                                </label>
                                <div class="input-group input-group-lg">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text font-weight-bold bg-success text-white">Rp</span>
                                    </div>
                                    <input type="number" name="nominal_bruto" id="nominalBruto" 
                                           class="form-control font-weight-bold text-dark" 
                                           style="font-size: 1.4rem;" 
                                           value="{{ old('nominal_bruto', $estimasiKoinBruto > 0 ? (int)$estimasiKoinBruto : '') }}" 
                                           placeholder="Contoh: 10000000" 
                                           min="1000" step="100" required oninput="calculateSharing()">
                                </div>
                                <small class="form-text text-muted">
                                    Masukkan total perolehan koin bruto yang dihimpun pada periode penthong ini sebelum dipotong operasional.
                                </small>
                            </div>

                            <!-- Visual Step-by-Step Kalkulasi Akuntansi -->
                            <div class="row">
                                <!-- Potongan Hak Amil / Operasional Ranting -->
                                <div class="col-md-6 mb-3">
                                    <div class="card border-0 shadow-xs h-100" style="background-color: #f1f5f9;">
                                        <div class="card-body">
                                            <span class="badge badge-secondary mb-1">Tahap 1</span>
                                            <h6 class="font-weight-bold text-secondary mb-1">
                                                Hak Amil & Operasional Ranting ({{ $persentase['operasional'] }}%)
                                            </h6>
                                            <p class="text-muted small mb-2">Dipotong di awal dari total bruto untuk operasional relawan & amil ranting.</p>
                                            <h4 class="font-weight-bold text-dark mb-0" id="dispOperasional">Rp 0</h4>
                                        </div>
                                    </div>
                                </div>

                                <!-- Dana Bersih (Netto) untuk Alokasi -->
                                <div class="col-md-6 mb-3">
                                    <div class="card border-0 shadow-xs h-100" style="background-color: #e0f2fe;">
                                        <div class="card-body">
                                            <span class="badge badge-info mb-1">Tahap 2</span>
                                            <h6 class="font-weight-bold text-primary mb-1">
                                                Dana Bersih (Netto) Pembagian Hasil ({{ 100 - $persentase['operasional'] }}%)
                                            </h6>
                                            <p class="text-muted small mb-2">Dana bersih dasar perhitungan porsi MWC, PC, PW, PB, dan Ranting.</p>
                                            <h4 class="font-weight-bold text-primary mb-0" id="dispNetto">Rp 0</h4>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Tabel Rincian Breakdown Struktur -->
                            <div class="table-responsive border rounded bg-white mt-2 mb-3">
                                <table class="table table-sm table-striped m-0 align-middle">
                                    <thead class="thead-light">
                                        <tr>
                                            <th>Tingkat Struktur Penerima</th>
                                            <th class="text-center">Porsi (%)</th>
                                            <th class="text-right">Nominal Bagian (Rp)</th>
                                            <th class="text-center">Status Aliran Kas</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td class="font-weight-bold">
                                                <i class="fas fa-landmark text-primary mr-2"></i> Majelis Wakil Cabang (MWC)
                                            </td>
                                            <td class="text-center font-weight-bold">{{ $persentase['mwc'] }}%</td>
                                            <td class="text-right font-weight-bold text-primary" id="dispMwc">Rp 0</td>
                                            <td class="text-center">
                                                <span class="badge badge-warning text-dark"><i class="fas fa-arrow-up mr-1"></i> Disetor ke MWC</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">
                                                <i class="fas fa-building text-success mr-2"></i> Pengurus Cabang (PC LAZISNU Lamongan)
                                            </td>
                                            <td class="text-center font-weight-bold">{{ $persentase['pc'] }}%</td>
                                            <td class="text-right font-weight-bold text-success" id="dispPc">Rp 0</td>
                                            <td class="text-center">
                                                <span class="badge badge-warning text-dark"><i class="fas fa-arrow-up mr-1"></i> Disetor ke Cabang</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">
                                                <i class="fas fa-map-marked-alt text-info mr-2"></i> Pengurus Wilayah (PW LAZISNU Jatim)
                                            </td>
                                            <td class="text-center font-weight-bold">{{ $persentase['pw'] }}%</td>
                                            <td class="text-right font-weight-bold text-info" id="dispPw">Rp 0</td>
                                            <td class="text-center">
                                                <span class="badge badge-warning text-dark"><i class="fas fa-arrow-up mr-1"></i> Kontribusi Wilayah</span>
                                            </td>
                                        </tr>
                                        <tr>
                                            <td class="font-weight-bold">
                                                <i class="fas fa-globe-asia text-secondary mr-2"></i> Pengurus Besar (PB LAZISNU Pusat)
                                            </td>
                                            <td class="text-center font-weight-bold">{{ $persentase['pb'] }}%</td>
                                            <td class="text-right font-weight-bold text-secondary" id="dispPb">Rp 0</td>
                                            <td class="text-center">
                                                <span class="badge badge-warning text-dark"><i class="fas fa-arrow-up mr-1"></i> Kontribusi Nasional</span>
                                            </td>
                                        </tr>
                                        <tr class="bg-light">
                                            <td class="font-weight-bold text-success">
                                                <i class="fas fa-home text-success mr-2"></i> Kas Internal Ranting (5 Pilar Pentasyarufan)
                                            </td>
                                            <td class="text-center font-weight-bold text-success">{{ $persentase['ranting'] }}%</td>
                                            <td class="text-right font-weight-bold text-success" id="dispRantingShare">Rp 0</td>
                                            <td class="text-center">
                                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> Tetap di Kas Ranting</span>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Highlight Total Pengeluaran BKU Ranting -->
                            <div class="card bg-gradient-success text-white shadow-sm mb-0">
                                <div class="card-body p-3">
                                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                                        <div>
                                            <span class="text-white-50 text-uppercase small font-weight-bold">Total Kewajiban Setoran ke Struktur Atas (MWC + PC + PW + PB):</span>
                                            <h3 class="font-weight-bold mb-0" id="dispTotalDisetor">Rp 0</h3>
                                        </div>
                                        <div class="text-right">
                                            <span class="badge badge-light text-success font-weight-bold px-3 py-2" style="font-size: 0.9rem;">
                                                <i class="fas fa-file-invoice-dollar mr-1"></i> Otomatis Memotong BKU Ranting
                                            </span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- =============================================================== -->
                    <!-- LANGKAH 3: INFORMASI TRANSAKSI & BUKTI SETORAN                   -->
                    <!-- =============================================================== -->
                    <div class="card bg-light border mb-4">
                        <div class="card-header bg-white py-2">
                            <span class="badge badge-dark mr-2" style="font-size: 0.9rem;">Langkah 3</span>
                            <strong class="text-dark"><i class="fas fa-receipt text-warning mr-1"></i> Informasi Penyaluran & Bukti Administrasi</strong>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 form-group">
                                    <label class="font-weight-bold">Tanggal Penyetoran <span class="text-danger">*</span></label>
                                    <input type="date" name="tanggal_setor" class="form-control" value="{{ old('tanggal_setor', date('Y-m-d')) }}" required>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label class="font-weight-bold">Metode Penyaluran <span class="text-danger">*</span></label>
                                    <select name="metode_penyaluran" class="form-control" required>
                                        <option value="transfer" {{ old('metode_penyaluran') == 'transfer' ? 'selected' : '' }}>Transfer Bank Rekening Resmi</option>
                                        <option value="tunai" {{ old('metode_penyaluran') == 'tunai' ? 'selected' : '' }}>Tunai / Serah Terima Langsung</option>
                                        <option value="qris" {{ old('metode_penyaluran') == 'qris' ? 'selected' : '' }}>QRIS LAZISNU</option>
                                    </select>
                                </div>

                                <div class="col-md-4 form-group">
                                    <label class="font-weight-bold">Nama Bank / Lembaga Tujuan</label>
                                    <input type="text" name="nama_bank" class="form-control" placeholder="Contoh: BSI / Bank BRI LAZISNU" value="{{ old('nama_bank', 'BSI PC LAZISNU Lamongan') }}">
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold">Nomor Rekening Tujuan</label>
                                    <input type="text" name="nomor_rekening" class="form-control" placeholder="Nomor rekening tujuan transfer" value="{{ old('nomor_rekening') }}">
                                </div>

                                <div class="col-md-6 form-group">
                                    <label class="font-weight-bold">Upload Bukti Transfer / Resi / Kuitansi</label>
                                    <div class="custom-file">
                                        <input type="file" name="bukti_transfer" class="custom-file-input" id="buktiTransfer" accept=".jpg,.jpeg,.png,.pdf">
                                        <label class="custom-file-label" for="buktiTransfer">Pilih file slip transfer / kwitansi...</label>
                                    </div>
                                    <small class="text-muted">Format: JPG, PNG, atau PDF (Maksimal 5 MB)</small>
                                </div>
                            </div>

                            <div class="form-group mb-0">
                                <label class="font-weight-bold">Catatan / Uraian Tambahan</label>
                                <textarea name="catatan" class="form-control" rows="2" placeholder="Catatan opsional untuk verifikasi bendahara MWC/PC">{{ old('catatan') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi -->
                    <div class="d-flex justify-content-between align-items-center pt-2">
                        <a href="{{ route('bku.index') }}" class="btn btn-secondary font-weight-bold px-4">
                            <i class="fas fa-arrow-left mr-1"></i> Kembali ke BKU
                        </a>
                        <button type="submit" class="btn btn-success btn-lg font-weight-bold px-5 shadow">
                            <i class="fas fa-check-circle mr-2"></i> Simpan & Bukukan ke BKU Ranting
                        </button>
                    </div>

                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@push('scripts')
<script>
const persentase = {
    operasional: {{ $persentase['operasional'] }},
    ranting: {{ $persentase['ranting'] }},
    mwc: {{ $persentase['mwc'] }},
    pc: {{ $persentase['pc'] }},
    pw: {{ $persentase['pw'] }},
    pb: {{ $persentase['pb'] }}
};

function formatRupiah(num) {
    return 'Rp ' + Number(num || 0).toLocaleString('id-ID');
}

function calculateSharing() {
    const bruto = parseFloat(document.getElementById('nominalBruto').value) || 0;
    
    // 1. Potongan Operasional (20%)
    const operasional = Math.round(bruto * (persentase.operasional / 100));
    
    // 2. Dana Bersih Netto (80%)
    const netto = Math.max(0, bruto - operasional);
    
    // 3. Porsi Struktur Atas
    const mwc = Math.round(netto * (persentase.mwc / 100));
    const pc  = Math.round(netto * (persentase.pc / 100));
    const pw  = Math.round(netto * (persentase.pw / 100));
    const pb  = Math.round(netto * (persentase.pb / 100));
    
    // 4. Porsi Internal Ranting
    const rantingShare = Math.round(netto * (persentase.ranting / 100));
    
    // 5. Total Disetor ke Struktur Atas (MWC + PC + PW + PB)
    const totalDisetor = mwc + pc + pw + pb;

    // Render ke Tampilan
    document.getElementById('dispOperasional').innerText = formatRupiah(operasional);
    document.getElementById('dispNetto').innerText = formatRupiah(netto);
    document.getElementById('dispMwc').innerText = formatRupiah(mwc);
    document.getElementById('dispPc').innerText = formatRupiah(pc);
    document.getElementById('dispPw').innerText = formatRupiah(pw);
    document.getElementById('dispPb').innerText = formatRupiah(pb);
    document.getElementById('dispRantingShare').innerText = formatRupiah(rantingShare);
    document.getElementById('dispTotalDisetor').innerText = formatRupiah(totalDisetor);
}

function setNominalBruto(val) {
    document.getElementById('nominalBruto').value = val;
    calculateSharing();
}

document.addEventListener('DOMContentLoaded', function () {
    calculateSharing();

    // Custom file input label update
    const fileInput = document.getElementById('buktiTransfer');
    if (fileInput) {
        fileInput.addEventListener('change', function(e) {
            const fileName = e.target.files[0] ? e.target.files[0].name : 'Pilih file slip transfer / kwitansi...';
            const label = e.target.nextElementSibling;
            if (label) label.innerText = fileName;
        });
    }
});
</script>
@endpush
