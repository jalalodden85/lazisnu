<?php

namespace App\Http\Controllers;

use App\Models\Mwc;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UserController extends Controller
{
    /**
     * Tampilkan daftar akun pengguna dengan filter hirarki
     */
    public function index(Request $request): View
    {
        $user = Auth::user();
        $query = User::with(['role', 'mwc', 'ranting']);

        if ($user && $user->isRanting()) {
            // Ranting HANYA bisa melihat akunnya sendiri
            $query->where('id', $user->id);
            $roles = Role::where('name', 'ranting')->get();
            $mwcs = Mwc::where('id', $user->ranting?->mwc_id ?? 0)->get();
            $countPc = 0;
            $countMwc = 0;
            $countRanting = 1;
        } elseif ($user && $user->isMwc()) {
            // MWC HANYA bisa melihat akun dalam wilayah MWC-nya (Akun MWC tersebut dan akun Ranting di bawahnya)
            $query->where('mwc_id', $user->mwc_id);
            $roles = Role::whereIn('name', ['mwc', 'ranting'])->get();
            $mwcs = Mwc::where('id', $user->mwc_id)->get();
            $countPc = 0;
            $countMwc = 1;
            $countRanting = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))
                ->where('mwc_id', $user->mwc_id)->count();

            if ($request->filled('role_id')) {
                $query->where('role_id', (int) $request->input('role_id'));
            }
        } else {
            // PC LAZISNU / Guest: Melihat semua pengguna se-Cabang
            $roles = Role::all();
            $mwcs = Mwc::orderBy('code')->get();

            $pcRoleNames = ['pc', 'pc_superadmin', 'pc_ketua', 'pc_sekretaris', 'pc_bendahara', 'pc_admin_koin', 'pc_admin_online'];
            $countPc = User::whereHas('role', fn($q) => $q->whereIn('name', $pcRoleNames))->count();
            $countMwc = User::whereHas('role', fn($q) => $q->where('name', 'mwc'))->count();
            $countRanting = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->count();

            if ($request->input('role_group') === 'pc') {
                $query->whereHas('role', fn($q) => $q->whereIn('name', $pcRoleNames));
            } elseif ($request->filled('role_id')) {
                $query->where('role_id', (int) $request->input('role_id'));
            }

            if ($request->filled('mwc_id')) {
                $query->where('mwc_id', (int) $request->input('mwc_id'));
            }

            $pcUsersList = User::with('role')
                ->whereHas('role', fn($q) => $q->whereIn('name', $pcRoleNames))
                ->orderBy('role_id')
                ->get();
        }

        if ($request->filled('search')) {
            $search = trim($request->input('search'));
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->orderBy('role_id')
            ->orderBy('username')
            ->paginate(20)
            ->withQueryString();

        $pcUsersList = $pcUsersList ?? collect();

        return view('users.index', compact('users', 'roles', 'mwcs', 'countPc', 'countMwc', 'countRanting', 'pcUsersList'));
    }

    /**
     * Form tambah akun pengguna baru (Super Admin Only)
     */
    public function create(): View
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->isSuperAdmin()) {
            abort(403, 'Akses Ditolak: Hanya Super Admin PC yang berwenang membuat akun pengguna baru.');
        }

        $roles = Role::all();
        $mwcs = Mwc::orderBy('code')->get();
        $groupedMenus = \App\Models\Menu::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');

        return view('users.create', compact('roles', 'mwcs', 'groupedMenus'));
    }

    /**
     * Simpan akun pengguna baru ke database beserta izin menu
     */
    public function store(Request $request): RedirectResponse
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->isSuperAdmin()) {
            abort(403, 'Akses Ditolak: Hanya Super Admin PC yang berwenang membuat akun pengguna baru.');
        }

        $validated = $request->validate([
            'name'       => 'required|string|max:255',
            'username'   => 'required|string|max:50|unique:users,username',
            'email'      => 'required|email|max:255|unique:users,email',
            'phone'      => 'nullable|string|max:25',
            'role_id'    => 'required|exists:roles,id',
            'mwc_id'     => 'nullable|exists:mwcs,id',
            'password'   => 'required|string|min:6',
            'is_active'  => 'required|boolean',
            'menus'      => 'nullable|array',
            'menus.*'    => 'exists:menus,id',
        ], [
            'name.required'     => 'Nama pengguna wajib diisi.',
            'username.required' => 'Username wajib diisi.',
            'username.unique'   => 'Username ini telah digunakan.',
            'email.required'    => 'Email wajib diisi.',
            'email.unique'      => 'Email ini telah terdaftar.',
            'password.required' => 'Kata sandi akun wajib diisi.',
            'password.min'      => 'Kata sandi minimal 6 karakter.',
        ]);

        $user = User::create([
            'name'       => $validated['name'],
            'username'   => trim($validated['username']),
            'email'      => strtolower(trim($validated['email'])),
            'phone'      => $validated['phone'],
            'role_id'    => (int) $validated['role_id'],
            'mwc_id'     => !empty($validated['mwc_id']) ? (int) $validated['mwc_id'] : null,
            'is_active'  => (bool) $validated['is_active'],
            'password'   => Hash::make($validated['password']),
        ]);

        if ($request->has('menus')) {
            $user->menus()->sync($request->input('menus', []));
        }

        return redirect()->route('users.index')
            ->with('success', "Akun baru [{$user->name}] ({$user->username}) berhasil dibuat beserta hak akses menunya!");
    }

    /**
     * Form edit akun & konfigurasi hak akses menu dinamis
     */
    public function edit(User $user): View
    {
        $currentUser = Auth::user();

        if ($currentUser && $currentUser->isRanting() && $user->id !== $currentUser->id) {
            abort(403, 'Akses Ditolak: Anda hanya berwenang mengelola akun Anda sendiri.');
        }

        if ($currentUser && $currentUser->isMwc()) {
            if ($user->isPc() || (int) $user->mwc_id !== (int) $currentUser->mwc_id) {
                abort(403, 'Akses Ditolak: Anda hanya berwenang mengelola akun di wilayah MWC Anda.');
            }
        }

        $user->load(['role', 'mwc', 'ranting', 'menus']);
        $roles = Role::all();
        $mwcs = Mwc::orderBy('code')->get();
        $groupedMenus = \App\Models\Menu::where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->groupBy('category');
        $userMenuIds = $user->menus->pluck('id')->toArray();

        return view('users.edit', compact('user', 'roles', 'mwcs', 'groupedMenus', 'userMenuIds'));
    }

    /**
     * Simpan pembaruan data pengguna, password, dan sinkronisasi menu
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $currentUser = Auth::user();

        if ($currentUser && $currentUser->isRanting() && $user->id !== $currentUser->id) {
            abort(403, 'Akses Ditolak: Anda hanya berwenang mengelola akun Anda sendiri.');
        }

        if ($currentUser && $currentUser->isMwc()) {
            if ($user->isPc() || (int) $user->mwc_id !== (int) $currentUser->mwc_id) {
                abort(403, 'Akses Ditolak: Anda hanya berwenang mengelola akun di wilayah MWC Anda.');
            }
        }

        $validated = $request->validate([
            'name'      => 'required|string|max:255',
            'username'  => ['nullable', 'string', 'max:50', Rule::unique('users', 'username')->ignore($user->id)],
            'email'     => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone'     => 'nullable|string|max:25',
            'is_active' => 'required|boolean',
            'password'  => 'nullable|string|min:6',
            'role_id'   => 'nullable|exists:roles,id',
            'mwc_id'    => 'nullable|exists:mwcs,id',
            'menus'     => 'nullable|array',
            'menus.*'   => 'exists:menus,id',
        ], [
            'name.required'     => 'Nama pengguna wajib diisi.',
            'email.required'    => 'Alamat email wajib diisi.',
            'email.unique'      => 'Email ini telah digunakan akun lain.',
            'username.unique'   => 'Username / Kode ini telah digunakan.',
            'password.min'      => 'Password baru minimal harus 6 karakter.',
        ]);

        $payload = [
            'name'      => $validated['name'],
            'username'  => $validated['username'] ? trim($validated['username']) : null,
            'email'     => strtolower(trim($validated['email'])),
            'phone'     => $validated['phone'],
            'is_active' => (bool) $validated['is_active'],
        ];

        if (!empty($validated['password'])) {
            $payload['password'] = Hash::make($validated['password']);
        }

        // Super Admin dapat mengubah role, MWC, dan hak akses menu
        if ($currentUser && $currentUser->isSuperAdmin()) {
            if ($request->filled('role_id')) {
                $payload['role_id'] = (int) $request->input('role_id');
            }
            if ($request->has('mwc_id')) {
                $payload['mwc_id'] = $request->input('mwc_id') ? (int) $request->input('mwc_id') : null;
            }

            // SINKRONISASI HAK AKSES MENU DINAMIS (MANY-TO-MANY SYNC)
            $user->menus()->sync($request->input('menus', []));
        }

        $user->update($payload);

        return redirect()->route('users.index')
            ->with('success', "Akun {$user->name} ({$user->username}) dan hak akses menunya berhasil diperbarui!");
    }

    /**
     * Hapus akun pengguna (Super Admin Only)
     */
    public function destroy(User $user): RedirectResponse
    {
        $currentUser = Auth::user();
        if (!$currentUser || !$currentUser->isSuperAdmin()) {
            abort(403, 'Akses Ditolak: Hanya Super Admin PC yang berwenang menghapus akun.');
        }

        if ($user->id === $currentUser->id) {
            return back()->with('error', 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif digunakan.');
        }

        $nama = $user->name;
        $username = $user->username;
        $user->menus()->detach();
        $user->delete();

        return redirect()->route('users.index')
            ->with('success', "Akun [{$nama}] ({$username}) berhasil dihapus dari sistem.");
    }
}
