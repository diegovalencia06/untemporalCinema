<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use Carbon\Carbon;
use Inertia\Inertia;
use App\Models\Session;

class MovieController extends Controller
{
    public function index()
    {
        $inicio = Carbon::today();
        $fin = Carbon::today()->addDays(6); // Cubrimos 7 días en total

        // 1. Buscamos películas con sesiones en esta semana
        $movies = Movie::with(['sessions' => function ($query) use ($inicio, $fin) {
            $query->whereBetween('start_time', [$inicio, $fin->copy()->endOfDay()])
                  ->orderBy('start_time', 'asc');
        }])
        ->whereHas('sessions', function ($query) use ($inicio, $fin) {
            $query->whereBetween('start_time', [$inicio, $fin->copy()->endOfDay()]);
        })
        ->get();

        // 2. Generamos los 7 días de la semana para los botones
        $semana = [];
        for ($i = 0; $i < 7; $i++) {
            $fecha = $inicio->copy()->addDays($i);
            
            // Forzamos el idioma a español, sacamos el nombre (ej: "miércoles") y cortamos 3 letras
            $nombreDia = mb_substr($fecha->locale('es')->dayName, 0, 3, 'UTF-8');
            
            $semana[] = [
                'label' => $i === 0 ? 'Hoy' : ($i === 1 ? 'Mañana' : $this->capitalizar($nombreDia)),
                'numero' => $fecha->format('d'),
                'fecha_completa' => $fecha->format('Y-m-d'),
            ];
        }

        return Inertia::render('Cartelera', [
            'movies' => $movies,
            'semana' => $semana
        ]);
    }

public function show($id)
{
    $inicio = \Carbon\Carbon::today();
    $fin = \Carbon\Carbon::today()->addDays(6);

    // Hacemos todo en un solo viaje a la base de datos:
    $movie = \App\Models\Movie::with(['sessions' => function ($query) use ($inicio, $fin) {
        $query->whereBetween('start_time', [$inicio, $fin->copy()->endOfDay()])
              ->orderBy('start_time', 'asc')
              ->with('room'); // <--- ¡LA MAGIA ESTÁ AQUÍ! Le decimos que, además de filtrar, traiga la sala.
    }])->findOrFail($id);

    // Enviamos los datos a Vue
    return \Inertia\Inertia::render('PeliculaDetalle', [
        'movie' => $movie
    ]);
}

public function asientos($id)
    {
        // 1. Buscamos la sesión con su película y sala
        $session = Session::with(['movie', 'room'])->findOrFail($id);

        // 2. Simulamos los asientos ocupados (más adelante lo conectarás a tus reservas reales)
        $asientosOcupados = ['C4', 'C5', 'F10', 'H1', 'H2'];

        // 3. Enviamos los datos al nuevo archivo Vue que creaste
        return Inertia::render('Asientos', [
            'session' => $session,
            'movie' => $session->movie,
            'occupiedSeats' => $asientosOcupados
        ]);
    }

    // Añade esto al final del archivo, antes de la última llave }
    private function capitalizar($string) {
        return mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
    }
}