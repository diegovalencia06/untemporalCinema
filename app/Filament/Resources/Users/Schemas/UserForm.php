<?php

namespace App\Filament\Resources\Users\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;


class UserForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                    TextInput::make('name')
                        ->label('Nombre completo')
                        ->required()
                        ->maxLength(255),

                    TextInput::make('email')
                        ->label('Correo electrónico')
                        ->email()
                        ->required()
                        ->maxLength(255)
                        ->unique(ignoreRecord: true),

                    TextInput::make('password')
                        ->label('Contraseña')
                        ->password()
                        // Solo guarda la contraseña si escribes algo (para no sobreescribir con vacío al editar)
                        ->dehydrated(fn ($state) => filled($state))
                        // Obligatoria solo cuando estamos creando un usuario nuevo
                        ->required(fn (string $context): bool => $context === 'create')
                        ->maxLength(255),
            ]);
    }
}
