<?php

namespace Roberts\LaravelWallets;

use Roberts\LaravelWallets\Commands\WalletsCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class WalletsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('laravel-wallets')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('2025_08_16_000000_create_blockchains_table')
            ->hasMigration('2025_08_16_000100_create_wallets_table')
            ->hasMigration('2025_08_18_000200_create_key_releases_table')
            ->hasCommand(WalletsCommand::class);
    }
}
