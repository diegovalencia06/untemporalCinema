<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\MovieController;

Route::get('/', [MovieController::class, 'index'])->name('cartelera');

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('/');

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

require __DIR__.'/auth.php';
