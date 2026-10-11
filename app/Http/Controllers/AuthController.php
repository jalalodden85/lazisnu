<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AuthController extends Controller
{
    /**
     * Tampilkan formulir login AdminLTE
     */
    public function showLoginForm(): View
    {
        // Ambil 5 akun PC untuk quick-login & petunjuk login
        $pcUsers = User::with('role')
            ->whereHas('role', fn($q) => $q->whereIn('name', [
                'pc_superadmin', 'pc_ketua', 'pc_sekretaris', 'pc_bendahara', 'pc_admin_koin', 'pc_admin_online', 'pc'
            ]))
            ->orderBy('role_id')
            ->get();

        $samplePc = $pcUsers->where('username', 'adminpc')->first() ?? $pcUsers->first();
        $sampleKetua = $pcUsers->where('username', 'ketua_lazisnu')->first();
        $sampleSekretaris = $pcUsers->where('username', 'sekretaris_lazisnu')->first();
        $sampleBendahara = $pcUsers->where('username', 'bendahara_lazisnu')->first();
        $sampleAdminKoin = $pcUsers->where('username', 'admin_koin')->first();
        $sampleAdminOnline = $pcUsers->where('username', 'admin_online')->first();

        $sampleMwc = User::whereHas('role', fn($q) => $q->where('name', 'mwc'))->first();
        $sampleRanting = User::whereHas('role', fn($q) => $q->where('name', 'ranting'))->first();

        return view('auth.login', compact(
            'pcUsers', 'samplePc', 'sampleKetua', 'sampleSekretaris', 'sampleBendahara', 
            'sampleAdminKoin', 'sampleAdminOnline', 'sampleMwc', 'sampleRanting'
        ));
    }

    /**
     * Proses autentikasi login (mendukung input Email atau Kode/Username)
     */
    public function login(Request $request): RedirectResponse
    {
        $request->validate([
            'login'    => 'required|string',
            'password' => 'required|string',
        ], [
            'login.required'    => 'Silakan masukkan Email atau Username/Kode Wilayah.',
            'password.required' => 'Password wajib diisi.',
        ]);

        $loginInput = trim($request->input('login'));
        $password = $request->input('password');
        $remember = $request->boolean('remember');

        // Deteksi apakah input merupakan format email
        $isEmail = filter_var($loginInput, FILTER_VALIDATE_EMAIL) || str_contains($loginInput, '@');

        if ($isEmail) {
            $email = strtolower($loginInput);

            // Cek jika akun non-aktif
            $userCheck = User::where('email', $email)->first();
            if ($userCheck && !$userCheck->is_active && \Illuminate\Support\Facades\Hash::check($password, $userCheck->password)) {
                return back()
                    ->withInput($request->only('login', 'remember'))
                    ->withErrors([
                        'login' => 'Akun Anda berstatus non-aktif. Silakan hubungi pengurus PC LAZISNU untuk aktivasi akun.',
                    ]);
            }

            if (Auth::attempt(['email' => $email, 'password' => $password, 'is_active' => true], $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'))
                    ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
            }
        }

        // Coba login via username dengan normalisasi cerdas
        // (Mendukung 'adminpc', 'admin pc', 'admin_pc', huruf besar/kecil kode MWC & Ranting)
        $normalized = strtolower(preg_replace('/[\s_\-]+/', '', $loginInput));
        $usernameCandidates = [
            $loginInput,
            strtolower($loginInput),
            strtoupper($loginInput),
            $normalized,
        ];

        // Alias umum untuk Super Admin PC
        if (in_array($normalized, ['adminpc', 'admin', 'superadmin', 'pc', 'pcsuperadmin', 'admincabang'], true)) {
            $usernameCandidates[] = 'adminpc';
        }

        $uniqueCandidates = array_values(array_unique($usernameCandidates));

        // Cek jika akun berstatus non-aktif dengan password valid
        $inactiveUser = User::whereIn('username', $uniqueCandidates)->where('is_active', false)->first();
        if ($inactiveUser && \Illuminate\Support\Facades\Hash::check($password, $inactiveUser->password)) {
            return back()
                ->withInput($request->only('login', 'remember'))
                ->withErrors([
                    'login' => 'Akun Anda berstatus non-aktif. Silakan hubungi pengurus PC LAZISNU untuk aktivasi akun.',
                ]);
        }

        foreach ($uniqueCandidates as $candidate) {
            if (Auth::attempt(['username' => $candidate, 'password' => $password, 'is_active' => true], $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'))
                    ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
            }
        }

        // Fallback jika input bukan format email tapi cocok dengan email di database
        if (!$isEmail) {
            $emailFallbackUser = User::where('email', strtolower($loginInput))->first();
            if ($emailFallbackUser && Auth::attempt(['email' => $emailFallbackUser->email, 'password' => $password, 'is_active' => true], $remember)) {
                $request->session()->regenerate();
                return redirect()->intended(route('dashboard'))
                    ->with('success', 'Selamat datang kembali, ' . Auth::user()->name . '!');
            }
        }

        return back()
            ->withInput($request->only('login', 'remember'))
            ->withErrors([
                'login' => 'Email / Username atau Password yang Anda masukkan tidak sesuai, atau akun Anda non-aktif.',
            ]);
    }

    /**
     * Fitur Quick Login untuk pengujian antar-role (PC, MWC, Ranting)
     */
    public function quickLogin(int $userId): RedirectResponse
    {
        $user = User::findOrFail($userId);
        Auth::login($user);
        request()->session()->regenerate();

        return redirect()->route('dashboard')
            ->with('success', "Berhasil beralih akun sebagai: {$user->name} ({$user->role_display_name})");
    }

    /**
     * Proses keluar (logout)
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'Anda telah berhasil keluar dari sistem.');
    }
}
