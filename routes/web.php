<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovieController;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Session;
use Carbon\Carbon;

Route::get('/', [MovieController::class, 'index'])->name('cartelera');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::get('/sesion/{id}/asientos', [MovieController::class, 'asientos'])->name('sesion.asientos');

Route::post('/sesion/{id}/comprar', [App\Http\Controllers\MovieController::class, 'comprar'])->name('sesion.comprar');

// Ruta para ver el detalle de una película
Route::get('/pelicula/{movie}', [MovieController::class, 'show'])->name('pelicula.show');

// Fíjate que le he quitado el /api/
Route::get('/buscar-peliculas', [MovieController::class, 'search']);
Route::post('/validar-cupon', [MovieController::class, 'validarCupon'])->name('cupon.validar');

Route::get('/pedido/{reference}/exito', [MovieController::class, 'compraExito'])->name('compra.exito');

Route::post('/api/escanear-qr', [MovieController::class, 'validarQr'])->name('api.escanear.qr');

// Ruta para ver la página de Vue
Route::get('/staff/escanear', function (Request $request) {
    $sessionId = $request->query('session_id');
    $sessionInfo = null;

    if ($sessionId) {
        // Buscamos la sesión con su película y sala asociada
        // (Nota: Si tu relación de sala se llama diferente a 'room', cámbialo)
        $session = Session::with(['movie', 'room'])->find($sessionId);
        
        if ($session) {
            $sessionInfo = [
                'pelicula' => $session->movie->title,
                'hora' => Carbon::parse($session->start_time)->format('H:i'),
                'sala' => $session->room->name ?? 'Sala N/A' 
            ];
        }
    }

    return Inertia::render('EscanearQR', [
        'sessionId' => $sessionId,
        'sessionInfo' => $sessionInfo // <-- Le pasamos los datos bonitos a Vue
    ]);
})->name('escanear.vue');

Route::get('/descargar-entrada/{reference}', [MovieController::class, 'descargarPdf'])->name('tickets.download');

require __DIR__.'/auth.php';
