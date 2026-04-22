<?php

namespace App\Filament\Resources\Tickets\Schemas;

use Filament\Forms\Components\Grid;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Schema;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;

class TicketForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Tabs::make('Tabs')
                ->tabs([
                    // PESTAÑA 1: SESIÓN
                    Tab::make('Sesión')
                        ->icon('heroicon-m-film')
                        ->schema([
                            Select::make('session_id')
                                ->relationship('session', 'id')
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->movie->title} - {$record->start_time->format('d/m/Y H:i')}")
                                ->label('Seleccionar Sesión')
                                ->searchable()
                                ->required(),
                        ]),

                    // PESTAÑA 2: COMPRADOR
                    Tabs\Tab::make('Comprador')
                        ->icon('heroicon-m-user')
                        ->schema([
                            TextInput::make('buyer_email')
                                ->label('Email del Comprador')
                                ->email()
                                ->required()
                                ->maxLength(255),
                            
                            Select::make('user_id')
                                ->relationship('user', 'name')
                                ->label('Usuario Registrado (Opcional)')
                                ->searchable()
                                ->helperText('Solo si el cliente tiene cuenta en el cine.'),
                        ])->columns(2),

                    // PESTAÑA 3: ASIENTO Y PAGO
                    Tabs\Tab::make('Asiento y Pago')
                        ->icon('heroicon-m-ticket')
                        ->schema([
                            TextInput::make('row')
                                ->label('Fila')
                                ->numeric()
                                ->required(),
                            TextInput::make('column')
                                ->label('Butaca/Asiento')
                                ->numeric()
                                ->required(),                            
                            TextInput::make('price')
                                ->label('Precio Final')
                                ->numeric()
                                ->prefix('€')
                                ->required(),

                            Select::make('status')
                                ->label('Estado')
                                ->options([
                                    'pending' => 'Pendiente',
                                    'paid' => 'Pagado',
                                    'cancelled' => 'Cancelado',
                                ])
                                ->required()
                                ->default('pending'),
                        ])->columns(2),
                ])
                ->columnSpanFull(), // Hace que las pestañas ocupen todo el ancho disponible
            ]);
    }
}
