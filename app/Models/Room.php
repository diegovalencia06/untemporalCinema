<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    protected $fillable = ['name', 'rows', 'columns'];

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }
}
