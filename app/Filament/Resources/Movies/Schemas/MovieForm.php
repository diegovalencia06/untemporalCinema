<?php

namespace App\Filament\Resources\Movies\Schemas;

use Filament\Schemas\Schema;
use Illuminate\Support\Facades\Http; 
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Hidden; // Añadido para los campos invisibles
use App\Models\Movie; 
use Illuminate\Validation\ValidationException;

class MovieForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('tmdb_id')
                    ->label('Buscar Película (TMDb)')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->searchable()
                    // 1. Esta función busca opciones mientras escribes
                    ->getSearchResultsUsing(function (string $search): array {
                        if (blank($search)) {
                            return [];
                        }

                        $response = Http::get('https://api.themoviedb.org/3/search/movie', [
                            'api_key' => env('TMDB_API_KEY'),
                            'query' => $search,
                            'language' => 'es-ES',
                        ]);

                        if ($response->failed()) {
                            return [];
                        }

                        return collect($response->json('results'))
                            ->mapWithKeys(function ($movie) {
                                $year = substr($movie['release_date'] ?? '', 0, 4);
                                $title = $movie['title'] . ($year ? " ({$year})" : '');
                                return [$movie['id'] => $title];
                            })
                            ->toArray();
                    })
                    // 2. Esta función muestra el nombre cuando el registro ya está guardado
                    ->getOptionLabelUsing(function ($value): ?string {
                        if (blank($value)) {
                            return null;
                        }

                        $response = Http::get("https://api.themoviedb.org/3/movie/{$value}", [
                            'api_key' => env('TMDB_API_KEY'),
                            'language' => 'es-ES',
                        ]);

                        if ($response->failed()) {
                            return null;
                        }

                        return $response->json('title');
                    })
                    // 3. ¡NUEVO! Escucha los cambios en tiempo real
                    ->live() 
                    // 4. ¡NUEVO! Cuando el usuario selecciona una película, hacemos esto:
                    ->afterStateUpdated(function ($set, $state) {
                        if (blank($state)) {
                            return;
                        }

                        // Pedimos los detalles completos de la película seleccionada a TMDb
                        $response = Http::get("https://api.themoviedb.org/3/movie/{$state}", [
                            'api_key' => env('TMDB_API_KEY'),
                            'language' => 'es-ES',
                            'append_to_response' => 'credits',
                        ]);

                        if ($response->successful()) {
                            $movie = $response->json();
                            
                            // Asignamos los datos de la API a nuestros campos ocultos
                            $set('title', $movie['title'] ?? null);
                            $set('synopsis', $movie['overview'] ?? null);
                            $set('poster_path', $movie['poster_path'] ?? null);
                            $set('runtime', $movie['runtime'] ?? null);

                            $set('generos', isset($movie['genres']) ? collect($movie['genres'])->pluck('name')->implode(', ') : null);
                            $set('productora', isset($movie['production_companies']) ? collect($movie['production_companies'])->pluck('name')->implode(', ') : null);
                            
                            $director = null;
                            if (isset($movie['credits']['crew'])) {
                                $director = collect($movie['credits']['crew'])->where('job', 'Director')->pluck('name')->implode(', ');
                            }
                            $set('director', $director);
                            $set('backdrop_path', $movie['backdrop_path'] ?? null);
                        }
                    }),

                // ¡NUEVO! Campos ocultos que Filament guardará silenciosamente en la base de datos
                Hidden::make('title'),
                Hidden::make('synopsis'),
                Hidden::make('poster_path'),
                Hidden::make('runtime'),
                Hidden::make('generos'),
                Hidden::make('productora'),
                Hidden::make('director'),
                Hidden::make('backdrop_path'),


                Toggle::make('is_active')
                    ->label('Activa en Web')
                    ->default(true)
                    // Hacemos que sea "live" para poder validar al momento
                    ->live(onBlur: true) 
                    ->afterStateUpdated(function (?Movie $record, $state, $set) {
                        // Si estamos intentando desactivar la película ($state es false)
                        // y el registro ya existe (estamos editando, no creando)
                        if ($state === false && $record) {
                            
                            // Comprobamos si tiene sesiones futuras (o actuales)
                            $hasSessions = $record->sessions()
                                ->where('start_time', '>=', now())
                                ->exists();

                            if ($hasSessions) {
                                // Volvemos a encender el Toggle a la fuerza
                                $set('is_active', true);
                                
                                // Lanzamos un error visual para el usuario
                                throw ValidationException::withMessages([
                                    'data.is_active' => 'No puedes desactivar una película que tiene sesiones programadas.',
                                ]);
                            }
                        }
                    }),
            ]);
    }
}