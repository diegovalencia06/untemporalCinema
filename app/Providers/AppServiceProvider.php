<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- IMPORTANTE AÑADIR ESTA LÍNEA

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        // Forzar HTTPS si estamos en el servidor (Render)
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }
    }
}