<?php

namespace Roberts\LaravelWallets\Filament\Resources\Wallets\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;

class WalletForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->disabled()
                    ->visibleOn('edit')
                    ->columnSpan(1)
                    ->helperText('Auto-generated unique identifier'),

                Select::make('protocol')
                    ->label('Protocol')
                    ->required()
                    ->options(Protocol::class)
                    ->enum(Protocol::class)
                    ->columnSpan(1)
                    ->helperText('The blockchain protocol for this wallet'),

                TextInput::make('address')
                    ->label('Wallet Address')
                    ->required()
                    ->maxLength(255)
                    ->columnSpan(2)
                    ->unique(ignoreRecord: true)
                    ->helperText('The blockchain address (must be unique per protocol)')
                    ->rules(['string', 'max:255'])
                    ->placeholder('0x... or base58 address'),

                Select::make('control_type')
                    ->label('Control Type')
                    ->required()
                    ->options(ControlType::class)
                    ->enum(ControlType::class)
                    ->columnSpan(1)
                    ->helperText('How this wallet is controlled'),

                TextEntry::make('metadata_info')
                    ->label('Metadata Information')
                    ->state('Use the metadata field below to store additional wallet information as JSON.')
                    ->columnSpan(1)
                    ->hiddenOn('create'),

                Textarea::make('metadata')
                    ->label('Metadata (JSON)')
                    ->nullable()
                    ->columnSpan(2)
                    ->rows(4)
                    ->helperText('Additional metadata stored as JSON. Leave empty if not needed.')
                    ->placeholder('{"chain_id": 1, "name": "Main Wallet"}')
                    ->rules(['nullable', 'json']),

                TextEntry::make('timestamps')
                    ->label('Timestamps')
                    ->state(fn ($record) => $record ?
                        "Created: {$record->created_at->format('M j, Y g:i A')} | Updated: {$record->updated_at->format('M j, Y g:i A')}" :
                        'Will be set automatically on creation'
                    )
                    ->columnSpan(2)
                    ->visibleOn('edit'),
            ]);
    }
}
