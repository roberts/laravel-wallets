<?php

namespace Roberts\LaravelWallets;

use Roberts\LaravelWallets\Commands\LaravelWalletsCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class LaravelWalletsServiceProvider extends PackageServiceProvider
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
            ->hasMigration('create_laravel_wallets_table')
            ->hasCommand(LaravelWalletsCommand::class);
    }
}
