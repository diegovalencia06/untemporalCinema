<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Models\Order;
use App\Models\Ticket;
use App\Models\Movie;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $ahora = now();

        $historial = Order::with(['session.movie'])
            ->withCount('tickets')
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->orderBy('created_at', 'desc')
            ->get();

        $estadisticas = $historial->map(function ($order) use ($ahora) {
            $movie = $order->session->movie;
            
            // --- ESTADÍSTICAS GLOBALES DE LA PELÍCULA ---
            // 1. Nota media de todos los usuarios que la han valorado
            $notaMedia = Order::whereHas('session', function($q) use ($movie) {
                $q->where('movie_id', $movie->id);
            })->whereNotNull('rating')->avg('rating');

            // 2. Total de personas (tickets) que han ido a ver esta película en total
            $totalEspectadores = Ticket::whereHas('session', function($q) use ($movie) {
                $q->where('movie_id', $movie->id);
            })->where('status', 'paid')->count();

            $fechaSesion = Carbon::parse($order->session->start_time);
            $esFuturo = $fechaSesion->isAfter($ahora);

            return [
                'id' => $order->id,
                'reference' => $order->reference,
                'fecha_sesion_raw' => $order->session->start_time, // Para ordenar en Vue
                'fecha_sesion' => $fechaSesion->format('d/m/Y'),
                'hora_sesion' => $fechaSesion->format('H:i'),
                'pelicula_titulo' => $movie->title ?? 'Película Eliminada',
                'pelicula_poster' => $movie->poster_path ?? null,
                'total_entradas' => $order->tickets_count,
                'acompañantes' => $order->tickets_count > 1 ? $order->tickets_count - 1 : 0,
                'es_futuro' => $esFuturo,
                'url_pdf' => route('tickets.download', ['reference' => $order->reference]),
                'rating' => $order->rating, // Valoración del usuario
                'nota_media_global' => $notaMedia ? round($notaMedia, 1) : 0, // Nota media de la comunidad
                'espectadores_globales' => $totalEspectadores,
            ];
        });

        return Inertia::render('Perfil', [
            'user' => [
                'name' => $user->name,
                'email' => $user->email,
            ],
            'historial' => $estadisticas,
        ]);
    }

    public function valorar(Request $request, Order $order)
    {
        if ($order->user_id !== $request->user()->id) { abort(403); }
        $request->validate(['rating' => 'required|integer|min:1|max:5']);
        $order->update(['rating' => $request->rating]);
        return back();
    }
}