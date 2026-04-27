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
                    return "https://image.tmdb.org/t/p/w200" . $record->poster_path;
                }),

                TextColumn::make('title')
                    ->label('Título')
                    ->searchable()
                    ->sortable()
                    ->weight('bold'), 

                TextColumn::make('runtime')
                    ->label('Duración')
                    ->formatStateUsing(fn ($state) => $state ? "{$state} min" : 'N/A')
                    ->icon('heroicon-o-clock') // Le ponemos un icono de relojito al lado
                    ->sortable(),

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
