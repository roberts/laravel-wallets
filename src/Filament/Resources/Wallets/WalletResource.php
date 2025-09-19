<?php

namespace Roberts\LaravelWallets\Filament\Resources\Wallets;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Roberts\LaravelWallets\Filament\Resources\Wallets\Pages\CreateWallet;
use Roberts\LaravelWallets\Filament\Resources\Wallets\Pages\EditWallet;
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
        return WalletForm::configure($schema);
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
            'edit' => EditWallet::route('/{record}/edit'),
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

        // Check if user has admin status - this should work on all tenants
        // This is a basic implementation - adjust based on your admin role logic
        if (method_exists($user, 'isAdmin')) {
            return $user->isAdmin();
        }

        // Fallback: check for common admin role patterns
        if (method_exists($user, 'hasRole')) {
            return $user->hasRole('admin') || $user->hasRole('super-admin');
        }

        // Additional fallback: check for admin-related methods
        if (method_exists($user, 'can')) {
            return $user->can('manage wallets') || $user->can('admin');
        }

        // Final fallback - always allow (should be configured properly in real usage)
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccessResource();
    }
}