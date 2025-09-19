<?php

namespace Roberts\LaravelWallets\Filament\Resources\Wallets\Pages;

use Filament\Resources\Pages\EditRecord;
use Roberts\LaravelWallets\Filament\Resources\Wallets\WalletResource;

class EditWallet extends EditRecord
{
    protected static string $resource = WalletResource::class;

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}