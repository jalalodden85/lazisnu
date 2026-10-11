<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDonatorRequest;
use App\Imports\DonatorImport;
use App\Models\Donator;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Relawan;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class DonatorController extends Controller
{
    /**
     * Menampilkan daftar penerima kotak infaq KOIN NU dengan role-based scoping
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Donator::with(['ranting.mwc', 'relawan']);

        // Scope hirarki hak akses
        if ($user && $user->isRanting()) {
            $query->where('ranting_id', $user->ranting_id);
            $mwcs = Mwc::where('id', $user->ranting?->mwc_id ?? 0)->get();
            $rantings = Ranting::where('id', $user->ranting_id)->get();
            $relawans = Relawan::where('ranting_id', $user->ranting_id)->active()->orderBy('nama')->get();
        } elseif ($user && $user->isMwc()) {
            $query->whereHas('ranting', fn($q) => $q->where('mwc_id', $user->mwc_id));
            $mwcs = Mwc::where('id', $user->mwc_id)->get();
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
            $relawans = Relawan::whereHas('ranting', fn($q) => $q->where('mwc_id', $user->mwc_id))->active()->orderBy('nama')->get();
        } else {
            // PC: Akses seluruh 15 MWC dan 258 Ranting
            $mwcs = Mwc::orderBy('code')->get();
            $rantings = Ranting::orderBy('name')->get();
            $relawans = Relawan::active()->orderBy('nama')->get();
        }

        // Filter MWC (Khusus PC)
        if ($request->filled('mwc_id') && (!$user || $user->isPc())) {
            $mwcId = (int) $request->input('mwc_id');
            $query->byMwc($mwcId);
            $rantings = Ranting::where('mwc_id', $mwcId)->orderBy('name')->get();
            $relawans = Relawan::whereHas('ranting', fn($q) => $q->where('mwc_id', $mwcId))->active()->orderBy('nama')->get();
        }

        // Filter Ranting
        if ($request->filled('ranting_id')) {
            $rantingId = (int) $request->input('ranting_id');
            $query->where('ranting_id', $rantingId);
            $relawans = Relawan::where('ranting_id', $rantingId)->active()->orderBy('nama')->get();
        }

        // Filter Petugas Relawan Pengampu
        if ($request->filled('relawan_id')) {
            $query->where('relawan_id', (int) $request->input('relawan_id'));
        }

        // Filter Status Kotak
        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        // Filter Pembagian Petugas (Sudah Ada Petugas vs Belum Ada Petugas)
        if ($request->filled('assignment')) {
            if ($request->input('assignment') === 'assigned') {
                $query->whereNotNull('relawan_id');
            } elseif ($request->input('assignment') === 'unassigned') {
                $query->whereNull('relawan_id');
            }
        }

        // Filter Search Keyword
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // KPI Ringkasan Data (dihitung dari query yang sesuai hak akses tanpa pagination)
        $baseKpiQuery = clone $query;
        $totalDonators = (clone $baseKpiQuery)->count();
        $activeDonators = (clone $baseKpiQuery)->where('status', Donator::STATUS_ACTIVE)->count();
        $assignedDonators = (clone $baseKpiQuery)->whereNotNull('relawan_id')->count();
        $unassignedDonators = (clone $baseKpiQuery)->whereNull('relawan_id')->count();

        $donators = $query->latest('id')->paginate(15)->withQueryString();

        return view('donators.index', compact(
            'donators',
            'mwcs',
            'rantings',
            'relawans',
            'totalDonators',
            'activeDonators',
            'assignedDonators',
            'unassignedDonators'
        ));
    }

    /**
     * Halaman Pembagian Petugas di Wilayah Masing-Masing
     * Menampilkan pemetaan relawan di wilayah tugas (RT/RW) beserta daftar kotak binaannya
     */
    public function distribution(Request $request): View
    {
        $user = Auth::user();
        $relawanQuery = Relawan::with(['ranting.mwc', 'donators'])->active();
        $unassignedQuery = Donator::with('ranting.mwc')->whereNull('relawan_id');

        // Scoping hak akses
        if ($user && $user->isRanting()) {
            $relawanQuery->where('ranting_id', $user->ranting_id);
            $unassignedQuery->where('ranting_id', $user->ranting_id);
            $rantings = Ranting::where('id', $user->ranting_id)->get();
        } elseif ($user && $user->isMwc()) {
            $relawanQuery->whereHas('ranting', fn($q) => $q->where('mwc_id', $user->mwc_id));
            $unassignedQuery->whereHas('ranting', fn($q) => $q->where('mwc_id', $user->mwc_id));
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        } else {
            $rantings = Ranting::orderBy('name')->get();
        }

        // Filter Ranting jika dipilih
        if ($request->filled('ranting_id')) {
            $rantingId = (int) $request->input('ranting_id');
            $relawanQuery->where('ranting_id', $rantingId);
            $unassignedQuery->where('ranting_id', $rantingId);
        }

        $relawans = $relawanQuery->orderBy('ranting_id')->orderBy('area_tugas')->get();
        $unassignedDonators = $unassignedQuery->latest('id')->get();

        return view('donators.distribution', compact('relawans', 'unassignedDonators', 'rantings'));
    }

    /**
     * Form pendaftaran donatur baru
     */
    public function create(): View
    {
        $user = Auth::user();
        $isRantingInactive = false;

        if ($user && $user->isRanting()) {
            $rantings = Ranting::where('id', $user->ranting_id)->get();
            $relawans = Relawan::where('ranting_id', $user->ranting_id)->active()->orderBy('nama')->get();
            if (!$user->ranting?->is_active) {
                $isRantingInactive = true;
            }
        } elseif ($user && $user->isMwc()) {
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
            $relawans = Relawan::whereHas('ranting', fn($q) => $q->where('mwc_id', $user->mwc_id))->active()->orderBy('nama')->get();
        } else {
            $rantings = Ranting::orderBy('name')->get();
            $relawans = Relawan::active()->orderBy('nama')->get();
        }

        return view('donators.create', compact('rantings', 'relawans', 'isRantingInactive'));
    }

    /**
     * API untuk mengambil nomor urut kotak berikutnya via AJAX:
     * Format: KLG-{KODE_RANTING}-{RT}{RW}-{URUT} contoh: KLG-M01R10-0102-005
     */
    public function getNextBoxNumber(Request $request): JsonResponse
    {
        $rantingId = (int) $request->input('ranting_id');
        if (!$rantingId) {
            return response()->json(['box_number' => '']);
        }

        $rt = $request->input('rt');
        $rw = $request->input('rw');

        $boxNumber = Donator::generateBoxNumber($rantingId, $rt, $rw);

        return response()->json([
            'box_number' => $boxNumber,
            'status'     => 'success'
        ]);
    }

    /**
     * Download template CSV untuk bulk import donatur
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_donatur_koin_nu.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['nama', 'nik', 'phone', 'address', 'rt', 'rw', 'box_number', 'petugas', 'notes']);
            // Contoh baris 1: box_number dikosongkan agar otomatis di-generate sistem
            fputcsv($handle, ['H. Ahmad Fauzi', '3524011203850001', '081234567890', 'Jl. Pesantren No. 12', '01', '02', '', 'Relawan Kord. RT 01', 'Penerima kotak baru']);
            fputcsv($handle, ['Ibu Siti Khodijah', '3524015509900002', '081298765432', 'Jl. Masjid Baiturrahman No. 05', '01', '02', '', '', 'Donatur tetap']);
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Import massal donatur & penerima kotak dari file Excel/CSV
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'],
        ], [
            'file.required' => 'Pilih file Excel/CSV terlebih dahulu.',
            'file.mimes'    => 'Format file harus berupa XLSX, XLS, atau CSV.',
            'file.max'      => 'Ukuran file maksimal adalah 5 Megabyte.',
        ]);

        $user = Auth::user();
        if ($user && $user->isRanting()) {
            $rantingId = (int) $user->ranting_id;
        } elseif ($user && $user->isMwc()) {
            $reqRantingId = (int) $request->input('ranting_id', 0);
            $ranting = Ranting::where('id', $reqRantingId)->where('mwc_id', $user->mwc_id)->first();
            if (!$ranting) {
                return back()->with('error', 'Pilih ranting yang valid di wilayah MWC Anda.');
            }
            $rantingId = $ranting->id;
        } else {
            $rantingId = (int) ($request->input('ranting_id') ?? $user?->ranting_id ?? 1);
        }

        $targetRanting = Ranting::findOrFail($rantingId);

        // Cek apakah Ranting aktif mengikuti program KOIN NU
        if (!$targetRanting->is_active) {
            return back()->with('error', "Import dibatalkan: Ranting {$targetRanting->name} ({$targetRanting->code}) sedang dinonaktifkan dari program KOIN NU oleh PC Lazisnu.");
        }

        $import = new DonatorImport($rantingId);

        try {
            $import->import($request->file('file'));
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal memproses file import donatur: ' . $e->getMessage());
        }

        $failures = $import->failures();
        $failureCount = $failures->count();

        $successMessage = "Import data donatur untuk Ranting {$targetRanting->name} ({$targetRanting->code}) berhasil diproses!";

        if ($failureCount > 0) {
            return redirect()
                ->route('donators.index', ['ranting_id' => $rantingId])
                ->with('success', $successMessage)
                ->with('import_failures', $failures->take(10)->map(fn($f) => "Baris {$f->row()}: " . implode(', ', $f->errors()))->all())
                ->with('total_failures', $failureCount);
        }

        return redirect()
            ->route('donators.index', ['ranting_id' => $rantingId])
            ->with('success', $successMessage . " Nomor kotak otomatis dibuat sesuai format (Contoh: KLG-{$targetRanting->code}-0102-xxx).");
    }

    /**
     * Simpan data donatur baru ke database
     */
    public function store(StoreDonatorRequest $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validated();

        // Enforce hak akses ranting
        if ($user && $user->isRanting()) {
            $data['ranting_id'] = $user->ranting_id;
        } elseif ($user && $user->isMwc()) {
            $targetRanting = Ranting::findOrFail($data['ranting_id']);
            if ($targetRanting->mwc_id !== $user->mwc_id) {
                abort(403, 'Anda tidak memiliki hak akses mendaftarkan donatur di luar MWC Anda.');
            }
        }

        $targetRanting = Ranting::findOrFail($data['ranting_id']);
        if (!$targetRanting->is_active) {
            return back()->withInput()->with('error', "Pendaftaran ditolak: Ranting {$targetRanting->name} ({$targetRanting->code}) sedang dinonaktifkan dari program KOIN NU oleh PC Lazisnu.");
        }

        // Auto-generate box_number jika kosong sesuai format: KLG-{KODE_RANTING}-{RT}{RW}-{URUT}
        if (empty($data['box_number'])) {
            $data['box_number'] = Donator::generateBoxNumber($data['ranting_id'], $data['rt'] ?? null, $data['rw'] ?? null);
        }

        // Verifikasi bahwa relawan yang dipilih berada pada ranting yang sama
        if (!empty($data['relawan_id'])) {
            $relawan = Relawan::findOrFail($data['relawan_id']);
            if ((int) $relawan->ranting_id !== (int) $data['ranting_id']) {
                return back()
                    ->withInput()
                    ->withErrors(['relawan_id' => 'Petugas relawan yang dipilih harus bertugas di ranting yang sama.']);
            }
        }

        $donator = Donator::create($data);

        return redirect()
            ->route('donators.index')
            ->with('success', "Donatur {$donator->name} (Kotak #{$donator->box_number}) berhasil didaftarkan.");
    }

    /**
     * Detail donatur dan penugasan relawan
     */
    public function show(Donator $donator): View
    {
        $this->authorizeAccess($donator);
        $donator->load(['ranting.mwc', 'relawan']);

        return view('donators.show', compact('donator'));
    }

    /**
     * Form edit data donatur
     */
    public function edit(Donator $donator): View
    {
        $this->authorizeAccess($donator);
        $user = Auth::user();

        if ($user && $user->isRanting()) {
            $rantings = Ranting::where('id', $user->ranting_id)->get();
            $relawans = Relawan::where('ranting_id', $user->ranting_id)->active()->orderBy('nama')->get();
        } elseif ($user && $user->isMwc()) {
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
            $relawans = Relawan::whereHas('ranting', fn($q) => $q->where('mwc_id', $user->mwc_id))->active()->orderBy('nama')->get();
        } else {
            $rantings = Ranting::orderBy('name')->get();
            $relawans = Relawan::where('ranting_id', $donator->ranting_id)->active()->orderBy('nama')->get();
        }

        return view('donators.edit', compact('donator', 'rantings', 'relawans'));
    }

    /**
     * Perbarui data donatur & pembagian petugas
     */
    public function update(Request $request, Donator $donator): RedirectResponse
    {
        $this->authorizeAccess($donator);
        $user = Auth::user();

        $rules = [
            'box_number'  => ['required', 'string', 'max:50', 'unique:donators,box_number,' . $donator->id],
            'name'        => ['required', 'string', 'max:150'],
            'nik'         => ['nullable', 'digits:16', 'unique:donators,nik,' . $donator->id],
            'phone'       => ['nullable', 'string', 'max:25'],
            'address'     => ['required', 'string', 'max:500'],
            'rt'          => ['nullable', 'string', 'max:10'],
            'rw'          => ['nullable', 'string', 'max:10'],
            'status'      => ['required', 'in:active,inactive'],
            'relawan_id'  => ['nullable', 'exists:relawans,id'],
            'joined_date' => ['nullable', 'date'],
            'notes'       => ['nullable', 'string', 'max:500'],
        ];

        if (!$user || $user->isPc()) {
            $rules['ranting_id'] = ['required', 'exists:rantings,id'];
        }

        $validated = $request->validate($rules);

        // Jika ranting tidak diizinkan diubah oleh user Ranting/MWC
        if ($user && $user->isRanting()) {
            $validated['ranting_id'] = $donator->ranting_id;
        }

        $targetRantingId = $validated['ranting_id'] ?? $donator->ranting_id;

        // Verifikasi relawan belongs to target ranting
        if (!empty($validated['relawan_id'])) {
            $relawan = Relawan::findOrFail($validated['relawan_id']);
            if ($relawan->ranting_id != $targetRantingId) {
                return back()
                    ->withInput()
                    ->withErrors(['relawan_id' => 'Petugas relawan yang dipilih harus bertugas di ranting yang sama.']);
            }
        }

        $donator->update($validated);

        return redirect()
            ->route('donators.index')
            ->with('success', "Data donatur {$donator->name} berhasil diperbarui.");
    }

    /**
     * Hapus data donatur
     */
    public function destroy(Donator $donator): RedirectResponse
    {
        $this->authorizeAccess($donator);

        $name = $donator->name;
        $boxNumber = $donator->box_number;
        $donator->delete();

        return redirect()
            ->route('donators.index')
            ->with('success', "Donatur {$name} (Kotak #{$boxNumber}) berhasil dihapus.");
    }

    /**
     * Tetapkan petugas lapangan pengampu secara cepat (Quick Assignment)
     */
    public function assignPetugas(Request $request, Donator $donator): RedirectResponse
    {
        $this->authorizeAccess($donator);

        $request->validate([
            'relawan_id' => ['nullable', 'exists:relawans,id'],
        ]);

        $relawanId = $request->input('relawan_id');

        if ($relawanId) {
            $relawan = Relawan::findOrFail($relawanId);
            if ((int) $relawan->ranting_id !== (int) $donator->ranting_id) {
                return back()->with('error', 'Petugas relawan harus berasal dari ranting yang sama.');
            }
            $donator->update(['relawan_id' => $relawanId]);
            $msg = "Kotak #{$donator->box_number} ({$donator->name}) berhasil ditugaskan kepada petugas {$relawan->nama}.";
        } else {
            $donator->update(['relawan_id' => null]);
            $msg = "Penugasan petugas untuk Kotak #{$donator->box_number} telah dilepas.";
        }

        return back()->with('success', $msg);
    }

    /**
     * Validasi otorisasi akses berdasarkan batasan wilayah pengguna
     */
    private function authorizeAccess(Donator $donator): void
    {
        $user = Auth::user();
        if (!$user) {
            return;
        }

        if ($user->isRanting() && (int) $donator->ranting_id !== (int) $user->ranting_id) {
            abort(403, 'Akses ditolak: Anda hanya dapat mengelola donatur di wilayah Ranting Anda.');
        }

        if ($user->isMwc() && (int) $donator->ranting?->mwc_id !== (int) $user->mwc_id) {
            abort(403, 'Akses ditolak: Anda hanya dapat mengelola donatur di wilayah MWC Anda.');
        }
    }

    /**
     * Ekspor Laporan Data Donatur Berdasarkan Kode Kaleng (Format PDF / Print View)
     * Menggunakan Natural Sort Ascending (1, 2, 3, 10, 11) dan Filter Dinamis Lengkap:
     * - Rentang Tanggal (start_date, end_date)
     * - Wilayah (MWC / Ranting)
     * - Kategori Program (Zakat, Infaq, KOIN NU, Kampanye)
     * - Status Transaksi (Terverifikasi / Semua)
     */
    public function exportPdf(Request $request): \Illuminate\Http\Response|\Illuminate\View\View
    {
        $user = Auth::user();

        // 1. Inisialisasi Query Builder
        $query = Donator::with(['ranting.mwc', 'relawan']);

        // 2. Filter Wilayah Hierarkis Berdasarkan Hak Akses Pengguna
        $mwcId = null;
        $rantingId = null;
        $wilayahLabel = 'Seluruh Wilayah PCNU Lamongan';

        if ($user && $user->isRanting()) {
            $rantingId = $user->ranting_id;
            $query->where('ranting_id', $rantingId);
            $ranting = Ranting::with('mwc')->find($rantingId);
            $wilayahLabel = "Ranting {$ranting?->name}" . ($ranting?->mwc ? " (MWC {$ranting->mwc->name})" : "");
        } elseif ($user && $user->isMwc()) {
            $mwcId = $user->mwc_id;
            $mwc = Mwc::find($mwcId);
            $wilayahLabel = "MWC {$mwc?->name}";

            if ($request->filled('ranting_id')) {
                $requestedRantingId = (int) $request->input('ranting_id');
                if (Ranting::where('id', $requestedRantingId)->where('mwc_id', $mwcId)->exists()) {
                    $rantingId = $requestedRantingId;
                    $query->where('ranting_id', $rantingId);
                    $ranting = Ranting::find($rantingId);
                    $wilayahLabel = "Ranting {$ranting?->name} (MWC {$mwc?->name})";
                } else {
                    $query->whereHas('ranting', fn($q) => $q->where('mwc_id', $mwcId));
                }
            } else {
                $query->whereHas('ranting', fn($q) => $q->where('mwc_id', $mwcId));
            }
        } else {
            // PC / Superadmin
            if ($request->filled('mwc_id')) {
                $mwcId = (int) $request->input('mwc_id');
                $mwc = Mwc::find($mwcId);
                $wilayahLabel = "MWC {$mwc?->name}";

                if ($request->filled('ranting_id')) {
                    $rantingId = (int) $request->input('ranting_id');
                    $query->where('ranting_id', $rantingId);
                    $ranting = Ranting::find($rantingId);
                    $wilayahLabel = "Ranting {$ranting?->name} (MWC {$mwc?->name})";
                } else {
                    $query->whereHas('ranting', fn($q) => $q->where('mwc_id', $mwcId));
                }
            } elseif ($request->filled('ranting_id')) {
                $rantingId = (int) $request->input('ranting_id');
                $query->where('ranting_id', $rantingId);
                $ranting = Ranting::with('mwc')->find($rantingId);
                $wilayahLabel = "Ranting {$ranting?->name}" . ($ranting?->mwc ? " (MWC {$ranting->mwc->name})" : "");
            }
        }

        // 3. Filter Rentang Tanggal (start_date & end_date)
        $startDate = $request->input('start_date');
        $endDate   = $request->input('end_date');
        $dateRange = 'Semua Periode';

        if ($startDate && $endDate) {
            $query->where(function ($q) use ($startDate, $endDate) {
                $q->whereBetween('joined_date', [$startDate, $endDate])
                  ->orWhere(function ($sub) use ($startDate, $endDate) {
                      $sub->whereNull('joined_date')
                          ->whereBetween('created_at', [
                              \Carbon\Carbon::parse($startDate)->startOfDay(),
                              \Carbon\Carbon::parse($endDate)->endOfDay()
                          ]);
                  });
            });
            $dateRange = \Carbon\Carbon::parse($startDate)->format('d/m/Y') . ' s/d ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
        } elseif ($startDate) {
            $query->where(function ($q) use ($startDate) {
                $q->where('joined_date', '>=', $startDate)
                  ->orWhere(function ($sub) use ($startDate) {
                      $sub->whereNull('joined_date')
                          ->where('created_at', '>=', \Carbon\Carbon::parse($startDate)->startOfDay());
                  });
            });
            $dateRange = 'Mulai ' . \Carbon\Carbon::parse($startDate)->format('d/m/Y');
        } elseif ($endDate) {
            $query->where(function ($q) use ($endDate) {
                $q->where('joined_date', '<=', $endDate)
                  ->orWhere(function ($sub) use ($endDate) {
                      $sub->whereNull('joined_date')
                          ->where('created_at', '<=', \Carbon\Carbon::parse($endDate)->endOfDay());
                  });
            });
            $dateRange = 'Sampai ' . \Carbon\Carbon::parse($endDate)->format('d/m/Y');
        }

        // 4. Filter Kategori Program (Zakat, Infaq, KOIN NU, Kampanye Tematik)
        $kategoriInput = $request->input('kategori_program', 'koin_nu');
        $kategoriMap = [
            'koin_nu'   => 'KOIN NU (Kotak Infaq Nahdlatul Ulama)',
            'infaq'     => 'Infaq & Shadaqah Terikat',
            'zakat'     => 'Zakat (Fitrah & Maal)',
            'kampanye'  => 'Kampanye Tematik / Khusus',
            'all'       => 'Semua Kategori Program',
        ];
        $kategoriLabel = $kategoriMap[$kategoriInput] ?? 'KOIN NU (Kotak Infaq Nahdlatul Ulama)';

        // 5. Filter Status Transaksi / Donatur (Default: Terverifikasi / Aktif)
        $statusInput = $request->input('status');
        if (empty($statusInput) || $statusInput === 'terverifikasi' || $statusInput === 'confirmed' || $statusInput === 'active') {
            $query->where('status', Donator::STATUS_ACTIVE);
            $statusLabel = 'Terverifikasi (Aktif)';
        } elseif ($statusInput === 'all') {
            $statusLabel = 'Semua Status';
        } else {
            $query->where('status', $statusInput);
            $statusLabel = ucfirst($statusInput);
        }

        // Filter Petugas Relawan Pengampu
        if ($request->filled('relawan_id')) {
            $query->where('relawan_id', (int) $request->input('relawan_id'));
        }

        // Filter Pembagian Petugas (Sudah Ada Petugas vs Belum Ada Petugas)
        if ($request->filled('assignment')) {
            if ($request->input('assignment') === 'assigned') {
                $query->whereNotNull('relawan_id');
            } elseif ($request->input('assignment') === 'unassigned') {
                $query->whereNull('relawan_id');
            }
        }

        // Filter Kata Kunci Pencarian
        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        // 6. LOGIKA NATURAL SORTING PADA KODE KALENG (CRITICAL REQUIREMENT)
        // Menangani '1, 2, 3, 10, 11' serta 'KLG-1, KLG-2, KLG-10' dan 'KLG-M09R17-0101-001'
        $driver = \Illuminate\Support\Facades\DB::getDriverName();

        if ($driver === 'mysql') {
            $query->orderByRaw("
                CASE 
                    WHEN box_number REGEXP '^[0-9]+$' THEN CAST(box_number AS UNSIGNED)
                    WHEN box_number REGEXP '[0-9]+$' THEN CAST(REGEXP_SUBSTR(box_number, '[0-9]+$') AS UNSIGNED)
                    ELSE CAST(box_number AS UNSIGNED)
                END ASC
            ")
            ->orderByRaw("LENGTH(box_number) ASC")
            ->orderBy("box_number", "asc");
        } else {
            // SQLite / Database Driver Lain
            $query->orderByRaw("CAST(box_number AS INTEGER) ASC")
                  ->orderByRaw("LENGTH(box_number) ASC")
                  ->orderBy("box_number", "asc");
        }

        // Eksekusi Query
        $rawDonators = $query->get();

        // Jaminan Lapis Kedua (Dual-Layer Natural Sort menggunakan algoritma strnatcasecmp PHP)
        $donators = $rawDonators->sort(function ($a, $b) {
            $kodeA = (string) ($a->kode_kaleng ?? $a->box_number ?? '');
            $kodeB = (string) ($b->kode_kaleng ?? $b->box_number ?? '');
            return strnatcasecmp($kodeA, $kodeB);
        })->values();

        // 7. Hitung Akumulasi Nominal & Data Rekapitulasi
        $totalNominal = 0;
        foreach ($donators as $d) {
            // Perolehan nominal donatur (default 0 jika belum ada mutasi individual)
            $d->nominal = $d->nominal ?? 0;
            $totalNominal += (float) $d->nominal;
        }

        $totalCount = $donators->count();
        $printedAt = \Carbon\Carbon::now()->translatedFormat('d F Y, H:i') . ' WIB';

        // 8. Lembar Pengesahan
        $signatures = [
            'doc_place_date' => $request->input('doc_place_date', 'Lamongan, ' . \Carbon\Carbon::now()->translatedFormat('d F Y')),
            'pihak1_jabatan' => $request->input('pihak1_jabatan', 'Petugas / Admin Keuangan'),
            'pihak1_nama'    => $request->input('pihak1_nama', $user?->name ?? 'M. KHOIRUL ANAM, S.Pd.'),
            'pihak1_nip'     => $request->input('pihak1_nip', 'NIP / ID: 35.24.01.002'),
            'pihak2_jabatan' => $request->input('pihak2_jabatan', 'Pimpinan PC LAZISNU Lamongan (Ketua)'),
            'pihak2_nama'    => $request->input('pihak2_nama', 'H. M. SYAMSUDDIN, M.Pd.I'),
            'pihak2_nip'     => $request->input('pihak2_nip', 'NIP / ID: 35.24.01.001'),
        ];

        $viewData = compact(
            'donators',
            'totalCount',
            'totalNominal',
            'dateRange',
            'wilayahLabel',
            'kategoriLabel',
            'statusLabel',
            'printedAt',
            'signatures'
        );

        // Jika user meminta download via library DomPDF (jika terinstall)
        if ($request->input('format') === 'dompdf' && class_exists('\Barryvdh\DomPDF\Facade\Pdf')) {
            $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView('donators.export_pdf', $viewData);
            return $pdf->download('laporan-data-donatur-' . date('Ymd-His') . '.pdf');
        }

        return response()->view('donators.export_pdf', $viewData);
    }
}
