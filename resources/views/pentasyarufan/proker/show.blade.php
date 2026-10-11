@extends('layouts.adminlte')

@section('title', 'Detail Program Kerja: ' . $proker->nomor_proker)
@section('page_title', 'Detail Program Kerja')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Beranda</a></li>
    <li class="breadcrumb-item"><a href="{{ route('proker.index') }}">Program Kerja</a></li>
    <li class="breadcrumb-item active">{{ $proker->nomor_proker }}</li>
@endsection

@section('content')
<div class="row">
    <!-- Informasi Detail Proker -->
    <div class="col-lg-8">
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-clipboard-check text-success mr-2"></i>{{ $proker->nama_kegiatan }}
                </h3>
                <div>
                    {!! $proker->status_badge !!}
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Nomor Registrasi Proker</small>
                        <code class="text-primary font-weight-bold" style="font-size: 1.1rem;">{{ $proker->nomor_proker }}</code>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Pilar Program NU Care</small>
                        <span class="badge text-white px-2 py-1 font-weight-bold" style="background: {{ $proker->pilarProgram?->warna ?? '#0a6b38' }}; font-size: 0.95rem;">
                            <i class="{{ $proker->pilarProgram?->icon ?? 'fas fa-circle' }} mr-1"></i>
                            {{ $proker->pilarProgram?->nama_pilar }}
                        </span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Tingkat & Wilayah Pelaksana</small>
                        <span class="font-weight-bold text-dark">{{ $proker->wilayah_label }}</span>
                        <span class="badge badge-light border ml-1">{{ strtoupper($proker->tingkat) }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Tahun Anggaran</small>
                        <span class="font-weight-bold text-dark">{{ $proker->tahun_anggaran }}</span>
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <small class="text-muted d-block">Target Penerima Manfaat</small>
                        <span class="font-weight-bold text-dark">{{ $proker->target_penerima_manfaat ?? '-' }}</span>
                    </div>
                    <div class="col-md-6">
                        <small class="text-muted d-block">Jadwal Pelaksanaan</small>
                        <span class="font-weight-bold text-dark">
                            {{ $proker->tanggal_mulai ? $proker->tanggal_mulai->format('d M Y') : 'Fleksibel' }}
                            @if($proker->tanggal_selesai)
                                s/d {{ $proker->tanggal_selesai->format('d M Y') }}
                            @endif
                        </span>
                    </div>
                </div>

                <div class="mb-3">
                    <small class="text-muted d-block font-weight-bold mb-1">Rincian Deskripsi Kegiatan:</small>
                    <div class="p-3 bg-light rounded border text-dark">
                        {{ $proker->deskripsi_kegiatan ?? 'Tidak ada rincian catatan tambahan.' }}
                    </div>
                </div>

                @if($proker->approved_by)
                    <div class="alert alert-info py-2 small mb-0">
                        <i class="fas fa-user-check mr-1"></i>
                        Persetujuan oleh: <strong>{{ $proker->approver?->name }}</strong> pada {{ $proker->approved_at?->format('d M Y H:i') }}.
                        @if($proker->catatan_approval)
                            <div class="mt-1"><em>"{{ $proker->catatan_approval }}"</em></div>
                        @endif
                    </div>
                @endif
            </div>
            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                <a href="{{ route('proker.index') }}" class="btn btn-secondary btn-sm">
                    <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                </a>

                <div class="btn-group">
                    @if($proker->status === 'draft')
                        <form action="{{ route('proker.submit', $proker->id) }}" method="POST" onsubmit="return confirm('Apakah Anda yakin ingin mengajukan Program Kerja ini untuk disetujui?');">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-warning font-weight-bold text-dark mr-1">
                                <i class="fas fa-paper-plane mr-1"></i> Ajukan Persetujuan
                            </button>
                        </form>
                    @endif

                    @if($proker->status === 'menunggu_persetujuan')
                        @php
                            $user = auth()->user();
                            $canApprove = $user && ($user->isSuperAdmin() || $user->isKetua() || ($user->isMwc() && $proker->tingkat === 'ranting'));
                        @endphp
                        @if($canApprove)
                            <button type="button" class="btn btn-sm btn-success font-weight-bold mr-1" data-toggle="modal" data-target="#modalApprove">
                                <i class="fas fa-check mr-1"></i> Setujui Proker
                            </button>
                            <button type="button" class="btn btn-sm btn-danger font-weight-bold" data-toggle="modal" data-target="#modalReject">
                                <i class="fas fa-times mr-1"></i> Tolak
                            </button>
                        @endif
                    @endif

                    @if($proker->status === 'disetujui' && $proker->sisa_anggaran > 0)
                        <a href="{{ route('pentasyarufan.create', ['proker_id' => $proker->id]) }}" class="btn btn-sm btn-success font-weight-bold">
                            <i class="fas fa-hand-holding-usd mr-1"></i> Cairkan Dana Pentasyarufan
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <!-- Tabel Riwayat Realisasi Pentasyarufan pada Proker ini -->
        <div class="card card-outline card-primary shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-history text-primary mr-2"></i>Riwayat Realisasi Pencairan Dana Proker
                </h3>
                <span class="badge badge-info">{{ $proker->pentasyarufans->count() }} Transaksi</span>
            </div>
            <div class="card-body table-responsive p-0">
                <table class="table table-hover table-striped align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Tanggal Penyaluran</th>
                            <th>Penerima Manfaat</th>
                            <th>Nominal Cair</th>
                            <th>Status SPJ</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($proker->pentasyarufans as $peny)
                            <tr>
                                <td><code>{{ $peny->nomor_transaksi }}</code></td>
                                <td>{{ $peny->tanggal_penyaluran->format('d/m/Y') }}</td>
                                <td>
                                    <strong>{{ $peny->penerima_manfaat }}</strong>
                                    <small class="text-muted d-block">{{ $peny->jumlah_penerima }} Jiwa</small>
                                </td>
                                <td><strong class="text-danger">Rp {{ number_format($peny->nominal_pencairan, 0, ',', '.') }}</strong></td>
                                <td>{!! $peny->spj_badge !!}</td>
                                <td class="text-center">
                                    <a href="{{ route('pentasyarufan.show', $peny->id) }}" class="btn btn-outline-primary btn-xs">
                                        <i class="fas fa-eye mr-1"></i>Detail SPJ
                                    </a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="text-center py-3 text-muted">
                                    Belum ada pencairan dana yang direalisasikan untuk program kerja ini.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Ringkasan Anggaran & Progress Serapan -->
    <div class="col-lg-4">
        <div class="card card-outline card-success shadow-sm mb-4">
            <div class="card-header bg-white">
                <h3 class="card-title font-weight-bold text-dark mb-0">
                    <i class="fas fa-coins text-warning mr-2"></i>Status Serapan Anggaran (RAB)
                </h3>
            </div>
            <div class="card-body">
                @php
                    $persen = $proker->rencana_anggaran > 0 ? round(($proker->total_pencairan / $proker->rencana_anggaran) * 100, 1) : 0;
                @endphp
                <div class="text-center my-3">
                    <h2 class="font-weight-bold text-success mb-0">{{ $persen }}%</h2>
                    <small class="text-muted text-uppercase">Persentase Terserap</small>
                </div>

                <div class="progress mb-4" style="height: 14px;">
                    <div class="progress-bar bg-success" role="progressbar" style="width: {{ min(100, $persen) }}%;"></div>
                </div>

                <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Rencana Anggaran (RAB):</span>
                        <strong class="text-dark">Rp {{ number_format($proker->rencana_anggaran, 0, ',', '.') }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Total Dana Tersalurkan:</span>
                        <strong class="text-danger">Rp {{ number_format($proker->total_pencairan, 0, ',', '.') }}</strong>
                    </li>
                    <li class="list-group-item d-flex justify-content-between align-items-center">
                        <span class="text-muted">Sisa Anggaran Belum Cair:</span>
                        <strong class="text-success" style="font-size: 1.05rem;">Rp {{ number_format($proker->sisa_anggaran, 0, ',', '.') }}</strong>
                    </li>
                </ul>

                @if($proker->status === 'disetujui')
                    @if($proker->sisa_anggaran > 0)
                        <a href="{{ route('pentasyarufan.create', ['proker_id' => $proker->id]) }}" class="btn btn-success btn-block font-weight-bold">
                            <i class="fas fa-hand-holding-usd mr-1"></i> Cairkan Dana Sekarang
                        </a>
                    @else
                        <div class="alert alert-success text-center py-2 mb-0 font-weight-bold small">
                            <i class="fas fa-check-circle mr-1"></i> Anggaran 100% Terserap Selesai
                        </div>
                    @endif
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Modal Approval Proker -->
<div class="modal fade" id="modalApprove" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('proker.approve', $proker->id) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-check-circle mr-1"></i> Persetujuan Program Kerja
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Apakah Anda menyetujui usulan Program Kerja <strong>{{ $proker->nama_kegiatan }}</strong> dengan total anggaran <strong>Rp {{ number_format($proker->rencana_anggaran, 0, ',', '.') }}</strong>?</p>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold text-dark">Catatan Persetujuan (Opsional)</label>
                    <textarea name="catatan_approval" rows="3" class="form-control" placeholder="Contoh: Disetujui sesuai RAB, pastikan dokumen SPJ dilaporkan tepat waktu."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-success font-weight-bold">
                    <i class="fas fa-check mr-1"></i> Ya, Setujui Proker
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Tolak Proker -->
<div class="modal fade" id="modalReject" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <form action="{{ route('proker.reject', $proker->id) }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title font-weight-bold">
                    <i class="fas fa-times-circle mr-1"></i> Tolak Program Kerja
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <div class="modal-body">
                <p>Berikan catatan revisi atau alasan penolakan untuk pengusul program kerja:</p>
                <div class="form-group mb-0">
                    <label class="small font-weight-bold text-dark">Alasan Penolakan / Catatan Revisi <span class="text-danger">*</span></label>
                    <textarea name="catatan_approval" rows="3" class="form-control" required placeholder="Jelaskan alasan penolakan atau bagian RAB yang perlu diperbaiki..."></textarea>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-danger font-weight-bold">
                    <i class="fas fa-ban mr-1"></i> Tolak Proker
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
