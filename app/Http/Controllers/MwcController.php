<?php

namespace App\Http\Controllers;

use App\Models\Mwc;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

use Illuminate\Support\Facades\Auth;

class MwcController extends Controller
{
    /**
     * Menampilkan daftar Master Data 15 MWC
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = Mwc::withCount(['rantings', 'relawans'])
            ->withSum(['transactions as total_collected' => function ($q) {
                $q->where('status', 'confirmed');
            }], 'amount');

        if ($user && $user->isMwc()) {
            $query->where('id', $user->mwc_id);
        } elseif ($user && $user->isRanting()) {
            $query->where('id', $user->ranting?->mwc_id ?? 0);
        }

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('kecamatan', 'like', "%{$search}%");
            });
        }

        $mwcs = $query->orderBy('code')->paginate(15)->withQueryString();

        return view('mwcs.index', compact('mwcs'));
    }

    /**
     * Form tambah MWC baru
     */
    public function create(): View
    {
        $user = Auth::user();
        if ($user && !$user->isPc()) {
            abort(403, 'Akses Ditolak: Hanya PC Lazisnu yang berhak mengelola master data MWC.');
        }

        return view('mwcs.create');
    }

    /**
     * Simpan data MWC baru
     */
    public function store(Request $request): RedirectResponse
    {
        $user = Auth::user();
        if ($user && !$user->isPc()) {
            abort(403, 'Akses Ditolak: Hanya PC Lazisnu yang berhak mengelola master data MWC.');
        }

        $validated = $request->validate([
            'code'      => ['required', 'string', 'max:10', 'unique:mwcs,code'],
            'name'      => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'address'   => ['nullable', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:25'],
        ], [
            'code.required' => 'Kode MWC wajib diisi (contoh: M01).',
            'code.unique'   => 'Kode MWC ini sudah terdaftar.',
            'name.required' => 'Nama MWC wajib diisi.',
        ]);

        $mwc = Mwc::create($validated);

        return redirect()
            ->route('mwcs.index')
            ->with('success', "Master data MWC {$mwc->name} ({$mwc->code}) berhasil ditambahkan.");
    }

    /**
     * Detail MWC dan daftar Ranting binaannya
     */
    public function show(Mwc $mwc): View
    {
        $user = Auth::user();
        if ($user && $user->isMwc() && (int) $mwc->id !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Anda tidak dapat melihat data MWC lain.');
        }
        if ($user && $user->isRanting() && (int) $mwc->id !== (int) ($user->ranting?->mwc_id ?? 0)) {
            abort(403, 'Akses Ditolak: Anda tidak dapat melihat data MWC lain.');
        }

        $mwc->load(['rantings.relawans', 'users']);
        $totalCollected = $mwc->transactions()->where('status', 'confirmed')->sum('amount');

        return view('mwcs.show', compact('mwc', 'totalCollected'));
    }

    /**
     * Form edit MWC
     */
    public function edit(Mwc $mwc): View
    {
        $user = Auth::user();
        if ($user && $user->isRanting()) {
            abort(403, 'Akses Ditolak.');
        }
        if ($user && $user->isMwc() && (int) $mwc->id !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Anda hanya berwenang mengubah profil MWC Anda sendiri.');
        }

        return view('mwcs.edit', compact('mwc'));
    }

    /**
     * Update data MWC
     */
    public function update(Request $request, Mwc $mwc): RedirectResponse
    {
        $user = Auth::user();
        if ($user && $user->isRanting()) {
            abort(403, 'Akses Ditolak.');
        }
        if ($user && $user->isMwc() && (int) $mwc->id !== (int) $user->mwc_id) {
            abort(403, 'Akses Ditolak: Anda hanya berwenang mengubah profil MWC Anda sendiri.');
        }

        $validated = $request->validate([
            'code'      => ['required', 'string', 'max:10', 'unique:mwcs,code,' . $mwc->id],
            'name'      => ['required', 'string', 'max:100'],
            'kecamatan' => ['required', 'string', 'max:100'],
            'address'   => ['nullable', 'string', 'max:255'],
            'phone'     => ['nullable', 'string', 'max:25'],
        ]);

        $mwc->update($validated);

        return redirect()
            ->route('mwcs.index')
            ->with('success', "Data MWC {$mwc->name} berhasil diperbarui.");
    }

    /**
     * Hapus data MWC
     */
    public function destroy(Mwc $mwc): RedirectResponse
    {
        $user = Auth::user();
        if ($user && !$user->isPc()) {
            abort(403, 'Akses Ditolak: Hanya PC Lazisnu yang berhak menghapus MWC.');
        }

        if ($mwc->rantings()->count() > 0) {
            return redirect()
                ->route('mwcs.index')
                ->with('error', "Gagal menghapus: MWC {$mwc->name} masih memiliki Ranting di bawah naungannya.");
        }

        $name = $mwc->name;
        $mwc->delete();

        return redirect()
            ->route('mwcs.index')
            ->with('success', "Data MWC {$name} berhasil dihapus.");
    }
}
