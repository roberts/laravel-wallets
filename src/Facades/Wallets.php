<?php

namespace Roberts\LaravelWallets\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Roberts\LaravelWallets\Wallets
 */
class Wallets extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'wallets';
    }
}
