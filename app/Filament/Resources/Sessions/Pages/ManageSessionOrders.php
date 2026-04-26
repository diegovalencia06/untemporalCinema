<?php

namespace App\Filament\Resources\Sessions\Pages;

use App\Filament\Resources\Sessions\SessionResource;
use BackedEnum;
use Filament\Actions\AssociateAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\DissociateAction;
use Filament\Actions\DissociateBulkAction;
use Filament\Actions\EditAction;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Pages\ManageRelatedRecords;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
// NUEVOS IMPORTS NECESARIOS:
use Filament\Actions\Action;
use Illuminate\Support\Facades\Storage;

class ManageSessionOrders extends ManageRelatedRecords
{
    protected static string $resource = SessionResource::class;

    protected static string $relationship = 'orders';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedRectangleStack;

    public function form(Schema $schema): Schema
    {
        return $schema;
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                TextColumn::make('id')
                    ->label('ID Pedido')
                    ->sortable(),

                TextColumn::make('user.name')
                    ->label('Cliente')
                    ->searchable()
                    ->default('Invitado'),

                TextColumn::make('total_price')
                    ->label('Total Pagado')
                    ->money('eur')
                    ->sortable(),

                TextColumn::make('coupon.code')
                    ->label('Cupón Usado')
                    ->badge()
                    ->color('success')
                    ->placeholder('Sin cupón'),

                TextColumn::make('tickets.seat')
                    ->label('Asientos')
                    ->badge()
                    ->color('info')
                    ->separator(',') 
                    ->limitList(3)
                    ->expandableLimitedList(),

                TextColumn::make('created_at')
                    ->label('Fecha de Compra')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([])
            ->recordActions([
                
                // --- NUEVA ACCIÓN PARA VER EL PDF ---
                Action::make('ver_pdf')
                    ->label('Descargar Ticket')
                    ->icon('heroicon-o-document-text')
                    ->url(fn ($record) => route('tickets.download', ['reference' => $record->reference]))
                    ->openUrlInNewTab()
                    ->visible(fn ($record) => $record->status === 'completed'),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}