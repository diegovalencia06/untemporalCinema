<?php

namespace App\Filament\Resources\Coupons\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ToggleColumn;

class CouponsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('code')
                ->label('Código')
                ->searchable()
                ->sortable()
                ->weight('bold')
                ->color('primary'), // Lo pone del color principal de tu panel
                
                TextColumn::make('discount_percentage')
                    ->label('Descuento')
                    ->sortable()
                    ->suffix('%')
                    ->badge(), // Le pone un diseño tipo "píldora"
                    
                TextColumn::make('stock')
                    ->label('Stock')
                    ->sortable()
                    ->color(fn ($state): string => $state < 10 ? 'danger' : 'success'), // Se pone rojo si quedan menos de 10
                    
                ToggleColumn::make('is_active')
                    ->label('Activo')
                    ->sortable(),
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
