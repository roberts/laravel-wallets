<?php

namespace Roberts\LaravelWallets\Filament\Resources\WalletOwners\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class WalletOwnersTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('uuid')
                    ->label('UUID')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('UUID copied!')
                    ->copyMessageDuration(1500)
                    ->limit(8)
                    ->tooltip(fn ($record) => $record->uuid),

                TextColumn::make('wallet.address')
                    ->label('Wallet Address')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Address copied!')
                    ->limit(16)
                    ->tooltip(fn ($record) => $record->wallet?->address),

                TextColumn::make('wallet.protocol')
                    ->label('Protocol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'eth' => 'info',
                        'sol' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state)),

                TextColumn::make('wallet.control_type')
                    ->label('Control Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'custodial' => 'success',
                        'shared' => 'warning',
                        'external' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),

                TextColumn::make('tenant_id')
                    ->label('Tenant')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('primary'),

                TextColumn::make('owner_type')
                    ->label('Owner Type')
                    ->searchable()
                    ->formatStateUsing(fn ($state) => class_basename($state))
                    ->tooltip(fn ($record) => $record->owner_type),

                TextColumn::make('owner_id')
                    ->label('Owner ID')
                    ->searchable()
                    ->sortable(),

                IconColumn::make('has_control')
                    ->label('Has Control')
                    ->boolean()
                    ->getStateUsing(fn ($record) => $record->hasControl())
                    ->trueIcon('heroicon-o-key')
                    ->falseIcon('heroicon-o-eye')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($record) => $record->hasControl() ? 'Has private key' : 'Watch-only'),

                TextColumn::make('created_at')
                    ->label('Created')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // TODO: Add filters for protocol, control type, tenant, etc.
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }
}