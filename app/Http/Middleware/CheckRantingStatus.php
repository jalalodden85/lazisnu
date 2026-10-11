<?php

namespace App\Http\Middleware;

use App\Models\Ranting;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckRantingStatus
{
    /**
     * Handle an incoming request.
     *
     * Mengecek apakah status operasional Ranting aktif (is_active == true).
     * Jika non-aktif, blokir hak akses input/manipulasi data (create, store, edit, update, destroy, import)
     * dan kembalikan pesan peringatan administrasi.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();

        if ($user) {
            $isRantingSuspended = false;

            // 1. Pengecekan jika user yang login adalah Admin Ranting
            if ($user->isRanting()) {
                $ranting = $user->ranting;
                if ($ranting && !$ranting->is_active) {
                    $isRantingSuspended = true;
                }
            }

            // 2. Pengecekan jika request mengirimkan atau memodifikasi ID Ranting tertentu (non-PC)
            if (!$user->isPc()) {
                $targetRantingId = $request->input('ranting_id') ?? $request->route('ranting')?->id;
                if ($targetRantingId) {
                    $targetRanting = Ranting::find($targetRantingId);
                    if ($targetRanting && !$targetRanting->is_active) {
                        $isRantingSuspended = true;
                    }
                }
            }

            // Jika status Ranting non-aktif, tolak akses manipulasi data
            if ($isRantingSuspended) {
                $errorMessage = 'Akses input dinonaktifkan oleh PC Lazisnu. Silakan selesaikan urusan administrasi Anda.';

                if ($request->expectsJson()) {
                    return response()->json([
                        'status'  => 'error',
                        'message' => $errorMessage,
                    ], 403);
                }

                $redirect = (url()->previous() && url()->previous() !== url()->current())
                    ? redirect()->back()
                    : redirect()->route('dashboard');

                return $redirect->with('error', $errorMessage);
            }
        }

        return $next($request);
    }
}
