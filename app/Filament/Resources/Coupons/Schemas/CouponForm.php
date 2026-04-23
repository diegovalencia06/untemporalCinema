<?php

namespace App\Filament\Resources\Coupons\Schemas;

use Filament\Schemas\Schema;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;

class CouponForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('code')
                    ->label('Código del Cupón')
                    ->required()
                    ->unique(ignoreRecord: true) // Que no haya dos códigos iguales
                    ->columnSpanFull()
                    ->extraInputAttributes(['style' => 'text-transform: uppercase;']), // Para que se vea en mayúsculas
                    
                TextInput::make('discount_percentage')
                    ->label('Descuento (%)')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->maxValue(100)
                    ->suffix('%'),
                    
                TextInput::make('stock')
                    ->label('Stock disponible')
                    ->required()
                    ->numeric()
                    ->default(100)
                    ->helperText('Cuántas veces se puede usar este cupón.'),
                    
                Toggle::make('is_active')
                    ->label('¿Cupón Activo?')
                    ->default(true),
            ]);
    }
}
