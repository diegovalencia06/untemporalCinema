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
        Schema::table('users', function (Blueprint $table) {
            // Solo intenta borrarla si existe
            if (Schema::hasColumn('users', 'roles')) {
                $table->dropColumn('roles');
            } 
            // Por si acaso se llamaba en singular
            elseif (Schema::hasColumn('users', 'role')) {
                $table->dropColumn('role');
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('roles')->nullable(); // Por si quisieras volver atrás
        });
    }
};
