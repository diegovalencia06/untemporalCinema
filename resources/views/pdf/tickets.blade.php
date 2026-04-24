<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Entradas - {{ $order->reference }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; }
        .ticket { border: 2px dashed #ccc; padding: 20px; margin-bottom: 20px; border-radius: 10px; page-break-inside: avoid; }
        .header { border-bottom: 1px solid #eee; padding-bottom: 10px; margin-bottom: 10px; }
        .movie-title { font-size: 24px; font-weight: bold; margin: 0; }
        .qr-code { float: right; }
        .clear { clear: both; }
    </style>
</head>
<body>
    <h1>Tu Pedido: {{ $order->reference }}</h1>
    <p>Comprador: {{ $order->user ? $order->user->name : 'Invitado' }}</p>
    <hr>

    @foreach($order->tickets as $ticket)
        <div class="ticket">
            <div class="qr-code">
                <img src="data:image/png;base64,{{ base64_encode(QrCode::format('png')->size(100)->generate($ticket->qr_code)) }}">            </div>
            <div class="header">
                <h2 class="movie-title">{{ $order->session->movie->title }}</h2>
                <p><strong>Sala:</strong> {{ $order->session->room->name }}</p>
            </div>
            <p><strong>Fecha:</strong> {{ \Carbon\Carbon::parse($order->session->start_time)->format('d/m/Y') }}</p>
            <p><strong>Hora:</strong> {{ \Carbon\Carbon::parse($order->session->start_time)->format('H:i') }}</p>
            <p><strong>Asiento:</strong> {{ $ticket->seat }}</p>
            <p><strong>Ref Ticket:</strong> {{ $ticket->reference }}</p>
            <div class="clear"></div>
        </div>
    @endforeach
</body>
</html>