<?php

namespace App\Filament\Resources\Movies\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;




class MoviesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                ImageColumn::make('poster_path')
                ->label('Cartel')
                ->height('150px')
                ->getStateUsing(function ($record) {
                    if (!$record->poster_path) return null;
                    // Retornamos la URL completa de la imagen para que Filament la dibuje
                    return "https://image.tmdb.org/t/p/w200" . $record->poster_path;
                }),

            // 2. El Título (Lo hacemos buscable y ordenable)
                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'), // Lo ponemos en negrita para que destaque

                // 3. La Duración (Le añadimos la palabra "min" al final)
                TextColumn::make('runtime')
                    ->label('Duración')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} min" : 'N/A')
                    ->icon('heroicon-o-clock') // Le ponemos un icono de relojito al lado
                    ->sortable(),

                // 4. Si está en cartelera (Muestra un check verde o una X roja)
                IconColumn::make('is_active')
                    ->label('En Web')
                    ->boolean(),
                ])
            ->filters([
                //
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),

            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
