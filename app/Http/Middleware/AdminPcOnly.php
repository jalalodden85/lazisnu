<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminPcOnly
{
    /**
     * Handle an incoming request.
     * Hanya memperbolehkan akses bagi role 'pc' (Admin PC LAZISNU Lamongan)
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user || !$user->isPc()) {
            if ($request->expectsJson() || $request->is('api/*')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Akses Ditolak: Fitur pengelolaan Periode Pentong hanya dapat diakses oleh Administrator Cabang (PC Lazisnu).',
                ], 403);
            }

            abort(403, 'Akses Ditolak: Fitur pengelolaan Periode Pentong hanya dapat diakses oleh Administrator Cabang (PC Lazisnu).');
        }

        return $next($request);
    }
}
