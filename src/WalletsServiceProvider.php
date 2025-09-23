<?php

namespace Roberts\LaravelWallets;

use Filament\Facades\Filament;
use Roberts\LaravelWallets\Commands\WalletsCommand;
use Roberts\LaravelWallets\Contracts\EncryptionServiceInterface;
use Roberts\LaravelWallets\Contracts\SecurityServiceInterface;
use Roberts\LaravelWallets\Filament\WalletsPlugin;
use Roberts\LaravelWallets\Protocols\Ethereum\Client as EthereumClient;
use Roberts\LaravelWallets\Protocols\Ethereum\WalletAdapter as EthereumWalletAdapter;
use Roberts\LaravelWallets\Protocols\Solana\Client as SolanaClient;
use Roberts\LaravelWallets\Protocols\Solana\RpcClient as SolanaRpcClient;
use Roberts\LaravelWallets\Protocols\Solana\WalletAdapter as SolanaWalletAdapter;
use Roberts\LaravelWallets\Services\Base58Service;
use Roberts\LaravelWallets\Services\Bip39Service;
use Roberts\LaravelWallets\Services\EncryptionService;
use Roberts\LaravelWallets\Services\KeccakService;
use Roberts\LaravelWallets\Services\SecurityService;
use Roberts\LaravelWallets\Services\Solana\SolanaService;
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

        // Register protocol-specific clients
        $this->app->bind(EthereumClient::class);
        $this->app->bind(SolanaClient::class, function ($app) {
            return new SolanaClient(
                $app->make(Base58Service::class)
            );
        });

        // Register wallet adapters
        $this->app->bind(EthereumWalletAdapter::class);
        $this->app->bind(SolanaWalletAdapter::class);

        // Register Solana RPC client
        $this->app->singleton(SolanaRpcClient::class, function ($app) {
            $config = config('wallets.drivers.sol', []);
            $endpoint = $config['use_testnet'] ?? false
                ? ($config['testnet_rpc_url'] ?? 'https://api.testnet.solana.com')
                : ($config['rpc_url'] ?? 'https://api.mainnet-beta.solana.com');

            return new SolanaRpcClient($endpoint, $config);
        });

        // Register Solana service
        $this->app->singleton(SolanaService::class, function ($app) {
            return new SolanaService(
                $app->make(SolanaRpcClient::class)
            );
        });

        // Auto-register Filament plugin if Filament is available
        $this->registerFilamentPlugin();
    }

    public function packageRegistered(): void
    {
        // Register configuration merging
        $this->mergeConfigFrom(__DIR__.'/../config/wallets.php', 'wallets');

        // Configure security logging channel if not already present
        $this->configureSecurityLogging();
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

    /**
     * Auto-register Filament plugin if Filament is available.
     */
    protected function registerFilamentPlugin(): void
    {
        if (! class_exists('Filament\Facades\Filament')) {
            return;
        }

        // Use booted callback to register plugin with panels after they're configured
        $this->app->booted(function () {
            if (! class_exists('Filament\Facades\Filament')) {
                return;
            }

            try {
                $panels = Filament::getPanels();

                foreach ($panels as $panel) {
                    if (! $panel->hasPlugin('roberts-laravel-wallets')) {
                        $panel->plugin(WalletsPlugin::make());
                    }
                }
            } catch (\Exception $e) {
                // Silently fail if Filament is not properly configured
                // This can happen during static analysis or testing
            }
        });
    }
}
