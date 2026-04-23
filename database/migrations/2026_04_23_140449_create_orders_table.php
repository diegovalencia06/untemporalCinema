<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained(); // Quién compra
            $table->foreignId('session_id')->constrained('movie_sessions')->cascadeOnDelete(); // Para qué sesión
            $table->foreignId('coupon_id')->nullable()->constrained(); // Qué cupón usó
            $table->decimal('total_price', 8, 2);
            $table->string('status')->default('completed'); // 'pending', 'completed', 'cancelled'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
