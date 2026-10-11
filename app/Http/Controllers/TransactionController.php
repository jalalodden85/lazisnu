<?php

namespace App\Http\Controllers;

use App\Models\KoinTransaction;
use App\Models\Mwc;
use App\Models\Ranting;
use App\Models\Relawan;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TransactionController extends Controller
{
    /**
     * Menampilkan riwayat setoran koin infaq per relawan dengan filter hirarki
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = KoinTransaction::with(['relawan', 'ranting', 'mwc', 'operator']);

        // Filter Hak Akses Hirarki:
        if ($user && $user->isRanting()) {
            // Admin Ranting: hanya melihat transaksi rantingnya
            $query->where('ranting_id', $user->ranting_id);
            $mwcs = Mwc::where('id', $user->ranting?->mwc_id ?? 0)->get();
            $rantings = Ranting::where('id', $user->ranting_id)->get();
        } elseif ($user && $user->isMwc()) {
            // Admin MWC: hanya melihat transaksi dalam lingkup MWC-nya
            $query->where('mwc_id', $user->mwc_id);
            $mwcs = Mwc::where('id', $user->mwc_id)->get();
            $rantings = Ranting::where('mwc_id', $user->mwc_id)->orderBy('name')->get();

            if ($request->filled('ranting_id')) {
                $requestedRantingId = (int) $request->input('ranting_id');
                if (Ranting::where('id', $requestedRantingId)->where('mwc_id', $user->mwc_id)->exists()) {
                    $query->byRanting($requestedRantingId);
                }
            }
        } else {
            // PC Lazisnu / Guest: Melihat semua transaksi se-Cabang
            $mwcs = Mwc::orderBy('name')->get();
            $rantings = Ranting::orderBy('name')->get();

            if ($request->filled('mwc_id')) {
                $query->byMwc((int) $request->input('mwc_id'));
            }

            if ($request->filled('ranting_id')) {
                $query->byRanting((int) $request->input('ranting_id'));
            }
        }

        if ($request->filled('relawan_id')) {
            $query->byRelawan((int) $request->input('relawan_id'));
        }

        if ($request->filled('period')) {
            $query->period($request->input('period'));
        }

        if ($request->filled('start_date') || $request->filled('end_date')) {
            if ($request->filled('start_date') && $request->filled('end_date')) {
                $query->whereBetween('collection_date', [$request->input('start_date'), $request->input('end_date')]);
            } elseif ($request->filled('start_date')) {
                $query->where('collection_date', '>=', $request->input('start_date'));
            } else {
                $query->where('collection_date', '<=', $request->input('end_date'));
            }
        }

        // Filter Periode Pentong:
        // - Default View: Tampilkan riwayat setoran untuk periode yang sedang aktif saat ini jika filter belum diset.
        // - Opsi "all" ("Semua Periode"): Menampilkan akumulasi seluruh data riwayat transaksi tanpa batasan periode.
        // - Opsi Spesifik: Filter transaksi milik periode tersebut.
        $allPenthongs = \App\Models\Penthong::orderByDesc('tahun')->orderByDesc('bulan')->orderByDesc('id')->get();
        $activePenthong = \App\Models\Penthong::getActive($user?->mwc_id, $user?->ranting_id);
        
        $selectedPeriode = $request->input('periode_id', $request->input('penthong_id'));
        if ($selectedPeriode === null) {
            if ($activePenthong) {
                $selectedPeriode = (string) $activePenthong->id;
                $query->where(function ($q) use ($activePenthong) {
                    $q->where('penthong_id', $activePenthong->id)
                      ->orWhere('periode_pentong_id', $activePenthong->id);
                });
            } else {
                $selectedPeriode = 'all';
            }
        } elseif ($selectedPeriode !== 'all' && !empty($selectedPeriode)) {
            $targetId = (int) $selectedPeriode;
            $query->where(function ($q) use ($targetId) {
                $q->where('penthong_id', $targetId)
                  ->orWhere('periode_pentong_id', $targetId);
            });
        }

        $totalNominal = (clone $query)->where('status', KoinTransaction::STATUS_CONFIRMED)->sum('amount');
        $totalCount = (clone $query)->count();

        $transactions = $query->with('penthong')
            ->latest('collection_date')
            ->latest('id')
            ->paginate(15)
            ->withQueryString();

        return view('transactions.index', compact(
            'transactions',
            'totalNominal',
            'totalCount',
            'mwcs',
            'rantings',
            'allPenthongs',
            'activePenthong',
            'selectedPeriode'
        ));
    }

    /**
     * Form pencatatan setoran koin dari Relawan
     */
    public function create(Request $request): View
    {
        $user = Auth::user();
        $relawanQuery = Relawan::active()->with('ranting');

        // Jika user adalah Admin Ranting, HANYA tampilkan relawan di rantingnya sendiri
        if ($user && $user->isRanting()) {
            $relawanQuery->where('ranting_id', $user->ranting_id);
        } elseif ($user && $user->isMwc()) {
            // Jika user adalah Admin MWC, HANYA tampilkan relawan di ranting wilayah MWC-nya
            $relawanQuery->whereHas('ranting', fn($q) => $q->where('mwc_id', $user->mwc_id));
            if ($request->filled('ranting_id')) {
                $requestedRantingId = (int) $request->input('ranting_id');
                if (Ranting::where('id', $requestedRantingId)->where('mwc_id', $user->mwc_id)->exists()) {
                    $relawanQuery->where('ranting_id', $requestedRantingId);
                }
            }
        } elseif ($request->filled('ranting_id')) {
            $relawanQuery->where('ranting_id', (int) $request->input('ranting_id'));
        }

        $relawans = $relawanQuery->orderBy('nama')->get();
        $activePenthong = \App\Models\Penthong::getActive($user?->mwc_id, $user?->ranting_id);
        $penthongs = \App\Models\Penthong::aktif()->get();

        return view('transactions.create', compact('relawans', 'activePenthong', 'penthongs'));
    }

    /**
     * Logika Store dengan Keamanan Multi-Tenancy Ketat:
     * - Memvalidasi kepemilikan relawan (mencegah manipulasi ID relawan lintas ranting)
     * - Mengunci ranting_id dan mwc_id murni dari relasi database internal
     */
    public function store(Request $request): RedirectResponse|JsonResponse
    {
        // 1. Sanitasi input nominal uang rupiah jika mengandung titik/koma/Rp
        if ($request->has('amount')) {
            $cleanAmount = preg_replace('/[^0-9]/', '', (string) $request->amount);
            $request->merge(['amount' => $cleanAmount]);
        }

        // 2. Validasi input formulir
        $validated = $request->validate([
            'relawan_id'      => ['required', 'exists:relawans,id'],
            'amount'          => ['required', 'numeric', 'min:500'],
            'collection_date' => ['required', 'date', 'before_or_equal:today'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'proof_image'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'relawan_id.required'      => 'Pilih nama Relawan koordinator penyetor koin.',
            'relawan_id.exists'        => 'Data relawan yang dipilih tidak terdaftar di sistem.',
            'amount.required'          => 'Nominal setoran koin wajib diisi.',
            'amount.numeric'           => 'Nominal setoran harus berupa angka yang valid.',
            'amount.min'               => 'Nominal setoran minimal Rp 500.',
            'collection_date.required' => 'Tanggal serah terima koin wajib diisi.',
            'collection_date.before_or_equal' => 'Tanggal setoran tidak boleh melewati tanggal hari ini.',
        ]);

        $user = Auth::user();

        // 3. AMBIL DATA RELAWAN BESERTA HIRARKINYA
        $relawan = Relawan::with('ranting.mwc')->findOrFail($validated['relawan_id']);

        // 4. LOGIKA KEAMANAN (MULTI-TENANT ENFORCEMENT):
        // Jika penginput adalah Admin Ranting, pastikan Relawan benar-benar terdaftar di Ranting miliknya
        if ($user && $user->isRanting()) {
            if ((int) $relawan->ranting_id !== (int) $user->ranting_id) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menginput transaksi relawan dari Ranting lain.');
            }
        } elseif ($user && $user->isMwc()) {
            if ((int) ($relawan->ranting?->mwc_id ?? 0) !== (int) $user->mwc_id) {
                abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang untuk menginput transaksi relawan di luar wilayah MWC Anda.');
            }
        }

        // 5. PENETAPAN DATA HIRARKI SECARA OTOMATIS & AMAN
        // Kolom ranting_id dan mwc_id DIKUNCI dari data relawan terverifikasi, bukan dari inputan user
        $rantingId = $relawan->ranting_id;
        $mwcId     = $relawan->ranting->mwc_id;
        $period    = Carbon::parse($validated['collection_date'])->format('Y-m');

        // 6. Handle upload bukti foto jika ada
        $proofPath = null;
        if ($request->hasFile('proof_image')) {
            $proofPath = $request->file('proof_image')->store('koin_proofs', 'public');
        }

        // 6. Validasi Wajib Periode Pentong (Active / Open Constraint)
        $penthongInputId = $request->input('periode_pentong_id', $request->input('penthong_id'));
        if ($penthongInputId) {
            $penthong = \App\Models\Penthong::find($penthongInputId);
        } else {
            $penthong = \App\Models\Penthong::getActive($mwcId, $rantingId);
        }

        if (!$penthong || !$penthong->isOpen()) {
            $errorMessage = 'Tidak dapat menginput setoran. Periode pentong saat ini sedang ditutup atau belum dibuka oleh admin.';
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }
            return back()->withInput()->with('error', $errorMessage);
        }

        $penthongId = $penthong->id;

        // 7. Simpan Transaksi ke Database (Terikat Wajib ke periode_pentong_id)
        $transaction = KoinTransaction::create([
            'penthong_id'        => $penthongId,
            'periode_pentong_id' => $penthongId,
            'relawan_id'         => $relawan->id,
            'ranting_id'         => $rantingId,
            'mwc_id'             => $mwcId,
            'user_id'            => $user?->id ?? 1,
            'amount'             => $validated['amount'],
            'collection_date'    => $validated['collection_date'],
            'period'             => $period,
            'notes'              => $validated['notes'] ?? null,
            'proof_image'        => $proofPath,
            'status'             => KoinTransaction::STATUS_CONFIRMED,
        ]);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Setoran koin infaq sebesar {$transaction->formatted_amount} dari Relawan {$relawan->nama} berhasil dicatat pada periode {$penthong->nama_periode}.",
                'data'    => $transaction->load(['penthong', 'relawan']),
            ], 201);
        }

        return redirect()
            ->route('transactions.show', $transaction->id)
            ->with('success', "Setoran koin infaq sebesar {$transaction->formatted_amount} dari Relawan {$relawan->nama} ({$relawan->area_tugas}) berhasil dicatat pada {$penthong->nama_periode}.");
    }

    /**
     * Tanda terima kuitansi setoran koin
     */
    public function show(KoinTransaction $transaction): View
    {
        $user = Auth::user();
        if ($user && $user->isRanting() && (int) $transaction->ranting_id !== (int) $user->ranting_id) {
            abort(403, 'Akses Ditolak: Transaksi ini berada di luar wilayah Ranting Anda.');
        }
        if ($user && $user->isMwc() && (int) $transaction->mwc_id !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Transaksi ini berada di luar wilayah MWC Anda.');
        }

        $transaction->load(['relawan', 'ranting', 'mwc', 'operator']);
        return view('transactions.show', compact('transaction'));
    }

    /**
     * Form edit transaksi setoran koin (Hanya jika periode pentong masih OPEN)
     */
    public function edit(KoinTransaction $transaction): View|RedirectResponse
    {
        $user = Auth::user();
        if ($user && $user->isRanting() && (int) $transaction->ranting_id !== (int) $user->ranting_id) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang mengedit transaksi dari Ranting lain.');
        }
        if ($user && $user->isMwc() && (int) $transaction->mwc_id !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang mengedit transaksi di luar wilayah MWC Anda.');
        }

        // Validasi Status Periode Pentong (Kunci Transaksi Otomatis)
        $penthong = $transaction->penthong;
        if (!$penthong || !$penthong->isOpen()) {
            return redirect()
                ->route('transactions.index')
                ->with('error', 'Data transaksi tidak dapat diubah atau dihapus karena periode pentong telah resmi ditutup oleh Admin PC.');
        }

        $transaction->load(['relawan', 'ranting', 'mwc', 'penthong']);
        return view('transactions.edit', compact('transaction'));
    }

    /**
     * Update data setoran koin (Hanya jika periode pentong masih OPEN)
     */
    public function update(Request $request, KoinTransaction $transaction): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->isRanting() && (int) $transaction->ranting_id !== (int) $user->ranting_id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak: Anda tidak memiliki wewenang mengedit transaksi dari Ranting lain.',
                ], 403);
            }
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang mengedit transaksi dari Ranting lain.');
        }
        if ($user && $user->isMwc() && (int) $transaction->mwc_id !== (int) $user->mwc_id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak: Anda tidak memiliki wewenang mengedit transaksi di luar wilayah MWC Anda.',
                ], 403);
            }
            abort(403, 'Akses Ditolak: Anda tidak memiliki wewenang mengedit transaksi di luar wilayah MWC Anda.');
        }

        // Validasi Status Periode Pentong (Kunci Transaksi Otomatis)
        $penthong = $transaction->penthong;
        if (!$penthong || !$penthong->isOpen()) {
            $errorMessage = 'Data transaksi tidak dapat diubah atau dihapus karena periode pentong telah resmi ditutup oleh Admin PC.';
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }
            return back()->withInput()->with('error', $errorMessage);
        }

        // Sanitasi input nominal uang rupiah
        if ($request->has('amount')) {
            $cleanAmount = preg_replace('/[^0-9]/', '', (string) $request->amount);
            $request->merge(['amount' => $cleanAmount]);
        }

        $validated = $request->validate([
            'amount'          => ['required', 'numeric', 'min:500'],
            'collection_date' => ['required', 'date', 'before_or_equal:today'],
            'notes'           => ['nullable', 'string', 'max:500'],
            'proof_image'     => ['nullable', 'image', 'mimes:jpeg,png,jpg,webp', 'max:2048'],
        ], [
            'amount.required'          => 'Nominal setoran koin wajib diisi.',
            'amount.numeric'           => 'Nominal setoran harus berupa angka yang valid.',
            'amount.min'               => 'Nominal setoran minimal Rp 500.',
            'collection_date.required' => 'Tanggal serah terima koin wajib diisi.',
            'collection_date.before_or_equal' => 'Tanggal setoran tidak boleh melewati tanggal hari ini.',
        ]);

        if ($request->hasFile('proof_image')) {
            if ($transaction->proof_image && Storage::disk('public')->exists($transaction->proof_image)) {
                Storage::disk('public')->delete($transaction->proof_image);
            }
            $validated['proof_image'] = $request->file('proof_image')->store('koin_proofs', 'public');
        }

        $validated['period'] = Carbon::parse($validated['collection_date'])->format('Y-m');

        $transaction->update($validated);

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Data transaksi {$transaction->invoice_code} berhasil diperbarui.",
                'data'    => $transaction->fresh(['penthong', 'relawan', 'ranting', 'mwc']),
            ]);
        }

        return redirect()
            ->route('transactions.show', $transaction->id)
            ->with('success', "Data transaksi {$transaction->invoice_code} berhasil diperbarui.");
    }

    /**
     * Batalkan / hapus transaksi (Hanya jika periode pentong masih OPEN)
     */
    public function destroy(Request $request, KoinTransaction $transaction): RedirectResponse|JsonResponse
    {
        $user = Auth::user();
        if ($user && $user->isRanting() && (int) $transaction->ranting_id !== (int) $user->ranting_id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak: Anda tidak berhak menghapus transaksi dari Ranting lain.',
                ], 403);
            }
            abort(403, 'Akses Ditolak: Anda tidak berhak menghapus transaksi dari Ranting lain.');
        }
        if ($user && $user->isMwc() && (int) $transaction->mwc_id !== (int) $user->mwc_id) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak: Anda tidak berhak menghapus transaksi dari MWC lain.',
                ], 403);
            }
            abort(403, 'Akses Ditolak: Anda tidak berhak menghapus transaksi dari MWC lain.');
        }

        // Validasi Status Periode Pentong (Kunci Transaksi Otomatis)
        $penthong = $transaction->penthong;
        if (!$penthong || !$penthong->isOpen()) {
            $errorMessage = 'Data transaksi tidak dapat diubah atau dihapus karena periode pentong telah resmi ditutup oleh Admin PC.';
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => $errorMessage,
                ], 422);
            }
            return back()->with('error', $errorMessage);
        }

        if ($transaction->proof_image && Storage::disk('public')->exists($transaction->proof_image)) {
            Storage::disk('public')->delete($transaction->proof_image);
        }

        $code = $transaction->invoice_code;
        $transaction->delete();

        if ($request->expectsJson() || $request->is('api/*')) {
            return response()->json([
                'success' => true,
                'message' => "Transaksi {$code} berhasil dihapus.",
            ]);
        }

        return redirect()
            ->route('transactions.index')
            ->with('success', "Transaksi {$code} berhasil dihapus.");
    }
}
