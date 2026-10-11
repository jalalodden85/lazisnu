<?php

namespace App\Http\Controllers;

use App\Models\DistributionSetting;
use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Pentasyarufan;
use App\Models\Penthong;
use App\Models\PilarProgram;
use App\Models\Ranting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PilarProgramController extends Controller
{
    /**
     * Hitung Statistik Pemasukan KOIN NU, Porsi Hirarki (PC/MWC/Ranting),
     * dan Pagu Anggaran 5 Pilar Program (Per-Bidang) berdasarkan Filter dan Hak Akses.
     */
    protected function calculateHierarchyPilarStats(Request $request): array
    {
        $user = Auth::user();

        // 1. Batasi Hak Akses Hirarki Wilayah Sesuai Role Pengguna
        $userCanFilterHierarchy = true;
        $selectedTingkat = $request->input('tingkat', 'all');
        $selectedMwcId = $request->filled('mwc_id') ? (int) $request->input('mwc_id') : null;
        $selectedRantingId = $request->filled('ranting_id') ? (int) $request->input('ranting_id') : null;
        $selectedPenthongId = $request->filled('penthong_id') ? (int) $request->input('penthong_id') : null;

        if ($user && $user->isRanting()) {
            $userCanFilterHierarchy = false;
            $selectedTingkat = 'ranting';
            $selectedRantingId = (int) $user->ranting_id;
            $selectedMwcId = (int) ($user->ranting?->mwc_id ?? $user->mwc_id);
        } elseif ($user && $user->isMwc()) {
            $userCanFilterHierarchy = false;
            $selectedMwcId = (int) $user->mwc_id;
            if (!in_array($selectedTingkat, ['mwc', 'ranting'], true)) {
                $selectedTingkat = 'all';
            }
        }

        // 2. Query Transaksi Koin Terkonfirmasi (Koleksi Bruto)
        $koinQuery = KoinTransaction::where('status', KoinTransaction::STATUS_CONFIRMED);

        if ($selectedPenthongId) {
            $koinQuery->where('penthong_id', $selectedPenthongId);
        }
        if ($selectedMwcId) {
            $koinQuery->where('mwc_id', $selectedMwcId);
        }
        if ($selectedRantingId) {
            $koinQuery->where('ranting_id', $selectedRantingId);
        }

        $totalKoinBruto = (float) $koinQuery->sum('amount');

        // 3. Aturan Tasharruf & Pemotongan Operasional (DistributionSetting dengan Grandfathering)
        $selectedPenthong = $selectedPenthongId ? Penthong::find($selectedPenthongId) : null;
        $setting = $selectedPenthong ? $selectedPenthong->getDistributionSetting() : DistributionSetting::getActive();
        $operasionalPct = (float) ($setting->operasional_pct ?? 20.00);
        $rantingPct     = (float) ($setting->ranting_pct ?? 80.00);
        $mwcPct         = (float) ($setting->mwc_pct ?? 13.00);
        $pcPct          = (float) ($setting->pc_pct ?? 5.00);
        $pwPct          = (float) ($setting->pw_pct ?? 1.50);
        $pbPct          = (float) ($setting->pb_pct ?? 0.50);

        // Potongan Operasional (20%)
        $potonganOperasional = round(($totalKoinBruto * $operasionalPct) / 100);
        $danaBersihNetto = max(0, $totalKoinBruto - $potonganOperasional);

        // Porsi Hak Hirarki Organisasi (Bagi Hasil Resmi LAZISNU Lamongan)
        $porsiRanting = round(($danaBersihNetto * $rantingPct) / 100);
        $porsiMwc     = round(($danaBersihNetto * $mwcPct) / 100);
        $porsiPc      = round(($danaBersihNetto * $pcPct) / 100);
        $porsiPwPb    = round(($danaBersihNetto * ($pwPct + $pbPct)) / 100);

        // 4. Tentukan Basis Pagu untuk 5 Pilar sesuai Tingkat Hirarki yang Difilter
        switch ($selectedTingkat) {
            case 'pc':
                $basisPaguPilar = $porsiPc;
                $tingkatLabel = "Khusus Tingkat Cabang (PC - {$pcPct}% Netto)";
                break;
            case 'mwc':
                $basisPaguPilar = $porsiMwc;
                $tingkatLabel = "Khusus Tingkat Wilayah (MWC - {$mwcPct}% Netto)";
                break;
            case 'ranting':
                $basisPaguPilar = $porsiRanting;
                $tingkatLabel = "Khusus Tingkat Desa (Ranting - {$rantingPct}% Netto)";
                break;
            default:
                $basisPaguPilar = $danaBersihNetto;
                $tingkatLabel = 'Semua Tingkat Hirarki (Konsolidasi Cabang)';
                break;
        }

        // 5. Query Pentasyarufan Aktual yang Terealisasi
        $pentasyarufanQuery = Pentasyarufan::query();

        if ($selectedPenthongId) {
            $pentasyarufanQuery->where('penthong_id', $selectedPenthongId);
        }
        if ($selectedTingkat !== 'all') {
            $pentasyarufanQuery->where('tingkat', $selectedTingkat);
        }
        if ($selectedMwcId) {
            $pentasyarufanQuery->where('mwc_id', $selectedMwcId);
        }
        if ($selectedRantingId) {
            $pentasyarufanQuery->where('ranting_id', $selectedRantingId);
        }

        // Realisasi per Tingkat Hirarki untuk Matriks Card
        $realisasiPc = (float) (clone $pentasyarufanQuery)->where('tingkat', 'pc')->sum('nominal_pencairan');
        $realisasiMwc = (float) (clone $pentasyarufanQuery)->where('tingkat', 'mwc')->sum('nominal_pencairan');
        $realisasiRanting = (float) (clone $pentasyarufanQuery)->where('tingkat', 'ranting')->sum('nominal_pencairan');

        $totalPentasyarufan = (float) (clone $pentasyarufanQuery)->sum('nominal_pencairan');
        $sisaTotalPagu = $basisPaguPilar - $totalPentasyarufan;
        $overallPersentaseSerapan = $basisPaguPilar > 0 ? round(($totalPentasyarufan / $basisPaguPilar) * 100, 2) : 0;

        // 6. Hitung Pagu & Realisasi per-Bidang (5 Pilar Program NU Care)
        $pilars = PilarProgram::orderBy('sort_order')->get();
        $pilarsData = [];

        foreach ($pilars as $pilar) {
            $paguNominal = round(($basisPaguPilar * $pilar->target_persentase) / 100);
            $realisasiPilar = (float) (clone $pentasyarufanQuery)
                ->where('pilar_program_id', $pilar->id)
                ->sum('nominal_pencairan');
            $countPenyaluran = (int) (clone $pentasyarufanQuery)
                ->where('pilar_program_id', $pilar->id)
                ->count();

            $sisaPagu = $paguNominal - $realisasiPilar;
            $serapan = $paguNominal > 0 ? round(($realisasiPilar / $paguNominal) * 100, 2) : 0;

            if ($paguNominal > 0 && $sisaPagu < 0) {
                $status = [
                    'code'    => 'overbudget',
                    'label'   => 'Overbudget (Defisit)',
                    'badge'   => 'badge-danger',
                    'variant' => '#dc3545',
                ];
            } elseif ($paguNominal > 0 && $serapan < 30.0) {
                $status = [
                    'code'    => 'low_utilization',
                    'label'   => 'Serapan Rendah (< 30%)',
                    'badge'   => 'badge-warning',
                    'variant' => '#ffc107',
                ];
            } else {
                $status = [
                    'code'    => 'optimal',
                    'label'   => 'Optimal',
                    'badge'   => 'badge-success',
                    'variant' => '#28a745',
                ];
            }

            $pilarsData[] = [
                'model'             => $pilar,
                'id'                => $pilar->id,
                'kode'              => $pilar->kode,
                'nama_pilar'        => $pilar->nama_pilar,
                'deskripsi'         => $pilar->deskripsi,
                'warna'             => $pilar->warna,
                'icon'              => $pilar->icon,
                'target_persentase' => (float) $pilar->target_persentase,
                'pagu_nominal'      => $paguNominal,
                'realisasi'         => $realisasiPilar,
                'count_penyaluran'  => $countPenyaluran,
                'sisa_pagu'         => $sisaPagu,
                'serapan'           => $serapan,
                'status'            => $status,
            ];
        }

        // Data Master untuk Dropdown Filter
        $penthongs = Penthong::orderByDesc('tahun')->orderByDesc('bulan')->get();
        $mwcs = Mwc::orderBy('code')->get();
        $rantings = $selectedMwcId ? Ranting::where('mwc_id', $selectedMwcId)->orderBy('name')->get() : collect();

        return [
            'totalKoinBruto'           => $totalKoinBruto,
            'operasionalPct'           => $operasionalPct,
            'rantingPct'               => $rantingPct,
            'mwcPct'                   => $mwcPct,
            'pcPct'                    => $pcPct,
            'pwPct'                    => $pwPct,
            'pbPct'                    => $pbPct,
            'potonganOperasional'      => $potonganOperasional,
            'danaBersihNetto'          => $danaBersihNetto,
            'porsiRanting'             => $porsiRanting,
            'porsiMwc'                 => $porsiMwc,
            'porsiPc'                  => $porsiPc,
            'porsiPwPb'                => $porsiPwPb,
            'basisPaguPilar'           => $basisPaguPilar,
            'tingkatLabel'             => $tingkatLabel,
            'realisasiPc'              => $realisasiPc,
            'realisasiMwc'             => $realisasiMwc,
            'realisasiRanting'         => $realisasiRanting,
            'totalPentasyarufan'       => $totalPentasyarufan,
            'sisaTotalPagu'            => $sisaTotalPagu,
            'overallPersentaseSerapan' => $overallPersentaseSerapan,
            'pilars'                   => $pilarsData,
            'pilarsModel'              => $pilars,
            'penthongs'                => $penthongs,
            'mwcs'                     => $mwcs,
            'rantings'                 => $rantings,
            'selectedPenthongId'       => $selectedPenthongId,
            'selectedTingkat'          => $selectedTingkat,
            'selectedMwcId'            => $selectedMwcId,
            'selectedRantingId'        => $selectedRantingId,
            'userCanFilterHierarchy'   => $userCanFilterHierarchy,
        ];
    }

    /**
     * Tampilkan 5 Pilar Program NU Care beserta kalkulasi Pagu Anggaran Virtual & Serapan
     * berdasarkan Hirarki Organisasi (PC, MWC, Ranting) dan Periode Penthong
     */
    public function index(Request $request): View
    {
        $stats = $this->calculateHierarchyPilarStats($request);
        return view('pentasyarufan.pilar.index', $stats);
    }

    /**
     * Perbarui setting alokasi persentase pagu (Super Admin / Bendahara Only)
     * Validasi: Total penjumlahan seluruh target_persentase harus tepat 100%
     */
    public function update(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || (!$user->isSuperAdmin() && !$user->isBendahara())) {
            abort(403, 'Akses Ditolak: Hanya Super Admin PC atau Bendahara yang berwenang mengubah alokasi persentase pagu program.');
        }

        $request->validate([
            'percentages'   => 'required|array',
            'percentages.*' => 'required|numeric|min:0|max:100',
        ]);

        $percentages = $request->input('percentages');
        $totalPersentase = array_sum($percentages);

        // Validasi total harus 100% (dengan toleransi floating point 0.01)
        if (abs($totalPersentase - 100.00) > 0.01) {
            return back()->withInput()->with('error', "Total persentase alokasi harus tepat 100%. Saat ini totalnya adalah: {$totalPersentase}%. Silakan sesuaikan kembali.");
        }

        DB::transaction(function () use ($percentages) {
            foreach ($percentages as $pilarId => $percent) {
                PilarProgram::where('id', $pilarId)->update([
                    'target_persentase' => (float) $percent,
                ]);
            }
        });

        return redirect()->route('pilar-program.index')
            ->with('success', 'Pengaturan persentase alokasi 5 Pilar Program berhasil diperbarui!');
    }

    /**
     * API JSON: Data Grafik Serapan Anggaran (Target vs Realisasi Aktual)
     * Terintegrasi dengan Filter Hirarki dan Periode Penthong
     */
    public function chartData(Request $request): JsonResponse
    {
        $stats = $this->calculateHierarchyPilarStats($request);

        $labels = [];
        $colors = [];
        $targetPersentase = [];
        $targetNominal = [];
        $realisasiNominal = [];
        $persentaseSerapanPilar = [];
        $statusLabels = [];

        foreach ($stats['pilars'] as $p) {
            $labels[] = $p['nama_pilar'];
            $colors[] = $p['warna'];
            $targetPersentase[] = $p['target_persentase'];
            $targetNominal[] = $p['pagu_nominal'];
            $realisasiNominal[] = $p['realisasi'];
            $persentaseSerapanPilar[] = $p['serapan'];
            $statusLabels[] = $p['status'];
        }

        return response()->json([
            'success' => true,
            'summary' => [
                'total_koin_bruto'         => $stats['totalKoinBruto'],
                'potongan_operasional'     => $stats['potonganOperasional'],
                'dana_bersih_netto'        => $stats['danaBersihNetto'],
                'basis_pagu_pilar'         => $stats['basisPaguPilar'],
                'tingkat_label'            => $stats['tingkatLabel'],
                'total_pentasyarufan'      => $stats['totalPentasyarufan'],
                'sisa_saldo_keseluruhan'   => $stats['sisaTotalPagu'],
                'overall_percentage'       => $stats['overallPersentaseSerapan'],
                'porsi_hirarki'            => [
                    'ranting' => $stats['porsiRanting'],
                    'mwc'     => $stats['porsiMwc'],
                    'pc'      => $stats['porsiPc'],
                    'pw_pb'   => $stats['porsiPwPb'],
                ],
            ],
            'chart' => [
                'labels'                   => $labels,
                'colors'                   => $colors,
                'target_persentase'        => $targetPersentase,
                'target_nominal'           => $targetNominal,
                'realisasi_nominal'        => $realisasiNominal,
                'persentase_serapan_pilar' => $persentaseSerapanPilar,
                'status_evaluasi'          => $statusLabels,
            ],
        ]);
    }
}
