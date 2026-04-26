<?php

namespace App\Filament\Resources\Sessions\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Notifications\Notification;
use Carbon\Carbon;
use Closure;

class SessionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                
                Select::make('room_id')
                    ->relationship('room', 'name')
                    ->label('Sala')
                    ->required()
                    ->live(),

                Select::make('movie_id')
                    ->relationship(
                        name: 'movie', 
                        titleAttribute: 'title', 
                        modifyQueryUsing: fn (Builder $query) => $query->where('is_active', true)
                    )
                    ->label('Película (Solo activas)')
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($set, $get) {
                        self::sincronizarStartAndEnd($set, $get);
                    }),

                DatePicker::make('session_date')
                    ->label('Fecha de la Función')
                    ->required()
                    ->native(false)
                    ->live()
                    ->formatStateUsing(fn (?Model $record) => $record?->start_time?->format('Y-m-d'))
                    ->afterStateUpdated(function ($set, $get) {
                        self::sincronizarStartAndEnd($set, $get);
                    }),

                // --- HORA DE INICIO ---
                TimePicker::make('session_time')
                    ->label('Hora de Inicio')
                    ->required()
                    ->native(false)
                    ->seconds(false)
                    ->minutesStep(15)
                    ->live(onBlur: true)
                    ->formatStateUsing(fn (?Model $record) => $record?->start_time?->format('H:i'))
                    ->afterStateUpdated(function ($set, $get) {
                        
                        // 1. AUTO-CORRECCIÓN A MÚLTIPLOS DE 15 MINUTOS
                        $horaOriginal = $get('session_time');
                        if ($horaOriginal) {
                            $minutos = Carbon::parse($horaOriginal)->minute;
                            if ($minutos % 15 !== 0) {
                                $minutosFaltantes = 15 - ($minutos % 15);
                                $nuevaHora = Carbon::parse($horaOriginal)->addMinutes($minutosFaltantes)->format('H:i');
                                $set('session_time', $nuevaHora);
                                
                                Notification::make()
                                    ->title('Hora Ajustada')
                                    ->body('La hora se redondeó automáticamente a un múltiplo de 15 minutos.')
                                    ->warning()
                                    ->send();
                            }
                        }

                        // 2. Sincronizamos
                        self::sincronizarStartAndEnd($set, $get);
                    }),

                Hidden::make('start_time'),

                // --- HORA DE FIN (Con validación de solapamiento) ---
                // --- HORA DE FIN (Con cálculo de próxima hora libre) ---
                DateTimePicker::make('end_time')
                    ->label('Hora de Fin (Auto + 15m limpieza)')
                    ->required()
                    ->readOnly()
                    ->native(false)
                    ->seconds(false)
                    ->rule(static function ($get, ?Model $record) {
                        return function (string $attribute, $value, Closure $fail) use ($get, $record) {
                            $salaId = $get('room_id');
                            $inicio = $get('start_time');
                            $fin = $value;
                            $fecha = $get('session_date');

                            if (!$salaId || !$inicio || !$fin || !$fecha) return;

                            $ocupada = \App\Models\Session::where('room_id', $salaId)
                                ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                ->where(function ($query) use ($inicio, $fin) {
                                    $query->where('start_time', '<', $fin)
                                          ->where('end_time', '>', $inicio);
                                })
                                ->exists();

                            // SI CHOCA, BUSCAMOS EL PRÓXIMO HUECO Y AVISAMOS
                            if ($ocupada) {
                                // Buscamos la última película de ese día en esa sala
                                $ultimaSesion = \App\Models\Session::where('room_id', $salaId)
                                    ->whereDate('start_time', $fecha)
                                    ->orderBy('end_time', 'desc')
                                    ->first();

                                if ($ultimaSesion) {
                                    $end = Carbon::parse($ultimaSesion->end_time);
                                    
                                    // Redondeamos al múltiplo de 15 más cercano hacia arriba
                                    $minutosResiduales = $end->minute % 15;
                                    if ($minutosResiduales !== 0) {
                                        $end->addMinutes(15 - $minutosResiduales);
                                    }
                                    
                                    $horaSugerida = $end->format('H:i');
                                    
                                    $fail("⚠️ La sala está ocupada en este horario. El próximo hueco libre disponible es a las {$horaSugerida}.");
                                } else {
                                    $fail('⚠️ La sala ya tiene otra función en este horario. Por favor, selecciona otra hora.');
                                }
                            }
                        };
                    }),
            ]);
    }

    public static function sincronizarStartAndEnd($set, $get)
    {
        $fecha = $get('session_date');
        $hora = $get('session_time');
        $movieId = $get('movie_id');

        if ($fecha && $hora) {
            $start = Carbon::parse("$fecha $hora");
            $set('start_time', $start->toDateTimeString());

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