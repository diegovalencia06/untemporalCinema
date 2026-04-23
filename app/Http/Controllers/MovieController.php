<?php

namespace App\Http\Controllers;

use App\Models\Movie;
use App\Models\Session;
use App\Models\Order;
use App\Models\Coupon;
use App\Models\Ticket;
use Carbon\Carbon;
use Inertia\Inertia;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MovieController extends Controller
{
    public function index()
    {
        $inicio = Carbon::today();
        $fin = Carbon::today()->addDays(6);

        $movies = Movie::with(['sessions' => function ($query) use ($inicio, $fin) {
            $query->whereBetween('start_time', [$inicio, $fin->copy()->endOfDay()])
                  ->orderBy('start_time', 'asc');
        }])
        ->whereHas('sessions', function ($query) use ($inicio, $fin) {
            $query->whereBetween('start_time', [$inicio, $fin->copy()->endOfDay()]);
        })
        ->get();

        $semana = [];
        for ($i = 0; $i < 7; $i++) {
            $fecha = $inicio->copy()->addDays($i);
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
        $inicio = Carbon::today();
        $fin = Carbon::today()->addDays(6);

        $movie = Movie::with(['sessions' => function ($query) use ($inicio, $fin) {
            $query->whereBetween('start_time', [$inicio, $fin->copy()->endOfDay()])
                  ->orderBy('start_time', 'asc')
                  ->with('room');
        }])->findOrFail($id);

        return Inertia::render('PeliculaDetalle', [
            'movie' => $movie
        ]);
    }

    public function asientos($id)
    {
        $session = Session::with(['movie', 'room'])->findOrFail($id);

        $asientosOcupados = Ticket::where('session_id', $id)
                                ->pluck('seat')
                                ->toArray();

        return Inertia::render('Asientos', [
            'session' => $session,
            'movie' => $session->movie,
            'occupiedSeats' => $asientosOcupados
        ]);
    }

    public function validarCupon(Request $request)
    {
        $codigo = strtoupper($request->input('code'));
        
        $cupon = Coupon::where('code', $codigo)
                       ->where('is_active', true)
                       ->where('stock', '>', 0)
                       ->first();

        if ($cupon) {
            return response()->json([
                'valid' => true, 
                'discount' => $cupon->discount_percentage, 
                'id' => $cupon->id
            ]);
        }

        return response()->json(['valid' => false, 'message' => 'Cupón no disponible o agotado.'], 404);
    }

    /**
     * Procesa la compra creando un Pedido (Order) y sus Entradas (Tickets)
     */
    public function comprar(Request $request, $id)
    {
        $request->validate([
            'asientos' => 'required|array|min:1',
            'precio_total' => 'required|numeric',
            'cupon_id' => 'nullable|exists:coupons,id' // Validamos que el cupón exista
        ]);

        return DB::transaction(function () use ($request, $id) {
            
            // 1. Gestión de Cupón y Bloqueo de Stock
            $cupon = null;
            if ($request->cupon_id) {
                // lockForUpdate previene que dos usuarios compren con el último cupón a la vez
                $cupon = Coupon::lockForUpdate()->find($request->cupon_id);
                
                if (!$cupon || $cupon->stock <= 0 || !$cupon->is_active) {
                    return back()->withErrors(['cupon' => 'El cupón ya no está disponible.']);
                }
            }

            $user = Auth::user();
            $buyerEmail = $user ? $user->email : 'invitado@luminous.com';
            $userId = $user ? $user->id : null;

            // 2. CREAMOS EL PEDIDO GLOBAL (Order)
            $order = Order::create([
                'user_id' => $userId,
                'session_id' => $id,
                'coupon_id' => $cupon ? $cupon->id : null,
                'total_price' => $request->precio_total,
                'status' => 'completed',
            ]);

            // 3. CREAMOS LAS ENTRADAS INDIVIDUALES (Tickets)
            $precioPorAsiento = $request->precio_total / count($request->asientos);

            foreach ($request->asientos as $asiento) {
                $letraFila = substr($asiento, 0, 1);
                $columna = (int) substr($asiento, 1);
                $fila = ord(strtoupper($letraFila)) - 64;

                Ticket::create([
                    'order_id' => $order->id, // Vínculo con el pedido recién creado
                    'session_id' => $id,
                    'user_id' => $userId,
                    'buyer_email' => $buyerEmail,
                    'row' => $fila,
                    'column' => $columna,
                    'price' => $precioPorAsiento,
                    'qr_code' => Str::random(12), // Un poco más largo para seguridad
                    'seat' => $asiento,
                    'status' => 'paid',
                ]);
            }

            // 4. ACTUALIZACIÓN DE STOCK
            if ($cupon) {
                $cupon->decrement('stock');
            }

            return redirect()->route('cartelera')->with('success', '¡Disfruta de la película! Compra realizada.');
        });
    }

    public function search(Request $request)
    {
        $query = $request->input('q');

        if (!$query) {
            return response()->json([]);
        }

        $movies = Movie::where('title', 'LIKE', "{$query}%")
            ->select('id', 'title', 'poster_path')
            ->limit(5)
            ->get();

        return response()->json($movies);
    }

    private function capitalizar($string) {
        return mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
    }
}