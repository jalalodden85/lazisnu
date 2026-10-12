@extends('layouts.adminlte')

@section('title', 'Data Mustahiq (Penerima Manfaat) - LAZISNU Lamongan')
@section('page_title', 'Data Mustahiq (Penerima Manfaat)')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="#">Master Data</a></li>
    <li class="breadcrumb-item active">Data Mustahiq</li>
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

@if(session('import_failures') && count(session('import_failures')) > 0)
    <div class="alert alert-warning alert-dismissible fade show shadow-sm" role="alert">
        <h6 class="font-weight-bold mb-2"><i class="fas fa-info-circle mr-2"></i>Catatan Baris Gagal / Perlu Koreksi:</h6>
        <ul class="mb-0 pl-3 small">
            @foreach(session('import_failures') as $fail)
                <li>{{ $fail }}</li>
            @endforeach
        </ul>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
@endif

<!-- KPI Ringkasan Statistik Mustahiq -->
@php
    $totalMustahiqs = \App\Models\Mustahiq::forUser($user)->count();
    $activeMustahiqs = \App\Models\Mustahiq::forUser($user)->active()->count();
    $fakirMiskinCount = \App\Models\Mustahiq::forUser($user)->whereIn('asnaf', ['fakir', 'miskin'])->count();
    $bantuanSum = \Illuminate\Support\Facades\DB::table('pentasyarufan_mustahiq')
        ->join('mustahiqs', 'pentasyarufan_mustahiq.mustahiq_id', '=', 'mustahiqs.id')
        ->when($user->isRanting() && $user->ranting_id, fn($q) => $q->where('mustahiqs.ranting_id', $user->ranting_id))
        ->when($user->isMwc() && $user->mwc_id, fn($q) => $q->where('mustahiqs.mwc_id', $user->mwc_id))
        ->sum('nominal_bantuan');
@endphp

<div class="row">
    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-success">{{ number_format($totalMustahiqs) }}</h3>
                <p class="text-muted font-weight-bold mb-0">Total Mustahiq Terdata</p>
            </div>
            <div class="icon">
                <i class="fas fa-users text-success" style="opacity: 0.25;"></i>
            </div>
            <a href="{{ route('mustahiqs.index') }}" class="small-box-footer text-success font-weight-bold">
                Semua Data <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-primary">{{ number_format($activeMustahiqs) }}</h3>
                <p class="text-muted font-weight-bold mb-0">Mustahiq Aktif (Layak)</p>
            </div>
            <div class="icon">
                <i class="fas fa-check-circle text-primary" style="opacity: 0.25;"></i>
            </div>
            <a href="{{ route('mustahiqs.index', ['status' => 'aktif']) }}" class="small-box-footer text-primary font-weight-bold">
                Status Aktif <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-danger">{{ number_format($fakirMiskinCount) }}</h3>
                <p class="text-muted font-weight-bold mb-0">Asnaf Fakir & Miskin</p>
            </div>
            <div class="icon">
                <i class="fas fa-hand-holding-heart text-danger" style="opacity: 0.25;"></i>
            </div>
            <a href="{{ route('mustahiqs.index', ['asnaf' => 'fakir']) }}" class="small-box-footer text-danger font-weight-bold">
                Prioritas Utama <i class="fas fa-arrow-circle-right ml-1"></i>
            </a>
        </div>
    </div>

    <div class="col-lg-3 col-6">
        <div class="small-box bg-white border shadow-sm">
            <div class="inner">
                <h3 class="text-warning">Rp {{ number_format($bantuanSum, 0, ',', '.') }}</h3>
                <p class="text-muted font-weight-bold mb-0">Akumulasi Bantuan Riil</p>
            </div>
            <div class="icon">
                <i class="fas fa-coins text-warning" style="opacity: 0.25;"></i>
            </div>
            <span class="small-box-footer text-muted font-weight-bold">
                Tersalurkan ke Mustahiq
            </span>
        </div>
    </div>
</div>

<!-- Filter & Pencarian -->
<div class="card card-outline card-success shadow-sm mb-4">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold text-dark mb-0">
            <i class="fas fa-filter text-success mr-2"></i>Filter & Pencarian Penerima Manfaat
        </h3>
        <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
            <!-- Tombol Cetak PDF -->
            <a href="{{ route('mustahiqs.export.pdf', request()->query()) }}" target="_blank" class="btn btn-outline-danger btn-sm font-weight-bold shadow-xs">
                <i class="fas fa-file-pdf mr-1"></i> Cetak PDF
            </a>

            <!-- Tombol Export Excel -->
            <a href="{{ route('mustahiqs.export.excel', request()->query()) }}" class="btn btn-outline-success btn-sm font-weight-bold shadow-xs">
                <i class="fas fa-file-excel mr-1"></i> Export Excel
            </a>

            @if($user->isRanting() || $user->isPc())
                <!-- Tombol Import Data Excel -->
                <button type="button" class="btn btn-info btn-sm font-weight-bold shadow-xs" data-toggle="modal" data-target="#modalImportMustahiq">
                    <i class="fas fa-file-import mr-1"></i> Import Data
                </button>

                <!-- Tombol Tambah Baru -->
                <a href="{{ route('mustahiqs.create') }}" class="btn btn-success btn-sm font-weight-bold shadow-xs">
                    <i class="fas fa-plus-circle mr-1"></i> Tambah Mustahiq Baru
                </a>
            @endif
        </div>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('mustahiqs.index') }}">
            <div class="row">
                <!-- Filter Asnaf -->
                <div class="col-md-3 mb-2">
                    <label class="font-weight-bold small text-dark">Kategori 8 Asnaf</label>
                    <select name="asnaf" class="form-control form-control-sm">
                        <option value="">-- Semua Golongan Asnaf --</option>
                        <option value="fakir" {{ request('asnaf') == 'fakir' ? 'selected' : '' }}>Fakir</option>
                        <option value="miskin" {{ request('asnaf') == 'miskin' ? 'selected' : '' }}>Miskin</option>
                        <option value="amil" {{ request('asnaf') == 'amil' ? 'selected' : '' }}>Amil</option>
                        <option value="muallaf" {{ request('asnaf') == 'muallaf' ? 'selected' : '' }}>Muallaf</option>
                        <option value="riqab" {{ request('asnaf') == 'riqab' ? 'selected' : '' }}>Riqab</option>
                        <option value="gharim" {{ request('asnaf') == 'gharim' ? 'selected' : '' }}>Gharim</option>
                        <option value="fisabilillah" {{ request('asnaf') == 'fisabilillah' ? 'selected' : '' }}>Fisabilillah</option>
                        <option value="ibnu_sabil" {{ request('asnaf') == 'ibnu_sabil' ? 'selected' : '' }}>Ibnu Sabil</option>
                    </select>
                </div>

                <!-- Filter Status -->
                <div class="col-md-2 mb-2">
                    <label class="font-weight-bold small text-dark">Status Kelayakan</label>
                    <select name="status" class="form-control form-control-sm">
                        <option value="">-- Semua Status --</option>
                        <option value="aktif" {{ request('status') == 'aktif' ? 'selected' : '' }}>Aktif (Layak)</option>
                        <option value="tidak_aktif" {{ request('status') == 'tidak_aktif' ? 'selected' : '' }}>Tidak Aktif / Mandiri</option>
                    </select>
                </div>

                <!-- Filter Wilayah untuk PC & MWC -->
                @if($user->isPc())
                    <div class="col-md-2 mb-2">
                        <label class="font-weight-bold small text-dark">MWC (Kecamatan)</label>
                        <select name="mwc_id" class="form-control form-control-sm">
                            <option value="">-- Semua MWC --</option>
                            @foreach($mwcs as $m)
                                <option value="{{ $m->id }}" {{ request('mwc_id') == $m->id ? 'selected' : '' }}>{{ $m->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if($user->isPc() || $user->isMwc())
                    <div class="col-md-2 mb-2">
                        <label class="font-weight-bold small text-dark">Ranting (Desa)</label>
                        <select name="ranting_id" class="form-control form-control-sm">
                            <option value="">-- Semua Ranting --</option>
                            @foreach($rantings as $r)
                                <option value="{{ $r->id }}" {{ request('ranting_id') == $r->id ? 'selected' : '' }}>{{ $r->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <!-- Kata Kunci -->
                <div class="col-md-{{ ($user->isPc() || $user->isMwc()) ? '3' : '5' }} mb-2">
                    <label class="font-weight-bold small text-dark">Cari Nama / NIK / Dusun</label>
                    <div class="input-group input-group-sm">
                        <input type="text" name="search" class="form-control" placeholder="Ketik kata kunci..." value="{{ request('search') }}">
                        <div class="input-group-append">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-search mr-1"></i> Cari
                            </button>
                            @if(request()->anyFilled(['asnaf', 'status', 'mwc_id', 'ranting_id', 'search']))
                                <a href="{{ route('mustahiqs.index') }}" class="btn btn-secondary">
                                    <i class="fas fa-undo"></i>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Tabel Daftar Mustahiq -->
<div class="card card-default shadow-sm mb-4">
    <div class="card-body p-0 table-responsive">
        <table class="table table-hover table-striped mb-0 text-nowrap">
            <thead class="bg-light">
                <tr class="text-dark small font-weight-bold text-uppercase">
                    <th style="width: 50px;" class="text-center">No</th>
                    <th>Nama & NIK</th>
                    <th>Wilayah Ranting</th>
                    <th>Kategori Asnaf</th>
                    <th>Kondisi / Kerentanan</th>
                    <th>Total Bantuan</th>
                    <th class="text-center">Status</th>
                    <th class="text-center" style="width: 140px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                @forelse($mustahiqs as $index => $item)
                    <tr>
                        <td class="text-center align-middle font-weight-bold text-muted">
                            {{ $mustahiqs->firstItem() + $index }}
                        </td>
                        <td class="align-middle">
                            <div class="font-weight-bold text-dark">
                                <a href="{{ route('mustahiqs.show', $item->id) }}" class="text-dark hover-primary">
                                    {{ $item->nama }}
                                </a>
                                @if($item->jenis_kelamin === 'L')
                                    <span class="badge badge-light border text-primary ml-1" title="Laki-laki"><i class="fas fa-mars"></i> L</span>
                                @else
                                    <span class="badge badge-light border text-danger ml-1" title="Perempuan"><i class="fas fa-venus"></i> P</span>
                                @endif
                            </div>
                            <div class="small text-muted font-monospace">
                                <i class="fas fa-id-card mr-1 text-secondary"></i> NIK: {{ $item->nik }}
                            </div>
                        </td>
                        <td class="align-middle">
                            <div class="font-weight-bold text-dark">
                                <i class="fas fa-map-marker-alt text-danger mr-1"></i> {{ $item->ranting?->name ?? '-' }}
                            </div>
                            <div class="small text-muted">
                                Dusun: {{ Str::limit($item->alamat, 25) }}
                            </div>
                        </td>
                        <td class="align-middle">
                            {!! $item->asnaf_badge !!}
                        </td>
                        <td class="align-middle">
                            <span class="text-dark small font-weight-bold d-block">
                                {{ $item->kondisi_spesifik ?: '-' }}
                            </span>
                            @if($item->pekerjaan)
                                <small class="text-muted d-block">Pekerjaan: {{ $item->pekerjaan }}</small>
                            @endif
                        </td>
                        <td class="align-middle">
                            @if($item->total_bantuan > 0)
                                <span class="badge badge-success px-2 py-1 font-weight-bold">
                                    <i class="fas fa-coins mr-1"></i> Rp {{ number_format($item->total_bantuan, 0, ',', '.') }}
                                </span>
                            @else
                                <span class="badge badge-light border text-muted">
                                    Belum Menerima
                                </span>
                            @endif
                        </td>
                        <td class="text-center align-middle">
                            {!! $item->status_badge !!}
                        </td>
                        <td class="text-center align-middle">
                            <div class="btn-group btn-group-sm shadow-xs">
                                <a href="{{ route('mustahiqs.show', $item->id) }}" class="btn btn-info" title="Lihat Profil & Histori Bantuan">
                                    <i class="fas fa-eye"></i>
                                </a>
                                @if($user->isSuperAdmin() || $user->isPc() || ($user->isRanting() && $user->ranting_id === $item->ranting_id))
                                    <a href="{{ route('mustahiqs.edit', $item->id) }}" class="btn btn-warning" title="Edit Data">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="8" class="text-center py-5">
                            <div class="text-muted mb-2">
                                <i class="fas fa-folder-open fa-3x" style="opacity: 0.3;"></i>
                            </div>
                            <h6 class="font-weight-bold text-dark">Belum Ada Data Mustahiq</h6>
                            <p class="text-muted small mb-3">
                                Tidak ada data penerima manfaat yang sesuai dengan filter atau kata kunci Anda.
                            </p>
                            @if($user->isRanting() || $user->isPc())
                                <a href="{{ route('mustahiqs.create') }}" class="btn btn-success btn-sm">
                                    <i class="fas fa-plus-circle mr-1"></i> Tambah Mustahiq Sekarang
                                </a>
                            @endif
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($mustahiqs->hasPages())
        <div class="card-footer bg-white d-flex justify-content-between align-items-center py-2">
            <span class="small text-muted">
                Menampilkan {{ $mustahiqs->firstItem() }} - {{ $mustahiqs->lastItem() }} dari total {{ $mustahiqs->total() }} data
            </span>
            <div>
                {{ $mustahiqs->links('pagination::bootstrap-4') }}
            </div>
        </div>
    @endif
</div>

<!-- Modal Import Data Mustahiq -->
<div class="modal fade" id="modalImportMustahiq" tabindex="-1" role="dialog" aria-labelledby="modalImportMustahiqLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold" id="modalImportMustahiqLabel">
                    <i class="fas fa-file-excel mr-2"></i>Import Data Mustahiq (Penerima Manfaat)
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('mustahiqs.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body p-4">
                    <!-- Banner Unduh Template Format -->
                    <div class="card border border-success bg-light mb-3 shadow-none">
                        <div class="card-body p-3 d-flex justify-content-between align-items-center">
                            <div>
                                <h6 class="font-weight-bold text-success mb-1">
                                    <i class="fas fa-download mr-1"></i> Download Format Template Excel
                                </h6>
                                <p class="text-muted small mb-0">
                                    Header baku: <code>NIK</code>, <code>Nama_Lengkap</code>, <code>Jenis_Kelamin</code>, <code>Alamat</code>, <code>Kategori_Asnaf</code>, <code>Pekerjaan/Keterangan</code>.
                                </p>
                            </div>
                            <a href="{{ route('mustahiqs.template') }}" class="btn btn-success btn-sm font-weight-bold text-nowrap ml-2 shadow-xs">
                                <i class="fas fa-file-download mr-1"></i> Unduh
                            </a>
                        </div>
                    </div>

                    @if($user->isPc() || $user->isMwc())
                        <div class="form-group mb-3">
                            <label class="font-weight-bold small text-dark">Pilih Ranting Tujuan <span class="text-danger">*</span></label>
                            <select name="ranting_id" class="form-control form-control-sm" required>
                                <option value="">-- Pilih Ranting (Desa) Target --</option>
                                @foreach($rantings as $rtg)
                                    <option value="{{ $rtg->id }}" {{ request('ranting_id') == $rtg->id ? 'selected' : '' }}>
                                        {{ $rtg->name }} ({{ $rtg->code }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="text-muted">Data yang diimpor akan langsung bernaung di ranting ini.</small>
                        </div>
                    @endif

                    <!-- Input Upload File -->
                    <div class="form-group mb-3">
                        <label class="font-weight-bold small text-dark">File Dokumen (.xlsx / .csv) <span class="text-danger">*</span></label>
                        <div class="custom-file">
                            <input type="file" name="file" class="custom-file-input" id="customFileImport" accept=".xlsx,.xls,.csv" required onchange="updateFileLabel(this)">
                            <label class="custom-file-label text-truncate" for="customFileImport" id="customFileImportLabel">Pilih file spreadsheet...</label>
                        </div>
                        <small class="text-muted d-block mt-1">Maksimal ukuran file: 5 MB.</small>
                    </div>

                    <!-- Ketentuan Validasi Baris -->
                    <div class="alert alert-secondary border py-2 px-3 small mb-0">
                        <strong class="d-block mb-1 text-dark"><i class="fas fa-info-circle mr-1 text-primary"></i>Ketentuan Validasi Import:</strong>
                        <ul class="mb-0 pl-3">
                            <li><strong>NIK:</strong> Wajib 16 digit angka. NIK yang sudah ada di database akan <em>dilewati secara otomatis (skip)</em> untuk mencegah data ganda.</li>
                            <li><strong>Kolom Wajib:</strong> <code>Nama_Lengkap</code> dan <code>Kategori_Asnaf</code> tidak boleh kosong.</li>
                            <li><strong>Pilihan Asnaf:</strong> <code>fakir</code>, <code>miskin</code>, <code>amil</code>, <code>muallaf</code>, <code>riqab</code>, <code>gharim</code>, <code>fisabilillah</code>, <code>ibnu_sabil</code>.</li>
                        </ul>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success btn-sm font-weight-bold">
                        <i class="fas fa-upload mr-1"></i> Mulai Proses Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function updateFileLabel(input) {
    const label = document.getElementById('customFileImportLabel');
    if (input.files && input.files[0]) {
        label.textContent = input.files[0].name;
    } else {
        label.textContent = 'Pilih file spreadsheet...';
    }
}
</script>
@endsection
