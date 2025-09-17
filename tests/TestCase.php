<?php

namespace Roberts\LaravelWallets\Tests;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Orchestra\Testbench\TestCase as Orchestra;
use Roberts\LaravelWallets\WalletsServiceProvider;

class TestCase extends Orchestra
{
    protected function setUp(): void
    {
        parent::setUp();

        Factory::guessFactoryNamesUsing(
            fn (string $modelName) => 'Roberts\\LaravelWallets\\Database\\Factories\\'.class_basename($modelName).'Factory'
        );

        // Seed the eth chains
        include_once __DIR__.'/../database/seeders/EthChainSeeder.php';
        $seeder = new \Database\Seeders\EthChainSeeder;
        $seeder->run();
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

        // Set up auth configuration for testing
        config()->set('auth.providers.users.model', TestUser::class);

        // Run the wallets migration
        $walletsMigration = include __DIR__.'/../database/migrations/2025_08_16_000100_create_wallets_table.php';
        $walletsMigration->up();

        // Run the ethchains migration
        $ethchainsMigration = include __DIR__.'/../database/migrations/2025_08_16_000000_create_eth_chains_table.php';
        $ethchainsMigration->up();

        // Create users table for testing
        $app['db']->connection()->getSchemaBuilder()->create('users', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });
    }
}

class TestUser extends \Illuminate\Foundation\Auth\User
{
    use HasFactory;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    protected static function newFactory()
    {
        return new class extends \Illuminate\Database\Eloquent\Factories\Factory
        {
            protected $model = TestUser::class;

            public function definition()
            {
                return [
                    'name' => $this->faker->name(),
                    'email' => $this->faker->unique()->safeEmail(),
                    'password' => bcrypt('password'),
                ];
            }
        };
    }
}
