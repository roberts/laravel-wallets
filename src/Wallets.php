<?php

namespace Roberts\LaravelWallets;

use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Contracts\WalletInterface;
use Roberts\LaravelWallets\Wallets\EthWallet;
use Roberts\LaravelWallets\Wallets\SolWallet;

class Wallets
{
    public function create(Protocol $protocol): WalletInterface
    {
        return match($protocol) {
            Protocol::ETH => EthWallet::create(),
            Protocol::SOL => SolWallet::create(),
        };
    }
}
