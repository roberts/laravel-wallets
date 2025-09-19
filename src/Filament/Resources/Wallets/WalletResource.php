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
        if (method_exists($user, 'can')) {
            return $user->can('manage wallets') || $user->can('admin');
        }

        // If using Spatie permission package
        if (method_exists($user, 'hasPermissionTo')) {
            return $user->hasPermissionTo('manage wallets') || $user->hasPermissionTo('admin');
        }

        // Final fallback - always allow (should be configured properly in real usage)
        // In production, you should replace this with your specific admin logic
        return true;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccessResource();
    }
}