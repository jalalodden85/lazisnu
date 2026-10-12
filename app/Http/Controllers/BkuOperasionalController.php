<?php

namespace App\Http\Controllers;

use App\Models\BkuOperasional;
use App\Models\KategoriOperasional;
use App\Models\Mwc;
use App\Models\Penthong;
use App\Models\Ranting;
use App\Models\User;
use App\Services\OperasionalWalletService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class BkuOperasionalController extends Controller
{
    protected OperasionalWalletService $walletService;

    public function __construct(OperasionalWalletService $walletService)
    {
        $this->walletService = $walletService;
    }

    /**
     * Tampilan Buku Kas Umum (BKU) Operasional / Hak Amil
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();
        $tingkatParam = $request->input('tingkat');
        $tingkat = $tingkatParam ?: ($user->isRanting() ? 'ranting' : ($user->isMwc() ? 'mwc' : 'pc'));

        $query = BkuOperasional::with(['kategori', 'penthong', 'ranting', 'mwc', 'creator'])
            ->forUser($user, $tingkatParam)
            ->latest('tanggal_transaksi')
            ->latest('id');

        // Filter Rentang Tanggal
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_transaksi', [$request->input('start_date'), $request->input('end_date')]);
        } elseif ($request->filled('start_date')) {
            $query->where('tanggal_transaksi', '>=', $request->input('start_date'));
        } elseif ($request->filled('end_date')) {
            $query->where('tanggal_transaksi', '<=', $request->input('end_date'));
        }

        // Filter Periode Pentong
        if ($request->filled('penthong_id')) {
            $query->where('periode_pentong_id', (int) $request->input('penthong_id'));
        }

        // Filter Kategori COA
        if ($request->filled('kategori_id')) {
            $query->where('kategori_operasional_id', (int) $request->input('kategori_id'));
        }

        // Filter Jenis (Debet / Kredit)
        if ($request->filled('jenis')) {
            $query->where('jenis', $request->input('jenis'));
        }

        // Filter Wilayah untuk PC & MWC
        if ($user->isPc()) {
            if ($request->filled('mwc_id')) {
                $query->where('mwc_id', (int) $request->input('mwc_id'));
            }
            if ($request->filled('ranting_id')) {
                $query->where('tingkat', 'ranting')->where('ranting_id', (int) $request->input('ranting_id'));
            }
        } elseif ($user->isMwc()) {
            if ($request->filled('ranting_id')) {
                $query->where('tingkat', 'ranting')->where('ranting_id', (int) $request->input('ranting_id'));
            }
        }

        // Stat query dibuat presisi sama persis dengan query aktif (clone)
        $baseStatQuery = clone $query;

        $mutasis = $query->paginate(25)->withQueryString();

        $totalPemasukan   = (float) (clone $baseStatQuery)->where('jenis', 'debet')->sum('nominal');
        $totalPengeluaran = (float) (clone $baseStatQuery)->where('jenis', 'kredit')->sum('nominal');
        $sisaSaldo        = max(0, $totalPemasukan - $totalPengeluaran);

        // Data pendukung form & filter
        $kategoris = KategoriOperasional::where('is_active', true)->orderBy('sort_order')->get();
        $penthongs = Penthong::latest('id')->take(12)->get();
        $mwcs = $user->isPc() ? Mwc::orderBy('name')->get() : collect();
        $rantings = collect();

        if ($user->isPc()) {
            if ($request->filled('mwc_id')) {
                $rantings = Ranting::where('mwc_id', (int) $request->input('mwc_id'))->orderBy('name')->get();
            } else {
                $rantings = Ranting::orderBy('name')->get();
            }
        } elseif ($user->isMwc()) {
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        }

        return view('bku_operasional.index', compact(
            'mutasis',
            'totalPemasukan',
            'totalPengeluaran',
            'sisaSaldo',
            'kategoris',
            'penthongs',
            'mwcs',
            'rantings',
            'tingkat',
            'user'
        ));
    }

    /**
     * Simpan Pengeluaran Operasional Baru (Beli ATK, Bisyaroh PLK, Rapat)
     */
    public function storeExpense(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'kategori_operasional_id' => 'required|exists:kategori_operasionals,id',
            'periode_pentong_id'      => 'nullable|exists:penthongs,id',
            'nominal'                 => 'required|numeric|min:1000',
            'tanggal_transaksi'       => 'required|date',
            'uraian'                  => 'required|string|max:255',
            'penerima_atau_vendor'    => 'nullable|string|max:150',
            'bukti_transaksi'         => 'nullable|image|mimes:jpeg,png,jpg,pdf|max:3072',
            'ranting_id'              => 'nullable|exists:rantings,id',
        ], [
            'kategori_operasional_id.required' => 'Pilih kategori beban operasional.',
            'nominal.min'                      => 'Nominal pengeluaran minimal Rp 1.000.',
        ]);

        // Upload bukti struk/kwitansi
        if ($request->hasFile('bukti_transaksi')) {
            $file = $request->file('bukti_transaksi');
            $fileName = 'bko_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $validated['bukti_transaksi'] = $file->storeAs('operasional/bukti', $fileName, 'public');
        }

        $this->walletService->recordExpense($validated, $user);

        return redirect()->route('bku-operasional.index')
            ->with('success', 'Pengeluaran operasional berhasil dicatat dan memotong Saldo Kas Operasional.');
    }

    /**
     * Catat Pemasukan Operasional Mandiri / Donasi Amil Khusus
     */
    public function storeIncome(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $validated = $request->validate([
            'sumber_dana'        => 'required|in:infaq_operasional,subsidi_struktur,saldo_awal,lainnya',
            'nominal'            => 'required|numeric|min:1000',
            'tanggal_transaksi'  => 'required|date',
            'uraian'             => 'required|string|max:255',
            'periode_pentong_id' => 'nullable|exists:penthongs,id',
            'ranting_id'         => 'nullable|exists:rantings,id',
        ]);

        $this->walletService->recordIncome($validated, $user);

        return redirect()->route('bku-operasional.index')
            ->with('success', 'Pemasukan kas operasional berhasil ditambahkan ke Saldo Operasional.');
    }

    /**
     * Sinkronisasi Potongan Operasional dari Rekapitulasi Periode Pentong
     */
    public function syncPenthong(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $request->validate([
            'penthong_id' => 'required|exists:penthongs,id',
            'ranting_id'  => 'nullable|exists:rantings,id',
        ]);

        $penthong = Penthong::findOrFail($request->input('penthong_id'));

        $count = 0;
        if ($user->isRanting()) {
            $res = $this->walletService->syncOperationalIncomeByLevel($penthong, 'ranting', $user->ranting_id, $user->mwc_id, $user->id);
            if ($res) $count++;
        } elseif ($request->filled('ranting_id')) {
            $r = Ranting::find($request->input('ranting_id'));
            $res = $this->walletService->syncOperationalIncomeByLevel($penthong, 'ranting', $r?->id, $r?->mwc_id, $user->id);
            if ($res) $count++;
        } elseif ($user->isMwc()) {
            // 1. Sinkronkan BKU Operasional MWC itu sendiri
            $resMwc = $this->walletService->syncOperationalIncomeByLevel($penthong, 'mwc', null, $user->mwc_id, $user->id);
            if ($resMwc) $count++;

            // 2. Sinkronkan seluruh Ranting di bawah naungan MWC ini
            $subRantings = Ranting::where('mwc_id', $user->mwc_id)->get();
            foreach ($subRantings as $r) {
                $res = $this->walletService->syncOperationalIncomeByLevel($penthong, 'ranting', $r->id, $r->mwc_id, $user->id);
                if ($res) $count++;
            }
        } else {
            // PC (Cabang):
            // 1. Sinkronkan BKU Operasional PC
            $resPc = $this->walletService->syncOperationalIncomeByLevel($penthong, 'pc', null, null, $user->id);
            if ($resPc) $count++;

            // 2. Sinkronkan seluruh MWC
            $allMwcs = Mwc::all();
            foreach ($allMwcs as $m) {
                $resM = $this->walletService->syncOperationalIncomeByLevel($penthong, 'mwc', null, $m->id, $user->id);
                if ($resM) $count++;
            }

            // 3. Sinkronkan seluruh Ranting
            $allRantings = Ranting::all();
            foreach ($allRantings as $r) {
                $res = $this->walletService->syncOperationalIncomeByLevel($penthong, 'ranting', $r->id, $r->mwc_id, $user->id);
                if ($res) $count++;
            }
        }

        return redirect()->route('bku-operasional.index')
            ->with('success', "Sinkronisasi potongan operasional selesai. {$count} data alokasi amil berhasil diperbarui.");
    }

    /**
     * Cetak Laporan Pertanggungjawaban (SPJ) Dana Operasional Hak Amil (PDF A4 Landscape)
     */
    public function exportPdf(Request $request): \Illuminate\Http\Response|\Illuminate\View\View
    {
        /** @var User $user */
        $user = Auth::user();
        $tingkatParam = $request->input('tingkat');
        $tingkat = $user->isRanting() ? 'ranting' : ($user->isMwc() ? 'mwc' : 'pc');

        $query = BkuOperasional::with(['kategori', 'penthong', 'ranting', 'mwc'])
            ->forUser($user, $tingkatParam)
            ->oldest('tanggal_transaksi')
            ->oldest('id');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_transaksi', [$request->input('start_date'), $request->input('end_date')]);
        }
        if ($request->filled('penthong_id')) {
            $query->where('periode_pentong_id', (int) $request->input('penthong_id'));
        }
        if ($request->filled('kategori_id')) {
            $query->where('kategori_operasional_id', (int) $request->input('kategori_id'));
        }
        if ($user->isPc()) {
            if ($request->filled('mwc_id')) $query->where('mwc_id', (int) $request->input('mwc_id'));
            if ($request->filled('ranting_id')) $query->where('tingkat', 'ranting')->where('ranting_id', (int) $request->input('ranting_id'));
        } elseif ($user->isMwc()) {
            if ($request->filled('ranting_id')) $query->where('tingkat', 'ranting')->where('ranting_id', (int) $request->input('ranting_id'));
        }

        $mutasis = $query->get();

        $totalPemasukan   = (float) $mutasis->where('jenis', 'debet')->sum('nominal');
        $totalPengeluaran = (float) $mutasis->where('jenis', 'kredit')->sum('nominal');
        $sisaSaldo        = max(0, $totalPemasukan - $totalPengeluaran);

        // Resolusi Entitas Wilayah untuk Penandatangan
        $activeRanting = null;
        $activeMwc = null;

        if ($user->isRanting()) {
            $activeRanting = $user->ranting;
            $activeMwc = $user->ranting?->mwc ?? $user->mwc;
        } elseif ($user->isMwc()) {
            $activeMwc = $user->mwc;
            if ($request->filled('ranting_id')) {
                $activeRanting = Ranting::find($request->input('ranting_id'));
            }
        } else {
            if ($request->filled('ranting_id')) {
                $activeRanting = Ranting::find($request->input('ranting_id'));
                $activeMwc = $activeRanting?->mwc;
            } elseif ($request->filled('mwc_id')) {
                $activeMwc = Mwc::find($request->input('mwc_id'));
            }
        }

        // Tentukan susunan 3 penandatangan resmi (Paling Kiri: Mengetahui Ketua Tanfidz, Tengah: Ketua Lazisnu/UPZIS, Paling Kanan: Bendahara)
        if ($activeRanting && ($tingkat === 'ranting' || $request->filled('ranting_id') || $user->isRanting())) {
            $signatories = [
                'level'       => 'ranting',
                'place'       => $activeRanting->desa ?: $activeRanting->name,
                'kiri'        => [
                    'jabatan' => 'Ketua Tanfidziyah Ranting NU',
                    'nama'    => $activeRanting->nama_ketua_ranting ?: '....................................',
                    'label'   => 'Mengetahui,',
                    'sub'     => 'PR Nahdlatul Ulama ' . ($activeRanting->desa ?: $activeRanting->name),
                ],
                'tengah'      => [
                    'jabatan' => 'Ketua UPZIS Ranting',
                    'nama'    => $activeRanting->nama_ketua_upzis ?: ($activeRanting->pj_name ?: '....................................'),
                    'label'   => 'Pimpinan Lembaga,',
                    'sub'     => 'UPZIS NU CARE - LAZISNU',
                ],
                'kanan'       => [
                    'jabatan' => 'Bendahara UPZIS Ranting',
                    'nama'    => $activeRanting->nama_bendahara ?: '....................................',
                    'label'   => 'Pemeriksa / Keuangan,',
                    'sub'     => 'Divisi Keuangan UPZIS',
                ],
            ];
        } elseif ($activeMwc && ($tingkat === 'mwc' || $request->filled('mwc_id') || $user->isMwc())) {
            $signatories = [
                'level'       => 'mwc',
                'place'       => $activeMwc->kecamatan ?: $activeMwc->name,
                'kiri'        => [
                    'jabatan' => 'Ketua Tanfidziyah MWC NU',
                    'nama'    => $activeMwc->nama_ketua_mwc ?: '....................................',
                    'label'   => 'Mengetahui,',
                    'sub'     => 'MWCNU ' . ($activeMwc->kecamatan ?: $activeMwc->name),
                ],
                'tengah'      => [
                    'jabatan' => 'Ketua LAZISNU MWC',
                    'nama'    => $activeMwc->nama_ketua_lazisnu ?: '....................................',
                    'label'   => 'Pimpinan Lembaga,',
                    'sub'     => 'NU CARE - LAZISNU MWC',
                ],
                'kanan'       => [
                    'jabatan' => 'Bendahara UPZIS MWC',
                    'nama'    => $activeMwc->nama_bendahara ?: '....................................',
                    'label'   => 'Pemeriksa / Keuangan,',
                    'sub'     => 'Divisi Keuangan MWC',
                ],
            ];
        } else {
            $signatories = [
                'level'       => 'pc',
                'place'       => 'Lamongan',
                'kiri'        => [
                    'jabatan' => 'Ketua Tanfidziyah PCNU Lamongan',
                    'nama'    => 'Dr. H. Supandi, S.Pd., M.Pd.',
                    'label'   => 'Mengetahui,',
                    'sub'     => 'Pengurus Cabang Nahdlatul Ulama',
                ],
                'tengah'      => [
                    'jabatan' => 'Ketua PC NU CARE - LAZISNU',
                    'nama'    => 'Drs. H. Suharjono, M.Si',
                    'label'   => 'Pimpinan Lembaga,',
                    'sub'     => 'NU CARE - LAZISNU Lamongan',
                ],
                'kanan'       => [
                    'jabatan' => 'Bendahara PC LAZISNU',
                    'nama'    => 'H. M. Mas\'ud, S.E.',
                    'label'   => 'Pemeriksa / Keuangan,',
                    'sub'     => 'Divisi Keuangan & Pembukuan',
                ],
            ];
        }

        // Label Filter
        $namaWilayah = 'Seluruh Wilayah PCNU Lamongan';
        if ($user->isRanting()) {
            $namaWilayah = 'Ranting ' . ($user->ranting?->name ?? 'Desa');
        } elseif ($user->isMwc()) {
            $namaWilayah = 'MWC ' . ($user->mwc?->name ?? 'Kecamatan');
        } elseif ($request->filled('ranting_id')) {
            $r = Ranting::find($request->input('ranting_id'));
            $namaWilayah = 'Ranting ' . ($r?->name ?? '');
        } elseif ($request->filled('mwc_id')) {
            $m = Mwc::find($request->input('mwc_id'));
            $namaWilayah = 'MWC ' . ($m?->name ?? '');
        }

        $periodeLabel = 'Semua Periode';
        if ($request->filled('penthong_id')) {
            $p = Penthong::find($request->input('penthong_id'));
            $periodeLabel = $p ? "Periode {$p->nama_periode} ({$p->bulan_nama} {$p->tahun})" : 'Periode Terpilih';
        } elseif ($request->filled('start_date') && $request->filled('end_date')) {
            $periodeLabel = Carbon::parse($request->input('start_date'))->format('d/m/Y') . ' s/d ' . Carbon::parse($request->input('end_date'))->format('d/m/Y');
        }

        $printedAt = now()->translatedFormat('d F Y, H:i') . ' WIB';
        $signerName = $user?->name ?? 'Admin Lazisnu Lamongan';

        $viewData = compact(
            'mutasis',
            'totalPemasukan',
            'totalPengeluaran',
            'sisaSaldo',
            'namaWilayah',
            'periodeLabel',
            'printedAt',
            'signerName',
            'tingkat',
            'signatories'
        );

        return response()->view('bku_operasional.export_pdf', $viewData);
    }

    /**
     * Ekspor Laporan BKU Operasional ke Excel (.xlsx)
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $tingkatParam = $request->input('tingkat');

        $query = BkuOperasional::with(['kategori', 'penthong', 'ranting', 'mwc'])
            ->forUser($user, $tingkatParam)
            ->oldest('tanggal_transaksi')
            ->oldest('id');

        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereBetween('tanggal_transaksi', [$request->input('start_date'), $request->input('end_date')]);
        }
        if ($request->filled('penthong_id')) {
            $query->where('periode_pentong_id', (int) $request->input('penthong_id'));
        }
        if ($request->filled('kategori_id')) {
            $query->where('kategori_operasional_id', (int) $request->input('kategori_id'));
        }
        if ($user->isPc()) {
            if ($request->filled('mwc_id')) $query->where('mwc_id', (int) $request->input('mwc_id'));
            if ($request->filled('ranting_id')) $query->where('tingkat', 'ranting')->where('ranting_id', (int) $request->input('ranting_id'));
        } elseif ($user->isMwc()) {
            if ($request->filled('ranting_id')) $query->where('tingkat', 'ranting')->where('ranting_id', (int) $request->input('ranting_id'));
        }

        $mutasis = $query->get();

        return Excel::download(new class($mutasis) implements 
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize,
            \Maatwebsite\Excel\Concerns\WithStyles
        {
            protected $data;
            public function __construct($data) { $this->data = $data; }

            public function collection()
            {
                $runningBalance = 0;
                return $this->data->map(function ($item, $index) use (&$runningBalance) {
                    $debet = $item->jenis === 'debet' ? (float) $item->nominal : 0;
                    $kredit = $item->jenis === 'kredit' ? (float) $item->nominal : 0;
                    $runningBalance += ($debet - $kredit);

                    return [
                        'no'           => $index + 1,
                        'tanggal'      => $item->tanggal_transaksi ? Carbon::parse($item->tanggal_transaksi)->format('d/m/Y') : '-',
                        'nomor_bukti'  => $item->nomor_transaksi,
                        'kategori'     => $item->kategori?->nama_kategori ?? ($item->sumber_dana === 'alokasi_pentong' ? 'Alokasi KOIN' : 'Pemasukan Kas'),
                        'uraian'       => $item->uraian,
                        'vendor'       => $item->penerima_atau_vendor ?? '-',
                        'debet'        => $debet,
                        'kredit'       => $kredit,
                        'saldo'        => $runningBalance,
                    ];
                });
            }

            public function headings(): array
            {
                return [
                    'NO', 'TANGGAL', 'NO. TRANSAKSI', 'KATEGORI / COA', 'URAIAN TRANSAKSI', 
                    'PENERIMA / VENDOR', 'DEBET (MASUK)', 'KREDIT (KELUAR)', 'SALDO RUNNING'
                ];
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => [
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => 'C0392B']], // Merah Elegan untuk Operasional
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    ],
                ];
            }
        }, 'BKU_Operasional_LAZISNU_' . date('Ymd_His') . '.xlsx');
    }
}
