<?php

namespace Roberts\LaravelWallets;

use Roberts\LaravelWallets\Commands\WalletsCommand;
use Roberts\LaravelWallets\Contracts\EncryptionServiceInterface;
use Roberts\LaravelWallets\Contracts\SecurityServiceInterface;
use Roberts\LaravelWallets\Protocols\Ethereum\Client as EthereumClient;
use Roberts\LaravelWallets\Protocols\Ethereum\WalletAdapter as EthereumWalletAdapter;
use Roberts\LaravelWallets\Protocols\Solana\Client as SolanaClient;
use Roberts\LaravelWallets\Protocols\Solana\WalletAdapter as SolanaWalletAdapter;
use Roberts\LaravelWallets\Services\Base58Service;
use Roberts\LaravelWallets\Services\Bip39Service;
use Roberts\LaravelWallets\Services\EncryptionService;
use Roberts\LaravelWallets\Services\KeccakService;
use Roberts\LaravelWallets\Services\SecurityService;
use Roberts\LaravelWallets\Services\WalletManager;
use Roberts\LaravelWallets\Services\WalletService;
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
            ->hasConfigFile('wallets')
            ->hasViews()
            ->hasMigration('2025_08_16_000000_create_eth_chains_table')
            ->hasMigration('2025_08_16_000100_create_wallets_table')
            ->hasMigration('2025_08_16_000200_create_wallet_owners_table')
            ->hasMigration('2025_08_18_000200_create_key_releases_table')
            ->hasMigration('2025_08_18_000300_create_wallet_audit_logs_table')
            ->hasCommand(WalletsCommand::class);
    }

    public function packageBooted(): void
    {
        // Register core services as singletons
        $this->app->singleton(WalletService::class);
        $this->app->singleton(WalletManager::class);

        // Register security services as singletons for performance and state consistency
        $this->app->singleton(SecurityServiceInterface::class, SecurityService::class);
        $this->app->singleton(EncryptionServiceInterface::class, EncryptionService::class);

        // Register utility services
        $this->app->singleton(Base58Service::class);
        $this->app->singleton(Bip39Service::class);
        $this->app->singleton(KeccakService::class);

        // Register protocol-specific clients (if they exist)
        if (class_exists(EthereumClient::class)) {
            $this->app->bind(EthereumClient::class);
        }

        if (class_exists(SolanaClient::class)) {
            $this->app->bind(SolanaClient::class, function ($app) {
                return new SolanaClient(
                    $app->make(Base58Service::class)
                );
            });
        }

        // Register wallet adapters with security service injection (if they exist)
        if (class_exists(EthereumWalletAdapter::class)) {
            $this->app->bind(EthereumWalletAdapter::class);
        }

        if (class_exists(SolanaWalletAdapter::class)) {
            $this->app->bind(SolanaWalletAdapter::class);
        }

        // Register middleware for security (if needed)
        $this->registerSecurityMiddleware();
    }

    public function packageRegistered(): void
    {
        // Register configuration merging
        $this->mergeConfigFrom(__DIR__.'/../config/wallets.php', 'wallets');

        // Configure security logging channel if not already present
        $this->configureSecurityLogging();
    }

    /**
     * Register security middleware if needed.
     */
    private function registerSecurityMiddleware(): void
    {
        // Security middleware registration can be added here if needed
        // For now, security is handled at the service level
    }

    /**
     * Configure security logging channel.
     */
    private function configureSecurityLogging(): void
    {
        // Only configure if Laravel's logging config doesn't already have a security channel
        $loggingConfig = config('logging.channels', []);

        if (! isset($loggingConfig['security'])) {
            config([
                'logging.channels.security' => [
                    'driver' => 'daily',
                    'path' => storage_path('logs/security/security.log'),
                    'level' => config('logging.level', 'info'),
                    'days' => 90,
                    'permission' => 0640,
                ],
            ]);
        }
    }
}
