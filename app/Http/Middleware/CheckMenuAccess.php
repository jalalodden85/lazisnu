<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckMenuAccess
{
    /**
     * Memvalidasi apakah user memiliki izin akses ke menu/URL yang diminta.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $routeName = $request->route()?->getName();
        $path = $request->path();

        // Daftar rute portal publik dan autentikasi yang dapat diakses oleh guest
        $publicRoutes = [
            'home',
            'programs.index',
            'programs.detail',
            'articles.index',
            'articles.detail',
            'zakat.calculator',
            'reports.index',
            'donation.store',
            'donation.success',
            'login',
            'login.post',
            'quick-login',
        ];

        // 1. Jika belum login (Guest / Session Expired)
        if (!$user) {
            // Izinkan rute publik dan sistem
            if ($routeName && in_array($routeName, $publicRoutes, true)) {
                return $next($request);
            }
            if ($path === '/' || $path === 'up' || str_starts_with($path, 'storage/')) {
                return $next($request);
            }

            // Tolak akses rute internal dan berikan response 401 untuk API/AJAX
            if ($request->expectsJson() || $request->is('api/*') || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated: Token atau sesi autentikasi tidak valid atau telah kadaluarsa.',
                ], 401);
            }

            // Redirect ke login dengan notifikasi sesi habis untuk navigasi browser
            return redirect()->guest(route('login'))->with('error', 'Sesi Anda telah habis, silakan login kembali.');
        }

        // 2. Super Admin memiliki hak akses absolut ke seluruh sistem
        if ($user->isSuperAdmin()) {
            return $next($request);
        }

        // Rute-rute dasar yang selalu diizinkan untuk semua user login
        $whitelistedRoutes = [
            'dashboard',
            'logout',
            'buku-panduan',
            'profile.edit',
            'profile.update',
            'profile.password.update',
            'mustahiqs.search',
            'mustahiqs.foto',
            'home',
            'programs.index',
            'programs.detail',
            'articles.index',
            'articles.detail',
            'zakat.calculator',
            'reports.index',
            'donation.store',
            'donation.success',
        ];

        if ($routeName && in_array($routeName, $whitelistedRoutes, true)) {
            return $next($request);
        }

        // Izinkan user mengedit profil & ubah password akunnya sendiri
        if (in_array($routeName, ['users.edit', 'users.update'], true)) {
            $targetUser = $request->route('user');
            $targetId = is_object($targetUser) ? $targetUser->id : $targetUser;
            if ($targetId && (int)$targetId === (int)$user->id) {
                return $next($request);
            }
        }

        // 3. Periksa apakah rute / URL ini terdaftar dalam master menu sistem
        $registeredMenu = Menu::where(function ($q) use ($routeName, $path) {
            if ($routeName) {
                $q->where('route_name', $routeName)
                  ->orWhereRaw('? LIKE REPLACE(url_pattern, "*", "%")', [$routeName]);
            }
            if ($path) {
                $q->orWhereRaw('? LIKE REPLACE(url_pattern, "*", "%")', [$path]);
            }
        })->first();

        // Jika rute ini terdaftar dalam proteksi menu, pastikan user memiliki izin
        if ($registeredMenu) {
            $hasAccess = $user->menus()->where('menus.id', $registeredMenu->id)->exists();

            if (!$hasAccess) {
                if ($request->expectsJson() || $request->ajax()) {
                    return response()->json([
                        'success' => false,
                        'message' => "Akses Ditolak (403 Forbidden): Anda tidak memiliki izin hak akses untuk menu [{$registeredMenu->name}]. Silakan hubungi Super Admin.",
                    ], 403);
                }

                abort(403, "Akses Ditolak (403 Forbidden): Anda tidak memiliki izin hak akses untuk modul [{$registeredMenu->name}]. Silakan hubungi Super Admin LAZISNU untuk mengaktifkan menu ini.");
            }
        }

        return $next($request);
    }
}
