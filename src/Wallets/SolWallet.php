<?php

namespace Roberts\LaravelWallets\Wallets;

use Illuminate\Contracts\Auth\Authenticatable;
use Roberts\LaravelWallets\Contracts\WalletInterface;

class SolWallet implements WalletInterface
{
    public static function create(?Authenticatable $user = null): self
    {
        // TODO: Implement SOL wallet creation logic.
        return new self;
    }

    public function getAddress(): string
    {
        // TODO: Implement getAddress() method.
        return '';
    }

    public function getPublicKey(): string
    {
        // TODO: Implement getPublicKey() method.
        return '';
    }

    public function getPrivateKey(): string
    {
        // TODO: Implement getPrivateKey() method.
        return '';
    }
}
