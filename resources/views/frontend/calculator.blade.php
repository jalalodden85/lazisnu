@extends('layouts.frontend')

@section('title', 'Kalkulator Zakat Interaktif | NU Care - LAZISNU')

@section('content')
<div class="bg-success text-white py-5" style="background: linear-gradient(135deg, #064d27, #0a6b38) !important;">
    <div class="container text-center">
        <h1 class="fw-bold mb-2"><i class="fa-solid fa-calculator text-warning me-2"></i> Kalkulator Zakat Online</h1>
        <p class="text-white-50 max-w-700 mx-auto mb-0">
            Hitung kewajiban Zakat Profesi / Penghasilan dan Zakat Maal (Harta) Anda secara akurat sesuai kaidah fiqih syariah.
        </p>
    </div>
</div>

<div class="container py-5">
    <div class="row g-4">
        <!-- Input & Form -->
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <!-- Tab Jenis Zakat -->
                <ul class="nav nav-pills mb-4 nav-justified" id="zakatTypeTab" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active fw-bold" id="profesi-tab" data-bs-toggle="pill" data-bs-target="#profesi-pane" type="button" role="tab">
                            <i class="fa-solid fa-briefcase me-1"></i> Zakat Penghasilan / Profesi
                        </button>
                    </li>
                    <li class="nav-item" role="presentation">
                        <button class="nav-link fw-bold" id="maal-tab" data-bs-toggle="pill" data-bs-target="#maal-pane" type="button" role="tab">
                            <i class="fa-solid fa-vault me-1"></i> Zakat Maal (Tabungan & Harta)
                        </button>
                    </li>
                </ul>

                <div class="tab-content" id="zakatTypeContent">
                    <!-- Tab 1: Profesi -->
                    <div class="tab-pane fade show active" id="profesi-pane" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">1. Gaji / Penghasilan Pokok per Bulan:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="proIncome" class="form-control fw-bold" value="12000000" oninput="runProCalc()">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">2. Penghasilan Tambahan / Bonus / Tunjangan:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="proExtra" class="form-control" value="0" oninput="runProCalc()">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">3. Pengeluaran Kebutuhan Pokok Bulanan (Pangan, Cicilan Rumah Pokok, Listrik, Pendidikan):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="proExpense" class="form-control" value="4000000" oninput="runProCalc()">
                            </div>
                        </div>
                    </div>

                    <!-- Tab 2: Maal -->
                    <div class="tab-pane fade" id="maal-pane" role="tabpanel">
                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">1. Tabungan / Deposito / Giro yang Tersimpan >= 1 Tahun (Haul):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="maalSavings" class="form-control fw-bold" value="150000000" oninput="runMaalCalc()">
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label fw-bold small text-muted">2. Emas / Perak / Logam Mulia (Konversi Nilai Rupiah):</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="maalGold" class="form-control" value="0" oninput="runMaalCalc()">
                            </div>
                        </div>

                        <div class="mb-4">
                            <label class="form-label fw-bold small text-muted">3. Hutang Jatuh Tempo Tahun Ini:</label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="number" id="maalDebt" class="form-control" value="0" oninput="runMaalCalc()">
                            </div>
                        </div>
                    </div>
                </div>

                <div class="p-4 bg-light rounded-4 border">
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <span class="text-muted small">Status Kewajiban:</span>
                        <span id="calcStatusBadge" class="badge bg-success">Wajib Zakat</span>
                    </div>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="fw-bold text-dark fs-5">Nilai Zakat (2.5%):</span>
                        <span class="fw-extrabold text-success fs-3" id="calcFinalResult">Rp 200.000</span>
                    </div>
                    <a href="{{ route('home') }}#rekening-resmi" class="btn btn-success fw-bold py-2 rounded-pill w-100" style="background: var(--nu-green);">
                        <i class="fa-solid fa-credit-card me-1"></i> Tunaikan Melalui Rekening Resmi Zakat LAZISNU
                    </a>
                </div>
            </div>
        </div>

        <!-- Ketentuan Nisab Sidebar -->
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 p-4">
                <h5 class="fw-bold mb-3"><i class="fa-solid fa-circle-question text-warning me-1"></i> Ketentuan Nisab</h5>
                <p class="small text-muted mb-3">
                    Nisab zakat profesi dan harta setara dengan <strong>85 gram emas murni</strong>.
                </p>
                <div class="bg-light p-3 rounded-3 border small mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Harga Emas / Gram:</span>
                        <strong>Rp {{ number_format($goldPrice, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span>Nisab per Tahun:</span>
                        <strong>Rp {{ number_format($nisabYearly, 0, ',', '.') }}</strong>
                    </div>
                    <div class="d-flex justify-content-between text-success">
                        <span>Nisab per Bulan:</span>
                        <strong>Rp {{ number_format($nisabMonthly, 0, ',', '.') }}</strong>
                    </div>
                </div>

                <div class="alert alert-info small mb-0">
                    <i class="fa-solid fa-info-circle me-1"></i> Jika penghasilan bersih belum mencapai batas nisab, Anda tidak dibebankan kewajiban zakat, namun sangat dianjurkan menyisihkan <strong>Infaq KOIN NU</strong> secara sukarela.
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const nisabMonthly = {{ $nisabMonthly }};
    const nisabYearly = {{ $nisabYearly }};
    let currentMode = 'profesi';

    function runProCalc() {
        const income = parseFloat(document.getElementById('proIncome').value) || 0;
        const extra = parseFloat(document.getElementById('proExtra').value) || 0;
        const expense = parseFloat(document.getElementById('proExpense').value) || 0;
        const net = Math.max(0, (income + extra) - expense);

        const statusBadge = document.getElementById('calcStatusBadge');
        const finalResult = document.getElementById('calcFinalResult');

        if (net >= nisabMonthly) {
            const z = net * 0.025;
            finalResult.innerText = 'Rp ' + Math.round(z).toLocaleString('id-ID');
            statusBadge.className = 'badge bg-success';
            statusBadge.innerText = 'Wajib Zakat (Mencapai Nisab)';
        } else {
            finalResult.innerText = 'Rp 0';
            statusBadge.className = 'badge bg-secondary';
            statusBadge.innerText = 'Belum Wajib Zakat (Dianjurkan Infaq)';
        }
    }

    function runMaalCalc() {
        const savings = parseFloat(document.getElementById('maalSavings').value) || 0;
        const gold = parseFloat(document.getElementById('maalGold').value) || 0;
        const debt = parseFloat(document.getElementById('maalDebt').value) || 0;
        const totalHarta = Math.max(0, (savings + gold) - debt);

        const statusBadge = document.getElementById('calcStatusBadge');
        const finalResult = document.getElementById('calcFinalResult');

        if (totalHarta >= nisabYearly) {
            const z = totalHarta * 0.025;
            finalResult.innerText = 'Rp ' + Math.round(z).toLocaleString('id-ID');
            statusBadge.className = 'badge bg-success';
            statusBadge.innerText = 'Wajib Zakat Maal (Mencapai Haul & Nisab)';
        } else {
            finalResult.innerText = 'Rp 0';
            statusBadge.className = 'badge bg-secondary';
            statusBadge.innerText = 'Belum Wajib Zakat';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        runProCalc();
        document.getElementById('profesi-tab').addEventListener('click', () => {
            currentMode = 'profesi';
            setTimeout(runProCalc, 50);
        });
        document.getElementById('maal-tab').addEventListener('click', () => {
            currentMode = 'maal';
            setTimeout(runMaalCalc, 50);
        });
    });
</script>
@endpush
