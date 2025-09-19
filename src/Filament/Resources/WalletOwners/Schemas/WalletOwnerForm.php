<?php

namespace Roberts\LaravelWallets\Filament\Resources\WalletOwners\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Roberts\LaravelWallets\Models\Wallet;

class WalletOwnerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('uuid')
                    ->label('UUID')
                    ->disabled()
                    ->visibleOn('edit')
                    ->helperText('Auto-generated unique identifier'),

                Select::make('wallet_id')
                    ->label('Wallet')
                    ->required()
                    ->relationship('wallet', 'address')
                    ->searchable()
                    ->preload()
                    ->getOptionLabelFromRecordUsing(fn (Wallet $record): string => "{$record->protocol->label()}: {$record->address} ({$record->control_type->label()})"
                    )
                    ->helperText('Select the wallet to assign ownership for'),

                TextInput::make('tenant_id')
                    ->label('Tenant ID')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->helperText('The tenant ID this ownership belongs to'),

                TextInput::make('owner_id')
                    ->label('Owner ID')
                    ->required()
                    ->numeric()
                    ->minValue(1)
                    ->helperText('The ID of the owner (e.g., User ID)'),

                TextInput::make('owner_type')
                    ->label('Owner Type')
                    ->required()
                    ->placeholder('e.g., App\\Models\\User')
                    ->helperText('The fully qualified class name of the owner model'),

                TextInput::make('encrypted_private_key')
                    ->label('Private Key')
                    ->password()
                    ->revealable()
                    ->nullable()
                    ->helperText('Private key (will be encrypted automatically). Leave empty for watch-only access.'),
            ]);
    }
}
