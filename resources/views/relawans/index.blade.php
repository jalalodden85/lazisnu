@extends('layouts.adminlte')

@section('title', 'Data Relawan KOIN NU')
@section('page_title', 'Manajemen Relawan (Kordinator RT / RW)')

@section('breadcrumb')
    <li class="breadcrumb-item active">Relawan</li>
@endsection

@section('content')
@php
    $isRantingSuspended = Auth::user()?->isRanting() && Auth::user()?->ranting && !Auth::user()->ranting->is_active;
@endphp

@if($isRantingSuspended)
    <x-adminlte-alert theme="danger" title="Akses Input Dinonaktifkan" icon="fas fa-ban" class="mb-3">
        Akses input dinonaktifkan oleh PC Lazisnu. Silakan selesaikan urusan administrasi Anda.
    </x-adminlte-alert>
@endif

<!-- Tombol Aksi & Modal Trigger -->
<div class="row mb-3">
    <div class="col-12 d-flex justify-content-between align-items-center flex-wrap">
        <div>
            <h5 class="text-muted font-weight-normal mb-0">
                <i class="fas fa-users-cog mr-1 text-success"></i> Relawan adalah ujung tombak pengumpul koin infaq di tingkat RT/RW.
            </h5>
        </div>
        <div class="mt-2 mt-sm-0">
            @if(!$isRantingSuspended)
                <!-- Tombol Buka Modal Bulk Import -->
                <button type="button" class="btn btn-primary shadow-sm mr-2" data-toggle="modal" data-target="#modalImportRelawan">
                    <i class="fas fa-file-excel mr-1"></i> Bulk Import Excel / CSV
                </button>
                <a href="{{ route('relawans.create') }}" class="btn btn-success shadow-sm">
                    <i class="fas fa-user-plus mr-1"></i> Tambah Relawan Manual
                </a>
            @else
                <button type="button" class="btn btn-secondary shadow-sm" disabled title="Akses input dinonaktifkan oleh PC Lazisnu">
                    <i class="fas fa-lock mr-1"></i> Pendaftaran Relawan Dinonaktifkan
                </button>
            @endif
        </div>
    </div>
</div>

<!-- MODAL BULK IMPORT RELAWAN (AdminLTE 3 Modal) -->
<div class="modal fade" id="modalImportRelawan" tabindex="-1" role="dialog" aria-labelledby="modalImportRelawanLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold" id="modalImportRelawanLabel">
                    <i class="fas fa-file-upload mr-2"></i> Bulk Import Data Relawan
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form action="{{ route('relawans.import') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="modal-body">
                    <div class="callout callout-info py-2">
                        <h6 class="font-weight-bold mb-1"><i class="fas fa-info-circle mr-1"></i> Ketentuan Format File:</h6>
                        <p class="text-xs mb-1">
                            File Excel (.xlsx / .xls) atau CSV harus memiliki baris header persis:
                            <code>nama, nik, no_hp, area_tugas</code>.
                        </p>
                        <p class="text-xs mb-0 text-success font-weight-bold">
                            <i class="fas fa-shield-alt mr-1"></i> Ranting ID dikunci otomatis berdasarkan akun Admin yang login.
                        </p>
                    </div>

                    <div class="form-group">
                        <label for="importFile" class="font-weight-bold">Pilih File Spreadsheet / CSV:</label>
                        <div class="custom-file">
                            <input type="file" 
                                   name="file" 
                                   class="custom-file-input @error('file') is-invalid @enderror" 
                                   id="importFile" 
                                   accept=".xlsx, .xls, .csv" 
                                   required>
                            <label class="custom-file-label" for="importFile">Pilih file Excel atau CSV...</label>
                            @error('file')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <small class="form-text text-muted">Maksimal ukuran file: 5 MB.</small>
                    </div>

                    <div class="mt-3 text-right">
                        <a href="{{ route('relawans.template') }}" class="btn btn-sm btn-outline-secondary">
                            <i class="fas fa-download mr-1"></i> Unduh Template CSV Contoh
                        </a>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-default" data-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success font-weight-bold">
                        <i class="fas fa-upload mr-1"></i> Mulai Proses Import
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Card Filter & Pencarian -->
<div class="card card-default shadow-sm mb-4">
    <div class="card-header bg-light">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-search mr-1 text-success"></i> Cari Data Relawan
        </h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('relawans.index') }}" class="row">
            <div class="col-md-4 form-group">
                <label>Pencarian:</label>
                <input type="text" name="search" class="form-control" placeholder="Cari nama, NIK, area RT/RW, atau No. HP..." value="{{ request('search') }}">
            </div>
            @if(isset($rantings) && $rantings->count() > 1)
                <div class="col-md-3 form-group">
                    <label>Filter Ranting:</label>
                    <select name="ranting_id" class="form-control">
                        <option value="">-- Semua Ranting --</option>
                        @foreach($rantings as $ranting)
                            <option value="{{ $ranting->id }}" {{ request('ranting_id') == $ranting->id ? 'selected' : '' }}>
                                [{{ $ranting->code }}] {{ $ranting->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 form-group">
            @else
                <div class="col-md-5 form-group">
            @endif
                <label>Status Relawan:</label>
                <select name="status" class="form-control">
                    <option value="">-- Semua Status --</option>
                    <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif</option>
                    <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-2 form-group d-flex align-items-end">
                <button type="submit" class="btn btn-success btn-block">
                    <i class="fas fa-filter mr-1"></i> Filter
                </button>
            </div>
        </form>
    </div>
</div>

@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle mr-1"></i> {{ session('error') }}
        <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
@endif

<!-- Tabel Daftar Relawan -->
<div class="card shadow-sm">
    <div class="card-header bg-white d-flex justify-content-between align-items-center">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-list mr-1"></i> Daftar Relawan Kordinator
        </h3>
        <span class="badge badge-success px-2 py-1">Total: {{ $relawans->total() }} Relawan</span>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-striped table-hover m-0 align-middle">
                <thead class="thead-light">
                    <tr>
                        <th>#</th>
                        <th>Nama Relawan</th>
                        <th>NIK</th>
                        <th>No. HP / WA</th>
                        <th>Area Tugas (RT/RW)</th>
                        <th>Ranting (Desa)</th>
                        <th>MWC (Kecamatan)</th>
                        <th>Status</th>
                        <th class="text-center" style="width: 220px;">Aksi & Kontrol</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($relawans as $index => $r)
                        <tr>
                            <td>{{ $relawans->firstItem() + $index }}</td>
                            <td class="font-weight-bold text-dark">{{ $r->nama }}</td>
                            <td><small class="text-muted">{{ $r->nik }}</small></td>
                            <td>{{ $r->no_hp ?? '-' }}</td>
                            <td><span class="badge badge-light border font-weight-bold text-dark">{{ $r->area_tugas }}</span></td>
                            <td>{{ $r->ranting->name ?? '-' }}</td>
                            <td><small class="text-muted">{{ $r->ranting->mwc->name ?? '-' }}</small></td>
                            <td>{!! $r->status_badge !!}</td>
                            <td class="text-center">
                                <div class="btn-group">
                                    <a href="{{ route('transactions.create', ['relawan_id' => $r->id]) }}" class="btn btn-xs btn-success font-weight-bold mr-1" title="Catat Setoran Koin">
                                        <i class="fas fa-coins mr-1"></i> Setor
                                    </a>
                                    @if(!auth()->check() || auth()->user()->canManageRelawan($r))
                                        <a href="{{ route('relawans.edit', $r->id) }}" class="btn btn-xs btn-warning text-white mr-1" title="Edit Relawan">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('relawans.destroy', $r->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus relawan {{ $r->nama }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-xs btn-danger" title="Hapus Relawan">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="text-center py-4 text-muted">
                                Belum ada data relawan. Silakan gunakan tombol <strong>Bulk Import Excel / CSV</strong> atau <strong>Tambah Relawan</strong> untuk menambahkan relawan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
    <div class="card-footer bg-white clearfix">
        <div class="float-right">
            {{ $relawans->links('pagination::bootstrap-4') }}
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", function () {
    const importInput = document.getElementById('importFile');
    if (importInput) {
        importInput.addEventListener('change', function(e) {
            let fileName = e.target.files[0] ? e.target.files[0].name : "Pilih file Excel atau CSV...";
            let label = e.target.nextElementSibling;
            label.innerText = fileName;
        });
    }
});
</script>
@endpush
