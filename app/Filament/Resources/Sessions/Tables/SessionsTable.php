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
                Action::make('ver_tickets')
                    ->label('Entradas')
                    ->icon('heroicon-o-ticket')
                    ->color('success') // Lo ponemos verde para que llame la atención
                    
                    // El toque Pro: Mostrar cuántas entradas pagadas hay
                    ->badge(fn ($record) => $record->tickets()->where('status', 'paid')->count())
                    ->url(fn ($record) => route('filament.admin.resources.sessions.tickets', ['record' => $record])),
                    // Al hacer clic, le llevamos a la página de edición de esa sesión específica
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
