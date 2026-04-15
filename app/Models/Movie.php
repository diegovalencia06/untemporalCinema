<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Movie extends Model
{
    protected $fillable = ['tmdb_id', 'title', 'synopsis', 'poster_path', 'runtime','is_active'];

    public function sessions()
    {
        return $this->hasMany(Session::class);
    }
}
