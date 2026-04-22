<?php

namespace App\Filament\Resources\Sessions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker; // ¡Nuevo!
use Filament\Forms\Components\TimePicker; // ¡Nuevo!
use Filament\Forms\Components\Hidden; // ¡Nuevo!
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Section;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Closure;

class SessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                // 1. Elegir Sala
                Select::make('room_id')
                    ->relationship('room', 'name')
                    ->label('Sala')
                    ->required()
                    ->live(),

                // 2. Elegir Película
                Select::make('movie_id')
                    ->relationship('movie', 'title')
                    ->label('Película')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set, $get) {
                        self::sincronizarStartAndEnd($set, $get);
                    }),

                // 3. LA FECHA (Campo Virtual)
                DatePicker::make('session_date')
                    ->label('Fecha de la Función')
                    ->required()
                    ->native(false) // Desactiva el calendario feo del navegador por el bonito de Filament
                    ->live()
                    ->formatStateUsing(fn (?Model $record) => $record?->start_time?->format('Y-m-d'))
                    ->afterStateUpdated(function ($set, $get) {
                        self::sincronizarStartAndEnd($set, $get);
                    }),

                // 4. LA HORA (Campo Virtual - Múltiplos de 5)
                // 4. LA HORA (Campo Virtual - Múltiplos de 5)
                TimePicker::make('session_time')
                    ->label('Hora de Inicio')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->minutesStep(15)
                    ->live()
                    ->formatStateUsing(fn (?Model $record) => $record?->start_time?->format('H:i'))
                    ->afterStateUpdated(function ($set, $get) {
                        self::sincronizarStartAndEnd($set, $get);
                    })
                    // --- EL NUEVO CANDADO DE SEGURIDAD ---
                    ->rule(static function () {
                        return function (string $attribute, $value, Closure $fail) {
                            if ($value) {
                                // Leemos la hora escrita y sacamos solo el número de los minutos
                                $minutos = \Carbon\Carbon::parse($value)->minute;
                                
                                // Si el resto de dividir los minutos entre 5 NO es cero...
                                if ($minutos % 15 !== 0) {
                                    // Lanzamos un error en rojo que bloquea el guardado
                                    $fail('La hora debe ser un múltiplo exacto de 5 minutos (ej: 18:00, 18:05, 18:10).');
                                }
                            }
                        };
                    }),

                // --- CAMPO OCULTO (El que realmente va a la Base de Datos) ---
                Hidden::make('start_time'),

                // 5. Hora de Fin (Calculada y Protegida)
                DateTimePicker::make('end_time')
                    ->label('Hora de Fin (Auto + 15m limpieza)')
                    ->required()
                    ->readOnly()
                    ->native(false)
                    ->seconds(false) // También le quitamos los segundos para que quede limpio
                    // LA MAGIA ANTI-SOLAPAMIENTO
                    ->rule(static function ($get, ?Model $record) {
                        return function (string $attribute, $value, Closure $fail) use ($get, $record) {
                            $salaId = $get('room_id');
                            $inicio = $get('start_time'); // Lee del campo oculto
                            $fin = $value;

                            if (!$salaId || !$inicio || !$fin) return;

                            $ocupada = \App\Models\Session::where('room_id', $salaId)
                                ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                ->where(function ($query) use ($inicio, $fin) {
                                    $query->where('start_time', '<', $fin)
                                          ->where('end_time', '>', $inicio);
                                })
                                ->exists();

                            if ($ocupada) {
                                $fail('Error de horario: La sala seleccionada ya tiene otra función en ese bloque de tiempo.');
                            }
                        };
                    }),

                // 6. Precio
                TextInput::make('base_price')
                    ->label('Precio Base')
                    ->numeric()
                    ->required()
                    ->prefix('€'),
            ]);
        
    }

    // Esta función reemplaza a calcularHoraFin y se encarga de todo
    public static function sincronizarStartAndEnd($set, $get)
    {
        $fecha = $get('session_date');
        $hora = $get('session_time');
        $movieId = $get('movie_id');

        if ($fecha && $hora) {
            // 1. Unimos los dos inputs en un solo texto (Ej: "2023-10-25 18:00:00")
            $start = Carbon::parse("$fecha $hora");
            
            // 2. Guardamos ese texto en nuestro campo oculto 'start_time'
            $set('start_time', $start->toDateTimeString());

            // 3. Si ya hay película, calculamos el final
            if ($movieId) {
                $pelicula = \App\Models\Movie::find($movieId);
                
                if ($pelicula && $pelicula->runtime) {
                    $fin = $start->copy()->addMinutes($pelicula->runtime + 15);
                    $set('end_time', $fin->toDateTimeString());
                }
            }
        }
    }
}