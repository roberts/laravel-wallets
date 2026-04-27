<?php

namespace Roberts\LaravelWallets\Filament\Resources\WalletOwners\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Roberts\LaravelWallets\Filament\Resources\WalletOwners\WalletOwnerResource;

class ListWalletOwners extends ListRecords
{
    protected static string $resource = WalletOwnerResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
