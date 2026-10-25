<?php

namespace App\Http\Controllers\Admin;

use App\Exports\OnlineDonationsExport;
use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\OnlineDonation;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;

class OnlineDonationAdminController extends Controller
{
    /**
     * Membangun kueri donasi online dengan filter lengkap:
     * - Jenis / Kategori Donasi (Zakat, Infaq, atau kampanye spesifik)
     * - Periode Bulan (Format: YYYY-MM)
     * - Status Pembayaran
     * - Program Kampanye
     * - Pencarian Teks (Kode, Donatur, HP, Alamat, dll)
     */
    protected function buildFilteredQuery(Request $request)
    {
        $query = OnlineDonation::with(['campaign', 'verifier']);

        // 1. Filter Status Pembayaran
        if ($request->filled('payment_status')) {
            $query->where('payment_status', $request->payment_status);
        }

        // 2. Filter Program Kampanye Spesifik
        if ($request->filled('campaign_id')) {
            $query->where('campaign_id', $request->campaign_id);
        }

        // 3. Filter Jenis / Kategori Donasi
        if ($request->filled('kategori')) {
            $kat = strtolower(trim($request->kategori));
            if ($kat === 'zakat') {
                $query->where(function ($w) {
                    $w->whereRaw('LOWER(kategori) = ?', ['zakat'])
                      ->orWhereHas('campaign', function ($c) {
                          $c->whereRaw('LOWER(kategori_judul) = ?', ['zakat']);
                      });
                });
            } elseif ($kat === 'infaq' || $kat === 'infaq nu') {
                $query->where(function ($w) {
                    $w->whereRaw('LOWER(kategori) LIKE ?', ['%infaq%'])
                      ->orWhereNull('kategori')
                      ->orWhere('kategori', '')
                      ->orWhereHas('campaign', function ($c) {
                          $c->whereRaw('LOWER(kategori_judul) LIKE ?', ['%infaq%']);
                      });
                });
            } else {
                $rawKat = trim($request->kategori);
                $query->where(function ($w) use ($rawKat) {
                    $w->where('kategori', $rawKat)
                      ->orWhereHas('campaign', function ($c) use ($rawKat) {
                          $c->where('kategori_judul', $rawKat)
                            ->orWhere('title', $rawKat);
                      });
                });
            }
        }

        // 4. Filter Tiap Bulan (Format: YYYY-MM)
        if ($request->filled('month')) {
            $monthStr = trim($request->month);
            $parts = explode('-', $monthStr);
            if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
                $query->whereYear('created_at', (int) $parts[0])
                      ->whereMonth('created_at', (int) $parts[1]);
            }
        }

        // 5. Pencarian Teks Bebas
        if ($request->filled('q')) {
            $q = trim($request->q);
            $query->where(function ($w) use ($q) {
                $w->where('donor_name', 'like', "%{$q}%")
                  ->orWhere('donation_code', 'like', "%{$q}%")
                  ->orWhere('donor_phone', 'like', "%{$q}%")
                  ->orWhere('alamat', 'like', "%{$q}%")
                  ->orWhereHas('campaign', function ($c) use ($q) {
                      $c->where('title', 'like', "%{$q}%")
                        ->orWhere('kategori_judul', 'like', "%{$q}%");
                  });
            });
        }

        return $query;
    }

    /**
     * Menyusun ringkasan label filter untuk subheader PDF dan file Excel
     */
    protected function resolveFilterLabels(Request $request): array
    {
        $parts = [];

        // Jenis Donasi
        $jenisLabel = 'Semua Jenis Donasi';
        if ($request->filled('kategori')) {
            $kat = trim($request->kategori);
            $jenisLabel = (strtolower($kat) === 'zakat') ? 'Zakat' : ((strtolower($kat) === 'infaq') ? 'Infaq / Shadaqah' : $kat);
            $parts[] = "Jenis: " . $jenisLabel;
        } elseif ($request->filled('campaign_id')) {
            $camp = Campaign::find($request->campaign_id);
            $jenisLabel = $camp ? ($camp->kategori_judul ?: $camp->title) : 'Program Terpilih';
            $parts[] = "Program: " . $jenisLabel;
        } else {
            $parts[] = "Jenis: Semua Donasi";
        }

        // Periode Bulan
        $bulanLabel = 'Semua Bulan (Akumulasi Data)';
        if ($request->filled('month')) {
            $monthStr = trim($request->month);
            $p = explode('-', $monthStr);
            if (count($p) === 2) {
                try {
                    $bulanLabel = Carbon::createFromDate((int) $p[0], (int) $p[1], 1)->translatedFormat('F Y');
                    $parts[] = "Periode: " . $bulanLabel;
                } catch (\Throwable $e) {
                    $bulanLabel = $monthStr;
                    $parts[] = "Periode: " . $monthStr;
                }
            }
        } else {
            $parts[] = "Periode: Semua Bulan";
        }

        // Status Pembayaran
        $statusLabel = 'Semua Status Pembayaran';
        if ($request->filled('payment_status')) {
            $statusLabel = match ($request->payment_status) {
                'verified' => 'Sah / Terverifikasi',
                'pending' => 'Pending (Menunggu Konfirmasi)',
                'cancelled' => 'Dibatalkan',
                default => ucfirst((string) $request->payment_status),
            };
            $parts[] = "Status: " . $statusLabel;
        }

        if ($request->filled('q')) {
            $parts[] = 'Pencarian: "' . $request->q . '"';
        }

        return [
            'jenis_donasi' => $jenisLabel,
            'bulan'        => $bulanLabel,
            'status'       => $statusLabel,
            'summary_text' => implode(' | ', $parts),
        ];
    }

    public function index(Request $request)
    {
        $query = $this->buildFilteredQuery($request);

        // Pengaturan Paging (10 baris, 50 baris, seluruh baris)
        $perPage = $request->input('per_page', '10');
        $totalMatching = (clone $query)->count();

        if ($perPage === 'all' || $perPage === 'semua' || $perPage === '-1') {
            $perPageNum = max(1, $totalMatching);
        } else {
            $perPageNum = in_array((int) $perPage, [10, 50]) ? (int) $perPage : 10;
        }

        $donations = $query->orderByDesc('created_at')->paginate($perPageNum);
        $campaigns = Campaign::orderBy('title')->get();

        // Kategori / Jenis Donasi yang tersedia
        $dbCategories = OnlineDonation::query()
            ->whereNotNull('kategori')
            ->where('kategori', '!=', '')
            ->distinct()
            ->pluck('kategori')
            ->map(fn($c) => trim($c))
            ->unique()
            ->values();

        $coreCategories = collect(['Zakat', 'Infaq']);
        $availableCategories = $coreCategories->merge($dbCategories)
            ->unique(fn($item) => strtolower($item))
            ->values();

        // Daftar Periode Bulan yang tersedia (12 bulan tahun berjalan + bulan riil transaksi)
        $monthsList = collect();
        for ($m = 1; $m <= 12; $m++) {
            $dt = now()->startOfYear()->month($m);
            $monthsList->put($dt->format('Y-m'), $dt->translatedFormat('F Y'));
        }
        OnlineDonation::query()
            ->select('created_at')
            ->whereNotNull('created_at')
            ->orderByDesc('created_at')
            ->get()
            ->each(function ($d) use ($monthsList) {
                if ($d->created_at) {
                    $key = $d->created_at->format('Y-m');
                    if (!$monthsList->has($key)) {
                        $monthsList->put($key, $d->created_at->translatedFormat('F Y'));
                    }
                }
            });
        $availableMonths = $monthsList->sortKeysDesc();

        $stats = [
            'total_verified' => OnlineDonation::where('payment_status', 'verified')->sum('total_amount'),
            'count_verified' => OnlineDonation::where('payment_status', 'verified')->count(),
            'count_pending'  => OnlineDonation::where('payment_status', 'pending')->count(),
        ];

        return view('admin.online_donations.index', compact(
            'donations',
            'campaigns',
            'stats',
            'availableCategories',
            'availableMonths',
            'perPage'
        ));
    }

    /**
     * Ekspor Data Donasi Online ke Format Excel (.xlsx) dengan Filter Aktif atau Seluruh Data
     */
    public function exportExcel(Request $request)
    {
        $query = $this->buildFilteredQuery($request);
        $donations = $query->orderByDesc('created_at')->get();
        $filterInfo = $this->resolveFilterLabels($request);

        $filename = 'Laporan_Donasi_Online_LAZISNU_' . date('Ymd_His') . '.xlsx';

        return Excel::download(new OnlineDonationsExport($donations, $filterInfo), $filename);
    }

    /**
     * Ekspor Data Donasi Online ke Format Dokumen PDF Cetak Resmi dengan Kop Surat LAZISNU
     */
    public function exportPdf(Request $request)
    {
        $query = $this->buildFilteredQuery($request);
        $donations = $query->orderByDesc('created_at')->get();

        $filterInfo = $this->resolveFilterLabels($request);
        $user = Auth::user();
        $signerName = $user?->name ?? 'Pengurus PC LAZISNU Lamongan';
        $printedAt = now()->translatedFormat('d F Y, H:i') . ' WIB';

        $totalNominal = $donations->sum('total_amount');
        $totalVerifiedNominal = $donations->where('payment_status', 'verified')->sum('total_amount');
        $totalCount = $donations->count();

        $viewData = compact(
            'donations',
            'filterInfo',
            'signerName',
            'printedAt',
            'totalNominal',
            'totalVerifiedNominal',
            'totalCount'
        );

        return response()->view('admin.online_donations.export_pdf', $viewData);
    }

    public function show(OnlineDonation $donation)
    {
        $donation->load(['campaign', 'verifier']);
        return view('admin.online_donations.show', compact('donation'));
    }

    public function verify(Request $request, OnlineDonation $donation, \App\Services\DonationVerificationService $service)
    {
        $nominal = $request->filled('nominal_terverifikasi')
            ? (float) $request->nominal_terverifikasi
            : null;

        try {
            $result = $service->verifyByCode($donation->donation_code, $nominal);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'               => true,
                    'status'                => 'verified',
                    'kode_donasi'           => $donation->donation_code,
                    'nominal_terverifikasi' => $result['nominal_terverifikasi'],
                    'message'               => $result['message'],
                ]);
            }

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function cancel(Request $request, OnlineDonation $donation, \App\Services\DonationVerificationService $service)
    {
        try {
            $result = $service->cancelByCode($donation->donation_code);

            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success'     => true,
                    'status'      => 'cancelled',
                    'kode_donasi' => $donation->donation_code,
                    'message'     => $result['message'],
                ]);
            }

            return back()->with('success', $result['message']);
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], 422);
            }

            return back()->with('error', $e->getMessage());
        }
    }

    public function destroy(OnlineDonation $donation)
    {
        if ($donation->payment_status === 'verified') {
            $donation->campaign->decrement('collected_amount', $donation->amount);
        }

        $donation->delete();

        return redirect()->route('admin.online-donations.index')->with('success', 'Catatan donasi berhasil dihapus.');
    }
}
