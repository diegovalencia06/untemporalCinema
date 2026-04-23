<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Ticket extends Model
{
    use HasFactory;

    protected $fillable = [
        'reference', 'order_id', 'session_id', 'user_id', 'buyer_email', 'row', 'column', 'price', 'qr_code', 'status', 'seat',
    ];

    // Magia de Laravel: Generar un código único automáticamente antes de crear el ticket
    protected static function booted()
    {
        static::creating(function ($ticket) {
            if (empty($ticket->qr_code)) {
                $ticket->qr_code = strtoupper(Str::random(10)); // Ej: X7B9K2M1PQ
            }
        });
    }

    // Relaciones
    public function session()
    {
        return $this->belongsTo(Session::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}
