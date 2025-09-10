<?php

namespace Roberts\LaravelWallets\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \Roberts\LaravelWallets\LaravelWallets
 */
class LaravelWallets extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Roberts\LaravelWallets\LaravelWallets::class;
    }
}
