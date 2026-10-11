@extends('layouts.adminlte')

@section('title', 'Pendaftaran Donatur & Penerima Kotak Infaq')
@section('page_title', 'Registrasi Penerima Kotak KOIN NU')

@section('breadcrumb')
    <li class="breadcrumb-item"><a href="{{ route('dashboard') }}">Dashboard</a></li>
    <li class="breadcrumb-item"><a href="{{ route('donators.index') }}">Penerima Kotak</a></li>
    <li class="breadcrumb-item active">Tambah Donatur</li>
@endsection

@section('content')
<div class="row justify-content-center">
    <div class="col-lg-9 col-md-12">
        @if(!empty($isRantingInactive))
            <div class="alert alert-danger shadow-sm mb-3">
                <h5><i class="icon fas fa-ban"></i> Program Nonaktif!</h5>
                Ranting Anda saat ini <strong>dinonaktifkan</strong> dari program KOIN NU oleh Admin PC Lazisnu. Pendaftaran dan penambahan kotak infaq baru ditangguhkan sementara.
            </div>
        @endif

        <div class="card card-outline card-success shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h3 class="card-title font-weight-bold text-success">
                    <i class="fas fa-hand-holding-heart mr-2"></i> Formulir Pendaftaran Penerima Kotak KOIN NU
                </h3>
                <span class="badge badge-success px-2 py-1"><i class="fas fa-magic mr-1"></i> Auto-Generate Kode Kotak</span>
            </div>
            <form action="{{ route('donators.store') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row">
                        <!-- Nomor Kaleng / Kotak -->
                        <div class="col-md-6 form-group">
                            <label for="box_number" class="font-weight-bold">
                                Nomor Kotak / Kaleng KOIN NU 
                                <small class="text-muted">(Boleh dikosongkan)</small>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-barcode"></i></span>
                                </div>
                                <input type="text" 
                                       name="box_number" 
                                       id="box_number" 
                                       class="form-control font-weight-bold text-success @error('box_number') is-invalid @enderror" 
                                       placeholder="Contoh: KLG-M01R01-0102-003" 
                                       value="{{ old('box_number') }}">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-success font-weight-bold" id="btn-generate-box" title="Generate Otomatis Kode Kotak">
                                        <i class="fas fa-magic mr-1"></i> Auto
                                    </button>
                                </div>
                                @error('box_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <small class="form-text text-muted">
                                Format standar: <strong>KLG-[KODEMWC][KODERANTING]-[RT][RW]-[NO_URUT]</strong> (Contoh: <code>KLG-M01R01-0102-003</code>). Dikosongkan = auto-generate saat disimpan.
                            </small>
                        </div>

                        <!-- Nama Kepala Keluarga / Donatur -->
                        <div class="col-md-6 form-group">
                            <label for="name" class="font-weight-bold">
                                Nama Kepala Keluarga / Donatur <span class="text-danger">*</span>
                            </label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fas fa-user"></i></span>
                                </div>
                                <input type="text" 
                                       name="name" 
                                       id="name" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       placeholder="Contoh: H. Ahmad Fauzi" 
                                       value="{{ old('name') }}" 
                                       required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <!-- NIK KTP -->
                        <div class="col-md-6 form-group">
                            <label for="nik" class="font-weight-bold">NIK KTP (16 Digit)</label>
                            <input type="text" 
                                   name="nik" 
                                   id="nik" 
                                   maxlength="16" 
                                   class="form-control @error('nik') is-invalid @enderror" 
                                   placeholder="352401xxxxxxxxxx (opsional)" 
                                   value="{{ old('nik') }}">
                            @error('nik')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- No HP / WhatsApp -->
                        <div class="col-md-6 form-group">
                            <label for="phone" class="font-weight-bold">Nomor WhatsApp / HP</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text"><i class="fab fa-whatsapp text-success"></i></span>
                                </div>
                                <input type="text" 
                                       name="phone" 
                                       id="phone" 
                                       class="form-control @error('phone') is-invalid @enderror" 
                                       placeholder="081234567890" 
                                       value="{{ old('phone') }}">
                                @error('phone')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <hr class="my-3">
                    <h5 class="text-success font-weight-bold mb-3">
                        <i class="fas fa-map-marked-alt mr-2"></i> Wilayah Domisili & Petugas Pengampu Lapangan
                    </h5>

                    <div class="row">
                        <!-- Ranting / Desa -->
                        <div class="col-md-6 form-group">
                            <label for="ranting_id" class="font-weight-bold">
                                Ranting / Desa Domisili <span class="text-danger">*</span>
                            </label>
                            @if(Auth::user()?->isRanting())
                                <input type="hidden" name="ranting_id" id="ranting_id" value="{{ Auth::user()->ranting_id }}">
                                <input type="text" class="form-control bg-light font-weight-bold" value="{{ Auth::user()->ranting?->code }} - {{ Auth::user()->ranting?->name }}" readonly>
                                <small class="form-text text-muted">Terkunci otomatis pada wilayah Ranting Anda.</small>
                            @else
                                <select name="ranting_id" id="ranting_id" class="form-control @error('ranting_id') is-invalid @enderror" required>
                                    <option value="">-- Pilih Ranting / Desa --</option>
                                    @foreach($rantings as $r)
                                        <option value="{{ $r->id }}" {{ old('ranting_id') == $r->id ? 'selected' : '' }} {{ !$r->is_active ? 'disabled' : '' }}>
                                            {{ $r->code }} - {{ $r->name }} ({{ $r->mwc?->code }}) {{ !$r->is_active ? '[NONAKTIF PROGRAM]' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('ranting_id')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            @endif
                        </div>

                        <!-- Petugas Relawan Pengampu (Pembagian Petugas Wilayah) -->
                        <div class="col-md-6 form-group">
                            <label for="relawan_id" class="font-weight-bold">
                                Petugas Lapangan Pengampu (Relawan)
                            </label>
                            <select name="relawan_id" id="relawan_id" class="form-control @error('relawan_id') is-invalid @enderror">
                                <option value="">-- Belum Ditugaskan (Pilih Nanti) --</option>
                                @foreach($relawans as $rel)
                                    <option value="{{ $rel->id }}" data-ranting="{{ $rel->ranting_id }}" {{ old('relawan_id') == $rel->id ? 'selected' : '' }}>
                                        {{ $rel->nama }} — Wilayah: {{ $rel->area_tugas }} ({{ $rel->ranting?->name }})
                                    </option>
                                @endforeach
                            </select>
                            <small class="form-text text-muted">Petugas relawan yang bertanggung jawab menyetor & memantau kotak ini.</small>
                            @error('relawan_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Alamat Lengkap Rumah -->
                    <div class="form-group">
                        <label for="address" class="font-weight-bold">
                            Alamat Rumah Lengkap <span class="text-danger">*</span>
                        </label>
                        <textarea name="address" 
                                  id="address" 
                                  rows="2" 
                                  class="form-control @error('address') is-invalid @enderror" 
                                  placeholder="Nama jalan, gang, dusun..." 
                                  required>{{ old('address') }}</textarea>
                        @error('address')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="row">
                        <div class="col-md-3 form-group">
                            <label for="rt" class="font-weight-bold">RT:</label>
                            <input type="text" name="rt" id="rt" class="form-control" placeholder="01" value="{{ old('rt') }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="rw" class="font-weight-bold">RW:</label>
                            <input type="text" name="rw" id="rw" class="form-control" placeholder="02" value="{{ old('rw') }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="joined_date" class="font-weight-bold">Tanggal Penyerahan Kotak:</label>
                            <input type="date" name="joined_date" id="joined_date" class="form-control" value="{{ old('joined_date', date('Y-m-d')) }}">
                        </div>
                        <div class="col-md-3 form-group">
                            <label for="status" class="font-weight-bold">Status Kotak <span class="text-danger">*</span></label>
                            <select name="status" id="status" class="form-control" required>
                                <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notes" class="font-weight-bold">Catatan Tambahan (Opsional):</label>
                        <textarea name="notes" id="notes" rows="2" class="form-control" placeholder="Keterangan khusus donatur...">{{ old('notes') }}</textarea>
                    </div>
                </div>

                <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                    <a href="{{ route('donators.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-arrow-left mr-1"></i> Batal / Kembali
                    </a>
                    <button type="submit" class="btn btn-success font-weight-bold px-4" {{ !empty($isRantingInactive) ? 'disabled' : '' }}>
                        <i class="fas fa-save mr-1"></i> Simpan Donatur & Kotak Infaq
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const btnGenerate = document.getElementById('btn-generate-box');
        const boxNumberInput = document.getElementById('box_number');
        const rantingSelect = document.getElementById('ranting_id');
        const rtInput = document.getElementById('rt');
        const rwInput = document.getElementById('rw');

        function fetchAutoBoxNumber() {
            const rantingId = rantingSelect ? rantingSelect.value : '';
            if (!rantingId) {
                return;
            }
            const rt = rtInput ? rtInput.value : '';
            const rw = rwInput ? rwInput.value : '';

            fetch(`{{ route('donators.get-next-box-number') }}?ranting_id=${encodeURIComponent(rantingId)}&rt=${encodeURIComponent(rt)}&rw=${encodeURIComponent(rw)}`)
                .then(response => response.json())
                .then(data => {
                    if (data.box_number) {
                        boxNumberInput.value = data.box_number;
                    }
                })
                .catch(err => console.error('Error fetching box number:', err));
        }

        if (btnGenerate) {
            btnGenerate.addEventListener('click', function () {
                fetchAutoBoxNumber();
            });
        }

        // Auto trigger jika belum ada nomor kotak dan RT/RW diisi
        [rtInput, rwInput].forEach(el => {
            if (el) {
                el.addEventListener('blur', function () {
                    if (!boxNumberInput.value) {
                        fetchAutoBoxNumber();
                    }
                });
            }
        });
    });
</script>
@endpush
