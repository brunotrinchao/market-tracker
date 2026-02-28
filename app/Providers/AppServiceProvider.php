<?php

namespace App\Providers;

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
        $appUrl = (string) config('app.url', '');

        // Garante URLs HTTPS quando o app estiver sendo exposto via túnel HTTPS (ex.: ngrok).
        if (str_starts_with($appUrl, 'https://')) {
            URL::forceScheme('https');
        }
    }
}
