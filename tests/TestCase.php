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

        // Load wallets configuration for testing
        $walletsConfig = include __DIR__.'/../config/wallets.php';
        config()->set('wallets', $walletsConfig);

        // Set up auth configuration for testing
        config()->set('auth.providers.users.model', TestUser::class);

        // Create a minimal tenants table for testing
        $app['db']->connection()->getSchemaBuilder()->create('tenants', function ($table) {
            $table->bigIncrements('id');
            $table->string('name');
            $table->timestamps();
            $table->softDeletes(); // Add soft deletes for tenancy package compatibility
        });

        // Insert a test tenant and set it as active
        $tenantId = $app['db']->table('tenants')->insertGetId([
            'name' => 'Test Tenant',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Set the tenant context
        config()->set('tenancy.tenant_id', $tenantId);

        // Run the wallets migration
        $walletsMigration = include __DIR__.'/../database/migrations/2025_08_16_000100_create_wallets_table.php';
        $walletsMigration->up();

        // Run the ethchains migration
        $ethchainsMigration = include __DIR__.'/../database/migrations/2025_08_16_000000_create_eth_chains_table.php';
        $ethchainsMigration->up();

        // Run the wallet_owners migration
        $walletOwnersMigration = include __DIR__.'/../database/migrations/2025_08_16_000200_create_wallet_owners_table.php';

        // First drop the table if it exists (for testing updates)
        $app['db']->connection()->getSchemaBuilder()->dropIfExists('wallet_owners');
        $walletOwnersMigration->up();

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
    use \Roberts\LaravelWallets\Concerns\HasWallets;

    protected $table = 'users';

    protected $fillable = ['name', 'email', 'password'];

    // Implement tenant relationship for testing
    public function getTenantId(): int
    {
        return config('tenancy.tenant_id', 1);
    }

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
