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
            
            // 3. Datos del comprador (Si no hay user_id, usamos este email para enviarle la entrada)
            $table->string('buyer_email'); 
            
            // 4. Ubicación en la sala
            $table->integer('row');
            $table->integer('column');
            
            // 5. Datos económicos
            $table->decimal('price', 8, 2);
            
            // 6. Código único para el QR
            $table->string('qr_code')->unique();

            $table->string('seat');
            
            // 7. Estado de la compra
            $table->enum('status', ['pending', 'paid', 'cancelled'])->default('pending');
            
            $table->timestamps();

            // Evitar que dos personas compren el mismo asiento en la misma sesión
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
