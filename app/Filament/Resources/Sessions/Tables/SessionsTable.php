<?php

namespace App\Filament\Resources\Sessions\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\Action;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Resources\SessionResource;
use Filament\Notifications\Notification;
use App\Models\Session;
use Carbon\Carbon;

class SessionsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('movie.title')
                ->label('Película')
                ->searchable()
                ->sortable()
                ->weight('bold') // Negrita para que destaque
                ->icon('heroicon-o-film'), // Un icono de película al lado

            // 2. La Sala (Como etiqueta visual)
            TextColumn::make('room.name')
                ->label('Sala')
                ->sortable()
                ->badge() // Esto lo convierte en una "etiqueta" o pastilla de color
                ->color('info'), // Color azulito por defecto

            // 3. Hora de Inicio (Formateada bonita)
            TextColumn::make('start_time')
                ->label('Inicio')
                ->dateTime('d/m/Y H:i') // Muestra: 25/10/2023 18:00
                ->sortable(),

            // 4. Hora de Fin (Solo la hora para no repetir la fecha)
            TextColumn::make('end_time')
                ->label('Fin')
                ->dateTime('H:i') // Muestra solo: 20:15
                ->color('gray'),

            // 5. El Precio
            TextColumn::make('base_price')
                ->label('Precio')
                ->money('EUR') // Cambia EUR por USD, MXN, etc., según tu país
                ->sortable(),
        ])
        ->filters([
            // Añadimos un filtro muy útil para el cine
            SelectFilter::make('room_id')
                ->relationship('room', 'name')
                ->label('Filtrar por Sala'),
            ])
            ->recordActions([
                Action::make('clonarSiguienteDia')
                    ->label('Clonar Mañana')
                    ->icon('heroicon-o-document-duplicate')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('¿Clonar a mañana?')
                    ->modalDescription('Se creará la misma sesión en la misma sala pero 24 horas después.')
                    ->action(function (Session $record) {
                        // 1. Calculamos los nuevos tiempos (+1 día)
                        $nuevoInicio = Carbon::parse($record->start_time)->addDay();
                        $nuevoFin = Carbon::parse($record->end_time)->addDay();
                        $salaId = $record->room_id;

                        // 2. VALIDACIÓN: Comprobamos si la sala está ocupada en ese nuevo hueco
                        // Usamos la misma lógica que tienes en tu Form
                        $ocupada = Session::where('room_id', $salaId)
                            ->where(function ($query) use ($nuevoInicio, $nuevoFin) {
                                $query->where('start_time', '<', $nuevoFin)
                                    ->where('end_time', '>', $nuevoInicio);
                            })
                            ->exists();

                        if ($ocupada) {
                            Notification::make()
                                ->title('Error al clonar')
                                ->body('La sala ya está ocupada mañana a esa misma hora.')
                                ->danger()
                                ->send();
                            return;
                        }

                        // 3. CLONACIÓN: Si está libre, creamos la copia
                        $nuevaSesion = $record->replicate();
                        $nuevaSesion->start_time = $nuevoInicio;
                        $nuevaSesion->end_time = $nuevoFin;
                        $nuevaSesion->save();

                        Notification::make()
                            ->title('Sesión clonada')
                            ->body('Se ha creado la sesión para mañana con éxito.')
                            ->success()
                            ->send();
                    }),
                Action::make('ver_tickets')
                    ->label('Entradas')
                    ->icon('heroicon-o-ticket')
                    ->color('success') // Lo ponemos verde para que llame la atención
                    
                    // El toque Pro: Mostrar cuántas entradas pagadas hay
                    ->badge(fn ($record) => $record->tickets()->where('status', 'paid')->count())
                    ->url(fn ($record) => route('filament.admin.resources.sessions.tickets', ['record' => $record])),
                    // Al hacer clic, le llevamos a la página de edición de esa sesión específica
                Action::make('ver_orders')
                    ->label('Pedidos')
                    ->icon('heroicon-o-ticket')
                    ->color('success')
                    ->url(fn ($record) => route('filament.admin.resources.sessions.orders', ['record' => $record])),

                DeleteAction::make(),
                EditAction::make()
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
