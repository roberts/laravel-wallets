<?php

namespace Roberts\LaravelWallets\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Orchestra\Testbench\TestCase as Orchestra;
use Roberts\LaravelWallets\WalletsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Roberts\\LaravelWallets\\Tests\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );
    }

    protected function getPackageProviders($app)
    {
        return [
            WalletsServiceProvider::class,
        ];
    }

    public function getEnvironmentSetUp($app)
    {
        config()->set('database.default', 'testing');

        // Manually run the migrations
        $walletsMigration = include __DIR__.'/../database/migrations/2025_08_16_000100_create_wallets_table.php';
        $walletsMigration->up();

        $usersMigration = include __DIR__.'/database/migrations/create_users_table.php';
        $usersMigration->up();
    }
}
