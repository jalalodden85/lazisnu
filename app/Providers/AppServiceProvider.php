<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        \Illuminate\Pagination\Paginator::useBootstrapFour();

        // Gate Hak Akses Memproses & Membagikan Hasil Koin (PC dan MWC)
        Gate::define('proses-hasil-koin', function (User $user) {
            return $user->isSuperAdmin() || $user->isPc() || $user->isMwc();
        });

        // Gate Hak Akses BKU (Buku Kas Umum) T-Account
        Gate::define('akses-bku', function (User $user) {
            return $user->hasMenuAccess('bku.index');
        });

        // Gate Hak Akses BKU Operasional (Hak Amil)
        Gate::define('akses-bku-operasional', function (User $user) {
            return $user->hasMenuAccess('bku-operasional.index');
        });

        // Gate Hak Akses Menu Hasil Koin
        Gate::define('akses-hasil-koin', function (User $user) {
            return $user->hasMenuAccess('pentasyarufan.hasil-koin.index');
        });

        if (app()->environment('production') || str_starts_with((string) config('app.url'), 'https://')) {
            URL::forceScheme('https');
        }
    }
}
