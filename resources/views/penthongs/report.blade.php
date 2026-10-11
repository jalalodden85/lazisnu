@extends('layouts.adminlte')

@section('title', 'Laporan Tasharruf & Distribusi Penthong')
@section('page_title', 'Laporan Bagi Hasil Tasharruf Penthong')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item active">Laporan Penthong</li>
@endsection

@section('content')
<div class="card card-default shadow-sm mb-4">
    <div class="card-header bg-light">
        <h3 class="card-title font-weight-bold">
            <i class="fas fa-filter mr-1 text-success"></i> Pilih Periode Penthong
        </h3>
    </div>
    <div class="card-body">
        <form method="GET" action="{{ route('penthongs.report') }}" class="row align-items-end">
            <div class="col-md-6 form-group mb-0">
                <label>Pilih Periode Penthong:</label>
                <select name="penthong_id" class="form-control" onchange="this.form.submit()">
                    <option value="">-- Pilih Periode Penthong --</option>
                    @foreach($penthongs as $p)
                        <option value="{{ $p->id }}" {{ request('penthong_id') == $p->id ? 'selected' : '' }}>
                            {{ $p->nama_periode }} ({{ $p->bulan_nama }} {{ $p->tahun }}) - {{ strtoupper($p->status) }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3 form-group mb-0">
                <button type="submit" class="btn btn-success">
                    <i class="fas fa-search mr-1"></i> Buka Laporan
                </button>
            </div>
        </form>
    </div>
</div>

@if(!$selectedPenthong)
    <div class="alert alert-info shadow-sm text-center py-4">
        <i class="fas fa-info-circle fs-3 mb-2 d-block"></i>
        <h5>Pilih salah satu periode Penthong di atas untuk melihat laporan distribusi bagi hasil.</h5>
    </div>
@endif
@endsection
