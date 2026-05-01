<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <style>
        /* Estilo basado en Untemporal Cinema */
        body { 
            background-color: #0c1324; /* Fondo principal de la app */
            margin: 0; 
            padding: 40px; 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            color: #ffffff;
        }
        .container { 
            background-color: #191f31; /* Fondo de tarjeta/container-low */
            border: 1px solid #2a3241; /* Borde sutil white/5 */
            border-radius: 16px; 
            padding: 40px; 
            max-width: 600px; 
            margin: auto; 
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.5);
        }
        .header { 
            color: #dc2626; /* Tu rojo primario */
            font-size: 30px; 
            font-weight: 900; 
            text-align: center; 
            margin-bottom: 30px;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .greeting { 
            color: #ffffff; 
            font-size: 18px; 
            margin-bottom: 20px; 
        }
        .info-box { 
            background-color: #0c1324; /* Fondo más oscuro para contraste */
            border-left: 4px solid #dc2626; 
            border-radius: 8px; 
            padding: 20px; 
            margin: 25px 0; 
            color: #cbd5e1; /* text-slate-300 */
            line-height: 1.8;
            font-size: 15px;
        }
        .important-text { 
            color: #cbd5e1; 
            font-size: 14px; 
            line-height: 1.6; 
        }
        .highlight {
            color: #ffffff;
            font-weight: bold;
        }
        .footer { 
            color: #64748b; /* text-slate-500 */
            font-size: 11px; 
            text-align: center; 
            margin-top: 40px; 
            border-top: 1px solid #2a3241; 
            padding-top: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">¡Reserva Confirmada!</div>
        
        <div class="greeting">
            Hola <span class="highlight">{{ $order->user ? $order->user->name : 'Cinéfilo' }}</span>,
        </div>

        <p class="important-text">
            ¡Gracias por tu compra! Tu reserva para la película <span class="highlight">{{ $order->session->movie->title }}</span> se ha completado correctamente en <span class="highlight">Untemporal Cinema</span>.
        </p>

        <div class="info-box">
            <strong style="color: #dc2626;">DETALLES DEL PEDIDO</strong><br>
            <span class="highlight">Referencia:</span> {{ $order->reference }}<br>
            <span class="highlight">Fecha sesión:</span> {{ \Carbon\Carbon::parse($order->session->start_time)->format('d/m/Y H:i') }}<br>
            <span class="highlight">Asientos:</span> {{ $order->tickets->pluck('seat')->implode(', ') }}
        </div>

        <p class="important-text">
            <strong style="color: #ffffff;">Entradas adjuntas:</strong> Hemos incluido tus tickets en formato <span class="highlight">PDF</span> en este correo. Puedes mostrarlos directamente desde tu móvil al llegar al cine.
        </p>

        <div class="footer">
            🎬 Untemporal Cinema - La mejor experiencia cinematográfica<br>
            © {{ date('Y') }} Luminous Systems
        </div>
    </div>
</body>
</html>