<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- ESTO ES VITAL

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Obligamos a Laravel a usar HTTPS para todo (CSS, JS, Imágenes)
        if (str_contains(config('app.url'), 'ngrok') || request()->secure()) {
            URL::forceScheme('https');
        }
    }
}