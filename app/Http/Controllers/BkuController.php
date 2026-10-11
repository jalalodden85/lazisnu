<?php

namespace App\Http\Controllers;

use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Services\BkuService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class BkuController extends Controller
{
    protected BkuService $bkuService;

    public function __construct(BkuService $bkuService)
    {
        $this->bkuService = $bkuService;
    }

    /**
     * Resolusi parameter filter dengan Data Scoping Ketat (Multi-Tenancy Lite)
     */
    protected function resolveFilters(Request $request): array
    {
        $user = Auth::user();
        $filters = [];

        // 1. ISOLASI DATA BERJENJANG (DATA SCOPING)
        if ($user && $user->isRanting()) {
            // Admin Ranting: TERKUNCI MUTLAK pada ranting_id miliknya
            $filters['ranting_id'] = (int) $user->ranting_id;
            $filters['mwc_id']     = (int) ($user->ranting?->mwc_id ?? $user->mwc_id);
            $filters['tingkat']    = 'ranting';
        } elseif ($user && $user->isMwc()) {
            // Admin MWC: Terkunci pada MWC miliknya, hanya dapat memilih ranting di wilayahnya
            $filters['mwc_id']  = (int) $user->mwc_id;
            $filters['tingkat'] = $request->input('tingkat', 'all');

            if ($request->filled('ranting_id')) {
                $requestedRantingId = (int) $request->input('ranting_id');
                // Validasi kepemilikan ranting di bawah MWC ini
                $isSubordinate = Ranting::where('mwc_id', $user->mwc_id)
                    ->where('id', $requestedRantingId)
                    ->exists();

                if ($isSubordinate) {
                    $filters['ranting_id'] = $requestedRantingId;
                }
            }
        } else {
            // Level Cabang (PC / Super Admin): Bebas memilih filter
            if ($request->filled('mwc_id')) {
                $filters['mwc_id'] = (int) $request->input('mwc_id');
            }
            if ($request->filled('ranting_id')) {
                $filters['ranting_id'] = (int) $request->input('ranting_id');
            }
            if ($request->filled('tingkat')) {
                $filters['tingkat'] = $request->input('tingkat');
            }
        }

        // 2. TAMPILAN DEFAULT & FILTER PERIODE PENTONG
        // Jika tidak ada periode yang dipilih via GET parameter, otomatis deteksi periode yang AKTIF (OPEN)
        if ($request->filled('penthong_id')) {
            $filters['penthong_id'] = (int) $request->input('penthong_id');
        } else {
            $activePenthong = Penthong::aktif()->first() 
                ?? Penthong::latest('id')->first();
            if ($activePenthong) {
                $filters['penthong_id'] = $activePenthong->id;
            }
        }

        return $filters;
    }

    /**
     * Tampilan Dashboard Buku Kas Umum (BKU) T-Account
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $filters = $this->resolveFilters($request);

        $selectedPenthongId = $filters['penthong_id'] ?? null;
        $bkuData = $this->bkuService->getBkuDataByPenthong($selectedPenthongId, $filters);

        // Data Master untuk Form Filter
        $penthongs = Penthong::orderByDesc('tahun')->orderByDesc('bulan')->get();
        $currentPenthong = $bkuData['penthong'];

        $mwcs = ($user && $user->isPc()) ? Mwc::orderBy('code')->get() : collect();
        
        $selectedMwcId = $filters['mwc_id'] ?? null;
        $rantings = collect();
        if ($user && $user->isMwc()) {
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        } elseif ($selectedMwcId) {
            $rantings = Ranting::where('mwc_id', $selectedMwcId)->orderBy('name')->get();
        }

        // Label Wilayah BKU
        $wilayahLabel = 'PC LAZISNU Kab. Lamongan';
        if (!empty($filters['ranting_id'])) {
            $r = Ranting::find($filters['ranting_id']);
            $wilayahLabel = 'Ranting ' . ($r?->name ?? '');
        } elseif (!empty($filters['mwc_id'])) {
            $m = Mwc::find($filters['mwc_id']);
            $wilayahLabel = 'MWC ' . ($m?->name ?? '');
        }

        $bulan = $bkuData['bulan'];
        $tahun = $bkuData['tahun'];

        return view('bku.index', compact(
            'bkuData',
            'currentPenthong',
            'selectedPenthongId',
            'bulan',
            'tahun',
            'filters',
            'mwcs',
            'rantings',
            'penthongs',
            'wilayahLabel'
        ));
    }

    /**
     * Halaman Cetak / Print Preview BKU Format Standar T-Account
     */
    public function cetak(Request $request): View
    {
        $filters = $this->resolveFilters($request);
        $selectedPenthongId = $filters['penthong_id'] ?? null;
        $bkuData = $this->bkuService->getBkuDataByPenthong($selectedPenthongId, $filters);

        // Label Wilayah
        $wilayahLabel = 'JPZIS LAZISNU Lamongan';
        if (!empty($filters['ranting_id'])) {
            $r = Ranting::find($filters['ranting_id']);
            $wilayahLabel = 'JPZIS Ranting ' . ($r?->name ?? '');
        } elseif (!empty($filters['mwc_id'])) {
            $m = Mwc::find($filters['mwc_id']);
            $wilayahLabel = 'JPZIS MWC ' . ($m?->name ?? '');
        }

        $namaKetua = $request->input('nama_ketua', 'Drs. H. M. Syukrillah');
        $namaBendahara = $request->input('nama_bendahara', 'H. Agus Salim, S.E.');
        $tanggalCetak = $request->input('tanggal_cetak', now()->translatedFormat('d F Y'));

        $bulan = $bkuData['bulan'];
        $tahun = $bkuData['tahun'];

        return view('bku.cetak', compact(
            'bkuData',
            'bulan',
            'tahun',
            'filters',
            'wilayahLabel',
            'namaKetua',
            'namaBendahara',
            'tanggalCetak'
        ));
    }

    /**
     * Ekspor Data BKU ke CSV / Spreadsheet
     */
    public function exportExcel(Request $request): Response
    {
        $filters = $this->resolveFilters($request);
        $selectedPenthongId = $filters['penthong_id'] ?? null;
        $bkuData = $this->bkuService->getBkuDataByPenthong($selectedPenthongId, $filters);

        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="BKU_KOIN_NU_' . str_replace(' ', '_', $bkuData['periode_title']) . '.csv"',
            'Pragma'              => 'no-cache',
            'Cache-Control'       => 'must-revalidate, post-check=0, pre-check=0',
            'Expires'             => '0',
        ];

        $callback = function () use ($bkuData) {
            $file = fopen('php://output', 'w');
            fprintf($file, chr(0xEF) . chr(0xBB) . chr(0xBF));

            fputcsv($file, ['BUKU KAS UMUM (BKU) T-ACCOUNT - LAZISNU KABUPATEN LAMONGAN']);
            fputcsv($file, ['PERIODE: ' . $bkuData['periode_title']]);
            fputcsv($file, []);

            fputcsv($file, [
                'NO', 'TGL MASUK', 'KODE', 'URAIAN PEMASUKAN', 'DEBET (Rp)',
                'TGL KELUAR', 'KODE', 'URAIAN PENGELUARAN', 'KREDIT (Rp)'
            ]);

            $no = 1;
            foreach ($bkuData['t_account_rows'] as $row) {
                $debet  = $row['debet'];
                $kredit = $row['kredit'];

                fputcsv($file, [
                    $no++,
                    $debet ? $debet['tanggal'] : '',
                    $debet ? $debet['kode'] : '',
                    $debet ? $debet['uraian'] : '',
                    $debet ? $debet['jumlah'] : '',
                    $kredit ? $kredit['tanggal'] : '',
                    $kredit ? $kredit['kode'] : '',
                    $kredit ? $kredit['uraian'] : '',
                    $kredit ? $kredit['jumlah'] : '',
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, [
                '', '', 'TOTAL PEMASUKAN', '', $bkuData['total_pemasukan_bulan_ini'],
                '', '', 'TOTAL PENGELUARAN', $bkuData['total_pengeluaran']
            ]);
            fputcsv($file, [
                '', '', 'JUMLAH (SALDO AWAL + IN)', '', $bkuData['jumlah_pemasukan'],
                '', '', 'SALDO BUKU (SISA KAS)', $bkuData['saldo_buku']
            ]);
            fputcsv($file, [
                '', '', 'TOTAL SEIMBANG', '', $bkuData['jumlah_pemasukan'],
                '', '', 'TOTAL SEIMBANG', $bkuData['total_seimbang_kanan']
            ]);

            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }
}
