<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\User;
use App\Models\Session;

class MassiveSeeder extends Seeder
{
    public function run(): void
    {
        DB::disableQueryLog();
        $this->command->info('🎬 Iniciando Untemporal Cinema Engine...');

        // 1. USUARIOS
        if (User::count() === 0) {
            User::factory(20)->create();
        }

        // 2. SALA
        if (DB::table('rooms')->count() === 0) {
            DB::table('rooms')->insert([
                'id' => 1, 'name' => 'Sala Premium 1', 'rows' => 10, 'columns' => 15, 'created_at' => now(), 'updated_at' => now(),
            ]);
        }

        // 3. PELÍCULAS TMDB
        if (DB::table('movies')->count() === 0) {
            $apiKey = env('TMDB_API_KEY');
            $response = Http::get("https://api.themoviedb.org/3/movie/popular", ['api_key' => $apiKey, 'language' => 'es-ES']);

            if ($response->successful()) {
                foreach (array_slice($response->json()['results'], 0, 10) as $tmdbMovie) {
                    $detail = Http::get("https://api.themoviedb.org/3/movie/{$tmdbMovie['id']}", ['api_key' => $apiKey, 'language' => 'es-ES'])->json();
                    DB::table('movies')->insert([
                        'tmdb_id' => $tmdbMovie['id'], 'title' => $tmdbMovie['title'], 'poster_path' => $tmdbMovie['poster_path'],
                        'runtime' => $detail['runtime'] ?? 120, 'created_at' => now(), 'updated_at' => now(),
                    ]);
                }
            }
        }

        // 4. SESIONES SECUENCIALES (Sin solapamientos)
        if (DB::table('movie_sessions')->count() === 0) {
            $movies = DB::table('movies')->select('id', 'runtime')->get();
            for ($dia = -10; $dia <= 10; $dia++) {
                $puntero = now()->addDays($dia)->setTime(15, 0, 0);
                $cierre = $puntero->copy()->addHours(11);
                while ($puntero->isBefore($cierre)) {
                    $m = $movies->random();
                    $fin = $puntero->copy()->addMinutes($m->runtime);
                    if ($fin->isBefore($cierre)) {
                        DB::table('movie_sessions')->insert([
                            'movie_id' => $m->id, 'room_id' => 1, 'start_time' => $puntero, 'end_time' => $fin, 'created_at' => now(), 'updated_at' => now(),
                        ]);
                        $puntero = $fin->copy()->addMinutes(20);
                    } else { break; }
                }
            }
        }

        // 5. INYECCIÓN MASIVA CON PROTECCIÓN ÚNICA
        $this->command->info('🚀 Lanzando 5.000 pedidos con protección anti-duplicados...');
        $userIds = User::pluck('id')->toArray();
        $sessions = Session::all();
        $asientosOcupados = []; // Radar de sesión_id -> fila -> columna

        $totalPedidos = 5000;
        $progress = $this->command->getOutput()->progressStart($totalPedidos);

        for ($i = 1; $i <= $totalPedidos; $i++) {
            $session = $sessions->random();
            $fechaSesion = Carbon::parse($session->start_time);
            $numEntradas = rand(1, 4);
            $userId = $userIds[array_rand($userIds)];
            
            // Creamos el pedido uno a uno para obtener el ID real y evitar fallos de bloque
            $orderId = DB::table('orders')->insertGetId([
                'reference' => strtoupper(Str::random(10)),
                'user_id' => $userId,
                'session_id' => $session->id,
                'total_price' => 9.50 * $numEntradas,
                'status' => 'completed',
                'rating' => ($fechaSesion->isBefore(now()) && rand(1, 100) <= 70) ? rand(3, 5) : null,
                'created_at' => $fechaSesion->copy()->subDays(rand(1, 5)),
                'updated_at' => now(),
            ]);

            for ($t = 0; $t < $numEntradas; $t++) {
                $intentos = 0;
                $asientoValido = false;
                
                while (!$asientoValido && $intentos < 30) {
                    $r = rand(1, 10);
                    $c = rand(1, 15);
                    $key = "s{$session->id}r{$r}c{$c}";

                    if (!isset($asientosOcupados[$key])) {
                        try {
                            DB::table('tickets')->insert([
                                'order_id' => $orderId,
                                'session_id' => $session->id,
                                'user_id' => $userId,
                                'buyer_email' => "user_{$userId}@untemporal.com",
                                'row' => $r,
                                'column' => $c,
                                'seat' => "F{$r}-P{$c}",
                                'price' => 9.50,
                                'qr_code' => Str::uuid()->toString(),
                                'reference' => strtoupper(Str::random(12)),
                                'status' => 'paid',
                                'created_at' => now(), 'updated_at' => now(),
                            ]);
                            $asientosOcupados[$key] = true;
                            $asientoValido = true;
                        } catch (\Exception $e) {
                            // Si falla por duplicado en BD, lo marcamos en el radar y seguimos
                            $asientosOcupados[$key] = true;
                        }
                    }
                    $intentos++;
                }
            }
            $this->command->getOutput()->progressAdvance();
        }

        $this->command->getOutput()->progressFinish();
        $this->command->info('🏆 ¡Hecho! Cine poblado y sin asientos duplicados.');
    }
}