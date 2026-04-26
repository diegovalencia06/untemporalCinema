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
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use Stripe\Stripe;
use Stripe\Checkout\Session as StripeSession;

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

        $precioOficial = $this->calcularPrecioTicket($session);

        return Inertia::render('Asientos', [
            'session' => $session,
            'movie' => $session->movie,
            'occupiedSeats' => $asientosOcupados,
            'precioTicket' => $precioOficial
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
        // 1. Validamos solo lo necesario (el precio ya no viene de Vue)
        $request->validate([
            'asientos' => 'required|array|min:1',
            'cupon_id' => 'nullable|exists:coupons,id'
        ]);

        return DB::transaction(function () use ($request, $id) {
            
            // 2. Calculamos los precios seguros en el backend
            $session = Session::with('movie')->findOrFail($id);
            $precioUnitario = $this->calcularPrecioTicket($session);
            $precioTotal = $precioUnitario * count($request->asientos);

            // 3. Aplicamos descuento si hay cupón válido
            $cupon = null;
            if ($request->cupon_id) {
                $cupon = Coupon::lockForUpdate()->find($request->cupon_id);
                if (!$cupon || $cupon->stock <= 0 || !$cupon->is_active) {
                    return back()->withErrors(['cupon' => 'El cupón ya no está disponible.']);
                }
                
                $descuentoDecimal = $cupon->discount_percentage / 100;
                $precioTotal = $precioTotal - ($precioTotal * $descuentoDecimal);
            }

            $user = Auth::user();
            $buyerEmail = $user ? $user->email : 'invitado@luminous.com';
            $userId = $user ? $user->id : null;

            $orderRef = 'PED-' . strtoupper(\Illuminate\Support\Str::random(6));

            // Guardamos con el precioTotal seguro
            $order = Order::create([
                'reference' => $orderRef,
                'user_id' => $userId,
                'session_id' => $id,
                'coupon_id' => $cupon ? $cupon->id : null,
                'total_price' => $precioTotal, 
                'status' => 'pending',
            ]);

            $movie = $session->movie;

            foreach ($request->asientos as $asiento) {
                $letraFila = substr($asiento, 0, 1);
                $columna = (int) substr($asiento, 1);
                $fila = ord(strtoupper($letraFila)) - 64;

                $ticketRef = 'TCK-' . strtoupper(\Illuminate\Support\Str::random(8));
                $qrContent = 'VALIDAR-' . $ticketRef . '-' . \Illuminate\Support\Str::random(10); 

                Ticket::create([
                    'reference' => $ticketRef,
                    'order_id' => $order->id,
                    'session_id' => $id,
                    'user_id' => $userId,
                    'buyer_email' => $buyerEmail,
                    'row' => $fila,
                    'column' => $columna,
                    'price' => $precioUnitario, // Guardamos con el precioUnitario seguro
                    'qr_code' => $qrContent, 
                    'seat' => $asiento,
                    'status' => 'pending', 
                ]);
            }

            if ($cupon) {
                $cupon->decrement('stock');
            }

            // 4. CONECTAMOS CON STRIPE
            Stripe::setApiKey(env('STRIPE_SECRET'));

            // Stripe requiere el precio en céntimos (ej: 15.50€ -> 1550) calculados del backend
            $precioEnCentimos = (int) round($precioTotal * 100);

            $checkout_session = StripeSession::create([
                'payment_method_types' => ['card'],
                'customer_email' => $buyerEmail,
                'expires_at' => time() + (30 * 60) + 10,
                'line_items' => [[
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => [
                            'name' => 'Entradas para ' . $movie->title,
                            'description' => count($request->asientos) . ' entradas (Asientos: ' . implode(', ', $request->asientos) . ')',
                        ],
                        'unit_amount' => $precioEnCentimos,
                    ],
                    'quantity' => 1,
                ]],
                'mode' => 'payment',
                // Rutas a las que volverá Stripe después de cobrar
                'success_url' => route('stripe.success', ['reference' => $orderRef]),
                'cancel_url' => route('stripe.cancel', ['reference' => $orderRef]),
            ]);

            // 5. REDIRIGIR A STRIPE
            return Inertia::location($checkout_session->url);
        });
    }

    /**
     * PASO 2: El cliente vuelve de Stripe tras pagar con éxito
     */
    public function pagoExito($reference)
    {
        $order = Order::with(['tickets', 'session.movie', 'session.room', 'user'])->where('reference', $reference)->firstOrFail();

        // Evitamos regenerar si el usuario recarga la página
        if ($order->status !== 'completed') {
            
            DB::transaction(function () use ($order) {
                // Pasamos todo a pagado
                $order->update(['status' => 'completed']);
                $order->tickets()->update(['status' => 'paid']);

                // AHORA SÍ GENERAMOS EL PDF
                $pdf = Pdf::loadView('pdf.tickets', ['order' => $order]);
                $fileName = 'tickets/Entradas_' . $order->reference . '.pdf';
                Storage::disk('s3')->put($fileName, $pdf->output()); 
            });
        }

        // Redirigimos a la pantalla de éxito de Vue que ya tenías
        return redirect()->route('compra.exito', ['reference' => $order->reference]);
    }

    /**
     * PASO 3: Si el cliente cancela el pago en Stripe y vuelve atrás
     */
    public function pagoCancelado($reference)
    {
        $order = Order::where('reference', $reference)->firstOrFail();
        
        if ($order->status === 'pending') {
            // Devolvemos el stock del cupón si usó uno
            if ($order->coupon_id) {
                Coupon::find($order->coupon_id)->increment('stock');
            }
            // Borramos los tickets y el pedido porque no pagó
            $order->tickets()->delete();
            $order->delete();
        }

        return redirect('/')->withErrors(['pago' => 'El pago ha sido cancelado.']);
    }

    public function compraExito($reference)
    {
        // 1. Find the order with related models
        $order = Order::with(['session.movie', 'tickets'])->where('reference', $reference)->firstOrFail();
        
        // 2. Generate the download URL for the PDF
        $downloadUrl = route('tickets.download', ['reference' => $reference]);

        // 3. Render the Vue page
        return Inertia::render('CompraExito', [
            'order' => $order,
            'downloadUrl' => $downloadUrl
        ]);
    }

    public function descargarPdf($reference)
    {
        $fileName = 'tickets/Entradas_' . $reference . '.pdf';

        // Verificamos si existe en el disco s3
        if (!Storage::disk('s3')->exists($fileName)) {
            abort(404, 'Lo sentimos, el archivo de la entrada no se encuentra en la nube.');
        }

        /** @var \Illuminate\Filesystem\FilesystemAdapter $disk */
        $disk = Storage::disk('s3');

        // Esto fuerza la descarga del PDF desde Cloudflare
        return $disk->download($fileName, "Entradas_{$reference}.pdf");
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

    public function validarQr(Request $request)
    {
        try {
            $qr = $request->input('qr_code');
            $sessionId = $request->input('session_id');

            $ticket = \App\Models\Ticket::with('session.movie')->where('qr_code', $qr)->first();

            // ROJO: No existe
            if (!$ticket) {
                return response()->json(['status' => 'invalid', 'message' => 'Código QR no reconocido.']);
            }

            $sesionTicket = \Carbon\Carbon::parse($ticket->session->start_time);

            // ROJO: Es de otra sesión específica
            if ($sessionId && $ticket->session_id != $sessionId) {
                return response()->json(['status' => 'invalid', 'message' => 'Esta entrada es para otra sesión.', 'seat' => $ticket->seat ?? '']);
            }

            // ROJO: Escaneo general (fuera de tiempo)
            if (!$sessionId) {
                $ahora = now();
                if ($ahora->isBefore($sesionTicket->copy()->subMinutes(15)) || $ahora->isAfter($sesionTicket->copy()->addMinutes(30))) {
                    return response()->json(['status' => 'invalid', 'message' => 'La sesión no empieza en los próximos 15 min.', 'seat' => $ticket->seat ?? '']);
                }
            }

            // AMARILLO: Ya usado
            if ($ticket->status === 'used') {
                return response()->json(['status' => 'used', 'message' => 'Entrada YA USADA.', 'seat' => $ticket->seat ?? '']);
            }

            // VERDE: Correcto (Aquí es donde daba el error, ahora está blindado)
            $ticket->status = 'used'; 
            $ticket->save(); // Usamos save() en vez de update() por si falta en el $fillable

            // Evitamos error si la película fue borrada por accidente
            $tituloPelicula = $ticket->session->movie ? $ticket->session->movie->title : 'Película';

            return response()->json([
                'status' => 'ok', 
                'message' => '¡Válida! ' . $tituloPelicula, 
                'seat' => $ticket->seat ?? 'N/A'
            ]);

        } catch (\Exception $e) {
            // MAGIA: Si la base de datos o PHP explotan, el error saldrá en tu móvil en ROJO
            return response()->json([
                'status' => 'invalid', 
                'message' => 'ERROR INTERNO: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Calcula el precio de 1 entrada según duración y día de la semana.
     */
    private function calcularPrecioTicket($session)
    {
        $duracion = $session->movie->runtime ?? 0;
        $precio = 5.00; 

        if ($duracion > 90 && $duracion <= 120) {
            $precio = 5.50; 
        } elseif ($duracion > 120 && $duracion <= 150) {
            $precio = 6.00; 
        } elseif ($duracion > 150) {
            $precio = 6.50; 
        }

        $fecha = Carbon::parse($session->start_time);
        if ($fecha->isWednesday()) {
            $precio -= 2.00;
        }

        return $precio;
    }

    private function capitalizar($string) {
        return mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
    }
}