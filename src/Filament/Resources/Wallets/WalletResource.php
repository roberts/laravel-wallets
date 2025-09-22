<?php

namespace Roberts\LaravelWallets\Filament\Resources\Wallets;

use BackedEnum;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Filament\Resources\Wallets\Pages\CreateWallet;
use Roberts\LaravelWallets\Filament\Resources\Wallets\Pages\ListWallets;
use Roberts\LaravelWallets\Filament\Resources\Wallets\Schemas\WalletForm;
use Roberts\LaravelWallets\Filament\Resources\Wallets\Tables\WalletsTable;
use Roberts\LaravelWallets\Models\Wallet;

class WalletResource extends Resource
{
    protected static ?string $model = Wallet::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Wallets';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedWallet;

    protected static ?string $recordTitleAttribute = 'address';

    protected static ?string $navigationLabel = 'Wallets';

    protected static ?string $pluralLabel = 'Wallets';

    public static function form(Schema $schema): Schema
    {
        // Check if we're on the create page
        if (request()->routeIs('filament.*.resources.wallets.create')) {
            return static::getCreateForm($schema);
        }

        // Default form for edit/view
        return WalletForm::configure($schema);
    }

    public static function getCreateForm(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextEntry::make('external_wallet_header')
                    ->label('Add External Wallet')
                    ->state('Enter an existing wallet address to track')
                    ->columnSpan(2)
                    ->extraAttributes(['style' => 'font-weight: 600; margin-bottom: 0.5rem;']),

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
                    ->columnSpan(1)
                    ->placeholder('0x... or base58 address')
                    ->helperText('Enter the blockchain address (must be unique per protocol)')
                    ->rules(['string', 'max:255']),

                TextEntry::make('or_separator')
                    ->label('')
                    ->state('— OR —')
                    ->columnSpan(2)
                    ->extraAttributes(['style' => 'text-align: center; color: #6b7280; font-weight: 500; padding: 1rem 0;']),

                TextEntry::make('custodial_wallet_header')
                    ->label('Generate Custodial Wallet')
                    ->state('Use the "Generate Custodial Wallet" action button above to create a new wallet with full private key control.')
                    ->columnSpan(2)
                    ->extraAttributes(['style' => 'font-weight: 600; text-align: center;']),
            ]);
    }

    public static function table(Table $table): Table
    {
        return WalletsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            // TODO: Add relation managers for wallet owners if needed
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWallets::route('/'),
            'create' => CreateWallet::route('/create'),
        ];
    }

    public static function canAccess(): bool
    {
        return static::canAccessResource();
    }

    public static function canAccessResource(): bool
    {
        $user = Auth::user();

        if (! $user) {
            return false;
        }

        // This resource should be available on all tenants for admin users
        // Check if user has admin status - this should work on all tenants
        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        // Check for common admin role patterns
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('super-admin');
        }

        // Check for permission-based access
        if ($user->can('manage wallets') || $user->can('admin')) {
            return true;
        }

        // If using Spatie permission package
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('manage wallets') || $user->hasPermissionTo('admin');
        }

        // Final fallback - always deny (secure-by-default)
        // In production, you should replace this with your specific admin logic
        return false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccessResource();
    }
}
