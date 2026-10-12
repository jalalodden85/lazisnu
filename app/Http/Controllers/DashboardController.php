<?php

namespace App\Http\Controllers;

use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Relawan;
use App\Models\Mustahiq;
use App\Models\BkuOperasional;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard Hirarki Multi-Level KOIN NU:
     * - PC Lazisnu: Menampilkan seluruh data cabang & komparasi 15 MWC
     * - MWC Lazisnu: Menampilkan data wilayah MWC & komparasi Ranting di bawahnya
     * - Ranting: Menampilkan data Ranting & performa Relawan di bawahnya
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $currentMonth = Carbon::now()->format('Y-m');

        $mwcChartLabels  = [];
        $mwcChartMonthly = [];
        $mwcChartAllTime = [];

        if ($user && $user->isRanting()) {
            // ==========================================
            // LEVEL RANTING (Admin Desa / Operator)
            // ==========================================
            $scopeRole  = 'ranting';
            $scopeTitle = $user->ranting ? $user->ranting->name : 'Ranting Desa';

            $totalDanaBulanIni = KoinTransaction::confirmed()
                ->where('ranting_id', $user->ranting_id)
                ->where('period', $currentMonth)
                ->sum('amount');

            $totalDanaAkumulasi = KoinTransaction::confirmed()
                ->where('ranting_id', $user->ranting_id)
                ->sum('amount');

            $totalRelawanAktif = Relawan::where('ranting_id', $user->ranting_id)
                ->active()
                ->count();

            $totalRantingAktif = 1;
            $totalMwcAktif     = 1;

            // Breakdown performa per relawan di ranting ini
            $breakdownStats = Relawan::where('ranting_id', $user->ranting_id)
                ->withSum(['transactions as monthly_funds' => function ($query) use ($currentMonth) {
                    $query->where('period', $currentMonth)->where('status', KoinTransaction::STATUS_CONFIRMED);
                }], 'amount')
                ->withSum(['transactions as alltime_funds' => function ($query) {
                    $query->where('status', KoinTransaction::STATUS_CONFIRMED);
                }], 'amount')
                ->orderByDesc('monthly_funds')
                ->get();

            foreach ($breakdownStats as $relawan) {
                $mwcChartLabels[]  = $relawan->nama;
                $mwcChartMonthly[] = (float) ($relawan->monthly_funds ?? 0);
                $mwcChartAllTime[] = (float) ($relawan->alltime_funds ?? 0);
            }

            $recentTransactions = KoinTransaction::where('ranting_id', $user->ranting_id)
                ->with(['relawan', 'ranting', 'mwc'])
                ->latest('collection_date')
                ->latest('id')
                ->take(8)
                ->get();

            $mwcStats = $breakdownStats;
        } elseif ($user && $user->isMwc()) {
            // ==========================================
            // LEVEL MWC (Admin Kecamatan)
            // ==========================================
            $scopeRole  = 'mwc';
            $scopeTitle = $user->mwc ? $user->mwc->name : 'MWC Kecamatan';

            $totalDanaBulanIni = KoinTransaction::confirmed()
                ->where('mwc_id', $user->mwc_id)
                ->where('period', $currentMonth)
                ->sum('amount');

            $totalDanaAkumulasi = KoinTransaction::confirmed()
                ->where('mwc_id', $user->mwc_id)
                ->sum('amount');

            $totalRelawanAktif = Relawan::whereHas('ranting', fn($q) => $q->where('mwc_id', $user->mwc_id))
                ->active()
                ->count();

            $totalRantingAktif = Ranting::where('mwc_id', $user->mwc_id)->activeProgram()->count();
            $totalMwcAktif     = 1;

            // Breakdown performa per Ranting di bawah MWC ini
            $breakdownStats = Ranting::where('mwc_id', $user->mwc_id)
                ->withCount('relawans')
                ->withSum(['transactions as monthly_funds' => function ($query) use ($currentMonth) {
                    $query->where('period', $currentMonth)->where('status', KoinTransaction::STATUS_CONFIRMED);
                }], 'amount')
                ->withSum(['transactions as alltime_funds' => function ($query) {
                    $query->where('status', KoinTransaction::STATUS_CONFIRMED);
                }], 'amount')
                ->orderByDesc('monthly_funds')
                ->get();

            foreach ($breakdownStats as $ranting) {
                $mwcChartLabels[]  = $ranting->name;
                $mwcChartMonthly[] = (float) ($ranting->monthly_funds ?? 0);
                $mwcChartAllTime[] = (float) ($ranting->alltime_funds ?? 0);
            }

            $recentTransactions = KoinTransaction::where('mwc_id', $user->mwc_id)
                ->with(['relawan', 'ranting', 'mwc'])
                ->latest('collection_date')
                ->latest('id')
                ->take(8)
                ->get();

            $mwcStats = $breakdownStats;
        } else {
            // ==========================================
            // LEVEL PC CABANG (Super Admin) / Guest Demo
            // ==========================================
            $scopeRole  = 'pc';
            $scopeTitle = 'Pimpinan Cabang (Seluruh Cabang)';

            $totalDanaBulanIni = KoinTransaction::confirmed()
                ->where('period', $currentMonth)
                ->sum('amount');

            $totalDanaAkumulasi = KoinTransaction::confirmed()->sum('amount');
            $totalRelawanAktif  = Relawan::active()->count();
            $totalRantingAktif  = Ranting::activeProgram()->count();
            $totalMwcAktif      = Mwc::count();

            // Komparasi 15 MWC
            $mwcStats = Mwc::withCount(['rantings', 'relawans'])
                ->withSum(['transactions as monthly_funds' => function ($query) use ($currentMonth) {
                    $query->where('period', $currentMonth)->where('status', KoinTransaction::STATUS_CONFIRMED);
                }], 'amount')
                ->withSum(['transactions as alltime_funds' => function ($query) {
                    $query->where('status', KoinTransaction::STATUS_CONFIRMED);
                }], 'amount')
                ->orderByDesc('monthly_funds')
                ->get();

            foreach ($mwcStats as $mwc) {
                $mwcChartLabels[]  = $mwc->name;
                $mwcChartMonthly[] = (float) ($mwc->monthly_funds ?? 0);
                $mwcChartAllTime[] = (float) ($mwc->alltime_funds ?? 0);
            }

            $recentTransactions = KoinTransaction::with(['relawan', 'ranting', 'mwc'])
                ->latest('collection_date')
                ->latest('id')
                ->take(8)
                ->get();
        }

        // Metrik Pentasyarufan & 5 Pilar Program NU Care
        $totalPentasyarufan = (float) \App\Models\Pentasyarufan::sum('nominal_pencairan');
        $sisaKasPenyaluran  = max(0, $totalDanaAkumulasi - $totalPentasyarufan);
        $pilars = \App\Models\PilarProgram::where('is_active', true)->orderBy('sort_order')->get();

        // Metrik Penerima Manfaat / Mustahiq (Dinamis Berdasarkan Hak Akses Wilayah)
        $totalMustahiqAktif = Mustahiq::forUser($user)->active()->count();
        $totalPenerimaRiil  = DB::table('pentasyarufan_mustahiq')
            ->join('mustahiqs', 'pentasyarufan_mustahiq.mustahiq_id', '=', 'mustahiqs.id')
            ->when($user && $user->isRanting() && $user->ranting_id, fn($q) => $q->where('mustahiqs.ranting_id', $user->ranting_id))
            ->when($user && $user->isMwc() && $user->mwc_id, fn($q) => $q->where('mustahiqs.mwc_id', $user->mwc_id))
            ->distinct('pentasyarufan_mustahiq.mustahiq_id')
            ->count('pentasyarufan_mustahiq.mustahiq_id');
        $totalPenerimaManfaat = $totalMustahiqAktif > 0 ? $totalMustahiqAktif : $totalPenerimaRiil;

        // Metrik Saldo Kas Operasional (Hak Amil) Terpisah
        $totalPemasukanOps   = (float) BkuOperasional::forUser($user)->debet()->sum('nominal');
        $totalPengeluaranOps = (float) BkuOperasional::forUser($user)->kredit()->sum('nominal');
        $sisaSaldoOps        = max(0, $totalPemasukanOps - $totalPengeluaranOps);

        return view('dashboard', compact(
            'scopeRole',
            'scopeTitle',
            'totalDanaBulanIni',
            'totalDanaAkumulasi',
            'totalRelawanAktif',
            'totalRantingAktif',
            'totalMwcAktif',
            'mwcStats',
            'mwcChartLabels',
            'mwcChartMonthly',
            'mwcChartAllTime',
            'recentTransactions',
            'totalPentasyarufan',
            'sisaKasPenyaluran',
            'pilars',
            'totalMustahiqAktif',
            'totalPenerimaRiil',
            'totalPenerimaManfaat',
            'totalPemasukanOps',
            'totalPengeluaranOps',
            'sisaSaldoOps'
        ));
    }
}
