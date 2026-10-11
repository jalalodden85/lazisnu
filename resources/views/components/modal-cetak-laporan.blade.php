@php
    $currentUser = Auth::user();
    $allPenthongs = \App\Models\Penthong::orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('id')->get();
    $activePenthong = \App\Models\Penthong::getActive($currentUser?->mwc_id, $currentUser?->ranting_id);
    $mwcs = \App\Models\Mwc::orderBy('code')->get();
    $rantings = \App\Models\Ranting::with('mwc')->orderBy('name')->get();

    // Default Signatories berdasarkan peran pengguna saat ini
    $laporanService = app(\App\Services\LaporanCetakService::class);
    $userScopeLevel = $currentUser?->isRanting() ? 'ranting' : ($currentUser?->isMwc() ? 'mwc' : 'pc');
    $defaultSignatures = $laporanService->resolveDefaultSignatories(
        $userScopeLevel,
        $currentUser?->mwc,
        $currentUser?->ranting
    );
@endphp

<!-- Modal Dialog Konfigurasi Cetak Laporan & Penandatangan -->
<div class="modal fade" id="modalCetakLaporan" tabindex="-1" role="dialog" aria-labelledby="modalCetakLaporanTitle" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content shadow-lg border-0">
            <!-- Header Modal -->
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title font-weight-bold d-flex align-items-center" id="modalCetakLaporanTitle">
                    <i class="fas fa-file-signature text-warning mr-2 fs-5"></i>
                    Konfigurasi Cetak Laporan Setoran KOIN NU
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <form id="formCetakLaporan" action="{{ route('api.laporan.cetak') }}" method="GET" target="_blank">
                <div class="modal-body p-4">
                    <!-- SECTION 1: PILIHAN PERIODE & FORMAT CETAK -->
                    <div class="card card-outline card-success shadow-none border mb-4 bg-light">
                        <div class="card-header py-2 bg-white">
                            <h6 class="font-weight-bold text-success mb-0">
                                <i class="fas fa-sliders-h mr-1"></i> 1. Periode & Format Dokumen
                            </h6>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                <!-- Periode Pentong -->
                                <div class="col-md-6 col-12 form-group mb-2">
                                    <label class="font-weight-bold small text-dark mb-1">
                                        <i class="fas fa-drum text-success mr-1"></i> Periode Buka Kotak (Penthong):
                                    </label>
                                    <select name="penthong_id" id="modal_penthong_id" class="form-control form-control-sm font-weight-bold">
                                        @if($activePenthong)
                                            <option value="{{ $activePenthong->id }}" selected>
                                                🟢 [AKTIF] {{ $activePenthong->nama_periode }} ({{ $activePenthong->bulan_nama }} {{ $activePenthong->tahun }})
                                            </option>
                                        @endif
                                        @foreach($allPenthongs as $p)
                                            @if(!$activePenthong || $p->id !== $activePenthong->id)
                                                <option value="{{ $p->id }}">
                                                    {{ $p->nama_periode }} ({{ $p->bulan_nama }} {{ $p->tahun }}) - [{{ strtoupper($p->status) }}]
                                                </option>
                                            @endif
                                        @endforeach
                                    </select>
                                </div>

                                <!-- Format Dokumen -->
                                <div class="col-md-6 col-12 form-group mb-2">
                                    <label class="font-weight-bold small text-dark mb-1">
                                        <i class="fas fa-file-export text-info mr-1"></i> Format Output:
                                    </label>
                                    <div class="d-flex gap-3 mt-1">
                                        <div class="custom-control custom-radio mr-3">
                                            <input type="radio" id="formatPdf" name="format" value="pdf" class="custom-control-input" checked>
                                            <label class="custom-control-label font-weight-bold text-danger cursor-pointer" for="formatPdf">
                                                <i class="fas fa-file-pdf mr-1"></i> PDF (Siap Cetak / Print)
                                            </label>
                                        </div>
                                        <div class="custom-control custom-radio">
                                            <input type="radio" id="formatExcel" name="format" value="excel" class="custom-control-input">
                                            <label class="custom-control-label font-weight-bold text-success cursor-pointer" for="formatExcel">
                                                <i class="fas fa-file-excel mr-1"></i> Excel (.xlsx Spreadsheet)
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2: PEMBATASAN DATA BERJENJANG (DATA SCOPING BY ROLE) -->
                    <div class="card card-outline card-info shadow-none border mb-4 bg-light">
                        <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold text-info mb-0">
                                <i class="fas fa-map-marked-alt mr-1"></i> 2. Cakupan Wilayah Laporan
                            </h6>
                            <span class="badge badge-secondary font-weight-normal px-2 py-1">
                                Role Anda: <strong class="text-white">{{ $currentUser?->role_display_name ?? 'PC' }}</strong>
                            </span>
                        </div>
                        <div class="card-body p-3">
                            <div class="row g-3">
                                @if($currentUser?->isRanting())
                                    <!-- ROLE ADMIN RANTING: Terkunci (disabled/read-only) pada rantingnya sendiri -->
                                    <input type="hidden" name="mwc_id" value="{{ $currentUser->ranting?->mwc_id ?? $currentUser->mwc_id }}">
                                    <input type="hidden" name="ranting_id" value="{{ $currentUser->ranting_id }}">

                                    <div class="col-md-6 col-12 form-group mb-2">
                                        <label class="small font-weight-bold text-muted mb-1">MWC (Kecamatan):</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm bg-white" value="{{ $currentUser->ranting?->mwc?->name ?? 'MWC NU' }} ({{ $currentUser->ranting?->mwc?->code ?? '-' }})" disabled readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12 form-group mb-2">
                                        <label class="small font-weight-bold text-muted mb-1">Ranting (Desa):</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white"><i class="fas fa-lock text-success"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm bg-white font-weight-bold text-success" value="{{ $currentUser->ranting?->name ?? 'Ranting' }} ({{ $currentUser->ranting?->code ?? '-' }})" disabled readonly>
                                        </div>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-info-circle text-info mr-1"></i>
                                            <strong>Hierarchical Scope:</strong> Sesuai wewenang Role Admin Ranting, dokumen laporan dibatasi khusus data setoran ranting Anda sendiri.
                                        </small>
                                    </div>

                                @elseif($currentUser?->isMwc())
                                    <!-- ROLE ADMIN MWC: Terkunci pada MWC kerjanya, dapat memilih semua ranting atau satu ranting spesifik -->
                                    <input type="hidden" name="mwc_id" value="{{ $currentUser->mwc_id }}">

                                    <div class="col-md-6 col-12 form-group mb-2">
                                        <label class="small font-weight-bold text-muted mb-1">Wilayah MWC (Kecamatan):</label>
                                        <div class="input-group input-group-sm">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text bg-white"><i class="fas fa-lock text-muted"></i></span>
                                            </div>
                                            <input type="text" class="form-control form-control-sm bg-white font-weight-bold" value="{{ $currentUser->mwc?->name ?? 'MWC NU' }} ({{ $currentUser->mwc?->code ?? '-' }})" disabled readonly>
                                        </div>
                                    </div>

                                    <div class="col-md-6 col-12 form-group mb-2">
                                        <label class="small font-weight-bold text-muted mb-1">Pilih Cakupan Ranting:</label>
                                        <select name="ranting_id" id="modal_ranting_id" class="form-control form-control-sm font-weight-bold border-info">
                                            <option value="">-- Cetak Semua Ranting di MWC Ini --</option>
                                            @if($currentUser->mwc)
                                                @foreach($currentUser->mwc->rantings()->orderBy('name')->get() as $r)
                                                    <option value="{{ $r->id }}">{{ $r->name }} ({{ $r->code }})</option>
                                                @endforeach
                                            @endif
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-info-circle text-info mr-1"></i>
                                            <strong>Hierarchical Scope:</strong> Anda dapat mencetak akumulasi seluruh ranting di bawah naungan MWC {{ $currentUser->mwc?->name }} atau memilih satu ranting spesifik.
                                        </small>
                                    </div>

                                @else
                                    <!-- ROLE ADMIN PC: Memiliki akses dropdown penuh se-Kabupaten Lamongan -->
                                    <div class="col-md-6 col-12 form-group mb-2">
                                        <label class="small font-weight-bold text-muted mb-1">Pilih Wilayah MWC:</label>
                                        <select name="mwc_id" id="modal_mwc_id" class="form-control form-control-sm">
                                            <option value="">-- Seluruh Wilayah PC (Se-Kabupaten Lamongan) --</option>
                                            @foreach($mwcs as $m)
                                                <option value="{{ $m->id }}">[{{ $m->code }}] {{ $m->name }} ({{ $m->kecamatan }})</option>
                                            @endforeach
                                        </select>
                                    </div>

                                    <div class="col-md-6 col-12 form-group mb-2">
                                        <label class="small font-weight-bold text-muted mb-1">Pilih Ranting Spesifik (Opsional):</label>
                                        <select name="ranting_id" id="modal_ranting_id" class="form-control form-control-sm">
                                            <option value="">-- Seluruh Ranting di Wilayah Terpilih --</option>
                                            @foreach($rantings as $rtg)
                                                <option value="{{ $rtg->id }}" data-mwc-id="{{ $rtg->mwc_id }}">
                                                    {{ $rtg->name }} ({{ $rtg->code }}) - MWC {{ $rtg->mwc->name ?? '' }}
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <small class="text-muted d-block mt-1">
                                            <i class="fas fa-info-circle text-info mr-1"></i>
                                            <strong>Hierarchical Scope:</strong> Sebagai Admin PC, Anda memiliki wewenang penuh mencetak laporan level Cabang, level per-MWC, maupun per-Ranting.
                                        </small>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 3: MODAL FORM PENANDATANGAN (SIGNATURE CONFIGURATION) -->
                    <div class="card card-outline card-warning shadow-none border mb-0 bg-light">
                        <div class="card-header py-2 bg-white d-flex justify-content-between align-items-center">
                            <h6 class="font-weight-bold text-dark mb-0">
                                <i class="fas fa-signature text-warning mr-1"></i> 3. Pengesahan & Pejabat Penandatangan
                            </h6>
                            <button type="button" class="btn btn-xs btn-outline-secondary" id="btnResetSignatories" title="Kembalikan nama & jabatan ke format default instansi">
                                <i class="fas fa-undo mr-1"></i> Reset Default
                            </button>
                        </div>
                        <div class="card-body p-3">
                            <!-- Tempat & Tanggal Dokumen -->
                            <div class="form-group mb-3">
                                <label class="small font-weight-bold text-dark mb-1">
                                    <i class="fas fa-calendar-day text-primary mr-1"></i> Tempat & Tanggal Dokumen:
                                </label>
                                <input type="text" name="tempat_tanggal" id="modal_doc_place_date" class="form-control form-control-sm font-weight-bold" value="{{ $defaultSignatures['doc_place_date'] ?? ('Lamongan, ' . \Carbon\Carbon::now()->translatedFormat('d F Y')) }}" placeholder="Contoh: Lamongan, 4 Oktober 2026">
                                <small class="text-muted">Akan dicetak di sisi kanan atas lembar pengesahan tanda tangan.</small>
                            </div>

                            <div class="row">
                                <!-- PIHAK 1 (KIRI - PEMERIKSA / PEMBUAT LAPORAN) -->
                                <div class="col-md-6 col-12 mb-3">
                                    <div class="p-3 bg-white border rounded h-100 shadow-xs">
                                        <div class="badge badge-light border text-dark font-weight-bold mb-2">
                                            <i class="fas fa-user-edit mr-1 text-primary"></i> Pihak 1 (Kiri - Pemeriksa / Pembuat)
                                        </div>

                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold text-muted mb-1">Jabatan:</label>
                                            <input type="text" name="pihak1_jabatan" id="modal_pihak1_jabatan" class="form-control form-control-sm font-weight-bold" value="{{ $defaultSignatures['pihak1_jabatan'] ?? 'Bendahara UPZIS' }}" placeholder="Contoh: Bendahara UPZIS / Divisi Keuangan">
                                        </div>

                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold text-muted mb-1">Nama Lengkap:</label>
                                            <input type="text" name="pihak1_nama" id="modal_pihak1_nama" class="form-control form-control-sm" value="{{ $defaultSignatures['pihak1_nama'] ?? '' }}" placeholder="Nama Lengkap Pejabat">
                                        </div>

                                        <div class="form-group mb-0">
                                            <label class="small font-weight-bold text-muted mb-1">Nomor Induk / Keterangan (Opsional):</label>
                                            <input type="text" name="pihak1_nip" id="modal_pihak1_nip" class="form-control form-control-sm" value="{{ $defaultSignatures['pihak1_nip'] ?? '' }}" placeholder="Contoh: NIP / NIA: 35.24.xx.xxx">
                                        </div>
                                    </div>
                                </div>

                                <!-- PIHAK 2 (KANAN - MENGETAHUI / PEJABAT PENANGGUNG JAWAB) -->
                                <div class="col-md-6 col-12 mb-3">
                                    <div class="p-3 bg-white border rounded h-100 shadow-xs">
                                        <div class="badge badge-light border text-dark font-weight-bold mb-2">
                                            <i class="fas fa-user-check mr-1 text-success"></i> Pihak 2 (Kanan - Mengetahui / Penanggung Jawab)
                                        </div>

                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold text-muted mb-1">Jabatan:</label>
                                            <input type="text" name="pihak2_jabatan" id="modal_pihak2_jabatan" class="form-control form-control-sm font-weight-bold" value="{{ $defaultSignatures['pihak2_jabatan'] ?? 'Ketua Pengurus Ranting NU' }}" placeholder="Contoh: Ketua Ranting NU / Ketua UPZIS MWC">
                                        </div>

                                        <div class="form-group mb-2">
                                            <label class="small font-weight-bold text-muted mb-1">Nama Lengkap:</label>
                                            <input type="text" name="pihak2_nama" id="modal_pihak2_nama" class="form-control form-control-sm" value="{{ $defaultSignatures['pihak2_nama'] ?? '' }}" placeholder="Nama Lengkap Pejabat Penanggung Jawab">
                                        </div>

                                        <div class="form-group mb-0">
                                            <label class="small font-weight-bold text-muted mb-1">Nomor Induk / Keterangan (Opsional):</label>
                                            <input type="text" name="pihak2_nip" id="modal_pihak2_nip" class="form-control form-control-sm" value="{{ $defaultSignatures['pihak2_nip'] ?? '' }}" placeholder="Contoh: NIP / NIA: 35.24.xx.xxx">
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex align-items-center justify-content-between pt-2 border-top">
                                <small class="text-success font-weight-bold">
                                    <i class="fas fa-save mr-1"></i> Data penandatangan otomatis tersimpan di LocalStorage peramban Anda.
                                </small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Footer Modal -->
                <div class="modal-footer bg-light py-2 d-flex justify-content-between">
                    <button type="button" class="btn btn-secondary px-3" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i> Batal
                    </button>
                    <button type="submit" class="btn btn-success font-weight-bold px-4 shadow-sm" id="btnSubmitGenerateDoc">
                        <i class="fas fa-print mr-1"></i> Generate Dokumen Laporan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function() {
    const USER_ROLE = @json($currentUser?->role?->name ?? 'pc');
    const DEFAULT_SIGS = @json($defaultSignatures);
    const STORAGE_KEY = 'lazisnu_koin_signatories_' + USER_ROLE;

    // 1. Muat Preferensi Terakhir dari LocalStorage
    function loadSavedSignatories() {
        try {
            const raw = localStorage.getItem(STORAGE_KEY);
            if (raw) {
                const data = JSON.parse(raw);
                if (data.tempat_tanggal) $('#modal_doc_place_date').val(data.tempat_tanggal);
                if (data.pihak1_jabatan) $('#modal_pihak1_jabatan').val(data.pihak1_jabatan);
                if (data.pihak1_nama) $('#modal_pihak1_nama').val(data.pihak1_nama);
                if (data.pihak1_nip) $('#modal_pihak1_nip').val(data.pihak1_nip);
                if (data.pihak2_jabatan) $('#modal_pihak2_jabatan').val(data.pihak2_jabatan);
                if (data.pihak2_nama) $('#modal_pihak2_nama').val(data.pihak2_nama);
                if (data.pihak2_nip) $('#modal_pihak2_nip').val(data.pihak2_nip);
            }
        } catch (e) {
            console.warn('Gagal memuat preferensi penandatangan dari LocalStorage', e);
        }
    }

    // 2. Simpan Preferensi ke LocalStorage saat Submit
    function saveSignatories() {
        try {
            const data = {
                tempat_tanggal: $('#modal_doc_place_date').val(),
                pihak1_jabatan: $('#modal_pihak1_jabatan').val(),
                pihak1_nama:    $('#modal_pihak1_nama').val(),
                pihak1_nip:     $('#modal_pihak1_nip').val(),
                pihak2_jabatan: $('#modal_pihak2_jabatan').val(),
                pihak2_nama:    $('#modal_pihak2_nama').val(),
                pihak2_nip:     $('#modal_pihak2_nip').val()
            };
            localStorage.setItem(STORAGE_KEY, JSON.stringify(data));
        } catch (e) {
            console.warn('Gagal menyimpan preferensi ke LocalStorage', e);
        }
    }

    // 3. Reset ke Default Instansi
    $('#btnResetSignatories').on('click', function() {
        if (confirm('Kembalikan isian pejabat penandatangan ke pengaturan bawaan lembaga?')) {
            $('#modal_doc_place_date').val(DEFAULT_SIGS.doc_place_date || 'Lamongan, {{ \Carbon\Carbon::now()->translatedFormat("d F Y") }}');
            $('#modal_pihak1_jabatan').val(DEFAULT_SIGS.pihak1_jabatan || '');
            $('#modal_pihak1_nama').val(DEFAULT_SIGS.pihak1_nama || '');
            $('#modal_pihak1_nip').val(DEFAULT_SIGS.pihak1_nip || '');
            $('#modal_pihak2_jabatan').val(DEFAULT_SIGS.pihak2_jabatan || '');
            $('#modal_pihak2_nama').val(DEFAULT_SIGS.pihak2_nama || '');
            $('#modal_pihak2_nip').val(DEFAULT_SIGS.pihak2_nip || '');
            try { localStorage.removeItem(STORAGE_KEY); } catch (e) {}
        }
    });

    // 4. Filter Dinamis Dropdown Ranting saat MWC Berubah (Khusus Admin PC)
    $('#modal_mwc_id').on('change', function() {
        const selectedMwcId = $(this).val();
        const $rantingSelect = $('#modal_ranting_id');

        if (!$rantingSelect.length) return;

        $rantingSelect.find('option').each(function() {
            const mwcId = $(this).data('mwc-id');
            if (!mwcId || !selectedMwcId || mwcId == selectedMwcId) {
                $(this).show();
            } else {
                $(this).hide();
            }
        });

        // Reset pilihan jika ranting yang aktif tersembunyi
        const currentRantingMwc = $rantingSelect.find('option:selected').data('mwc-id');
        if (selectedMwcId && currentRantingMwc && currentRantingMwc != selectedMwcId) {
            $rantingSelect.val('');
        }
    });

    // 5. Interaksi Radio Format PDF vs Excel
    $('input[name="format"]').on('change', function() {
        const format = $('input[name="format"]:checked').val();
        const $btn = $('#btnSubmitGenerateDoc');
        const $form = $('#formCetakLaporan');

        if (format === 'excel') {
            $btn.html('<i class="fas fa-file-excel mr-1"></i> Download File Excel (.xlsx)')
                .removeClass('btn-danger btn-success').addClass('btn-success');
            $form.attr('target', '_self');
        } else {
            $btn.html('<i class="fas fa-file-pdf mr-1"></i> Buka Pratinjau Cetak PDF')
                .removeClass('btn-danger btn-success').addClass('btn-danger');
            $form.attr('target', '_blank');
        }
    });

    // 6. Tangani Submit Form
    $('#formCetakLaporan').on('submit', function() {
        saveSignatories();
        // Tutup modal setelah jeda singkat agar UX tetap nyaman
        setTimeout(function() {
            $('#modalCetakLaporan').modal('hide');
        }, 500);
        return true;
    });

    // 7. Muat data saat modal siap
    $(document).ready(function() {
        loadSavedSignatories();
    });

    // Expose Helper Global agar mudah dipanggil dari tombol manapun di antarmuka
    window.openModalCetakLaporan = function(options = {}) {
        if (options.format) {
            $(`input[name="format"][value="${options.format}"]`).prop('checked', true).trigger('change');
        }
        if (options.penthong_id) {
            $('#modal_penthong_id').val(options.penthong_id);
        }
        if (options.mwc_id && $('#modal_mwc_id').length) {
            $('#modal_mwc_id').val(options.mwc_id).trigger('change');
        }
        if (options.ranting_id && $('#modal_ranting_id').length) {
            $('#modal_ranting_id').val(options.ranting_id);
        }

        loadSavedSignatories();
        $('#modalCetakLaporan').modal('show');
    };
})();
</script>
@endpush
