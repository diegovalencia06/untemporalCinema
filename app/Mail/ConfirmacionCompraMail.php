<?php

namespace App\Mail;

use App\Models\Order;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Mail\Mailables\Attachment; // Importante
use Illuminate\Queue\SerializesModels;

class ConfirmacionCompraMail extends Mailable
{
    use Queueable, SerializesModels;

    public $order;
    public $pdfContent; // Nueva variable para el contenido del PDF

    public function __construct(Order $order, $pdfContent = null)
    {
        $this->order = $order;
        $this->pdfContent = $pdfContent;
    }

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: '🎬 Reserva Confirmada - ' . $this->order->session->movie->title,
        );
    }

    public function content(): Content
    {
        return new Content(
            view: 'emails.compra',
        );
    }

    // Esta es la parte que añade el archivo adjunto
    public function attachments(): array
    {
        if ($this->pdfContent) {
            return [
                Attachment::fromData(fn () => $this->pdfContent, 'Entradas_' . $this->order->reference . '.pdf')
                    ->withMime('application/pdf'),
            ];
        }
        return [];
    }
}