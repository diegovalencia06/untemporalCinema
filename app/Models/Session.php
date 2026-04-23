<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Models\Room;

class Session extends Model
{

    protected $table = 'movie_sessions';

    protected $fillable = ['movie_id', 'room_id', 'start_time', 'end_time', 'base_price'];

    protected $casts = [
        'start_time' => 'datetime',
        'end_time' => 'datetime',
    ];
    
    public function movie()
    {
        return $this->belongsTo(Movie::class);
    }

    public function room()
    {
        return $this->belongsTo(Room::class, 'room_id', 'id');
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
    
    public function orders()
    {
        // Una sesión tiene muchos (hasMany) pedidos
        return $this->hasMany(\App\Models\Order::class);
    }
}
