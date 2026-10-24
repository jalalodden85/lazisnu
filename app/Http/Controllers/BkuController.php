<?php

namespace App\Http\Controllers;

use App\Models\BkuTransaction;
use App\Models\KasMutasi;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Services\BkuService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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

        /** @var \App\Models\User|null $user */
        $user = Auth::user();

        // Resolusi Entitas Wilayah untuk Penandatangan & Label Laporan
        $activeRanting = null;
        $activeMwc = null;

        if (!empty($filters['ranting_id'])) {
            $activeRanting = Ranting::with('mwc')->find($filters['ranting_id']);
            $activeMwc = $activeRanting?->mwc;
        } elseif (!empty($filters['mwc_id'])) {
            $activeMwc = Mwc::find($filters['mwc_id']);
        } elseif ($user && $user->isRanting()) {
            $activeRanting = $user->ranting;
            $activeMwc = $activeRanting?->mwc ?? $user->mwc;
        } elseif ($user && $user->isMwc()) {
            $activeMwc = $user->mwc;
        }

        $tanggalCetak = $request->input('tanggal_cetak', now()->translatedFormat('d F Y'));

        // Konfigurasi 3 Penandatangan Otomatis Berdasarkan Data Entri (Kiri: Ketua Tanfidz, Tengah: Ketua Lazisnu/UPZIS, Kanan: Bendahara)
        if ($activeRanting) {
            $wilayahLabel = 'JPZIS Ranting ' . ($activeRanting->name ?? '');
            $tingkatWilayah = 'Ranting ' . ($activeRanting->name ?? '');
            $tempatCetak = $activeRanting->desa ?: $activeRanting->name;
            $signatories = [
                'level'       => 'ranting',
                'place'       => $tempatCetak,
                'kiri'        => [
                    'label'   => 'Mengetahui,',
                    'jabatan' => 'Ketua Tanfidziyah Ranting NU',
                    'nama'    => $request->input('nama_ketua_tanfidz', $activeRanting->nama_ketua_ranting ?: '....................................'),
                    'sub'     => 'PR Nahdlatul Ulama ' . ($activeRanting->desa ?: $activeRanting->name),
                ],
                'tengah'      => [
                    'label'   => 'Pimpinan Lembaga,',
                    'jabatan' => 'Ketua UPZIS Ranting',
                    'nama'    => $request->input('nama_ketua_lazisnu', $activeRanting->nama_ketua_upzis ?: ($activeRanting->pj_name ?: '....................................')),
                    'sub'     => 'UPZIS NU CARE - LAZISNU',
                ],
                'kanan'       => [
                    'label'   => 'Pemeriksa / Keuangan,',
                    'jabatan' => 'Bendahara UPZIS Ranting',
                    'nama'    => $request->input('nama_bendahara', $activeRanting->nama_bendahara ?: '....................................'),
                    'sub'     => 'Divisi Keuangan UPZIS',
                ],
            ];
        } elseif ($activeMwc) {
            $wilayahLabel = 'JPZIS MWC ' . ($activeMwc->name ?? '');
            $tingkatWilayah = 'MWC ' . ($activeMwc->name ?? '');
            $tempatCetak = $activeMwc->kecamatan ?: $activeMwc->name;
            $signatories = [
                'level'       => 'mwc',
                'place'       => $tempatCetak,
                'kiri'        => [
                    'label'   => 'Mengetahui,',
                    'jabatan' => 'Ketua Tanfidziyah MWC NU',
                    'nama'    => $request->input('nama_ketua_tanfidz', $activeMwc->nama_ketua_mwc ?: '....................................'),
                    'sub'     => 'MWCNU ' . ($activeMwc->kecamatan ?: $activeMwc->name),
                ],
                'tengah'      => [
                    'label'   => 'Pimpinan Lembaga,',
                    'jabatan' => 'Ketua LAZISNU MWC',
                    'nama'    => $request->input('nama_ketua_lazisnu', $activeMwc->nama_ketua_lazisnu ?: '....................................'),
                    'sub'     => 'NU CARE - LAZISNU MWC',
                ],
                'kanan'       => [
                    'label'   => 'Pemeriksa / Keuangan,',
                    'jabatan' => 'Bendahara UPZIS MWC',
                    'nama'    => $request->input('nama_bendahara', $activeMwc->nama_bendahara ?: '....................................'),
                    'sub'     => 'Divisi Keuangan MWC',
                ],
            ];
        } else {
            $wilayahLabel = 'JPZIS LAZISNU Lamongan';
            $tingkatWilayah = 'PCNU Lamongan';
            $tempatCetak = 'Lamongan';
            $signatories = [
                'level'       => 'pc',
                'place'       => $tempatCetak,
                'kiri'        => [
                    'label'   => 'Mengetahui,',
                    'jabatan' => 'Ketua Tanfidziyah PCNU Lamongan',
                    'nama'    => $request->input('nama_ketua_tanfidz', 'Dr. H. Supandi, S.Pd., M.Pd.'),
                    'sub'     => 'Pengurus Cabang Nahdlatul Ulama',
                ],
                'tengah'      => [
                    'label'   => 'Pimpinan Lembaga,',
                    'jabatan' => 'Ketua PC NU CARE - LAZISNU',
                    'nama'    => $request->input('nama_ketua_lazisnu', $request->input('nama_ketua', 'Drs. H. Suharjono, M.Si')),
                    'sub'     => 'NU CARE - LAZISNU Lamongan',
                ],
                'kanan'       => [
                    'label'   => 'Pemeriksa / Keuangan,',
                    'jabatan' => 'Bendahara PC LAZISNU',
                    'nama'    => $request->input('nama_bendahara', 'H. Agus Salim, S.E.'),
                    'sub'     => 'Divisi Keuangan & Pembukuan',
                ],
            ];
        }

        $namaKetuaTanfidz = $signatories['kiri']['nama'];
        $namaKetuaLazisnu = $signatories['tengah']['nama'];
        $namaKetua        = $namaKetuaLazisnu;
        $namaBendahara    = $signatories['kanan']['nama'];

        $bulan = $bkuData['bulan'];
        $tahun = $bkuData['tahun'];

        return view('bku.cetak', compact(
            'bkuData',
            'bulan',
            'tahun',
            'filters',
            'wilayahLabel',
            'tingkatWilayah',
            'activeRanting',
            'activeMwc',
            'signatories',
            'namaKetuaTanfidz',
            'namaKetuaLazisnu',
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

    /**
     * Menyimpan data pengeluaran BKU baru dengan validasi anti-defisit kas dan upload bukti kwitansi
     */
    public function storePengeluaran(Request $request): RedirectResponse
    {
        $user = Auth::user();

        // 1. Validasi Input Form
        $request->validate([
            'kategori'           => 'required|string|in:Pendidikan,Kesehatan,Ekonomi,Sosial,Dakwah,Biaya Operasional',
            'tanggal'            => 'required|date',
            'nominal'            => 'required',
            'penerima_vendor'    => 'nullable|string|max:150',
            'uraian'             => 'required|string|max:1000',
            'bukti_kwitansi'     => 'nullable|file|mimes:jpeg,png,jpg,webp,pdf|max:2048',
            'penthong_id'        => 'nullable|exists:penthongs,id',
            'mwc_id'             => 'nullable|exists:mwcs,id',
            'ranting_id'         => 'nullable|exists:rantings,id',
        ], [
            'kategori.required'        => 'Kategori pengeluaran wajib dipilih.',
            'kategori.in'              => 'Kategori pengeluaran tidak valid.',
            'tanggal.required'         => 'Tanggal pengeluaran wajib diisi.',
            'nominal.required'         => 'Nominal pengeluaran wajib diisi.',
            'uraian.required'          => 'Uraian keperluan belanja wajib diisi.',
            'bukti_kwitansi.max'       => 'Ukuran file bukti kwitansi maksimal 2MB.',
            'bukti_kwitansi.mimes'     => 'Format bukti kwitansi harus berupa gambar (JPG, PNG) atau PDF.',
        ]);

        // 2. Normalisasi & Konversi Nominal Ribuan
        $rawNominal = preg_replace('/[^\d]/', '', (string) $request->input('nominal'));
        $nominal = (float) $rawNominal;

        if ($nominal <= 0) {
            return back()->withInput()->with('error', 'Nominal pengeluaran harus lebih besar dari Rp 0.');
        }

        // 3. Resolusi Tingkatan & Data Scoping
        $penthongId = $request->input('penthong_id') 
            ?? Penthong::aktif()->value('id') 
            ?? Penthong::latest('id')->value('id');

        $filters = [];
        $tingkat = 'ranting';
        $mwcId = null;
        $rantingId = null;

        if ($user && $user->isRanting()) {
            $tingkat = 'ranting';
            $rantingId = (int) $user->ranting_id;
            $mwcId = (int) ($user->ranting?->mwc_id ?? $user->mwc_id);
            $filters['ranting_id'] = $rantingId;
            $filters['mwc_id'] = $mwcId;
            $filters['tingkat'] = 'ranting';
        } elseif ($user && $user->isMwc()) {
            $mwcId = (int) $user->mwc_id;
            if ($request->filled('ranting_id')) {
                $reqRantingId = (int) $request->input('ranting_id');
                $isSubordinate = Ranting::where('mwc_id', $mwcId)->where('id', $reqRantingId)->exists();
                if ($isSubordinate) {
                    $rantingId = $reqRantingId;
                    $tingkat = 'ranting';
                    $filters['ranting_id'] = $rantingId;
                } else {
                    $tingkat = 'mwc';
                }
            } else {
                $tingkat = 'mwc';
            }
            $filters['mwc_id'] = $mwcId;
            $filters['tingkat'] = $tingkat;
        } else {
            // Level PC / Superadmin
            if ($request->filled('ranting_id')) {
                $rantingId = (int) $request->input('ranting_id');
                $r = Ranting::find($rantingId);
                $mwcId = $r?->mwc_id;
                $tingkat = 'ranting';
                $filters['ranting_id'] = $rantingId;
                $filters['mwc_id'] = $mwcId;
            } elseif ($request->filled('mwc_id')) {
                $mwcId = (int) $request->input('mwc_id');
                $tingkat = 'mwc';
                $filters['mwc_id'] = $mwcId;
            } else {
                $tingkat = 'pc';
            }
            $filters['tingkat'] = $tingkat;
        }
        $filters['penthong_id'] = $penthongId;

        // 4. Validasi Backend Anti-Defisit Kas (Saldo Kas Tidak Boleh Minus)
        $bkuData = $this->bkuService->getBkuDataByPenthong($penthongId, $filters);
        $sisaSaldoAkhir = (float) ($bkuData['saldo_buku'] ?? 0);

        if ($nominal > $sisaSaldoAkhir) {
            return back()
                ->withInput()
                ->with('error', "Saldo Kas tidak mencukupi! Sisa saldo saat ini: Rp " . number_format($sisaSaldoAkhir, 0, ',', '.') . ". Nominal pengeluaran Rp " . number_format($nominal, 0, ',', '.') . " melebihi sisa saldo kas.");
        }

        // 5. Penanganan File Upload Bukti Kwitansi / Nota Struk
        $buktiPath = null;
        if ($request->hasFile('bukti_kwitansi')) {
            $file = $request->file('bukti_kwitansi');
            $extension = $file->getClientOriginalExtension();
            $fileName = 'kwitansi_' . date('Ymd_His') . '_' . uniqid() . '.' . $extension;
            $buktiPath = $file->storeAs('kwitansi', $fileName, 'public');
        }

        // 6. Mapping Kategori ke COA Standar BKU
        $coa = match ($request->input('kategori')) {
            'Pendidikan'        => 'NU Cerdas',
            'Kesehatan'         => 'NU Sehat',
            'Ekonomi'           => 'NU Berdaya',
            'Sosial'            => 'NU Damai',
            'Dakwah'            => 'NU Hijau',
            'Biaya Operasional' => 'NU-O',
            default             => 'NU-O',
        };

        // 7. Generate Nomor Transaksi Unik
        $prefix = 'BKU-OUT-' . date('Ym');
        $countToday = BkuTransaction::where('nomor_transaksi', 'like', "{$prefix}%")->count() + 1;
        $nomorTransaksi = sprintf('%s-%04d', $prefix, $countToday);

        // 8. Simpan Transaksi secara Atomic (DB::transaction)
        DB::transaction(function () use ($nomorTransaksi, $penthongId, $request, $coa, $nominal, $buktiPath, $tingkat, $mwcId, $rantingId, $user) {
            $bkuTx = BkuTransaction::create([
                'nomor_transaksi'    => $nomorTransaksi,
                'periode_pentong_id' => $penthongId,
                'jenis'              => 'pengeluaran',
                'kategori'           => $request->input('kategori'),
                'kode_transaksi'     => $coa,
                'tanggal'            => $request->input('tanggal'),
                'nominal'            => $nominal,
                'penerima_vendor'    => $request->input('penerima_vendor'),
                'uraian'             => $request->input('uraian'),
                'bukti_kwitansi'     => $buktiPath,
                'tingkat'            => $tingkat,
                'mwc_id'             => $mwcId,
                'ranting_id'         => $rantingId,
                'user_id'            => $user?->id ?? 1,
            ]);

            // Sinkronkan ke KasMutasi sebagai catatan kredit
            KasMutasi::create([
                'nomor_bukti'        => $nomorTransaksi,
                'periode_pentong_id' => $penthongId,
                'penthong_id'        => $penthongId,
                'jenis'              => 'kredit',
                'kode_transaksi'     => $coa,
                'uraian'             => $request->input('uraian') . ($request->filled('penerima_vendor') ? " ({$request->input('penerima_vendor')})" : ""),
                'nominal'            => $nominal,
                'tanggal_transaksi'  => $request->input('tanggal'),
                'tingkat'            => $tingkat,
                'ranting_id'         => $rantingId,
                'mwc_id'             => $mwcId,
                'user_id'            => $user?->id ?? 1,
                'source_type'        => BkuTransaction::class,
                'source_id'          => $bkuTx->id,
                'keterangan'         => "Pengeluaran BKU Kategori {$request->input('kategori')}",
            ]);
        });

        return back()->with('success', "Pengeluaran BKU sebesar Rp " . number_format($nominal, 0, ',', '.') . " berhasil dicatat ke dalam pembukuan kas.");
    }
}
