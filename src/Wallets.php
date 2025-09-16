<?php

namespace Roberts\LaravelWallets;

use Roberts\LaravelWallets\Enums\Protocol;

class Wallets
{
    public function create(Protocol $protocol)
    {
        // Logic to return a new EthWallet or SolWallet
        // based on the $protocol enum.
    }
}
