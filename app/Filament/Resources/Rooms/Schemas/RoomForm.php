<?php

namespace App\Filament\Resources\Rooms\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;

class RoomForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('name')->required()->label('Nombre de la Sala'),
                TextInput::make('rows')->numeric()->required()->label('Filas'),
                TextInput::make('columns')->numeric()->required()->label('Columnas'),
            ]);
    }
}
