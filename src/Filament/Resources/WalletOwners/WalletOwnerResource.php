<?php

namespace Roberts\LaravelWallets\Filament\Resources\WalletOwners;

use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Roberts\LaravelWallets\Filament\Resources\WalletOwners\Pages\CreateWalletOwner;
use Roberts\LaravelWallets\Filament\Resources\WalletOwners\Pages\EditWalletOwner;
use Roberts\LaravelWallets\Filament\Resources\WalletOwners\Pages\ListWalletOwners;
use Roberts\LaravelWallets\Filament\Resources\WalletOwners\Schemas\WalletOwnerForm;
use Roberts\LaravelWallets\Filament\Resources\WalletOwners\Tables\WalletOwnersTable;
use Roberts\LaravelWallets\Models\WalletOwner;

class WalletOwnerResource extends Resource
{
    protected static ?string $model = WalletOwner::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Super Admin';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static ?string $recordTitleAttribute = 'uuid';

    protected static ?string $navigationLabel = 'Wallet Owners';

    protected static ?string $pluralLabel = 'Wallet Owners';

    public static function form(Schema $schema): Schema
    {
        return WalletOwnerForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return WalletOwnersTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListWalletOwners::route('/'),
            'create' => CreateWalletOwner::route('/create'),
            'edit' => EditWalletOwner::route('/{record}/edit'),
        ];
    }

    public static function getMiddleware(): array
    {
        return [
            'auth.primary',
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

        // Check if we're on the primary tenancy domain
        $currentDomain = request()->getHost();
        $primaryDomain = config('app.url'); // or however primary domain is configured

        // This resource should only be available on primary domain
        if (! str_contains($primaryDomain, $currentDomain)) {
            return false;
        }

        // Check if we have the SuperAdmin service from tenancy package
        if (class_exists('Roberts\\LaravelSingledbTenancy\\Services\\SuperAdmin')) {
            return app('Roberts\\LaravelSingledbTenancy\\Services\\SuperAdmin')->is($user);
        }

        // Fallback: check if user has admin role (assuming a method like isAdmin exists)
        return method_exists($user, 'isAdmin') ? $user->isAdmin() : false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccessResource();
    }
}
