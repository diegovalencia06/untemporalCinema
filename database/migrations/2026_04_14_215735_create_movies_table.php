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
        Schema::create('movies', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tmdb_id')->unique(); // El ID de la API externa
            $table->string('title'); 
            $table->text('synopsis')->nullable(); // Guardamos el texto largo
            $table->string('poster_path')->nullable(); // Guardamos la ruta: /abc.jpg
            $table->integer('runtime')->nullable();
            $table->string('generos')->nullable();
            $table->string('productora')->nullable();
            $table->string('director')->nullable();
            $table->string('backdrop_path')->nullable();

            $table->boolean('is_active')->default(true); // Para saber si está en cartelera
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('movies');
    }
};
