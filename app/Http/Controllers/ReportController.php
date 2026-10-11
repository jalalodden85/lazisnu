<?php

namespace App\Http\Controllers;

use App\Models\Donator;
use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Relawan;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\LaporanCetakController;

class ReportController extends Controller
{
    /**
     * 1. Rekapitulasi Perolehan Setoran Koin
     */
    public function koin(Request $request)
    {
        $selectedYear = $request->input('year', date('Y'));
        $selectedPeriod = $request->input('period', date('Y-m'));
        $mwcId = $request->input('mwc_id');

        $query = KoinTransaction::with(['relawan', 'ranting.mwc'])
            ->where('status', KoinTransaction::STATUS_CONFIRMED);

        if ($request->filled('period')) {
            $query->where('period', $selectedPeriod);
        } elseif ($request->filled('year')) {
            $query->where('period', 'like', "{$selectedYear}-%");
        }

        if ($request->filled('mwc_id')) {
            $query->where('mwc_id', $mwcId);
        }

        $transactions = (clone $query)->orderByDesc('collection_date')->paginate(20);

        // Ringkasan Statistik
        $totalNominal = (clone $query)->sum('amount');
        $totalTrx = (clone $query)->count();
        $avgNominal = $totalTrx > 0 ? $totalNominal / $totalTrx : 0;
        $maxNominal = (clone $query)->max('amount') ?? 0;

        // Rekap Bulanan dalam Tahun Berjalan
        $monthlyRecap = KoinTransaction::selectRaw("period, count(*) as total_trx, sum(amount) as total_amount")
            ->where('status', KoinTransaction::STATUS_CONFIRMED)
            ->where('period', 'like', "{$selectedYear}-%")
            ->when($mwcId, fn($q) => $q->where('mwc_id', $mwcId))
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $mwcs = Mwc::orderBy('code')->get();

        return view('reports.koin', compact(
            'transactions',
            'totalNominal',
            'totalTrx',
            'avgNominal',
            'maxNominal',
            'monthlyRecap',
            'mwcs',
            'selectedYear',
            'selectedPeriod',
            'mwcId'
        ));
    }

    /**
     * 2. Laporan Perolehan per MWC & Ranting
     */
    public function wilayah(Request $request)
    {
        $selectedPeriod = $request->input('period', date('Y-m'));

        // Data 15 MWC dengan rincian
        $mwcs = Mwc::withCount(['rantings', 'donators'])->get()->map(function ($mwc) use ($selectedPeriod) {
            $mwc->relawan_count = Relawan::whereHas('ranting', fn($q) => $q->where('mwc_id', $mwc->id))->count();

            $mwc->period_amount = KoinTransaction::where('mwc_id', $mwc->id)
                ->where('status', KoinTransaction::STATUS_CONFIRMED)
                ->where('period', $selectedPeriod)
                ->sum('amount');

            $mwc->total_amount = KoinTransaction::where('mwc_id', $mwc->id)
                ->where('status', KoinTransaction::STATUS_CONFIRMED)
                ->sum('amount');

            return $mwc;
        })->sortByDesc('period_amount');

        return view('reports.wilayah', compact('mwcs', 'selectedPeriod'));
    }

    /**
     * 3. Laporan Kinerja & Performa Relawan Lapangan
     */
    public function relawan(Request $request)
    {
        $selectedPeriod = $request->input('period', date('Y-m'));
        $mwcId = $request->input('mwc_id');

        $query = Relawan::with(['ranting.mwc'])
            ->withCount(['donators'])
            ->where('status', Relawan::STATUS_ACTIVE);

        if ($request->filled('mwc_id')) {
            $query->whereHas('ranting', fn($q) => $q->where('mwc_id', $mwcId));
        }

        $relawans = $query->paginate(20);

        // Tambahkan perolehan periode ini untuk masing-masing relawan
        foreach ($relawans as $rel) {
            $rel->period_amount = KoinTransaction::where('relawan_id', $rel->id)
                ->where('status', KoinTransaction::STATUS_CONFIRMED)
                ->where('period', $selectedPeriod)
                ->sum('amount');

            $rel->total_amount = KoinTransaction::where('relawan_id', $rel->id)
                ->where('status', KoinTransaction::STATUS_CONFIRMED)
                ->sum('amount');
        }

        $mwcs = Mwc::orderBy('code')->get();

        return view('reports.relawan', compact('relawans', 'mwcs', 'selectedPeriod', 'mwcId'));
    }

    /**
     * 4. Laporan Distribusi Kotak Infaq Donatur
     */
    public function donatur(Request $request)
    {
        $mwcId = $request->input('mwc_id');

        $query = Donator::with(['ranting.mwc', 'relawan']);

        if ($request->filled('mwc_id')) {
            $query->whereHas('ranting', fn($q) => $q->where('mwc_id', $mwcId));
        }

        if ($request->filled('status_petugas')) {
            if ($request->status_petugas === 'assigned') {
                $query->whereNotNull('relawan_id');
            } elseif ($request->status_petugas === 'unassigned') {
                $query->whereNull('relawan_id');
            }
        }

        $donators = $query->paginate(25);

        $stats = [
            'total_kotak' => Donator::count(),
            'kotak_assigned' => Donator::whereNotNull('relawan_id')->count(),
            'kotak_unassigned' => Donator::whereNull('relawan_id')->count(),
        ];

        $mwcs = Mwc::orderBy('code')->get();

        return view('reports.donatur', compact('donators', 'stats', 'mwcs', 'mwcId'));
    }

    /**
     * 5. Halaman Cetak Laporan Resmi (Print View)
     */
    public function print(Request $request)
    {
        if ($request->filled('penthong_id') || $request->filled('format') || $request->input('type') === 'penthong') {
            return app(LaporanCetakController::class)->cetak($request);
        }

        $user = \Illuminate\Support\Facades\Auth::user();
        $mwcName = 'Seluruh Wilayah PCNU';
        $mwcId = $request->input('mwc_id');

        if ($user) {
            $laporanService = app(\App\Services\LaporanCetakService::class);
            $scope = $laporanService->validateAndResolveScope($user, $request->all());
            $mwcId = $scope['mwc_id'];
            $mwcName = $scope['wilayah_label'];
        } elseif ($mwcId) {
            $mwcObj = Mwc::find($mwcId);
            $mwcName = $mwcObj ? $mwcObj->name : $mwcName;
        }

        $type = $request->input('type', 'rekap_koin'); // rekap_koin, rekap_mwc, rekap_relawan
        $period = $request->input('period', date('Y-m'));

        $data = [];
        $totalNominal = 0;

        if ($type === 'rekap_koin') {
            $query = KoinTransaction::with(['relawan', 'ranting.mwc'])
                ->where('status', KoinTransaction::STATUS_CONFIRMED)
                ->where('period', $period);

            if ($mwcId) {
                $query->where('mwc_id', $mwcId);
            }

            if (!empty($scope['ranting_id'])) {
                $query->where('ranting_id', $scope['ranting_id']);
            }

            $data = $query->orderBy('collection_date')->get();
            $totalNominal = $data->sum('amount');
        } elseif ($type === 'rekap_mwc') {
            $mwcQuery = Mwc::withCount(['rantings', 'donators']);
            if ($mwcId) {
                $mwcQuery->where('id', $mwcId);
            }
            $data = $mwcQuery->get()->map(function ($m) use ($period) {
                $m->nominal = KoinTransaction::where('mwc_id', $m->id)
                    ->where('status', KoinTransaction::STATUS_CONFIRMED)
                    ->where('period', $period)
                    ->sum('amount');
                return $m;
            })->sortByDesc('nominal');
            $totalNominal = $data->sum('nominal');
        }

        return view('reports.print', compact('type', 'period', 'mwcName', 'data', 'totalNominal'));
    }
}
