<?php

namespace App\Http\Controllers;

use App\Models\DistributionSetting;
use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\PenthongDistribution;
use App\Models\Ranting;
use App\Models\User;
use App\Services\DistributionService;
use App\Services\LaporanCetakService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PenthongController extends Controller
{
    protected DistributionService $distributionService;

    public function __construct(DistributionService $distributionService)
    {
        $this->distributionService = $distributionService;
    }

    /**
     * Daftar Riwayat Periode Penthong
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Penthong::with(['closer', 'mwc', 'ranting'])->withCount('transactions');

        if ($user && $user->isMwc()) {
            $query->where(function ($q) use ($user) {
                $q->where('mwc_id', $user->mwc_id)->orWhereNull('mwc_id');
            });
        } elseif ($user && $user->isRanting()) {
            $query->where(function ($q) use ($user) {
                $q->where('ranting_id', $user->ranting_id)
                  ->orWhere('mwc_id', $user->mwc_id)
                  ->orWhereNull('mwc_id');
            });
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('tahun')) {
            $query->where('tahun', (int) $request->input('tahun'));
        }

        $penthongs = $query->latest('tahun')->latest('bulan')->latest('id')->paginate(12)->withQueryString();
        $activePenthong = Penthong::getActive($user?->mwc_id, $user?->ranting_id);

        // Ambil kalkulasi live untuk penthong aktif jika ada
        $activeSummary = null;
        if ($activePenthong) {
            $activeGross = $activePenthong->transactions()
                ->where('status', KoinTransaction::STATUS_CONFIRMED)
                ->sum('amount');
            $activeSummary = $this->distributionService->calculateSplit($activeGross);
        }

        $setting = DistributionSetting::getActive();

        return view('penthongs.index', compact('penthongs', 'activePenthong', 'activeSummary', 'setting'));
    }

    /**
     * Form Buka Periode Penthong Baru (Admin PC Only)
     */
    public function create(): View
    {
        $user = Auth::user();
        if (!$user || !$user->isPc()) {
            abort(403, 'Akses Ditolak: Fitur pengelolaan Periode Pentong hanya dapat diakses oleh Administrator Cabang (PC Lazisnu).');
        }

        $mwcs = Mwc::orderBy('code')->get();
        $currentMonth = (int) date('n');
        $currentYear  = (int) date('Y');

        // Nomor urut penthong berikutnya
        $count = Penthong::count() + 1;
        $defaultName = "Penthong {$count} - " . Carbon::now()->translatedFormat('F Y');

        return view('penthongs.create', compact('mwcs', 'currentMonth', 'currentYear', 'defaultName'));
    }

    /**
     * Simpan Periode Penthong Baru (Admin PC Only)
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isPc()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak: Fitur pengelolaan Periode Pentong hanya dapat diakses oleh Administrator Cabang (PC Lazisnu).',
                ], 403);
            }
            abort(403, 'Akses Ditolak: Fitur pengelolaan Periode Pentong hanya dapat diakses oleh Administrator Cabang (PC Lazisnu).');
        }

        $validated = $request->validate([
            'nama_periode' => ['required', 'string', 'max:150'],
            'bulan'        => ['required', 'integer', 'between:1,12'],
            'tahun'        => ['required', 'integer', 'min:2020', 'max:2099'],
            'start_date'   => ['nullable', 'date'],
            'end_date'     => ['nullable', 'date', 'after_or_equal:start_date'],
            'mwc_id'       => ['nullable', 'exists:mwcs,id'],
            'notes'        => ['nullable', 'string', 'max:500'],
        ]);

        $validated['status'] = Penthong::STATUS_AKTIF;

        $penthong = $this->distributionService->createPenthong($validated);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Periode {$penthong->nama_periode} berhasil dibuka dan disetel sebagai Penthong Aktif.",
                'data'    => $penthong,
            ], 201);
        }

        return redirect()
            ->route('penthongs.show', $penthong->id)
            ->with('success', "Periode {$penthong->nama_periode} berhasil dibuka dan disetel sebagai Penthong Aktif.");
    }

    /**
     * Rincian Penthong & Laporan Distribusi Bagi Hasil
     */
    public function show(Penthong $penthong, Request $request): View
    {
        $user = Auth::user();
        $setting = $penthong->getDistributionSetting();

        // 1. Jika Penthong SUDAH TUTUP: Tampilkan data terkunci
        if ($penthong->isTutup()) {
            $distributionQuery = $penthong->distributions()->with(['ranting.mwc', 'mwc']);

            if ($user && $user->isMwc()) {
                $distributionQuery->where('mwc_id', $user->mwc_id);
            } elseif ($user && $user->isRanting()) {
                $distributionQuery->where('ranting_id', $user->ranting_id);
            }

            if ($request->filled('mwc_id')) {
                $distributionQuery->where('mwc_id', (int) $request->input('mwc_id'));
            }

            $distributions = $distributionQuery->get();
            $split = [
                'total_gross'        => $penthong->total_collected,
                'operasional_amount' => $penthong->operasional_amount,
                'net_amount'         => $penthong->net_amount,
                'hak_ranting'        => $penthong->ranting_amount,
                'hak_mwc'            => $penthong->mwc_amount,
                'hak_pc'             => $penthong->pc_amount,
                'hak_pw'             => $penthong->pw_amount,
                'hak_pb'             => $penthong->pb_amount,
            ];
        } else {
            // 2. Jika Penthong MASIH AKTIF: Hitung simulasi live dari transaksi terkini
            $transactionsQuery = $penthong->transactions()
                ->where('status', KoinTransaction::STATUS_CONFIRMED);

            $totalGross = (float) (clone $transactionsQuery)->sum('amount');
            $split = $this->distributionService->calculateSplit($totalGross, $setting);

            // Simulasi per Ranting
            $rantingStats = (clone $transactionsQuery)
                ->select('ranting_id', 'mwc_id', DB::raw('SUM(amount) as gross_amount'))
                ->groupBy('ranting_id', 'mwc_id')
                ->with(['ranting.mwc', 'mwc'])
                ->get();

            $distributions = $rantingStats->map(function ($item) use ($penthong, $setting) {
                $calc = $this->distributionService->calculateSplit((float) $item->gross_amount, $setting);
                $dist = new PenthongDistribution([
                    'penthong_id'        => $penthong->id,
                    'ranting_id'         => $item->ranting_id,
                    'mwc_id'             => $item->mwc_id,
                    'gross_amount'       => $calc['total_gross'],
                    'operasional_amount' => $calc['operasional_amount'],
                    'net_amount'         => $calc['net_amount'],
                    'ranting_amount'     => $calc['hak_ranting'],
                    'mwc_amount'         => $calc['hak_mwc'],
                    'pc_amount'          => $calc['hak_pc'],
                    'pw_amount'          => $calc['hak_pw'],
                    'pb_amount'          => $calc['hak_pb'],
                ]);
                $dist->setRelation('ranting', $item->ranting);
                $dist->setRelation('mwc', $item->mwc);
                return $dist;
            });

            if ($user && $user->isMwc()) {
                $distributions = $distributions->where('mwc_id', $user->mwc_id);
            } elseif ($user && $user->isRanting()) {
                $distributions = $distributions->where('ranting_id', $user->ranting_id);
            }
        }

        $mwcs = Mwc::orderBy('code')->get();

        return view('penthongs.show', compact('penthong', 'distributions', 'split', 'setting', 'mwcs'));
    }

    /**
     * Tutup dan Kunci Periode Penthong (Admin PC Only)
     */
    public function close(Penthong $penthong): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isPc()) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak: Fitur pengelolaan Periode Pentong hanya dapat diakses oleh Administrator Cabang (PC Lazisnu).',
                ], 403);
            }
            abort(403, 'Akses Ditolak: Fitur pengelolaan Periode Pentong hanya dapat diakses oleh Administrator Cabang (PC Lazisnu).');
        }

        if ($penthong->isTutup()) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => "Periode {$penthong->nama_periode} sudah dalam status tertutup.",
                ], 400);
            }
            return back()->with('info', "Periode {$penthong->nama_periode} sudah dalam status tertutup.");
        }

        $this->distributionService->closePenthong($penthong, $user);

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Periode {$penthong->nama_periode} berhasil ditutup! Dana agregat dan rincian bagi hasil telah dikunci secara permanen.",
                'data'    => $penthong->fresh(),
            ]);
        }

        return redirect()
            ->route('penthongs.show', $penthong->id)
            ->with('success', "Periode {$penthong->nama_periode} berhasil ditutup! Dana agregat dan rincian bagi hasil telah dikunci secara permanen.");
    }

    /**
     * Buka / Tutup Status Periode Pentong (Admin PC Only)
     */
    public function toggleStatus(Penthong $penthong): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if (!$user || !$user->isPc()) {
            if (request()->expectsJson() || request()->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak: Fitur pengelolaan Periode Pentong hanya dapat diakses oleh Administrator Cabang (PC Lazisnu).',
                ], 403);
            }
            abort(403, 'Akses Ditolak: Fitur pengelolaan Periode Pentong hanya dapat diakses oleh Administrator Cabang (PC Lazisnu).');
        }

        if ($penthong->isOpen()) {
            $penthong->update([
                'status'    => Penthong::STATUS_TUTUP,
                'is_active' => false,
            ]);
            $msg = "Periode {$penthong->nama_periode} berhasil ditutup.";
        } else {
            Penthong::where('id', '!=', $penthong->id)->where('status', Penthong::STATUS_AKTIF)->update([
                'status'    => Penthong::STATUS_TUTUP,
                'is_active' => false,
            ]);

            $penthong->update([
                'status'    => Penthong::STATUS_AKTIF,
                'is_active' => true,
            ]);
            $msg = "Periode {$penthong->nama_periode} berhasil dibuka kembali sebagai periode aktif.";
        }

        if (request()->expectsJson() || request()->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => $msg,
                'data'    => $penthong->fresh(),
            ]);
        }

        return back()->with('success', $msg);
    }

    /**
     * Ekspor Laporan Resmi Perolehan Pentong Format PDF / Print-Ready dengan Scoping Berjenjang & Pejabat Penandatangan
     */
    public function exportPdf(Penthong $penthong, Request $request): View
    {
        $user = Auth::user() ?? User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();
        $laporanService = app(LaporanCetakService::class);
        $scope = $laporanService->validateAndResolveScope($user, $request->all());
        $signatures = $laporanService->resolveSignatures($scope, $request->all());
        $transactions = $laporanService->queryTransactions($penthong, $scope);
        $totalNominal = $transactions->sum('amount');
        $totalCount   = $transactions->count();

        $wilayahLabel = $scope['wilayah_label'];
        $kop = $scope['kop'];

        $dateRange = $penthong->start_date && $penthong->end_date
            ? Carbon::parse($penthong->start_date)->format('d/m/Y') . ' - ' . Carbon::parse($penthong->end_date)->format('d/m/Y')
            : "Bulan {$penthong->bulan_nama} {$penthong->tahun}";

        $printedAt = Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB';

        return view('penthongs.export_pdf', compact(
            'penthong',
            'transactions',
            'totalNominal',
            'totalCount',
            'wilayahLabel',
            'dateRange',
            'printedAt',
            'kop',
            'signatures',
            'scope'
        ));
    }

    /**
     * Ekspor Laporan Resmi Perolehan Pentong Format Excel (.xlsx) dengan Kop Berjenjang & Tanda Tangan
     */
    public function exportExcel(Penthong $penthong, Request $request)
    {
        $user = Auth::user() ?? User::whereHas('role', fn($q) => $q->where('name', 'pc'))->first();
        $laporanService = app(LaporanCetakService::class);
        $scope = $laporanService->validateAndResolveScope($user, $request->all());
        $signatures = $laporanService->resolveSignatures($scope, $request->all());
        $transactions = $laporanService->queryTransactions($penthong, $scope);

        return $laporanService->exportExcel($penthong, $transactions, $scope, $signatures);
    }

    /**
     * Laporan Khusus Penthong & Bagi Hasil Tasharruf (Tingkat MWC & PC)
     */
    public function report(Request $request): View
    {
        $user = Auth::user();
        $penthongs = Penthong::latest('tahun')->latest('bulan')->get();
        
        $selectedPenthongId = $request->input('penthong_id', $penthongs->first()?->id);
        $selectedPenthong = $penthongs->firstWhere('id', $selectedPenthongId) ?? Penthong::getActive();

        $setting = DistributionSetting::getActive();
        $distributions = collect();
        $split = [];

        if ($selectedPenthong) {
            return $this->show($selectedPenthong, $request);
        }

        $mwcs = Mwc::orderBy('code')->get();
        return view('penthongs.report', compact('penthongs', 'selectedPenthong', 'distributions', 'split', 'setting', 'mwcs'));
    }
}
