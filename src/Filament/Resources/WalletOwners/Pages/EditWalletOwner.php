<?php

namespace Roberts\LaravelWallets\Filament\Resources\WalletOwners\Pages;

use Filament\Resources\Pages\EditRecord;
use Roberts\LaravelWallets\Filament\Resources\WalletOwners\WalletOwnerResource;

class EditWalletOwner extends EditRecord
{
    protected static string $resource = WalletOwnerResource::class;

    protected function getRedirectUrl(): ?string
    {
        return $this->getResource()::getUrl('index');
    }
}