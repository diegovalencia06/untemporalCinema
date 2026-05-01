<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        @page { margin: 0; }
        body { font-family: 'Helvetica', sans-serif; background-color: #0c1324; color: #ffffff; margin: 0; padding: 0; }
        .page-break { page-break-after: always; }
        .wrapper { padding: 50px; }
        .ticket { width: 100%; background-color: #191f31; border: 1px solid #2a3241; border-radius: 12px; display: table; border-collapse: collapse; overflow: hidden; }
        .poster-col { width: 200px; vertical-align: middle; background-color: #191f31; text-align: center; line-height: 0;}

        .poster-img { width: 200px; height: auto; display: block; }       
        .content-col { padding: 30px; vertical-align: top; }
        .brand { color: #dc2626; font-size: 10px; font-weight: bold; text-transform: uppercase; letter-spacing: 2px; margin-bottom: 5px; }
        .movie-title { font-size: 22px; font-weight: bold; text-transform: uppercase; margin-bottom: 20px; }
        .label { color: #64748b; font-size: 8px; text-transform: uppercase; margin-bottom: 2px; }
        .value { font-size: 14px; font-weight: bold; margin-bottom: 15px; }
        .seat-value { color: #dc2626; font-size: 24px; font-weight: bold; }
        .qr-box { background: white; padding: 10px; border-radius: 8px; display: inline-block; }
        .footer-bar { background-color: #dc2626; color: white; text-align: center; padding: 8px; font-size: 10px; font-weight: bold; text-transform: uppercase; }
    </style>
</head>
<body>

                    @foreach($order->tickets as $ticket)
                        <div class="wrapper {{ !$loop->last ? 'page-break' : '' }}">
                            <div class="ticket">
                                <table style="width: 100%; border-spacing: 0;">
                                    <tr>
                                        <td class="poster-col">
                                            @php
                        $base64Poster = null;
                        try {
                            // 1. Construimos la URL completa de TMDB (usamos w500 para buena calidad)
                            $tmdbPath = $order->session->movie->poster_path;
                            $fullUrl = "https://image.tmdb.org/t/p/w500" . $tmdbPath;

                            // 2. Descargamos la imagen usando file_get_contents
                            // Usamos un stream context por si el servidor de TMDB bloquea peticiones sin User-Agent
                            $opts = [
                                "http" => [
                                    "method" => "GET",
                                    "header" => "User-Agent: PHP\r\n"
                                ]
                            ];
                            $context = stream_context_create($opts);
                            $imageData = file_get_contents($fullUrl, false, $context);

                            if ($imageData) {
                                $extension = pathinfo($tmdbPath, PATHINFO_EXTENSION);
                                $base64Poster = 'data:image/' . $extension . ';base64,' . base64_encode($imageData);
                            }
                        } catch (\Exception $e) {
                            // Si falla la descarga, no mostramos nada o un placeholder
                        }
                    @endphp

                    @if($base64Poster)
                        <img src="{{ $base64Poster }}" class="poster-img">
                    @else
                        <div style="padding-top: 100px; color: #444; font-size: 10px;">
                            PÓSTER NO<br>DISPONIBLE
                        </div>
                    @endif
                    </td>

                    <td class="content-col">
                        <div class="brand">Untemporal Cinema</div>
                        <div class="movie-title">{{ $order->session->movie->title }}</div>

                        <table style="width: 100%;">
                            <tr>
                                <td>
                                    <div class="label">Fecha</div>
                                    <div class="value">{{ \Carbon\Carbon::parse($order->session->start_time)->format('d/m/Y') }}</div>
                                </td>
                                <td>
                                    <div class="label">Hora</div>
                                    <div class="value">{{ \Carbon\Carbon::parse($order->session->start_time)->format('H:i') }}</div>
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <div class="label">Sala</div>
                                    <div class="value">{{ $order->session->room->name }}</div>
                                </td>
                                <td>
                                    <div class="label">Asiento</div>
                                    <div class="seat-value">{{ $ticket->seat }}</div>
                                </td>
                            </tr>
                        </table>

                        <table style="width: 100%; margin-top: 20px;">
                            <tr>
                                <td style="vertical-align: middle;">
                                    <div class="label">Referencia</div>
                                    <div class="value" style="font-size: 10px;">{{ $ticket->reference }}</div>
                                    <div class="label">Comprador</div>
                                    <div class="value" style="font-size: 10px;">{{ $order->user ? $order->user->name : 'Invitado' }}</div>
                                </td>
                                <td style="text-align: right; vertical-align: middle;">
                                    <div class="qr-box">
                                        {{-- Usamos SVG normal, pero sin el método ->format('png') que pide imagick --}}
                                        <img src="data:image/svg+xml;base64,{{ base64_encode(QrCode::size(80)->margin(0)->generate($ticket->qr_code)) }}">
                                    </div>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
            <div class="footer-bar">Presenta este ticket en la entrada del cine</div>
        </div>
    </div>
@endforeach

</body>
</html>