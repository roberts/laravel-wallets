<?php

namespace Roberts\LaravelWallets\Filament\Resources\Wallets\Pages;

use Filament\Resources\Pages\CreateRecord;
use Roberts\LaravelWallets\Filament\Resources\Wallets\WalletResource;

class CreateWallet extends CreateRecord
{
    protected static string $resource = WalletResource::class;
}