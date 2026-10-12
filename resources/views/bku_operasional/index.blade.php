@extends('layouts.adminlte')

@section('title', 'BKU Operasional (Hak Amil) - LAZISNU Lamongan')
@section('page_title', 'Buku Kas Umum (BKU) Operasional — Hak Amil')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="#">Pentasyarufan</a></li>
    <li class="breadcrumb-item active">BKU Operasional (Hak Amil)</li>
@endsection

@section('content')
@if(session('success'))
    <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-check-circle mr-2"></i><strong>Sukses:</strong> {{ session('success') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <i class="fas fa-exclamation-triangle mr-2"></i><strong>Perhatian:</strong> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

@if($errors->any())
    <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
        <h6 class="font-weight-bold mb-1"><i class="fas fa-exclamation-circle mr-2"></i>Terdapat Kesalahan Input:</h6>
        <ul class="mb-0 pl-3 small">
            @foreach($errors->all() as $err)
                <li>{{ $err }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<!-- Banner Indikator Tingkatan Kas Operasional -->
<div class="alert alert-light border shadow-xs d-flex align-items-center justify-content-between flex-wrap py-2 px-3 mb-3">
    <div>
        <i class="fas fa-landmark text-info mr-2"></i>
        <span class="text-dark font-weight-bold">Buku Kas Operasional (Hak Amil):</span>
        <span class="badge badge-info ml-1 text-uppercase">{{ $tingkat }}</span>
        <span class="text-muted small ml-2">
            @if($tingkat === 'pc')
                (Tingkat PC Cabang &bull; 20% dari Kontribusi Cabang)
            @elseif($tingkat === 'mwc')
                (Tingkat MWC {{ $user->mwc?->name ?? 'Kecamatan' }} &bull; 20% dari Dana Kontribusi Masuk MWC)
            @elseif($tingkat === 'ranting')
                (Tingkat Ranting {{ $user->ranting?->name ?? 'Desa' }} &bull; 20% dari Rekap Koin Bruto)
            @else
                (Semua Tingkatan Struktur)
            @endif
        </span>
    </div>
    <div class="small text-muted font-italic">
        <i class="fas fa-shield-alt text-success mr-1"></i> Terisolasi Mandiri per Tingkatan Struktur
    </div>
</div>

<!-- Virtual Wallet Metric Cards -->
<div class="row">
    <!-- 1. Total Pemasukan Operasional -->
    <div class="col-lg-4 col-12">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <span class="text-muted font-weight-bold text-uppercase small">Total Hak Amil Masuk (Debet)</span>
                <h3 class="text-success font-weight-bold">Rp {{ number_format($totalPemasukan, 0, ',', '.') }}</h3>
                <p class="text-muted mb-0 small">Alokasi potongan KOIN & infaq amil</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-holding-usd text-success" style="opacity: 0.25;"></i>
            </div>
            <span class="small-box-footer bg-light text-muted font-weight-bold">
                Pemasukan Kas Amil Sah
            </span>
        </div>
    </div>

    <!-- 2. Total Pengeluaran Operasional -->
    <div class="col-lg-4 col-12">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <span class="text-muted font-weight-bold text-uppercase small">Penggunaan Operasional (Kredit)</span>
                <h3 class="text-danger font-weight-bold">Rp {{ number_format($totalPengeluaran, 0, ',', '.') }}</h3>
                <p class="text-muted mb-0 small">Bisyaroh PLK, ATK, rapat, dan transport</p>
            </div>
            <div class="icon">
                <i class="fas fa-receipt text-danger" style="opacity: 0.25;"></i>
            </div>
            <span class="small-box-footer bg-light text-muted font-weight-bold">
                Total Belanja Amil Terpakai
            </span>
        </div>
    </div>

    <!-- 3. Sisa Kas Operasional (Running Balance) -->
    <div class="col-lg-4 col-12">
        <div class="small-box bg-gradient-info shadow-sm text-white">
            <div class="inner">
                <span class="text-white-50 font-weight-bold text-uppercase small">Sisa Saldo Kas Operasional</span>
                <h3 class="font-weight-bold">Rp {{ number_format($sisaSaldo, 0, ',', '.') }}</h3>
                <p class="text-white-50 mb-0 small">Terpisah 100% dari Dana 5 Pilar Umat</p>
            </div>
            <div class="icon">
                <i class="fas fa-wallet text-white" style="opacity: 0.3;"></i>
            </div>
            <span class="small-box-footer text-white font-weight-bold" style="background: rgba(0,0,0,0.1);">
                Tersedia untuk Pengeluaran Amil
            </span>
        </div>
    </div>
</div>

<!-- Filter & Action Card -->
<div class="card card-outline card-info shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center flex-wrap" style="gap: 10px;">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-filter text-info mr-2"></i>Filter & Manajemen Kas Hak Amil
        </h3>
        <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
            <!-- Tombol Cetak PDF -->
            <a href="{{ route('bku-operasional.export.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger btn-sm font-weight-bold shadow-xs">
                <i class="fas fa-file-pdf mr-1"></i> Cetak SPJ (PDF)
            </a>

            <!-- Tombol Export Excel -->
            <a href="{{ route('bku-operasional.export.excel', request()->query()) }}" class="btn btn-outline-success btn-sm font-weight-bold shadow-xs">
                <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>

            <!-- Tombol Tarik Alokasi Pentong -->
            <button type="button" class="btn btn-secondary btn-sm font-weight-bold shadow-xs" data-toggle="modal" data-target="#modalSyncPentong">
                <i class="fas fa-sync-alt mr-1"></i> Alokasi Pentong
            </button>

            <!-- Tombol Pemasukan Amil Lainnya -->
            <button type="button" class="btn btn-outline-info btn-sm font-weight-bold shadow-xs" data-toggle="modal" data-target="#modalInputPemasukanOps">
                <i class="fas fa-plus-circle mr-1"></i> Pemasukan Lainnya
            </button>

            <!-- Tombol Catat Pengeluaran Operasional -->
            <button type="button" class="btn btn-danger btn-sm font-weight-bold shadow-xs" data-toggle="modal" data-target="#modalInputPengeluaranOps">
                <i class="fas fa-minus-circle mr-1"></i> Catat Pengeluaran
            </button>
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('bku-operasional.index') }}">
            <div class="row">
                <!-- Filter Tanggal Awal -->
                <div class="col-md-2 mb-2">
                    <label class="font-weight-bold small text-dark">Mulai Tanggal</label>
                    <input type="date" name="start_date" class="form-control form-control-sm" value="{{ request('start_date') }}">
                </div>

                <!-- Filter Tanggal Akhir -->
                <div class="col-md-2 mb-2">
                    <label class="font-weight-bold small text-dark">Sampai Tanggal</label>
                    <input type="date" name="end_date" class="form-control form-control-sm" value="{{ request('end_date') }}">
                </div>

                <!-- Filter Periode Pentong -->
                <div class="col-md-3 mb-2">
                    <label class="font-weight-bold small text-dark">Periode Pentong</label>
                    <select name="penthong_id" class="form-control form-control-sm">
                        <option value="">-- Semua Periode Pentong --</option>
                        @foreach($penthongs as $pt)
                            <option value="{{ $pt->id }}" {{ request('penthong_id') == $pt->id ? 'selected' : '' }}>
                                {{ $pt->nama_periode }} ({{ $pt->bulan_nama }} {{ $pt->tahun }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Kategori COA -->
                <div class="col-md-3 mb-2">
                    <label class="font-weight-bold small text-dark">Kategori Beban</label>
                    <select name="kategori_id" class="form-control form-control-sm">
                        <option value="">-- Semua Kategori Beban --</option>
                        @foreach($kategoris as $k)
                            <option value="{{ $k->id }}" {{ request('kategori_id') == $k->id ? 'selected' : '' }}>
                                {{ $k->nama_kategori }} ({{ $k->kode }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Filter Jenis -->
                <div class="col-md-2 mb-2">
                    <label class="font-weight-bold small text-dark">Mutasi Kas</label>
                    <select name="jenis" class="form-control form-control-sm">
                        <option value="">-- Debet & Kredit --</option>
                        <option value="debet" {{ request('jenis') == 'debet' ? 'selected' : '' }}>Debet (Masuk)</option>
                        <option value="kredit" {{ request('jenis') == 'kredit' ? 'selected' : '' }}>Kredit (Keluar)</option>
                    </select>
                </div>

                @if($user->isPc())
                    <div class="col-md-3 mb-2">
                        <label class="font-weight-bold small text-dark">Tingkat Kas BKU</label>
                        <select name="tingkat" class="form-control form-control-sm">
                            <option value="pc" {{ request('tingkat', 'pc') === 'pc' ? 'selected' : '' }}>Kas Operasional PC (Default)</option>
                            <option value="mwc" {{ request('tingkat') === 'mwc' ? 'selected' : '' }}>Kas Operasional MWC</option>
                            <option value="ranting" {{ request('tingkat') === 'ranting' ? 'selected' : '' }}>Kas Operasional Ranting</option>
                            <option value="semua" {{ request('tingkat') === 'semua' ? 'selected' : '' }}>Semua Tingkatan</option>
                        </select>
                    </div>
                    <div class="col-md-3 mb-2">
                        <label class="font-weight-bold small text-dark">MWC (Kecamatan)</label>
                        <select name="mwc_id" class="form-control form-control-sm">
                            <option value="">-- Semua MWC --</option>
                            @foreach($mwcs as $m)
                                <option value="{{ $m->id }}" {{ request('mwc_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @elseif($user->isMwc())
                    <div class="col-md-3 mb-2">
                        <label class="font-weight-bold small text-dark">Tingkat Kas BKU</label>
                        <select name="tingkat" class="form-control form-control-sm">
                            <option value="mwc" {{ request('tingkat', 'mwc') === 'mwc' ? 'selected' : '' }}>Kas Operasional MWC (Default)</option>
                            <option value="ranting" {{ request('tingkat') === 'ranting' ? 'selected' : '' }}>Kas Operasional Ranting Binaan</option>
                            <option value="semua" {{ request('tingkat') === 'semua' ? 'selected' : '' }}>Semua (MWC & Ranting)</option>
                        </select>
                    </div>
                @endif

                @if($user->isPc() || $user->isMwc())
                    <div class="col-md-3 mb-2">
                        <label class="font-weight-bold small text-dark">Ranting (Desa)</label>
                        <select name="ranting_id" class="form-control form-control-sm">
                            <option value="">-- Semua Ranting --</option>
                            @foreach($rantings as $r)
                                <option value="{{ $r->id }}" {{ request('ranting_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-12 mt-2 d-flex justify-content-end" style="gap: 6px;">
                    <button type="submit" class="btn btn-info btn-sm font-weight-bold">
                        <i class="fas fa-search mr-1"></i> Terapkan Filter
                    </button>
                    @if(request()->anyFilled(['start_date', 'end_date', 'penthong_id', 'kategori_id', 'jenis', 'mwc_id', 'ranting_id', 'tingkat']))
                        <a href="{{ route('bku-operasional.index') }}" class="btn btn-secondary btn-sm">
                            <i class="fas fa-undo mr-1"></i> Reset
                        </a>
                    @endif
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Mutasi Kas BKU Operasional -->
<div class="card card-default shadow-sm mb-4">
    <div class="card-header bg-white border-bottom">
        <h3 class="card-title font-weight-bold text-dark">
            <i class="fas fa-list-alt text-info mr-2"></i>Rincian Mutasi Buku Kas Umum Operasional
        </h3>
    </div>
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover table-striped mb-0 text-nowrap">
            <thead class="bg-light">
                <tr class="text-dark small font-weight-bold text-uppercase">
                    <th style="width: 40px;" class="text-center">No</th>
                    <th>Tanggal</th>
                    <th>No. Transaksi / COA</th>
                    <th>Uraian Transaksi</th>
                    <th>Penerima / Vendor</th>
                    <th class="text-right" style="width: 140px;">Debet (Masuk)</th>
                    <th class="text-right" style="width: 140px;">Kredit (Keluar)</th>
                    <th class="text-center" style="width: 80px;">Bukti</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mutasis as $idx => $item)
                    <tr>
                        <td class="text-center align-middle font-weight-bold text-muted">
                            {{ $mutasis->firstItem() + $idx }}
                        </td>
                        <td class="align-middle">
                            <span class="font-weight-bold text-dark d-block">
                                {{ $item->tanggal_transaksi ? $item->tanggal_transaksi->format('d/m/Y') : '-' }}
                            </span>
                            @if($item->penthong)
                                <small class="text-muted"><i class="fas fa-drum mr-1"></i>{{ $item->penthong->nama_periode }}</small>
                            @endif
                        </td>
                        <td class="align-middle">
                            <div class="font-weight-bold text-dark font-monospace small">
                                {{ $item->nomor_transaksi }}
                            </div>
                            @if($item->kategori)
                                <span class="badge badge-light border text-danger small font-weight-bold">
                                    <i class="{{ $item->kategori->icon }} mr-1"></i>{{ $item->kategori->nama_kategori }}
                                </span>
                            @else
                                <span class="badge badge-light border text-success small font-weight-bold">
                                    <i class="fas fa-coins mr-1"></i>{{ ucfirst(str_replace('_', ' ', $item->sumber_dana)) }}
                                </span>
                            @endif
                        </td>
                        <td class="align-middle" style="max-width: 320px; white-space: normal;">
                            <span class="text-dark">{{ $item->uraian }}</span>
                            @if($item->ranting)
                                <small class="d-block text-muted">
                                    <i class="fas fa-map-marker-alt text-danger mr-1"></i>{{ $item->ranting->name }}
                                </small>
                            @endif
                        </td>
                        <td class="align-middle">
                            <span class="text-muted small">{{ $item->penerima_atau_vendor ?: '-' }}</span>
                        </td>
                        <td class="align-middle text-right font-weight-bold text-success">
                            @if($item->jenis === 'debet')
                                + Rp {{ number_format($item->nominal, 0, ',', '.') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="align-middle text-right font-weight-bold text-danger">
                            @if($item->jenis === 'kredit')
                                - Rp {{ number_format($item->nominal, 0, ',', '.') }}
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>
                        <td class="align-middle text-center">
                            @if($item->bukti_transaksi)
                                <a href="{{ asset('storage/' . $item->bukti_transaksi) }}" target="_blank" class="btn btn-xs btn-outline-info" title="Lihat Kwitansi / Struk">
                                    <i class="fas fa-paperclip"></i>
                                </a>
                            @else
                                <span class="text-muted small">-</span>
                            @endif
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-2">
                                <i class="fas fa-wallet fa-3x" style="opacity: 0.3;"></i>
                            </div>
                            <h6 class="font-weight-bold text-dark">Belum Ada Transaksi Kas Operasional</h6>
                            <p class="text-muted small mb-0">
                                Belum ada mutasi debet/kredit kas hak amil pada rentang filter ini.
                            </p>
                        </td>
                    </tr>
                @endforelse
            </tbody>
            <tfoot class="bg-light font-weight-bold">
                <tr>
                    <td colspan="5" class="text-right text-uppercase small">Total Mutasi Pada Halaman Ini:</td>
                    <td class="text-right text-success">+ Rp {{ number_format($mutasis->where('jenis', 'debet')->sum('nominal'), 0, ',', '.') }}</td>
                    <td class="text-right text-danger">- Rp {{ number_format($mutasis->where('jenis', 'kredit')->sum('nominal'), 0, ',', '.') }}</td>
                    <td></td>
                </tr>
            </tfoot>
        </table>
    </div>
    @if($mutasis->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="small text-muted">
                Menampilkan {{ $mutasis->firstItem() }} - {{ $mutasis->lastItem() }} dari {{ $mutasis->total() }} transaksi
            </span>
            <div>
                {{ $mutasis->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>

<!-- ========================================================================= -->
<!-- 1. MODAL INPUT PENGELUARAN OPERASIONAL (BEBAN AMIL)                       -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalInputPengeluaranOps" tabindex="-1" role="dialog" aria-labelledby="modalPengeluaranOpsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-danger text-white">
                <div class="d-flex align-items-center">
                    <i class="fas fa-receipt fa-lg mr-2"></i>
                    <div>
                        <h5 class="modal-title font-weight-bold" id="modalPengeluaranOpsLabel">Catat Pengeluaran Kas Operasional (Hak Amil)</h5>
                        <small class="text-white-50">Pengeluaran ini murni memotong saldo amil dan terpisah dari dana 5 Pilar Umat</small>
                    </div>
                </div>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('bku-operasional.expense') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <!-- Live Balance Indicator Guard -->
                    <div class="alert alert-warning border border-warning shadow-sm d-flex justify-content-between align-items-center mb-4">
                        <div>
                            <span class="text-uppercase font-weight-bold text-dark small d-block">Sisa Saldo Kas Operasional Tersedia</span>
                            <strong class="text-danger font-weight-bold h5 mb-0">
                                Rp {{ number_format($sisaSaldo, 0, ',', '.') }}
                            </strong>
                        </div>
                        <span class="badge badge-success px-2 py-1"><i class="fas fa-check-circle mr-1"></i>Anti-Defisit</span>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-dark">Kategori Beban Operasional <span class="text-danger">*</span></label>
                            <select name="kategori_operasional_id" class="form-control form-control-sm" required>
                                <option value="">-- Pilih Kategori Beban --</option>
                                @foreach($kategoris as $kat)
                                    <option value="{{ $kat->id }}">{{ $kat->nama_kategori }} ({{ $kat->kode }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-dark">Terkait Periode Pentong (Opsional)</label>
                            <select name="periode_pentong_id" class="form-control form-control-sm">
                                <option value="">-- Tidak Terikat Periode Tertentu --</option>
                                @foreach($penthongs as $pt)
                                    <option value="{{ $pt->id }}">{{ $pt->nama_periode }} ({{ $pt->bulan_nama }} {{ $pt->tahun }})</option>
                                @endforeach
                            </select>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-dark">Tanggal Pengeluaran <span class="text-danger">*</span></label>
                            <input type="date" name="tanggal_transaksi" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-dark">Nominal Pengeluaran (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <div class="input-group-prepend">
                                    <span class="input-group-text font-weight-bold">Rp</span>
                                </div>
                                <input type="number" name="nominal" id="nominalExpenseInput" max="{{ $sisaSaldo }}" min="1000" class="form-control font-weight-bold" placeholder="Contoh: 150000" required>
                            </div>
                            <small class="text-danger" id="expenseWarning" style="display: none;">
                                <i class="fas fa-exclamation-triangle mr-1"></i>Nominal melebihi sisa kas operasional!
                            </small>
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-dark">Penerima / Nama Toko / Vendor</label>
                            <input type="text" name="penerima_atau_vendor" class="form-control form-control-sm" placeholder="Misal: Percetakan Jaya / Bisyaroh PLK RT 01">
                        </div>

                        <div class="col-md-6 mb-3">
                            <label class="font-weight-bold small text-dark">Bukti Kwitansi / Nota Struk (Opsional)</label>
                            <div class="custom-file">
                                <input type="file" name="bukti_transaksi" class="custom-file-input" id="buktiKwitansiOps" accept="image/*,.pdf">
                                <label class="custom-file-label" for="buktiKwitansiOps">Pilih berkas nota...</label>
                            </div>
                        </div>

                        @if($user->isPc())
                            <div class="col-md-12 mb-3">
                                <label class="font-weight-bold small text-dark">Beban Wilayah Ranting (Opsional)</label>
                                <select name="ranting_id" class="form-control form-control-sm">
                                    <option value="">-- Kas Cabang PC (Global) --</option>
                                    @foreach($rantings as $rtg)
                                        <option value="{{ $rtg->id }}">{{ $rtg->name }} ({{ $rtg->code }})</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif

                        <div class="col-12 mb-3">
                            <label class="font-weight-bold small text-dark">Uraian Keperluan Belanja Operasional <span class="text-danger">*</span></label>
                            <textarea name="uraian" rows="3" class="form-control" placeholder="Jelaskan rincian belanja operasional, misal: Pembelian ATK, konsumsi rapat pengurus, atau bisyaroh koordinator..." required></textarea>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" id="btnSubmitExpense" class="btn btn-danger btn-sm font-weight-bold">
                        <i class="fas fa-save mr-1"></i> Simpan Transaksi Belanja
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 2. MODAL INPUT PEMASUKAN OPERASIONAL LAINNYA                              -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalInputPemasukanOps" tabindex="-1" role="dialog" aria-labelledby="modalPemasukanOpsLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title font-weight-bold" id="modalPemasukanOpsLabel">
                    <i class="fas fa-hand-holding-usd mr-2"></i>Pemasukan Kas Operasional Lainnya
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('bku-operasional.income') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-dark">Sumber Dana Amil <span class="text-danger">*</span></label>
                        <select name="sumber_dana" class="form-control form-control-sm" required>
                            <option value="infaq_operasional">Infaq Khusus Operasional / Amil dari Donatur</option>
                            <option value="subsidi_struktur">Subsidi Kas Operasional dari MWC / PC</option>
                            <option value="saldo_awal">Pencatatan Saldo Awal Kas Amil</option>
                            <option value="lainnya">Pemasukan Amil Lainnya</option>
                        </select>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-dark">Tanggal Penerimaan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_transaksi" class="form-control form-control-sm" value="{{ date('Y-m-d') }}" required>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-dark">Nominal Dana (Rp) <span class="text-danger">*</span></label>
                        <div class="input-group input-group-sm">
                            <div class="input-group-prepend">
                                <span class="input-group-text font-weight-bold">Rp</span>
                            </div>
                            <input type="number" name="nominal" min="1000" class="form-control font-weight-bold" placeholder="Contoh: 500000" required>
                        </div>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-dark">Uraian Pemasukan <span class="text-danger">*</span></label>
                        <textarea name="uraian" rows="2" class="form-control form-control-sm" placeholder="Misal: Infaq hamba Allah khusus operasional amil..." required></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-info btn-sm font-weight-bold">
                        <i class="fas fa-plus-circle mr-1"></i> Tambahkan ke Saldo Operasional
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ========================================================================= -->
<!-- 3. MODAL SINKRONISASI ALOKASI POTONGAN PENTONG                            -->
<!-- ========================================================================= -->
<div class="modal fade" id="modalSyncPentong" tabindex="-1" role="dialog" aria-labelledby="modalSyncPentongLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title font-weight-bold" id="modalSyncPentongLabel">
                    <i class="fas fa-sync-alt mr-2"></i>Sinkronisasi Alokasi Hak Amil Periode Pentong
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('bku-operasional.sync-penthong') }}" method="POST">
                @csrf
                <div class="modal-body p-4">
                    <div class="alert alert-light border small text-muted mb-3">
                        <i class="fas fa-info-circle text-info mr-1"></i>
                        <strong>Sistem Alokasi Operasional Berjenjang:</strong>
                        <ul class="mb-0 pl-3 mt-1">
                            <li><strong>Ranting:</strong> 20% dari Perolehan Koin Bruto Ranting.</li>
                            <li><strong>MWC:</strong> 20% dari Dana Kontribusi Masuk ke MWC (13%).</li>
                            <li><strong>PC:</strong> 20% dari Dana Kontribusi Masuk ke PC (5%).</li>
                        </ul>
                    </div>

                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-dark">Pilih Periode Pentong <span class="text-danger">*</span></label>
                        <select name="penthong_id" class="form-control form-control-sm" required>
                            @foreach($penthongs as $pt)
                                <option value="{{ $pt->id }}">
                                    {{ $pt->nama_periode }} ({{ $pt->bulan_nama }} {{ $pt->tahun }})
                                </option>
                            @endforeach
                        </select>
                    </div>

                    @if($user->isPc() || $user->isMwc())
                        <div class="form-group mb-3">
                            <label class="font-weight-bold small text-dark">Pilih Ranting Spesifik (Opsional)</label>
                            <select name="ranting_id" class="form-control form-control-sm">
                                <option value="">-- Sinkronkan Semua Ranting Sekaligus --</option>
                                @foreach($rantings as $rtg)
                                    <option value="{{ $rtg->id }}">{{ $rtg->name }} ({{ $rtg->code }})</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-secondary btn-sm font-weight-bold">
                        <i class="fas fa-calculator mr-1"></i> Jalankan Alokasi Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
document.getElementById('nominalExpenseInput').addEventListener('input', function() {
    const maxSaldo = {{ (float) $sisaSaldo }};
    const inputVal = parseFloat(this.value) || 0;
    const warning = document.getElementById('expenseWarning');
    const submitBtn = document.getElementById('btnSubmitExpense');

    if (inputVal > maxSaldo) {
        warning.style.display = 'block';
        submitBtn.disabled = true;
    } else {
        warning.style.display = 'none';
        submitBtn.disabled = false;
    }
});
</script>
@endpush
@endsection
