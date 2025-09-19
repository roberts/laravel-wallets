<?php

namespace Roberts\LaravelWallets\Filament\Resources\Wallets\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;

class WalletsTable
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

                TextColumn::make('protocol')
                    ->label('Protocol')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'eth' => 'info',
                        'sol' => 'purple',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => strtoupper($state))
                    ->sortable(),

                TextColumn::make('address')
                    ->label('Address')
                    ->searchable()
                    ->sortable()
                    ->copyable()
                    ->copyMessage('Address copied!')
                    ->limit(20)
                    ->tooltip(fn ($record) => $record->address)
                    ->fontFamily('mono'),

                TextColumn::make('control_type')
                    ->label('Control')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'custodial' => 'success',
                        'shared' => 'warning',
                        'external' => 'gray',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state))
                    ->sortable(),

                TextColumn::make('owners_count')
                    ->label('Owners')
                    ->counts('owners')
                    ->badge()
                    ->color('primary')
                    ->sortable(),

                TextColumn::make('metadata')
                    ->label('Metadata')
                    ->limit(30)
                    ->tooltip(fn ($record) => $record->metadata ? json_encode($record->metadata, JSON_PRETTY_PRINT) : 'No metadata')
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->formatStateUsing(fn ($state) => $state ? 'Has metadata' : 'No metadata'),

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
                SelectFilter::make('protocol')
                    ->label('Protocol')
                    ->options([
                        'eth' => 'Ethereum',
                        'sol' => 'Solana',
                    ])
                    ->multiple(),

                SelectFilter::make('control_type')
                    ->label('Control Type')
                    ->options([
                        'custodial' => 'Custodial',
                        'shared' => 'Shared',
                        'external' => 'External',
                    ])
                    ->multiple(),
            ])
            ->recordActions([
                ViewAction::make(),
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