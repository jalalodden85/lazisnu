@extends('layouts.adminlte')

@section('title', 'Kuitansi Setoran Kontribusi Struktur')
@section('page_title', 'Detail Bukti Setoran Kontribusi')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('pentasyarufan.kontribusi.index') }}">Kontribusi Struktur</a></li>
    <li class="breadcrumb-item active">{{ $kontribusi->nomor_transaksi }}</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9">
        <div class="card shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                <div>
                    <span class="badge badge-success px-3 py-1 mb-1">
                        <i class="fas fa-check-circle mr-1"></i> Bukti Pembukuan BKU Sah
                    </span>
                    <h4 class="font-weight-bold mb-0 text-dark">{{ $kontribusi->nomor_transaksi }}</h4>
                </div>
                <div>
                    <a href="{{ route('bku.index', ['penthong_id' => $kontribusi->periode_pentong_id, 'ranting_id' => $kontribusi->ranting_id]) }}" class="btn btn-outline-success btn-sm font-weight-bold mr-2">
                        <i class="fas fa-book-open mr-1"></i> Lihat di BKU Ranting
                    </a>
                    <a href="{{ route('pentasyarufan.kontribusi.index') }}" class="btn btn-secondary btn-sm font-weight-bold">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-sm-6">
                        <span class="text-muted small">Ranting Penyetor:</span>
                        <h5 class="font-weight-bold text-dark">Ranting {{ $kontribusi->ranting?->name }}</h5>
                        <p class="text-muted small mb-0">MWC NU {{ $kontribusi->mwc?->name ?? '-' }} | Kode: {{ $kontribusi->ranting?->code }}</p>
                    </div>
                    <div class="col-sm-6 text-sm-right">
                        <span class="text-muted small">Periode Sumber Dana:</span>
                        <h5 class="font-weight-bold text-primary">{{ $kontribusi->penthong?->nama_periode }}</h5>
                        <p class="text-muted small mb-0">Tanggal Setor: {{ $kontribusi->tanggal_setor?->translatedFormat('d F Y') }}</p>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="thead-light">
                            <tr>
                                <th>Keterangan Komponen Akuntansi</th>
                                <th class="text-center" style="width: 15%;">Persentase</th>
                                <th class="text-right" style="width: 30%;">Nominal</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td><strong>Total Koin Bruto Terhimpun Ranting</strong></td>
                                <td class="text-center font-weight-bold">100%</td>
                                <td class="text-right font-weight-bold">Rp {{ number_format($kontribusi->nominal_bruto, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="text-muted">
                                <td>Potongan Hak Amil & Operasional Ranting</td>
                                <td class="text-center">{{ $kontribusi->persentase_operasional }}%</td>
                                <td class="text-right">- Rp {{ number_format($kontribusi->nominal_operasional, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="bg-light">
                                <td><strong>Dana Bersih (Netto) Dasar Bagi Hasil</strong></td>
                                <td class="text-center font-weight-bold">{{ 100 - $kontribusi->persentase_operasional }}%</td>
                                <td class="text-right font-weight-bold text-primary">Rp {{ number_format($kontribusi->nominal_netto, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="pl-4"><i class="fas fa-landmark mr-2 text-primary"></i> Porsi MWC NU</td>
                                <td class="text-center">12.00%</td>
                                <td class="text-right">Rp {{ number_format($kontribusi->nominal_mwc, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="pl-4"><i class="fas fa-building mr-2 text-success"></i> Porsi PC LAZISNU Lamongan</td>
                                <td class="text-center">6.00%</td>
                                <td class="text-right">Rp {{ number_format($kontribusi->nominal_pc, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="pl-4"><i class="fas fa-map-marked-alt mr-2 text-info"></i> Porsi PW LAZISNU Jatim</td>
                                <td class="text-center">1.50%</td>
                                <td class="text-right">Rp {{ number_format($kontribusi->nominal_pw, 0, ',', '.') }}</td>
                            </tr>
                            <tr>
                                <td class="pl-4"><i class="fas fa-globe-asia mr-2 text-secondary"></i> Porsi PB LAZISNU Pusat</td>
                                <td class="text-center">0.50%</td>
                                <td class="text-right">Rp {{ number_format($kontribusi->nominal_pb, 0, ',', '.') }}</td>
                            </tr>
                            <tr class="table-success font-weight-bold">
                                <td>TOTAL DANA DISETOR KE STRUKTUR ATAS (KREDIT BKU: NU-KONTRIBUSI)</td>
                                <td class="text-center">20.00%</td>
                                <td class="text-right text-success" style="font-size: 1.15rem;">
                                    Rp {{ number_format($kontribusi->total_disetor, 0, ',', '.') }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="row mt-4">
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body p-3 small">
                                <h6 class="font-weight-bold mb-2">Metode Penyaluran:</h6>
                                <p class="mb-1"><strong>Metode:</strong> {{ strtoupper($kontribusi->metode_penyaluran) }}</p>
                                @if($kontribusi->nama_bank)
                                    <p class="mb-1"><strong>Bank:</strong> {{ $kontribusi->nama_bank }}</p>
                                @endif
                                @if($kontribusi->nomor_rekening)
                                    <p class="mb-1"><strong>No. Rekening:</strong> {{ $kontribusi->nomor_rekening }}</p>
                                @endif
                                @if($kontribusi->catatan)
                                    <p class="mb-0"><strong>Catatan:</strong> {{ $kontribusi->catatan }}</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    @if($kontribusi->bukti_transfer)
                    <div class="col-md-6">
                        <div class="card bg-light border-0">
                            <div class="card-body p-3 text-center">
                                <h6 class="font-weight-bold mb-2 text-left small">Bukti Transfer:</h6>
                                <a href="{{ asset('storage/' . $kontribusi->bukti_transfer) }}" target="_blank">
                                    <img src="{{ asset('storage/' . $kontribusi->bukti_transfer) }}" alt="Bukti Transfer" class="img-fluid rounded border" style="max-height: 140px;">
                                </a>
                            </div>
                        </div>
                    </div>
                    @endif
                </div>

            </div>
        </div>
    </div>
</div>
@endsection
