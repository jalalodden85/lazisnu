<?php

namespace App\Http\Controllers;

use App\Imports\RelawanImport;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Relawan;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\StreamedResponse;

class RelawanController extends Controller
{
    /**
     * Menampilkan daftar relawan koordinator RT/RW
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Relawan::with('ranting.mwc');

        // Batasi Hak Akses Hirarki Wilayah
        if ($user && $user->isRanting()) {
            // Ranting HANYA bisa melihat & mengontrol relawan rantingnya sendiri
            $query->where('ranting_id', $user->ranting_id);
            $rantings = Ranting::where('id', $user->ranting_id)->get();
        } elseif ($user && $user->isMwc()) {
            // MWC HANYA bisa melihat relawan di wilayah ranting binaan MWC-nya
            $query->whereHas('ranting', fn($q) => $q->where('mwc_id', $user->mwc_id));

            if ($request->filled('ranting_id')) {
                $requestedRantingId = (int) $request->input('ranting_id');
                if (Ranting::where('id', $requestedRantingId)->where('mwc_id', $user->mwc_id)->exists()) {
                    $query->where('ranting_id', $requestedRantingId);
                }
            }
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        } else {
            // PC Lazisnu / Guest: Melihat semua relawan se-Cabang
            if ($request->filled('mwc_id')) {
                $query->whereHas('ranting', fn($q) => $q->where('mwc_id', (int) $request->input('mwc_id')));
            }
            if ($request->filled('ranting_id')) {
                $query->where('ranting_id', (int) $request->input('ranting_id'));
            }
            $rantings = Ranting::orderBy('name')->get();
        }

        if ($request->filled('search')) {
            $query->search($request->input('search'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        $relawans = $query->latest('id')->paginate(15)->withQueryString();

        return view('relawans.index', compact('relawans', 'rantings'));
    }

    /**
     * Form pendaftaran relawan baru
     */
    public function create(): View
    {
        $user = Auth::user();

        if ($user && $user->isRanting()) {
            $rantings = Ranting::where('id', $user->ranting_id)->get();
        } elseif ($user && $user->isMwc()) {
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        } else {
            $rantings = Ranting::with('mwc')->orderBy('name')->get();
        }

        return view('relawans.create', compact('rantings'));
    }

    /**
     * Simpan data relawan secara manual
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();

        if ($user && $user->isRanting()) {
            // Kunci murni ke Ranting milik user sendiri
            $rantingId = (int) $user->ranting_id;
        } elseif ($user && $user->isMwc()) {
            $reqRantingId = (int) $request->input('ranting_id');
            $allowed = Ranting::where('id', $reqRantingId)->where('mwc_id', $user->mwc_id)->exists();
            if (!$allowed) {
                abort(403, 'Akses Ditolak: Ranting yang dipilih berada di luar wilayah MWC Anda.');
            }
            $rantingId = $reqRantingId;
        } else {
            $rantingId = (int) $request->input('ranting_id', 1);
        }

        $validated = $request->validate([
            'nama'       => ['required', 'string', 'max:150'],
            'nik'        => ['required', 'digits:16', 'unique:relawans,nik'],
            'no_hp'      => ['nullable', 'string', 'max:25'],
            'area_tugas' => ['required', 'string', 'max:100'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        $validated['ranting_id'] = $rantingId;
        $validated['status'] = Relawan::STATUS_ACTIVE;
        $validated['joined_date'] = now()->toDateString();

        $relawan = Relawan::create($validated);

        return redirect()
            ->route('relawans.index')
            ->with('success', "Relawan {$relawan->nama} ({$relawan->area_tugas}) berhasil didaftarkan.");
    }

    /**
     * Form edit data relawan
     */
    public function edit(Relawan $relawan): View
    {
        $user = Auth::user();

        // Ranting hanya bisa mengontrol relawan di wilayah rantingnya
        if ($user && $user->isRanting() && (int) $relawan->ranting_id !== (int) $user->ranting_id) {
            abort(403, 'Akses Ditolak: Anda hanya berwenang mengontrol relawan di wilayah Ranting Anda.');
        }

        // MWC hanya bisa melihat/mengontrol relawan di wilayah MWC-nya
        if ($user && $user->isMwc() && (int) ($relawan->ranting?->mwc_id ?? 0) !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Relawan ini berada di luar wilayah MWC Anda.');
        }

        if ($user && $user->isRanting()) {
            $rantings = Ranting::where('id', $user->ranting_id)->get();
        } elseif ($user && $user->isMwc()) {
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();
        } else {
            $rantings = Ranting::with('mwc')->orderBy('name')->get();
        }

        return view('relawans.edit', compact('relawan', 'rantings'));
    }

    /**
     * Simpan pembaruan data relawan
     */
    public function update(Request $request, Relawan $relawan): RedirectResponse
    {
        $user = Auth::user();

        if ($user && $user->isRanting() && (int) $relawan->ranting_id !== (int) $user->ranting_id) {
            abort(403, 'Akses Ditolak: Anda hanya berwenang mengontrol relawan di wilayah Ranting Anda.');
        }

        if ($user && $user->isMwc() && (int) ($relawan->ranting?->mwc_id ?? 0) !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Relawan ini berada di luar wilayah MWC Anda.');
        }

        $validated = $request->validate([
            'nama'       => ['required', 'string', 'max:150'],
            'nik'        => ['required', 'digits:16', 'unique:relawans,nik,' . $relawan->id],
            'no_hp'      => ['nullable', 'string', 'max:25'],
            'area_tugas' => ['required', 'string', 'max:100'],
            'status'     => ['required', 'in:active,inactive'],
            'notes'      => ['nullable', 'string', 'max:500'],
        ]);

        if ($user && $user->isRanting()) {
            $validated['ranting_id'] = $user->ranting_id;
        } elseif ($user && $user->isMwc() && $request->filled('ranting_id')) {
            $reqRantingId = (int) $request->input('ranting_id');
            if (Ranting::where('id', $reqRantingId)->where('mwc_id', $user->mwc_id)->exists()) {
                $validated['ranting_id'] = $reqRantingId;
            }
        } elseif ($request->filled('ranting_id')) {
            $validated['ranting_id'] = (int) $request->input('ranting_id');
        }

        $relawan->update($validated);

        return redirect()
            ->route('relawans.index')
            ->with('success', "Data Relawan {$relawan->nama} ({$relawan->area_tugas}) berhasil diperbarui.");
    }

    /**
     * Method Bulk Import Relawan via file Excel/CSV
     */
    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv,txt', 'max:5120'], // Max 5MB
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
            $rantingId = $ranting ? $ranting->id : (Ranting::where('mwc_id', $user->mwc_id)->first()?->id ?? 1);
        } else {
            $rantingId = (int) ($request->input('ranting_id') ?? $user?->ranting_id ?? 1);
        }

        $import = new RelawanImport($rantingId);

        try {
            $import->import($request->file('file'));
        } catch (\Exception $e) {
            return redirect()
                ->route('relawans.index')
                ->with('error', 'Gagal memproses file import: ' . $e->getMessage());
        }

        $failures = $import->failures();
        $failureCount = $failures->count();

        // Kumpulkan detail kegagalan jika ada baris yang invalid/duplikat
        $failureMessages = [];
        if ($failureCount > 0) {
            foreach ($failures as $failure) {
                $rowNumber = $failure->row();
                $attribute = $failure->attribute();
                $errors = implode(', ', $failure->errors());
                $failureMessages[] = "Baris {$rowNumber} ({$attribute}): {$errors}";
            }
        }

        $successMessage = "Proses import data Relawan selesai diproses ke sistem!";
        if ($failureCount > 0) {
            return redirect()
                ->route('relawans.index')
                ->with('success', $successMessage)
                ->with('import_failures', array_slice($failureMessages, 0, 10))
                ->with('total_failures', $failureCount);
        }

        return redirect()
            ->route('relawans.index')
            ->with('success', $successMessage . " Seluruh baris data berhasil diimpor.");
    }

    /**
     * Download format template CSV untuk bulk import relawan
     */
    public function downloadTemplate(): StreamedResponse
    {
        $headers = [
            'Content-Type'        => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="template_import_relawan_koin_nu.csv"',
        ];

        return response()->stream(function () {
            $handle = fopen('php://output', 'w');
            // Header CSV wajib
            fputcsv($handle, ['nama', 'nik', 'no_hp', 'area_tugas']);
            // Contoh baris data 1 & 2
            fputcsv($handle, ['Ahmad Fauzi', '3524011205900001', '081234567890', 'RT 01 / RW 01']);
            fputcsv($handle, ['Siti Maryam', '3524015508920002', '085233445566', 'RT 02 / RW 01']);
            fclose($handle);
        }, 200, $headers);
    }

    /**
     * Hapus data relawan
     */
    public function destroy(Relawan $relawan): RedirectResponse
    {
        $user = Auth::user();

        // Ranting hanya bisa mengontrol & menghapus relawan di wilayah rantingnya
        if ($user && $user->isRanting() && (int) $relawan->ranting_id !== (int) $user->ranting_id) {
            abort(403, 'Akses Ditolak: Anda hanya berwenang mengontrol dan menghapus relawan di wilayah Ranting Anda.');
        }

        // MWC hanya bisa menghapus relawan di wilayah MWC-nya
        if ($user && $user->isMwc() && (int) ($relawan->ranting?->mwc_id ?? 0) !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Relawan ini berada di luar wilayah MWC Anda.');
        }

        // Cek keamanan relasi transaksi: jangan hapus relawan yang sudah ada transaksi
        if ($relawan->transactions()->exists()) {
            return back()->with('error', "Relawan {$relawan->nama} tidak dapat dihapus karena sudah memiliki riwayat setoran koin infaq.");
        }

        $nama = $relawan->nama;
        $relawan->delete();

        return redirect()
            ->route('relawans.index')
            ->with('success', "Relawan {$nama} berhasil dihapus.");
    }
}
