<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = ['tmdb_id', 'title', 'synopsis', 'poster_path', 'runtime','is_active', 'generos', 'productora', 'director', 'backdrop_path'];

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }
}
