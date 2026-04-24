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
            'cupon_id' => 'nullable|exists:coupons,id'
        ]);

        return DB::transaction(function () use ($request, $id) {
            
            $cupon = null;
            if ($request->cupon_id) {
                $cupon = Coupon::lockForUpdate()->find($request->cupon_id);
                if (!$cupon || $cupon->stock <= 0 || !$cupon->is_active) {
                    return back()->withErrors(['cupon' => 'El cupón ya no está disponible.']);
                }
            }

            $user = Auth::user();
            $buyerEmail = $user ? $user->email : 'invitado@luminous.com';
            $userId = $user ? $user->id : null;

            // 1. GENERAR REFERENCIA DEL PEDIDO (Ej: PED-4F8A9B)
            $orderRef = 'PED-' . strtoupper(\Illuminate\Support\Str::random(6));

            $order = Order::create([
                'reference' => $orderRef,
                'user_id' => $userId,
                'session_id' => $id,
                'coupon_id' => $cupon ? $cupon->id : null,
                'total_price' => $request->precio_total,
                'status' => 'completed',
            ]);

            $precioPorAsiento = $request->precio_total / count($request->asientos);

            foreach ($request->asientos as $asiento) {
                $letraFila = substr($asiento, 0, 1);
                $columna = (int) substr($asiento, 1);
                $fila = ord(strtoupper($letraFila)) - 64;

                // 2. GENERAR REFERENCIA Y QR PARA CADA TICKET
                $ticketRef = 'TCK-' . strtoupper(\Illuminate\Support\Str::random(8));
                $qrContent = 'VALIDAR-' . $ticketRef . '-' . \Illuminate\Support\Str::random(10); // Lo que leerá el láser del cine

                Ticket::create([
                    'reference' => $ticketRef,
                    'order_id' => $order->id,
                    'session_id' => $id,
                    'user_id' => $userId,
                    'buyer_email' => $buyerEmail,
                    'row' => $fila,
                    'column' => $columna,
                    'price' => $precioPorAsiento,
                    'qr_code' => $qrContent, 
                    'seat' => $asiento,
                    'status' => 'paid',
                ]);
            }

            if ($cupon) {
                $cupon->decrement('stock');
            }

            // 3. GENERAR EL PDF
            // Cargamos las relaciones para que la vista Blade tenga todos los datos
            $order->load(['tickets', 'session.movie', 'session.room', 'user']);
            
            // return view('pdf.tickets', ['order' => $order]); 

            // DESCOMENTAMOS LA GENERACIÓN DEL PDF REAL
            $pdf = Pdf::loadView('pdf.tickets', ['order' => $order]);
            
            $fileName = 'tickets/Entradas_' . $orderRef . '.pdf';

            // Usamos put() y Laravel lanzará una excepción si falla (gracias al 'throw' => true)
            Storage::disk('s3')->put($fileName, $pdf->output()); 

            // Si llegamos aquí, es que se subió bien
            return redirect()->route('compra.exito', ['reference' => $orderRef]);
        });
    }

    public function compraExito($reference)
    {
        // 1. Buscamos el pedido
        $order = Order::with('session.movie')->where('reference', $reference)->firstOrFail();
        
        // 2. CONSTRUCCIÓN SEGURA DE LA URL
        // En lugar de asset(), usamos Storage::url(). 
        // Esto detecta automáticamente si estás en local o en producción (HTTPS).
        // En tu función compraExito
        $downloadUrl = route('tickets.download', ['reference' => $reference]);

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

    private function capitalizar($string) {
        return mb_convert_case($string, MB_CASE_TITLE, "UTF-8");
    }
}