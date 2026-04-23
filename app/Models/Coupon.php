<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Coupon extends Model
{
    // Esto permite que Filament y tu controlador guarden los datos
    protected $fillable = [
        'code',
        'discount_percentage',
        'is_active',
        'stock',
    ];

    /**
     * Un cupón puede haber sido aplicado en muchos pedidos.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }
}