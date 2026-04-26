<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('movie_sessions', function (Blueprint $blueprint) {
            // Eliminamos la columna
            $blueprint->dropColumn('base_price');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('movie_sessions', function (Blueprint $blueprint) {
            // Por si alguna vez quieres volver atrás, la recreamos
            $blueprint->decimal('base_price', 8, 2)->nullable();
        });
    }
};