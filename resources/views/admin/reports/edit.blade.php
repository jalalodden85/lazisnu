@extends('layouts.adminlte')

@section('title', 'Edit Laporan Keuangan')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold">Edit Laporan Keuangan</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.financial-reports.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali
                </a>
            </div>
        </div>
    </div>
</div>

<section class="content">
    <div class="container-fluid">
        <div class="card card-outline card-success shadow-sm">
            <form action="{{ route('admin.financial-reports.update', $financialReport->id) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-8 form-group">
                            <label>Judul Laporan Pertanggungjawaban <span class="text-danger">*</span></label>
                            <input type="text" name="title" class="form-control" value="{{ old('title', $financialReport->title) }}" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Tahun Buku <span class="text-danger">*</span></label>
                            <input type="number" name="report_year" class="form-control" value="{{ old('report_year', $financialReport->report_year) }}" required>
                        </div>
                        <div class="col-md-2 form-group">
                            <label>Periode Pelaporan <span class="text-danger">*</span></label>
                            <input type="text" name="report_period" class="form-control" value="{{ old('report_period', $financialReport->report_period) }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-4 form-group">
                            <label>Total Pemasukan / Himpunan (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                                <input type="number" name="total_inflow" class="form-control font-weight-bold text-success" value="{{ old('total_inflow', $financialReport->total_inflow) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Total Penyaluran / Pentasyarufan (Rp) <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <div class="input-group-prepend"><span class="input-group-text font-weight-bold">Rp</span></div>
                                <input type="number" name="total_outflow" class="form-control font-weight-bold text-danger" value="{{ old('total_outflow', $financialReport->total_outflow) }}" required>
                            </div>
                        </div>
                        <div class="col-md-4 form-group">
                            <label>Status Opini Audit KAP <span class="text-danger">*</span></label>
                            <input type="text" name="audit_status" class="form-control font-weight-bold" value="{{ old('audit_status', $financialReport->audit_status) }}" required>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 form-group">
                            <label>Tanggal Publikasi Resmi <span class="text-danger">*</span></label>
                            <input type="date" name="published_at" class="form-control" value="{{ old('published_at', $financialReport->published_at?->format('Y-m-d')) }}" required>
                        </div>
                        <div class="col-md-6 form-group">
                            <label>Ganti File PDF</label>
                            <input type="file" name="pdf_file" class="form-control-file border p-1 rounded w-100" accept=".pdf">
                        </div>
                    </div>
                </div>

                <div class="card-footer bg-light text-right">
                    <button type="submit" class="btn btn-success font-weight-bold px-4">
                        <i class="fas fa-save mr-1"></i> Perbarui Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</section>
@endsection
