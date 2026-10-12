<?php

namespace App\Http\Controllers;

use App\Models\Mustahiq;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;
use App\Imports\MustahiqImport;
use Maatwebsite\Excel\Facades\Excel;

class MustahiqController extends Controller
{
    /**
     * Tampilkan daftar data Mustahiq dengan isolasi data berbasis hierarki pengguna:
     * - Admin Ranting: Hanya melihat data mustahiq di rantingnya.
     * - Admin MWC: Melihat seluruh mustahiq di kecamatan bernaung.
     * - Admin PC / Super Admin: Melihat seluruh mustahiq se-Kabupaten Lamongan.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Mustahiq::with(['ranting', 'mwc', 'creator'])
            ->forUser($user)
            ->latest();

        // Filter Asnaf
        if ($request->filled('asnaf')) {
            $query->where('asnaf', $request->input('asnaf'));
        }

        // Filter Status Kelayakan
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter MWC (Khusus PC)
        if ($user->isPc() && $request->filled('mwc_id')) {
            $query->where('mwc_id', (int) $request->input('mwc_id'));
        }

        // Filter Ranting (Khusus PC & MWC)
        if (($user->isPc() || $user->isMwc()) && $request->filled('ranting_id')) {
            $query->where('ranting_id', (int) $request->input('ranting_id'));
        }

        // Pencarian Teks (Nama, NIK, Alamat)
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $mustahiqs = $query->paginate(15)->withQueryString();

        // Data pendukung filter
        $mwcs = $user->isPc() ? Mwc::orderBy('name')->get() : collect();
        $rantings = collect();

        if ($user->isPc()) {
            if ($request->filled('mwc_id')) {
                $rantings = Ranting::where('mwc_id', $request->input('mwc_id'))->orderBy('name')->get();
            } else {
                $rantings = Ranting::orderBy('name')->get();
            }
        } elseif ($user->isMwc()) {
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        }

        return view('mustahiq.index', compact('mustahiqs', 'mwcs', 'rantings', 'user'));
    }

    /**
     * Endpoint API Autocomplete / Select2 untuk modul Pentasyarufan & SPJ.
     * Mengembalikan data Mustahiq berstatus 'aktif' yang disaring ketat berdasarkan
     * teritori hak akses pengguna yang sedang login.
     */
    public function search(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            return response()->json([
                'results' => [],
                'message' => 'Unauthenticated',
            ], 401);
        }

        $term = trim((string) $request->input('q', $request->input('term', '')));
        $page = (int) $request->input('page', 1);
        $perPage = 15;

        // Query dasar terisolasi per wilayah user & berstatus aktif
        $query = Mustahiq::with(['ranting', 'mwc'])
            ->forUser($user)
            ->active();

        // Filter tambahan jika user memilih ranting tertentu pada form pentasyarufan
        if ($request->filled('ranting_id')) {
            $targetRantingId = (int) $request->input('ranting_id');

            // Jika user adalah ranting, cegah manipulasi query ke ranting lain
            if ($user->isRanting() && (int) $user->ranting_id !== $targetRantingId) {
                return response()->json([
                    'results' => [],
                    'pagination' => ['more' => false],
                ]);
            }

            $query->where('ranting_id', $targetRantingId);
        }

        // Filter pencarian nama, NIK, alamat
        if (!empty($term)) {
            $query->search($term);
        }

        $paginator = $query->orderBy('nama', 'asc')->paginate($perPage, ['*'], 'page', $page);

        // Format keluaran terstandar untuk Select2
        $formattedResults = $paginator->getCollection()->map(function (Mustahiq $m) {
            $rantingName = $m->ranting?->name ?? 'Ranting N/A';
            $mwcName = $m->mwc?->name ?? 'MWC N/A';
            $asnafLabel = $m->asnaf_label;

            return [
                'id' => $m->id,
                'text' => "{$m->nama} (NIK: {$m->nik}) — Asnaf: {$asnafLabel} [{$rantingName}, Kec. {$mwcName}]",
                'nama' => $m->nama,
                'nik' => $m->nik,
                'asnaf' => $m->asnaf,
                'asnaf_label' => $asnafLabel,
                'alamat' => $m->alamat,
                'ranting_id' => $m->ranting_id,
                'ranting_nama' => $rantingName,
                'mwc_nama' => $mwcName,
                'kondisi_spesifik' => $m->kondisi_spesifik,
                'total_bantuan_sebelumnya' => $m->total_bantuan,
            ];
        });

        return response()->json([
            'results' => $formattedResults,
            'pagination' => [
                'more' => $paginator->hasMorePages(),
            ],
            'meta' => [
                'total' => $paginator->total(),
                'current_page' => $paginator->currentPage(),
            ],
        ]);
    }

    /**
     * Form Tambah Mustahiq (Ditujukan utama untuk Admin Ranting sebagai petugas lapangan)
     */
    public function create(): View
    {
        /** @var User $user */
        $user = Auth::user();

        // Validasi hierarki input
        $mwcs = collect();
        $rantings = collect();

        if ($user->isPc()) {
            $mwcs = Mwc::orderBy('name')->get();
            $rantings = Ranting::orderBy('name')->get();
        } elseif ($user->isMwc()) {
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        }

        return view('mustahiq.create', compact('mwcs', 'rantings', 'user'));
    }

    /**
     * Simpan data Mustahiq baru dengan validasi integritas wilayah & privasi foto
     */
    public function store(Request $request): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();

        // Tentukan ranting_id & mwc_id berdasarkan role
        if ($user->isRanting()) {
            $rantingId = $user->ranting_id;
            $mwcId = $user->mwc_id ?? $user->ranting?->mwc_id;
        } elseif ($user->isMwc()) {
            $rantingId = (int) $request->input('ranting_id');
            $mwcId = $user->mwc_id;
        } else {
            $rantingId = (int) $request->input('ranting_id');
            $ranting = Ranting::find($rantingId);
            $mwcId = $ranting ? $ranting->mwc_id : (int) $request->input('mwc_id');
        }

        $validated = $request->validate([
            'nik' => [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]+$/',
                Rule::unique('mustahiqs', 'nik'),
            ],
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'telepon' => 'nullable|string|max:25',
            'alamat' => 'required|string|max:500',
            'asnaf' => 'required|in:fakir,miskin,amil,muallaf,riqab,gharim,fisabilillah,ibnu_sabil',
            'kondisi_spesifik' => 'nullable|string|max:255',
            'pekerjaan' => 'nullable|string|max:100',
            'penghasilan_rata_rata' => 'nullable|numeric|min:0',
            'jumlah_tanggungan' => 'nullable|integer|min:0',
            'foto_kondisi' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072', // Max 3MB
            'catatan_verifikasi' => 'nullable|string|max:1000',
        ], [
            'nik.required' => 'Nomor Induk Kependudukan (NIK) wajib diisi.',
            'nik.size' => 'NIK harus berjumlah tepat 16 digit angka.',
            'nik.regex' => 'NIK hanya boleh berisi karakter angka.',
            'nik.unique' => 'NIK ini sudah terdaftar dalam sistem penerima manfaat.',
            'foto_kondisi.max' => 'Ukuran file foto kondisi maksimal 3 MB.',
        ]);

        // Simpan foto kondisi ke direktori privat (storage/app/private/mustahiqs)
        // Foto tidak ditaruh di public/storage agar tidak bisa di-hotlink tanpa autentikasi
        $fotoPath = null;
        if ($request->hasFile('foto_kondisi')) {
            $file = $request->file('foto_kondisi');
            $fileName = 'mustahiq_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $fotoPath = $file->storeAs('mustahiqs/foto', $fileName, 'local');
        }

        $mustahiq = Mustahiq::create([
            'nik' => $validated['nik'],
            'nama' => $validated['nama'],
            'jenis_kelamin' => $validated['jenis_kelamin'],
            'telepon' => $validated['telepon'] ?? null,
            'alamat' => $validated['alamat'],
            'ranting_id' => $rantingId,
            'mwc_id' => $mwcId,
            'asnaf' => $validated['asnaf'],
            'kondisi_spesifik' => $validated['kondisi_spesifik'] ?? null,
            'pekerjaan' => $validated['pekerjaan'] ?? null,
            'penghasilan_rata_rata' => $validated['penghasilan_rata_rata'] ?? 0,
            'jumlah_tanggungan' => $validated['jumlah_tanggungan'] ?? 0,
            'status' => 'aktif',
            'foto_kondisi' => $fotoPath,
            'catatan_verifikasi' => $validated['catatan_verifikasi'] ?? null,
            'created_by' => $user->id,
        ]);

        return redirect()->route('mustahiqs.show', $mustahiq->id)
            ->with('success', "Data Mustahiq atas nama [{$mustahiq->nama}] berhasil didaftarkan.");
    }

    /**
     * Tampilkan Profil Detail Mustahiq beserta Rekam Jejak (Histori) Bantuan Pentasyarufan
     */
    public function show(Mustahiq $mustahiq): View
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorizeAccess($user, $mustahiq);

        // Muat relasi bantuan dari tabel pivot & transaksi langsung
        $mustahiq->load([
            'ranting',
            'mwc',
            'creator',
            'pentasyarufans.pilarProgram',
            'pentasyarufans.programKerja',
            'directPentasyarufans.pilarProgram',
            'directPentasyarufans.programKerja',
        ]);

        return view('mustahiq.show', compact('mustahiq', 'user'));
    }

    /**
     * Form Edit Data Mustahiq
     */
    public function edit(Mustahiq $mustahiq): View
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorizeAccess($user, $mustahiq);

        $mwcs = $user->isPc() ? Mwc::orderBy('name')->get() : collect();
        $rantings = collect();

        if ($user->isPc()) {
            $rantings = Ranting::orderBy('name')->get();
        } elseif ($user->isMwc()) {
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        }

        return view('mustahiq.edit', compact('mustahiq', 'mwcs', 'rantings', 'user'));
    }

    /**
     * Update data Mustahiq & status kelayakan bantuan
     */
    public function update(Request $request, Mustahiq $mustahiq): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorizeAccess($user, $mustahiq);

        $validated = $request->validate([
            'nik' => [
                'required',
                'string',
                'size:16',
                'regex:/^[0-9]+$/',
                Rule::unique('mustahiqs', 'nik')->ignore($mustahiq->id),
            ],
            'nama' => 'required|string|max:255',
            'jenis_kelamin' => 'required|in:L,P',
            'telepon' => 'nullable|string|max:25',
            'alamat' => 'required|string|max:500',
            'asnaf' => 'required|in:fakir,miskin,amil,muallaf,riqab,gharim,fisabilillah,ibnu_sabil',
            'kondisi_spesifik' => 'nullable|string|max:255',
            'pekerjaan' => 'nullable|string|max:100',
            'penghasilan_rata_rata' => 'nullable|numeric|min:0',
            'jumlah_tanggungan' => 'nullable|integer|min:0',
            'status' => 'required|in:aktif,tidak_aktif',
            'alasan_tidak_aktif' => 'nullable|string|max:255',
            'foto_kondisi' => 'nullable|image|mimes:jpeg,png,jpg,webp|max:3072',
            'catatan_verifikasi' => 'nullable|string|max:1000',
        ]);

        // Upload foto baru jika ada penggantian
        if ($request->hasFile('foto_kondisi')) {
            // Hapus file lama dari storage privat
            if ($mustahiq->foto_kondisi && Storage::disk('local')->exists($mustahiq->foto_kondisi)) {
                Storage::disk('local')->delete($mustahiq->foto_kondisi);
            }

            $file = $request->file('foto_kondisi');
            $fileName = 'mustahiq_' . time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            $validated['foto_kondisi'] = $file->storeAs('mustahiqs/foto', $fileName, 'local');
        } else {
            unset($validated['foto_kondisi']);
        }

        $mustahiq->update($validated);

        return redirect()->route('mustahiqs.show', $mustahiq->id)
            ->with('success', "Data Mustahiq [{$mustahiq->nama}] berhasil diperbarui.");
    }

    /**
     * Hapus data Mustahiq (Hanya jika belum pernah menerima pentasyarufan)
     */
    public function destroy(Mustahiq $mustahiq): RedirectResponse
    {
        /** @var User $user */
        $user = Auth::user();
        $this->authorizeAccess($user, $mustahiq);

        // Jika sudah memiliki riwayat bantuan, tolak hard-delete demi integritas audit keuangan
        if ($mustahiq->pentasyarufans()->exists() || $mustahiq->directPentasyarufans()->exists()) {
            return back()->with('error', 'Mustahiq ini telah memiliki riwayat penerimaan pentasyarufan dan tidak dapat dihapus. Silakan ubah statusnya menjadi "Tidak Aktif".');
        }

        if ($mustahiq->foto_kondisi && Storage::disk('local')->exists($mustahiq->foto_kondisi)) {
            Storage::disk('local')->delete($mustahiq->foto_kondisi);
        }

        $mustahiq->delete();

        return redirect()->route('mustahiqs.index')
            ->with('success', 'Data Mustahiq berhasil dihapus dari sistem.');
    }

    /**
     * Akses Privat Foto Kondisi Mustahiq (Data Privacy & Confidentiality).
     * Endpoint ini memverifikasi hak akses pengguna secara ketat dan mengirimkan
     * stream binary gambar langsung dari disk lokal yang terisolasi.
     */
    public function serveFoto(Mustahiq $mustahiq): StreamedResponse|BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();

        if (!$user) {
            abort(401, 'Silakan login terlebih dahulu untuk mengakses data sensitif mustahiq.');
        }

        $this->authorizeAccess($user, $mustahiq);

        if (!$mustahiq->foto_kondisi || !Storage::disk('local')->exists($mustahiq->foto_kondisi)) {
            abort(404, 'File dokumentasi foto tidak ditemukan di penyimpanan server.');
        }

        return Storage::disk('local')->response($mustahiq->foto_kondisi);
    }

    /**
     * Guard Otorisasi Wilayah (Data Scoping Enforcement)
     */
    protected function authorizeAccess(User $user, Mustahiq $mustahiq): void
    {
        if ($user->isSuperAdmin() || $user->isPc()) {
            return;
        }

        if ($user->isMwc() && (int) $user->mwc_id === (int) $mustahiq->mwc_id) {
            return;
        }

        if ($user->isRanting() && (int) $user->ranting_id === (int) $mustahiq->ranting_id) {
            return;
        }

        abort(403, 'Akses Ditolak: Anda tidak memiliki otoritas teritorial atas data Mustahiq ini.');
    }

    /**
     * Download Format Template Excel (.xlsx) untuk Import Data Mustahiq
     */
    public function downloadTemplate(): BinaryFileResponse|\Illuminate\Http\Response
    {
        $headers = [
            ['NIK', 'Nama_Lengkap', 'Jenis_Kelamin', 'Alamat', 'Kategori_Asnaf', 'Pekerjaan/Keterangan'],
            ['3524010101900001', 'Ahmad Dahlan', 'L', 'Dusun Krajan RT 01 RW 02 Desa Sedayu', 'fakir', 'Buruh tani serabutan / Lansia'],
            ['3524015002850002', 'Siti Aminah', 'P', 'Dusun Glugu RT 03 RW 01 Desa Sedayu', 'miskin', 'Janda tanggungan 3 anak yatim'],
        ];

        return Excel::download(new class($headers) implements \Maatwebsite\Excel\Concerns\FromArray, \Maatwebsite\Excel\Concerns\ShouldAutoSize, \Maatwebsite\Excel\Concerns\WithStyles {
            protected array $data;
            public function __construct(array $data) { $this->data = $data; }
            public function array(): array { return $this->data; }
            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => [
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A6B38']],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    ],
                ];
            }
        }, 'Template_Import_Mustahiq_LAZISNU.xlsx');
    }

    /**
     * Import massal data Mustahiq dari Excel/CSV dengan validasi NIK unik dan skip duplikat
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
            'ranting_id' => ['nullable', 'exists:rantings,id'],
        ], [
            'file.required' => 'Pilih berkas Excel/CSV terlebih dahulu.',
            'file.mimes'    => 'Format file harus berupa .xlsx, .xls, atau .csv',
            'file.max'      => 'Ukuran file maksimal adalah 5 Megabyte.',
        ]);

        /** @var User $user */
        $user = Auth::user();

        if ($user && $user->isRanting()) {
            $targetRantingId = (int) $user->ranting_id;
            $targetMwcId = (int) ($user->mwc_id ?? $user->ranting?->mwc_id ?? 1);
        } elseif ($user && $user->isMwc()) {
            $reqRantingId = (int) $request->input('ranting_id', 0);
            $ranting = Ranting::where('id', $reqRantingId)->where('mwc_id', $user->mwc_id)->first();
            $targetRantingId = $ranting ? $ranting->id : (int) ($user->ranting_id ?? 1);
            $targetMwcId = (int) $user->mwc_id;
        } else {
            $targetRantingId = (int) ($request->input('ranting_id') ?? 1);
            $ranting = Ranting::find($targetRantingId);
            $targetMwcId = (int) ($ranting?->mwc_id ?? 1);
        }

        $import = new MustahiqImport($targetRantingId, $targetMwcId);

        try {
            Excel::import($import, $request->file('file'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses berkas import mustahiq: ' . $e->getMessage());
        }

        $successMsg = "Berhasil mengimpor {$import->importedCount} data mustahiq.";
        $details = [];

        if ($import->skippedDuplicateCount > 0) {
            $details[] = "{$import->skippedDuplicateCount} data dilewati karena NIK sudah terdaftar";
        }

        if (!empty($import->errors)) {
            $details[] = count($import->errors) . " data gagal format";
        }

        if (!empty($details)) {
            $successMsg .= " (" . implode(', ', $details) . ").";
        }

        return redirect()->route('mustahiqs.index')
            ->with('success', $successMsg)
            ->with('import_failures', array_slice($import->errors, 0, 10));
    }

    /**
     * Cetak Laporan PDF Data Mustahiq dengan Kop Surat Resmi dan Lembar Pengesahan
     */
    public function exportPdf(Request $request): \Illuminate\Http\Response|\Illuminate\View\View
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Mustahiq::with(['ranting', 'mwc'])
            ->forUser($user)
            ->latest();

        if ($request->filled('asnaf')) {
            $query->where('asnaf', $request->input('asnaf'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('mwc_id') && ($user->isPc() || $user->isSuperAdmin())) {
            $query->where('mwc_id', (int) $request->input('mwc_id'));
        }
        if ($request->filled('ranting_id')) {
            $query->where('ranting_id', (int) $request->input('ranting_id'));
        }
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $mustahiqs = $query->get();

        // Keterangan Filter untuk Sub-header
        $filterParts = [];
        if ($request->filled('ranting_id')) {
            $r = Ranting::find($request->input('ranting_id'));
            $filterParts[] = "Ranting: " . ($r?->name ?? 'N/A');
        } elseif ($request->filled('mwc_id')) {
            $m = Mwc::find($request->input('mwc_id'));
            $filterParts[] = "MWC: " . ($m?->name ?? 'N/A');
        } elseif ($user->isRanting()) {
            $filterParts[] = "Wilayah: Ranting " . ($user->ranting?->name ?? 'Desa');
        } elseif ($user->isMwc()) {
            $filterParts[] = "Wilayah: MWC " . ($user->mwc?->name ?? 'Kecamatan');
        } else {
            $filterParts[] = "Wilayah: Seluruh Kabupaten Lamongan";
        }

        if ($request->filled('asnaf')) {
            $filterParts[] = "Asnaf: " . ucfirst($request->input('asnaf'));
        }
        if ($request->filled('status')) {
            $filterParts[] = "Status: " . ucfirst($request->input('status'));
        }

        $subHeaderFilter = implode(' | ', $filterParts);
        $totalCount = $mustahiqs->count();
        $printedAt = now()->translatedFormat('d F Y, H:i') . ' WIB';
        $signerName = $user?->name ?? 'Pengurus PC LAZISNU Lamongan';

        $viewData = compact('mustahiqs', 'subHeaderFilter', 'totalCount', 'printedAt', 'signerName');

        return response()->view('mustahiq.export_pdf', $viewData);
    }

    /**
     * Ekspor Data Mustahiq ke Spreadsheet Excel (.xlsx) dengan Formatting Rapi
     */
    public function exportExcel(Request $request): BinaryFileResponse
    {
        /** @var User $user */
        $user = Auth::user();

        $query = Mustahiq::with(['ranting', 'mwc'])
            ->forUser($user)
            ->latest();

        if ($request->filled('asnaf')) {
            $query->where('asnaf', $request->input('asnaf'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }
        if ($request->filled('mwc_id') && ($user->isPc() || $user->isSuperAdmin())) {
            $query->where('mwc_id', (int) $request->input('mwc_id'));
        }
        if ($request->filled('ranting_id')) {
            $query->where('ranting_id', (int) $request->input('ranting_id'));
        }
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        $mustahiqs = $query->get();

        return Excel::download(new class($mustahiqs) implements 
            \Maatwebsite\Excel\Concerns\FromCollection,
            \Maatwebsite\Excel\Concerns\WithHeadings,
            \Maatwebsite\Excel\Concerns\ShouldAutoSize,
            \Maatwebsite\Excel\Concerns\WithStyles
        {
            protected $data;
            public function __construct($data) { $this->data = $data; }

            public function collection()
            {
                return $this->data->map(function ($item, $index) {
                    return [
                        'no'            => $index + 1,
                        'nik'           => "'" . $item->nik, // Format teks agar angka 16 digit tidak terpotong ilmiah
                        'nama_lengkap'  => $item->nama,
                        'jenis_kelamin' => $item->jenis_kelamin,
                        'alamat'        => $item->alamat,
                        'ranting'       => $item->ranting?->name ?? '-',
                        'mwc'           => $item->mwc?->name ?? '-',
                        'asnaf'         => $item->asnaf_label,
                        'pekerjaan'     => $item->kondisi_spesifik ?: ($item->pekerjaan ?: '-'),
                        'status'        => ucfirst($item->status),
                    ];
                });
            }

            public function headings(): array
            {
                return [
                    'NO', 'NIK', 'NAMA LENGKAP', 'L/P', 'ALAMAT', 
                    'RANTING', 'MWC', 'KATEGORI ASNAF', 'KETERANGAN / KERENTANAN', 'STATUS'
                ];
            }

            public function styles(\PhpOffice\PhpSpreadsheet\Worksheet\Worksheet $sheet)
            {
                return [
                    1 => [
                        'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                        'fill' => ['fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID, 'startColor' => ['rgb' => '0A6B38']],
                        'alignment' => ['horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER],
                    ],
                ];
            }
        }, 'Data_Mustahiq_LAZISNU_' . date('Ymd_His') . '.xlsx');
    }
}
