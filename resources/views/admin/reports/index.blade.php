@extends('layouts.adminlte')

@section('title', 'Laporan Keuangan & Akuntabilitas Publik')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-file-invoice-dollar text-success mr-2"></i>Laporan Keuangan & Akuntabilitas Publik</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.financial-reports.create') }}" class="btn btn-success font-weight-bold">
                    <i class="fas fa-plus-circle mr-1"></i> Tambah Laporan Baru
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
            <div class="card-header">
                <h3 class="card-title font-weight-bold">Daftar Publikasi Laporan Keuangan (Audit WTP & Periodik)</h3>
            </div>
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>Tahun & Periode</th>
                            <th>Judul Laporan</th>
                            <th>Total Pemasukan (Rp)</th>
                            <th>Total Penyaluran (Rp)</th>
                            <th>Status Opini Audit KAP</th>
                            <th>Tanggal Terbit</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($reports as $rep)
                            <tr>
                                <td>
                                    <span class="badge badge-success fs-6">{{ $rep->report_year }}</span>
                                    <div class="small text-muted mt-1">{{ $rep->report_period }}</div>
                                </td>
                                <td>
                                    <strong>{{ $rep->title }}</strong>
                                    @if($rep->file_pdf && $rep->file_pdf !== '#')
                                        <div class="mt-1">
                                            <a href="{{ $rep->file_pdf }}" target="_blank" class="badge badge-danger">
                                                <i class="fas fa-file-pdf mr-1"></i> Berkas PDF
                                            </a>
                                        </div>
                                    @endif
                                </td>
                                <td class="text-success font-weight-bold">
                                    Rp {{ number_format($rep->total_inflow, 0, ',', '.') }}
                                </td>
                                <td class="text-danger font-weight-bold">
                                    Rp {{ number_format($rep->total_outflow, 0, ',', '.') }}
                                </td>
                                <td>
                                    <span class="badge badge-warning text-dark font-weight-bold p-1">
                                        <i class="fas fa-award mr-1"></i> {{ $rep->audit_status }}
                                    </span>
                                </td>
                                <td><small class="text-muted">{{ $rep->published_at ? $rep->published_at->format('d/m/Y') : '-' }}</small></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.financial-reports.edit', $rep->id) }}" class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.financial-reports.destroy', $rep->id) }}" method="POST" onsubmit="return confirm('Hapus laporan keuangan ini?')" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-danger" title="Hapus">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada laporan keuangan terdaftar.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($reports->hasPages())
                <div class="card-footer bg-white">
                    {{ $reports->links() }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
