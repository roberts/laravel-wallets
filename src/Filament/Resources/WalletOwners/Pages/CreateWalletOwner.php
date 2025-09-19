<?php

namespace Roberts\LaravelWallets\Filament\Resources\WalletOwners\Pages;

use Filament\Resources\Pages\CreateRecord;
use Roberts\LaravelWallets\Filament\Resources\WalletOwners\WalletOwnerResource;

class CreateWalletOwner extends CreateRecord
{
    protected static string $resource = WalletOwnerResource::class;
}
