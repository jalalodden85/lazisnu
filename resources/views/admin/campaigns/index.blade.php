@extends('layouts.adminlte')

@section('title', 'Manajemen Kampanye Donasi & Crowdfunding')

@section('content')
<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2 align-items-center">
            <div class="col-sm-6">
                <h1 class="m-0 text-dark font-weight-bold"><i class="fas fa-hand-holding-heart text-danger mr-2"></i>Kampanye Donasi & Crowdfunding</h1>
            </div>
            <div class="col-sm-6 text-right">
                <a href="{{ route('admin.campaigns.create') }}" class="btn btn-success font-weight-bold">
                    <i class="fas fa-plus-circle mr-1"></i> Buat Kampanye Baru
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

        <!-- Filter Card -->
        <div class="card card-outline card-secondary shadow-sm mb-3">
            <div class="card-body p-3">
                <form action="{{ route('admin.campaigns.index') }}" method="GET" class="row g-2 align-items-center">
                    <div class="col-md-4">
                        <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari judul kampanye..." value="{{ request('q') }}">
                    </div>
                    <div class="col-md-3">
                        <select name="category_id" class="form-control form-control-sm">
                            <option value="">-- Semua 5 Pilar --</option>
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}" {{ request('category_id') == $cat->id ? 'selected' : '' }}>{{ $cat->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <select name="status" class="form-control form-control-sm">
                            <option value="">-- Semua Status --</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Aktif (Penggalangan Berjalan)</option>
                            <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Selesai / Terpenuhi</option>
                            <option value="suspended" {{ request('status') == 'suspended' ? 'selected' : '' }}>Ditangguhkan (Suspended)</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary btn-sm btn-block"><i class="fas fa-filter mr-1"></i> Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card card-outline card-success shadow-sm">
            <div class="card-body p-0 table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light">
                        <tr>
                            <th style="width: 80px;">Thumbnail</th>
                            <th>Judul & Pilar</th>
                            <th>Target Dana (Rp)</th>
                            <th>Terkumpul (Rp)</th>
                            <th style="width: 140px;">Progres (%)</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Sorotan</th>
                            <th style="width: 140px;" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($campaigns as $camp)
                            <tr>
                                <td>
                                    <img src="{{ $camp->banner_image }}" style="width: 70px; height: 50px; object-fit: cover; border-radius: 6px;" alt="Banner">
                                </td>
                                <td>
                                    <strong>{{ $camp->title }}</strong>
                                    <div class="small text-muted">
                                        <span class="badge badge-light border">{{ $camp->category->name ?? '-' }}</span>
                                        @if($camp->mwc)
                                            <span class="badge badge-info">{{ $camp->mwc->code }}</span>
                                        @endif
                                        <span class="ml-1"><i class="fas fa-users mr-1"></i>{{ $camp->donations_count }} Donatur</span>
                                    </div>
                                </td>
                                <td><strong>Rp {{ number_format($camp->target_amount, 0, ',', '.') }}</strong></td>
                                <td class="text-success font-weight-bold">Rp {{ number_format($camp->collected_amount, 0, ',', '.') }}</td>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <div class="progress flex-grow-1 mr-2" style="height: 8px;">
                                            <div class="progress-bar bg-success" style="width: {{ $camp->progress_percentage }}%"></div>
                                        </div>
                                        <small class="font-weight-bold">{{ $camp->progress_percentage }}%</small>
                                    </div>
                                </td>
                                <td class="text-center">
                                    @if($camp->status == 'active')
                                        <span class="badge badge-success">Aktif</span>
                                    @elseif($camp->status == 'completed')
                                        <span class="badge badge-primary">Selesai</span>
                                    @else
                                        <span class="badge badge-secondary">{{ $camp->status }}</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    @if($camp->is_featured)
                                        <span class="badge badge-warning text-dark" title="Tampil di Beranda"><i class="fas fa-star mr-1"></i>Utama</span>
                                    @endif
                                    @if($camp->is_emergency)
                                        <span class="badge badge-danger ml-1" title="Tanggap Bencana/Darurat"><i class="fas fa-fire mr-1"></i>Darurat</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="{{ route('admin.campaigns.show', $camp->id) }}" class="btn btn-primary" title="Kelola & Verifikasi Donasi">
                                            <i class="fas fa-list-check"></i>
                                        </a>
                                        <a href="{{ route('programs.detail', $camp->slug) }}" target="_blank" class="btn btn-info" title="Lihat Halaman Publik">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                        <a href="{{ route('admin.campaigns.edit', $camp->id) }}" class="btn btn-warning" title="Edit">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <form action="{{ route('admin.campaigns.destroy', $camp->id) }}" method="POST" onsubmit="return confirm('Hapus kampanye {{ $camp->title }}?')" class="d-inline">
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
                                <td colspan="8" class="text-center text-muted py-4">Belum ada kampanye ditemukan.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($campaigns->hasPages())
                <div class="card-footer bg-white">
                    {{ $campaigns->withQueryString()->links() }}
                </div>
            @endif
        </div>
    </div>
</section>
@endsection
