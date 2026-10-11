@extends('layouts.adminlte')

@section('title', 'Profil Mustahiq: ' . $mustahiq->nama . ' - LAZISNU Lamongan')
@section('page_title', 'Detail Profil & Rekam Jejak Bantuan Mustahiq')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('mustahiqs.index') }}">Data Mustahiq</a></li>
    <li class="breadcrumb-item active">{{ $mustahiq->nama }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Kolom Kiri: Profil & Kondisi Mustahiq -->
    <div class="col-lg-4 col-md-5">
        <!-- Card Foto Kondisi & Identitas Utama -->
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-body text-center p-4">
                @if($mustahiq->foto_kondisi)
                    <div class="position-relative mb-3">
                        <img src="{{ route('mustahiqs.foto', $mustahiq->id) }}" alt="Foto Kondisi Mustahiq" 
                             class="img-fluid rounded shadow-sm border" style="max-height: 220px; width: 100%; object-fit: cover;">
                        <span class="badge badge-dark position-absolute" style="top: 10px; right: 10px; opacity: 0.85;">
                            <i class="fas fa-lock text-warning mr-1"></i> Dokumentasi Lapangan
                        </span>
                    </div>
                @else
                    <div class="bg-light rounded p-4 mb-3 border text-muted">
                        <i class="fas fa-camera fa-3x mb-2 text-secondary opacity-50"></i>
                        <p class="small mb-0">Belum ada foto dokumentasi rumah</p>
                    </div>
                @endif

                <h4 class="font-weight-bold text-dark mb-1">{{ $mustahiq->nama }}</h4>
                <div class="text-muted small mb-2 font-monospace">
                    <i class="fas fa-id-card mr-1 text-secondary"></i> NIK: {{ $mustahiq->nik }}
                </div>

                <div class="mb-3">
                    {!! $mustahiq->asnaf_badge !!}
                    <span class="ml-1">{!! $mustahiq->status_badge !!}</span>
                </div>

                <hr>

                <div class="text-left small">
                    <div class="mb-2">
                        <strong class="text-muted d-block">Wilayah Ranting:</strong>
                        <span class="font-weight-bold text-dark">
                            <i class="fas fa-mosque text-success mr-1"></i> Ranting {{ $mustahiq->ranting?->name }} (MWC {{ $mustahiq->mwc?->name }})
                        </span>
                    </div>
                    <div class="mb-2">
                        <strong class="text-muted d-block">Alamat Lengkap:</strong>
                        <span class="text-dark">{{ $mustahiq->alamat }}</span>
                    </div>
                    <div class="mb-2">
                        <strong class="text-muted d-block">Kontak / Telepon:</strong>
                        <span class="text-dark">{{ $mustahiq->telepon ?: '-' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong class="text-muted d-block">Kondisi Spesifik / Kerentanan:</strong>
                        <span class="text-danger font-weight-bold">{{ $mustahiq->kondisi_spesifik ?: '-' }}</span>
                    </div>
                    <div class="mb-2">
                        <strong class="text-muted d-block">Pekerjaan & Penghasilan:</strong>
                        <span class="text-dark">{{ $mustahiq->pekerjaan ?: '-' }} (Rp {{ number_format($mustahiq->penghasilan_rata_rata, 0, ',', '.') }}/bln)</span>
                    </div>
                    <div class="mb-2">
                        <strong class="text-muted d-block">Jumlah Jiwa Tanggungan:</strong>
                        <span class="text-dark font-weight-bold">{{ $mustahiq->jumlah_tanggungan }} Orang</span>
                    </div>
                    @if($mustahiq->catatan_verifikasi)
                        <div class="mb-2 alert alert-light border py-2 px-2 mt-2">
                            <strong class="text-muted d-block"><i class="fas fa-clipboard-check text-success mr-1"></i> Catatan Verifikasi:</strong>
                            <span class="text-dark">{{ $mustahiq->catatan_verifikasi }}</span>
                        </div>
                    @endif
                </div>

                @if($user->isSuperAdmin() || $user->isPc() || ($user->isRanting() && $user->ranting_id === $mustahiq->ranting_id))
                    <div class="mt-3">
                        <a href="{{ route('mustahiqs.edit', $mustahiq->id) }}" class="btn btn-warning btn-block font-weight-bold shadow-xs">
                            <i class="fas fa-edit mr-1"></i> Edit Data Mustahiq
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Kolom Kanan: Rekam Jejak (Histori) Bantuan Pentasyarufan -->
    <div class="col-lg-8 col-md-7">
        <!-- Card Ringkasan Bantuan -->
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-history text-success mr-2"></i>Rekam Jejak Penyaluran Bantuan (Histori Bantuan)
                </h5>
                <span class="badge badge-success px-3 py-2 font-weight-bold">
                    Total Diterima: Rp {{ number_format($mustahiq->total_bantuan, 0, ',', '.') }}
                </span>
            </div>
            <div class="card-body">
                <!-- Info Pemerataan Bantuan -->
                <div class="alert alert-light border-left-success py-2 px-3 small mb-4 text-dark shadow-xs" style="border-left: 4px solid #28a745; background-color: #f8fbf9;">
                    <div class="d-flex align-items-center">
                        <i class="fas fa-balance-scale text-success fa-2x mr-3"></i>
                        <div>
                            <strong>Pemerataan Pentasyarufan LAZISNU:</strong>
                            <span class="text-muted d-block">
                                Rekam jejak ini mencatat seluruh bantuan tunai maupun barang yang pernah diterima oleh Mustahiq ini guna mencegah penumpukan bantuan pada orang yang sama.
                            </span>
                        </div>
                    </div>
                </div>

                @php
                    $allPentasyarufan = collect();

                    // Dari Pivot Kolektif
                    foreach($mustahiq->pentasyarufans as $p) {
                        $allPentasyarufan->push([
                            'id' => $p->id,
                            'nomor_transaksi' => $p->nomor_transaksi,
                            'tanggal' => $p->tanggal_penyaluran,
                            'pilar' => $p->pilarProgram?->nama_pilar ?? 'Pilar Program',
                            'kegiatan' => $p->programKerja?->nama_kegiatan ?? $p->penerima_manfaat,
                            'nominal' => $p->pivot->nominal_bantuan ?? $p->nominal_pencairan,
                            'keterangan' => $p->pivot->keterangan ?: $p->keterangan,
                            'spj_status' => $p->spj_status,
                        ]);
                    }

                    // Dari Direct Transaksi 1-on-1 (jika belum tercakup di pivot)
                    foreach($mustahiq->directPentasyarufans as $dp) {
                        if (!$allPentasyarufan->contains('id', $dp->id)) {
                            $allPentasyarufan->push([
                                'id' => $dp->id,
                                'nomor_transaksi' => $dp->nomor_transaksi,
                                'tanggal' => $dp->tanggal_penyaluran,
                                'pilar' => $dp->pilarProgram?->nama_pilar ?? 'Pilar Program',
                                'kegiatan' => $dp->programKerja?->nama_kegiatan ?? $dp->penerima_manfaat,
                                'nominal' => $dp->nominal_pencairan,
                                'keterangan' => $dp->keterangan,
                                'spj_status' => $dp->spj_status,
                            ]);
                        }
                    }

                    $allPentasyarufan = $allPentasyarufan->sortByDesc('tanggal');
                @endphp

                @if($allPentasyarufan->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-striped mb-0 text-nowrap">
                            <thead class="bg-light">
                                <tr class="text-dark small font-weight-bold text-uppercase">
                                    <th>Tanggal</th>
                                    <th>No. Transaksi</th>
                                    <th>Pilar & Kegiatan</th>
                                    <th>Nominal Riil</th>
                                    <th>Keterangan / Bentuk Bantuan</th>
                                    <th>Status SPJ</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($allPentasyarufan as $row)
                                    <tr>
                                        <td class="align-middle font-weight-bold text-dark">
                                            {{ \Carbon\Carbon::parse($row['tanggal'])->translatedFormat('d M Y') }}
                                        </td>
                                        <td class="align-middle">
                                            <a href="{{ route('pentasyarufan.show', $row['id']) }}" class="badge badge-light border text-primary">
                                                {{ $row['nomor_transaksi'] }}
                                            </a>
                                        </td>
                                        <td class="align-middle">
                                            <span class="badge badge-info px-2 py-1 mb-1 d-inline-block">{{ $row['pilar'] }}</span>
                                            <div class="small text-dark font-weight-bold">{{ Str::limit($row['kegiatan'], 35) }}</div>
                                        </td>
                                        <td class="align-middle font-weight-bold text-success" style="font-size: 1.05rem;">
                                            Rp {{ number_format($row['nominal'], 0, ',', '.') }}
                                        </td>
                                        <td class="align-middle small text-muted">
                                            {{ $row['keterangan'] ?: '-' }}
                                        </td>
                                        <td class="align-middle">
                                            @if($row['spj_status'] === 'terverifikasi')
                                                <span class="badge badge-success"><i class="fas fa-check-circle mr-1"></i> SPJ Sah</span>
                                            @elseif($row['spj_status'] === 'diupload')
                                                <span class="badge badge-warning"><i class="fas fa-clock mr-1"></i> Menunggu Verifikasi</span>
                                            @else
                                                <span class="badge badge-secondary"><i class="fas fa-hourglass-start mr-1"></i> Belum SPJ</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="text-center py-5">
                        <div class="text-muted mb-2">
                            <i class="fas fa-hand-holding-usd fa-3x" style="opacity: 0.3;"></i>
                        </div>
                        <h6 class="font-weight-bold text-dark">Belum Pernah Menerima Bantuan</h6>
                        <p class="text-muted small mb-3">
                            Mustahiq ini terdaftar aktif dan siap dipilih pada Formulir Pentasyarufan atau Pencairan Dana.
                        </p>
                        @if($user->isRanting() || $user->isMwc() || $user->isPc())
                            <a href="{{ route('pentasyarufan.create') }}" class="btn btn-outline-success btn-sm">
                                <i class="fas fa-hand-holding-usd mr-1"></i> Buka Form Realisasi Pentasyarufan
                            </a>
                        @endif
                    </div>
                @endif
            </div>
            <div class="card-footer bg-white d-flex justify-content-between">
                <a href="{{ route('mustahiqs.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar Mustahiq
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
