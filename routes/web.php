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

// Ruta para ver el detalle de una película
Route::get('/pelicula/{movie}', [MovieController::class, 'show'])->name('pelicula.show');

require __DIR__.'/auth.php';
