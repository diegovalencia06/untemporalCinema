<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; // <-- Para forzar HTTPS
use Illuminate\Auth\Notifications\VerifyEmail; // <-- Para el correo
use Illuminate\Notifications\Messages\MailMessage; // <-- Para el correo

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
        // 1. Forzar HTTPS si estamos en producción (Render)
        if (env('APP_ENV') !== 'local') {
            URL::forceScheme('https');
        }

        // 2. Personalizar el correo de verificación
        VerifyEmail::toMailUsing(function (object $notifiable, string $url) {
            return (new MailMessage)
                ->subject('🎬 Verifica tu cuenta en Untemporal Cinema')
                ->greeting('¡Hola, ' . $notifiable->name . '!')
                ->line('Bienvenido a Untemporal Cinema. Solo te queda un paso para poder elegir tus asientos.')
                ->line('Por favor, haz clic en el botón de abajo para confirmar tu dirección de correo electrónico.')
                ->action('Verificar mi correo', $url)
                ->line('Si tú no creaste esta cuenta, puedes ignorar este mensaje de forma segura.')
                ->salutation('¡Nos vemos en el cine!');
        });
    }
}