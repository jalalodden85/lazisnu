<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->alias([
            'ranting.active'  => \App\Http\Middleware\CheckRantingStatus::class,
            'admin.pc'        => \App\Http\Middleware\AdminPcOnly::class,
            'menu.permission' => \App\Http\Middleware\CheckMenuAccess::class,
        ]);
        $middleware->web(append: [
            \App\Http\Middleware\CheckMenuAccess::class,
        ]);
        $middleware->redirectGuestsTo(function (\Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                return null;
            }
            return route('login');
        });
        $middleware->redirectUsersTo(function (\Illuminate\Http\Request $request) {
            return route('dashboard');
        });
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Illuminate\Auth\AuthenticationException $e, \Illuminate\Http\Request $request) {
            if ($request->is('api/*') || $request->expectsJson() || $request->ajax()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated: Token atau sesi autentikasi tidak valid atau telah kadaluarsa.',
                ], 401);
            }
            return redirect()->guest(route('login'))->with('error', 'Sesi Anda telah habis, silakan login kembali.');
        });
    })->create();
