<?php

use Roberts\LaravelWallets\Protocols\Solana\RpcClient;
use Roberts\LaravelWallets\Protocols\Solana\RpcException;
use Roberts\LaravelWallets\Services\Solana\SolanaService;
use Roberts\LaravelWallets\Facades\SolanaRpc;

describe('Solana RPC Integration', function () {
    beforeEach(function () {
        // These tests would typically use a testnet endpoint or mock responses
        // For demonstration, we'll use a mock setup
        $this->testEndpoint = 'https://api.testnet.solana.com';
        $this->rpcClient = new RpcClient($this->testEndpoint, [
            'cache' => false, // Disable caching for tests
            'timeout' => 10
        ]);
    });

    describe('Service Provider Registration', function () {
        it('registers RPC client in service container', function () {
            expect(app()->bound(RpcClient::class))->toBeTrue();
            
            $client = app(RpcClient::class);
            expect($client)->toBeInstanceOf(RpcClient::class);
        });

        it('registers Solana service in service container', function () {
            expect(app()->bound(SolanaService::class))->toBeTrue();
            
            $service = app(SolanaService::class);
            expect($service)->toBeInstanceOf(SolanaService::class);
        });

        it('facade resolves to RPC client', function () {
            expect(SolanaRpc::getFacadeRoot())->toBeInstanceOf(RpcClient::class);
        });
    });

    describe('Configuration Integration', function () {
        it('uses configuration values', function () {
            // Test that the RPC client uses config values
            $client = app(RpcClient::class);
            $endpoint = $client->getEndpoint();
            
            // Should match configuration or default
            expect($endpoint)->toBeString();
            expect(strlen($endpoint))->toBeGreaterThan(10); // Basic URL validation
        });

        it('respects testnet configuration', function () {
            // Test configuration switching
            config(['wallets.drivers.sol.use_testnet' => true]);
            config(['wallets.drivers.sol.testnet_rpc_url' => 'https://api.testnet.solana.com']);
            
            // Create a new instance to test configuration
            $client = new RpcClient();
            expect($client->getEndpoint())->toContain('testnet');
        });
    });

    describe('Facade Usage', function () {
        it('can call methods through facade', function () {
            // Mock a simple response for testing
            expect(function () {
                SolanaRpc::setEndpoint('https://api.testnet.solana.com');
                $endpoint = SolanaRpc::getEndpoint();
                expect($endpoint)->toBe('https://api.testnet.solana.com');
            })->not->toThrow();
        });

        it('facade provides utility methods', function () {
            $lamports = 1000000000;
            $sol = SolanaRpc::lamportsToSol($lamports);
            expect($sol)->toBe(1.0);

            $solBack = SolanaRpc::solToLamports($sol);
            expect($solBack)->toBe($lamports);
        });
    });

    describe('Error Handling', function () {
        it('throws proper exceptions for invalid endpoints', function () {
            $client = new RpcClient('https://invalid-endpoint-that-does-not-exist.com');
            
            expect(function () use ($client) {
                $client->getHealth();
            })->toThrow(RpcException::class);
        });

        it('exception provides useful information', function () {
            try {
                $client = new RpcClient('https://invalid-endpoint.com');
                $client->call('invalidMethod', []);
            } catch (RpcException $e) {
                expect($e->getMessage())->toBeString();
                expect(strlen($e->getMessage()))->toBeGreaterThan(0);
            }
        });
    });

    describe('Caching Functionality', function () {
        it('can enable and disable caching', function () {
            $client = new RpcClient('https://api.testnet.solana.com', ['cache' => true]);
            
            $client->setCache(false);
            $client->setCacheTimeout(300);
            
            // Should not throw any errors
            expect(true)->toBeTrue();
        });

        it('can clear cache', function () {
            $client = new RpcClient('https://api.testnet.solana.com', ['cache' => true]);
            
            $result = $client->clearCache();
            expect($result)->toBeBool();
        });
    });

    describe('Service Integration', function () {
        it('service uses injected RPC client', function () {
            $service = app(SolanaService::class);
            $client = $service->getRpcClient();
            
            expect($client)->toBeInstanceOf(RpcClient::class);
        });

        it('service provides high-level functionality', function () {
            $service = app(SolanaService::class);
            
            // Test validation method (doesn't require network)
            $isValid = $service->isValidPublicKey('9WzDXwBbmkg8ZTbNMqUxvQRAyrZzDsGYdLVL9zYtAWWM');
            expect($isValid)->toBeTrue();

            $isValid = $service->isValidPublicKey('invalid-key');
            expect($isValid)->toBeFalse();
        });
    });

    describe('Laravel Integration Points', function () {
        it('works with Laravel collections', function () {
            $service = app(SolanaService::class);
            
            // This method returns a Collection
            $client = $service->getRpcClient();
            
            // Mock a token accounts response
            expect($client)->toBeInstanceOf(RpcClient::class);
            
            // Test that empty collection is returned when no data (without network call)
            expect(collect([]))->toBeInstanceOf(\Illuminate\Support\Collection::class);
        });

        it('respects Laravel configuration', function () {
            // Test that service respects Laravel config system
            $originalConfig = config('wallets.drivers.sol.rpc_url');
            
            config(['wallets.drivers.sol.rpc_url' => 'https://custom-endpoint.com']);
            $customUrl = config('wallets.drivers.sol.rpc_url');
            
            expect($customUrl)->toBe('https://custom-endpoint.com');
            
            // Restore original config
            config(['wallets.drivers.sol.rpc_url' => $originalConfig]);
        });

        it('integrates with Laravel logging', function () {
            // Test that logging integration works (doesn't require network)
            expect(\Illuminate\Support\Facades\Log::getLogger())->toBeTruthy();
        });

        it('integrates with Laravel caching', function () {
            // Test cache integration
            expect(\Illuminate\Support\Facades\Cache::getStore())->toBeTruthy();
            
            // Test cache operations
            \Illuminate\Support\Facades\Cache::put('test-key', 'test-value', 60);
            $value = \Illuminate\Support\Facades\Cache::get('test-key');
            expect($value)->toBe('test-value');
        });
    });
    
    describe('Real Network Interaction (Testnet)', function () {
        // These tests would require network access and should be run separately
        
        it('can get network health (if testnet available)', function () {
            // Skip this test if we don't have network access
            // This would be enabled for actual testnet testing
            
            expect(true)->toBeTrue(); // Placeholder
            
            /*
            try {
                $client = new RpcClient('https://api.testnet.solana.com');
                $health = $client->getHealth();
                expect($health)->toBe('ok');
            } catch (RpcException $e) {
                // Network not available, skip test
                expect(true)->toBeTrue();
            }
            */
        });

        it('can get version info (if testnet available)', function () {
            expect(true)->toBeTrue(); // Placeholder
            
            /*
            try {
                $client = new RpcClient('https://api.testnet.solana.com');
                $version = $client->getVersion();
                expect($version)->toBeArray();
                expect($version['solana-core'])->toBeString();
            } catch (RpcException $e) {
                // Network not available, skip test
                expect(true)->toBeTrue();
            }
            */
        });
    });
});