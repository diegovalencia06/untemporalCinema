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
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('movie_sessions')->cascadeOnDelete();
        
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            
            $table->string('buyer_email'); 
            
            $table->integer('row');
            $table->integer('column');
            
            $table->decimal('price', 8, 2);
            
            $table->string('qr_code')->unique();

            $table->string('seat');
            
            $table->enum('status', ['pending', 'paid', 'cancelled', 'used'])->default('pending');
            
            $table->timestamps();

            $table->unique(['session_id', 'row', 'column']);

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tickets');
    }
};
