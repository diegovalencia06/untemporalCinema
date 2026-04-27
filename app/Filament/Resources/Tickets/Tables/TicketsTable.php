<?php

namespace App\Filament\Resources\Tickets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class TicketsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('qr_code')
                    ->label('Código')
                    ->searchable()
                    ->weight('bold')
                    ->copyable() 
                    ->copyMessage('Código copiado'),

                TextColumn::make('session.movie.title')
                    ->label('Película')
                    ->sortable()
                    ->searchable(),

                TextColumn::make('buyer_email')
                    ->label('Comprador')
                    ->searchable(),

                TextColumn::make('ubicacion')
                    ->label('Asiento')
                    ->getStateUsing(fn ($record) => "Fila {$record->row}, As. {$record->column}"),

                TextColumn::make('price')
                    ->label('Precio')
                    ->money('EUR')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'paid' => 'success',
                        'cancelled' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending' => 'Pendiente',
                        'paid' => 'Pagado',
                        'cancelled' => 'Cancelado',
                        default => $state,
                    }),
                    
                TextColumn::make('created_at')
                    ->label('Fecha de Compra')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true), 
            ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
