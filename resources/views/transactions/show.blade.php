@extends('layouts.adminlte')

@section('title', 'Kuitansi Setoran - ' . $transaction->invoice_code)
@section('page_title', 'Tanda Terima Setoran Koin Infaq Relawan')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('transactions.index') }}">Transaksi</a></li>
    <li class="breadcrumb-item active">{{ $transaction->invoice_code }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8 col-12">
        <div class="invoice p-3 mb-3 bg-white shadow-sm rounded">
            <div class="row">
                <div class="col-12 pb-3 border-bottom">
                    <h4>
                        <i class="fas fa-hand-holding-heart text-success mr-2"></i> LAZISNU Care
                        <small class="float-right text-muted">Tanggal: {{ \Carbon\Carbon::parse($transaction->collection_date)->format('d F Y') }}</small>
                    </h4>
                    <span class="text-muted">Kuitansi Resmi Penyerahan Koin Infaq KOIN NU dari Relawan RT/RW</span>
                </div>
            </div>

            <div class="row invoice-info my-3">
                <div class="col-sm-6 invoice-col">
                    <strong>Relawan Penyetor:</strong>
                    <address class="mt-2">
                        <strong class="text-success" style="font-size: 1.15rem;">{{ $transaction->relawan->nama }}</strong><br>
                        Area Tugas: <span class="badge badge-info">{{ $transaction->relawan->area_tugas }}</span><br>
                        NIK: {{ $transaction->relawan->nik }}<br>
                        No. HP: {{ $transaction->relawan->no_hp ?? '-' }}
                    </address>
                </div>
                <div class="col-sm-6 invoice-col text-sm-right">
                    <b>No. Kuitansi:</b> #{{ $transaction->invoice_code }}<br>
                    <b>Wilayah:</b> {{ $transaction->ranting->name }} &bull; {{ $transaction->mwc->name }}<br>
                    <b>Periode:</b> {{ $transaction->period }}<br>
                    <b>Penerima / Operator:</b> {{ $transaction->operator->name }}<br>
                    <b>Status:</b> {!! $transaction->status_badge !!}
                </div>
            </div>

            <div class="row">
                <div class="col-12 table-responsive">
                    <table class="table table-bordered">
                        <thead class="thead-light">
                            <tr>
                                <th>Deskripsi Penyerahan Koin Infaq</th>
                                <th>Tanggal Penyerahan</th>
                                <th class="text-right">Jumlah Setoran</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <strong>Penghimpunan Koin Warga Wilayah {{ $transaction->relawan->area_tugas }}</strong><br>
                                    <small class="text-muted">{{ $transaction->notes ?: 'Penyetoran koin infaq bulanan relawan koordinator' }}</small>
                                </td>
                                <td>{{ \Carbon\Carbon::parse($transaction->collection_date)->format('d/m/Y') }}</td>
                                <td class="text-right font-weight-bold text-success" style="font-size: 1.3rem;">
                                    {{ $transaction->formatted_amount }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            @if($transaction->proof_image)
                <div class="row mt-3">
                    <div class="col-12">
                        <p class="font-weight-bold mb-1">Foto Bukti Penyerahan / Fisik Uang:</p>
                        <img src="{{ asset('storage/' . $transaction->proof_image) }}" alt="Bukti Setoran" class="img-thumbnail" style="max-height: 250px;">
                    </div>
                </div>
            @endif

            <div class="row no-print mt-4 pt-3 border-top">
                <div class="col-12">
                    <button type="button" class="btn btn-default" onclick="window.print()">
                        <i class="fas fa-print mr-1"></i> Cetak Kuitansi
                    </button>
                    <a href="{{ route('transactions.create') }}" class="btn btn-success float-right ml-2">
                        <i class="fas fa-plus-circle mr-1"></i> Input Setoran Lain
                    </a>
                    <a href="{{ route('transactions.index') }}" class="btn btn-outline-secondary float-right">
                        Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
