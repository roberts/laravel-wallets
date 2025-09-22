<?php

namespace Roberts\LaravelWallets\Filament\Resources\Wallets\Pages;

use Filament\Resources\Pages\ListRecords;
use Roberts\LaravelWallets\Filament\Resources\Wallets\WalletResource;

class ListWallets extends ListRecords
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\CreateAction::make(),
        ];
    }
}
