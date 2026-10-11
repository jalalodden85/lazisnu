<?php

namespace App\Http\Controllers;

use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Services\LaporanCetakService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class LaporanCetakController extends Controller
{
    public function __construct(
        protected LaporanCetakService $laporanCetakService
    ) {}

    /**
     * Endpoint Cetak Laporan Perolehan KOIN NU dengan Pembatasan Berjenjang & Pejabat Penandatangan
     * Mendukung format PDF (Print-Ready View), Excel (.xlsx), dan JSON Data
     */
    public function cetak(Request $request): Response
    {
        $user = Auth::user();
        if (!$user) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Silakan login terlebih dahulu.',
                ], 401);
            }
            return redirect()->route('login');
        }

        // 1. Validasi Keamanan & Resolusi Hirarki Wilayah (Backend Security Rule)
        try {
            $scope = $this->laporanCetakService->validateAndResolveScope($user, $request->all());
        } catch (HttpException $e) {
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $e->getMessage(),
                ], $e->getStatusCode());
            }
            abort($e->getStatusCode(), $e->getMessage());
        }

        // 2. Resolusi Periode Pentong
        $penthongId = $request->input('penthong_id', $request->input('periode_id'));
        if ($penthongId && $penthongId !== 'all') {
            $penthong = Penthong::find((int) $penthongId);
        } else {
            $penthong = Penthong::getActive($scope['mwc_id'], $scope['ranting_id'])
                ?? Penthong::latest('tahun')->latest('bulan')->latest('id')->first();
        }

        if (!$penthong) {
            $errorMsg = 'Data periode pentong tidak ditemukan.';
            if ($request->wantsJson() || $request->is('api/*')) {
                return response()->json(['success' => false, 'message' => $errorMsg], 404);
            }
            return back()->with('error', $errorMsg);
        }

        // 3. Resolusi Pejabat Penandatangan dari Form Modal / Request
        $signatures = $this->laporanCetakService->resolveSignatures($scope, $request->all());

        // 4. Query Data Transaksi yang Sesuai Hak Akses
        $transactions = $this->laporanCetakService->queryTransactions($penthong, $scope);
        $totalNominal = $transactions->sum('amount');
        $totalCount   = $transactions->count();

        $format = strtolower($request->input('format', ''));
        if ($request->wantsJson() && $format !== 'pdf' && $format !== 'excel') {
            $format = 'json';
        }

        // 5. Output Format Sesuai Permintaan
        // A. Format JSON (untuk integrasi API atau headless frontend)
        if ($format === 'json') {
            return response()->json([
                'success' => true,
                'penthong' => [
                    'id'           => $penthong->id,
                    'nama_periode' => $penthong->nama_periode,
                    'bulan'        => $penthong->bulan,
                    'bulan_nama'   => $penthong->bulan_nama,
                    'tahun'        => $penthong->tahun,
                    'status'       => $penthong->status,
                    'is_open'      => $penthong->isOpen(),
                ],
                'scope' => [
                    'level'         => $scope['scope_level'],
                    'wilayah_label' => $scope['wilayah_label'],
                    'mwc_id'        => $scope['mwc_id'],
                    'ranting_id'    => $scope['ranting_id'],
                ],
                'kop' => $scope['kop'],
                'signatures' => $signatures,
                'summary' => [
                    'total_nominal'    => (float) $totalNominal,
                    'total_transaksi'  => $totalCount,
                    'formatted_total'  => 'Rp ' . number_format($totalNominal, 0, ',', '.'),
                ],
                'data' => $transactions->map(function ($tx, $idx) {
                    return [
                        'no'              => $idx + 1,
                        'invoice_code'    => $tx->invoice_code,
                        'tanggal_setor'   => $tx->collection_date ? $tx->collection_date->format('Y-m-d') : null,
                        'tanggal_format'  => $tx->collection_date ? $tx->collection_date->format('d/m/Y') : '-',
                        'ranting_name'    => $tx->ranting->name ?? '-',
                        'mwc_name'        => $tx->mwc->name ?? '-',
                        'nama_munfiq'     => $tx->relawan->nama ?? '-',
                        'petugas_plk'     => $tx->relawan->nama ?? '-',
                        'nominal'         => (float) $tx->amount,
                        'nominal_rp'      => 'Rp ' . number_format($tx->amount, 0, ',', '.'),
                        'keterangan'      => $tx->notes ?: ($tx->status === 'confirmed' ? 'Terkonfirmasi' : $tx->status),
                    ];
                }),
            ]);
        }

        // B. Format Excel (.xlsx) Menggunakan PhpSpreadsheet
        if ($format === 'excel') {
            return $this->laporanCetakService->exportExcel($penthong, $transactions, $scope, $signatures);
        }

        // C. Format PDF / Print-Ready HTML View
        $dateRange = $penthong->start_date && $penthong->end_date
            ? Carbon::parse($penthong->start_date)->format('d/m/Y') . ' - ' . Carbon::parse($penthong->end_date)->format('d/m/Y')
            : "Bulan {$penthong->bulan_nama} {$penthong->tahun}";

        $printedAt = Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB';
        $wilayahLabel = $scope['wilayah_label'];
        $kop = $scope['kop'];

        return response()->view('penthongs.export_pdf', compact(
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
     * Endpoint API pembantu untuk mendapatkan opsi filter wilayah & tanda tangan default sesuai role pengguna
     */
    public function scopeOptions(Request $request): JsonResponse
    {
        $user = Auth::user();
        if (!$user) {
            return response()->json(['success' => false, 'message' => 'Unauthenticated'], 401);
        }

        $allPenthongs = Penthong::orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('id')->get();
        $activePenthong = Penthong::getActive($user->mwc_id, $user->ranting_id);

        $data = [
            'user' => [
                'id'       => $user->id,
                'name'     => $user->name,
                'role'     => $user->role?->name,
                'is_pc'    => $user->isPc(),
                'is_mwc'   => $user->isMwc(),
                'is_ranting' => $user->isRanting(),
            ],
            'penthongs' => $allPenthongs->map(fn($p) => [
                'id'           => $p->id,
                'nama_periode' => $p->nama_periode,
                'bulan_nama'   => $p->bulan_nama,
                'tahun'        => $p->tahun,
                'status'       => $p->status,
                'is_active'    => $p->isOpen(),
            ]),
            'active_penthong_id' => $activePenthong?->id,
        ];

        if ($user->isRanting()) {
            $ranting = $user->ranting;
            $mwc = $ranting?->mwc;
            $data['mode'] = 'locked_ranting';
            $data['locked_mwc'] = $mwc ? ['id' => $mwc->id, 'name' => $mwc->name, 'code' => $mwc->code] : null;
            $data['locked_ranting'] = $ranting ? ['id' => $ranting->id, 'name' => $ranting->name, 'code' => $ranting->code] : null;
            $data['defaults'] = $this->laporanCetakService->resolveDefaultSignatories('ranting', $mwc, $ranting);
        } elseif ($user->isMwc()) {
            $mwc = $user->mwc;
            $rantings = $mwc ? $mwc->rantings()->orderBy('name')->get() : collect();
            $data['mode'] = 'scoped_mwc';
            $data['locked_mwc'] = $mwc ? ['id' => $mwc->id, 'name' => $mwc->name, 'code' => $mwc->code] : null;
            $data['rantings'] = $rantings->map(fn($r) => ['id' => $r->id, 'name' => $r->name, 'code' => $r->code]);
            $data['defaults'] = $this->laporanCetakService->resolveDefaultSignatories('mwc', $mwc, null);
        } else {
            $mwcs = Mwc::orderBy('name')->get();
            $rantings = Ranting::with('mwc')->orderBy('name')->get();
            $data['mode'] = 'full_pc';
            $data['mwcs'] = $mwcs->map(fn($m) => ['id' => $m->id, 'name' => $m->name, 'code' => $m->code]);
            $data['rantings'] = $rantings->map(fn($r) => [
                'id'     => $r->id,
                'name'   => $r->name,
                'code'   => $r->code,
                'mwc_id' => $r->mwc_id,
            ]);
            $data['defaults'] = $this->laporanCetakService->resolveDefaultSignatories('pc');
        }

        return response()->json([
            'success' => true,
            'data'    => $data,
        ]);
    }
}
