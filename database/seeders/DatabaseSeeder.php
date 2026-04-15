<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        User::factory()->create([
            'name' => 'Admin Cine',
            'email' => 'admin@cinema.com',
            'password' => bcrypt('password'), // La contraseña será 'password'
            'role' => 'admin',
        ]);

        // 2. Un Socio de prueba
        User::factory()->create([
            'name' => 'Socio VIP',
            'email' => 'socio@cinema.com',
            'password' => bcrypt('password'),
            'role' => 'socio',
        ]);

        // 3. Un Usuario normal de prueba
        User::factory()->create([
            'name' => 'Usuario Normal',
            'email' => 'usuario@cinema.com',
            'password' => bcrypt('password'),
            'role' => 'user',
        ]);
    }
}
