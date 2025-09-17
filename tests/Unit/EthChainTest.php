<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Roberts\LaravelWallets\Models\EthChain;

describe('EthChain Model', function () {
    beforeEach(function () {
        $this->chain = EthChain::factory()->create([
            'name' => 'Test Chain',
            'chain_id' => 999999, // Use a unique chain_id that won't conflict with seeded data
            'rpc' => 'https://eth-mainnet.public.blastapi.io',
            'rpc_alternates' => ['https://rpc.ankr.com/eth'],
            'scanner' => 'https://etherscan.io',
            'supports_eip1559' => true,
            'native_symbol' => 'ETH',
            'native_decimals' => 18,
            'is_active' => false, // Make it inactive to not affect active chains count
            'is_default' => false,
        ]);
    });

    describe('Basic Model', function () {
        test('can check table exists', function () {
            $this->assertTrue(Schema::hasTable('eth_chains'));
        });

        test('casts attributes correctly', function () {
            $chain = EthChain::factory()->create([
                'rpc_alternates' => ['https://rpc1.com', 'https://rpc2.com'],
                'is_active' => true,
                'supports_eip1559' => false,
            ]);

            expect($chain->rpc_alternates)->toBeArray()
                ->and($chain->is_active)->toBeBool()
                ->and($chain->supports_eip1559)->toBeBool();
        });

        test('can persist chain to database', function () {
            $chain = EthChain::factory()->create([
                'name' => 'Test Network',
                'chain_id' => 999,
            ]);

            expect($chain)->toBeInstanceOf(EthChain::class)
                ->and($chain->exists)->toBeTrue()
                ->and($chain->name)->toBe('Test Network')
                ->and($chain->chain_id)->toBe(999);
        });

        test('ensures unique chain_id constraint', function () {
            EthChain::factory()->create(['chain_id' => 123]);

            expect(fn() => EthChain::factory()->create(['chain_id' => 123]))
                ->toThrow(Exception::class);
        });
    });

    describe('Factory Tests', function () {
        test('factory creates valid model instance', function () {
            $chain = EthChain::factory()->make();

            expect($chain)->toBeInstanceOf(EthChain::class)
                ->and($chain->name)->not->toBeEmpty()
                ->and($chain->chain_id)->toBeInt()
                ->and($chain->rpc)->not->toBeEmpty();
        });

        test('can create a chain using factory', function () {
            $chain = EthChain::factory()->create();

            expect($chain)->toBeInstanceOf(EthChain::class)
                ->and($chain->exists)->toBeTrue();
        });

        test('can create mainnet chain', function () {
            $chain = EthChain::factory()->mainnet()->make();

            expect($chain->chain_id)->toBe(1)
                ->and($chain->name)->toContain('Mainnet');
        });

        test('can create testnet chain', function () {
            $chain = EthChain::factory()->testnet()->make();

            expect($chain->chain_id)->toBeGreaterThan(1000);
        });

        test('can create layer2 chain', function () {
            $chain = EthChain::factory()->layer2()->make();

            expect($chain->chain_id)->not->toBe(1);
        });

        test('can create inactive chain', function () {
            $chain = EthChain::factory()->inactive()->make();

            expect($chain->is_active)->toBeFalse();
        });

        test('can create chain with legacy transactions', function () {
            $chain = EthChain::factory()->legacyTransactions()->make();

            expect($chain->supports_eip1559)->toBeFalse();
        });
    });

    describe('Static Methods', function () {
        test('can get default chain', function () {
            $defaultChain = EthChain::default();

            expect($defaultChain)->not->toBeNull()
                ->and($defaultChain->name)->toBe('Ethereum Mainnet')
                ->and($defaultChain->is_default)->toBeTrue();
        });

        test('returns null when no default chain exists', function () {
            EthChain::query()->update(['is_default' => false]);
            expect(EthChain::default())->toBeNull();
        });

        test('can get active chains', function () {
            $activeChains = EthChain::active();

            expect($activeChains)->toHaveCount(18)
                ->and($activeChains->every(fn($chain) => $chain->is_active))->toBeTrue();
        });

        test('can get chain by chain_id', function () {
            $chain = EthChain::byChainId(1);

            expect($chain)->not->toBeNull()
                ->and($chain->chain_id)->toBe(1);
        });

        test('returns null when chain_id not found', function () {
            $chain = EthChain::byChainId(99999);
            expect($chain)->toBeNull();
        });
    });

    describe('RPC Methods', function () {
        test('can get primary RPC URL', function () {
            $rpcUrl = $this->chain->getPrimaryRpc();
            expect($rpcUrl)->toBe('https://eth-mainnet.public.blastapi.io');
        });

        test('can get all RPC URLs', function () {
            $allRpcs = $this->chain->getAllRpcs();

            expect($allRpcs)->toBeArray()
                ->toHaveCount(2)
                ->toContain('https://eth-mainnet.public.blastapi.io')
                ->toContain('https://rpc.ankr.com/eth');
        });

        test('handles chains without alternate RPCs', function () {
            $chain = EthChain::factory()->create([
                'rpc' => 'https://rpc.com',
                'rpc_alternates' => null,
            ]);

            expect($chain->getAllRpcs())->toBe(['https://rpc.com']);
        });

        test('removes duplicate RPCs', function () {
            $chain = EthChain::factory()->create([
                'rpc' => 'https://rpc.com',
                'rpc_alternates' => ['https://backup.com', 'https://rpc.com'],
            ]);

            $allRpcs = $chain->getAllRpcs();
            expect($allRpcs)->toHaveCount(2)
                ->toContain('https://rpc.com')
                ->toContain('https://backup.com');
        });
    });

    describe('Explorer/Scanner Methods', function () {
        test('can get explorer URL', function () {
            expect($this->chain->getExplorerUrl())->toBe('https://etherscan.io');
        });

        test('handles chains without explorer', function () {
            $chain = EthChain::factory()->create(['scanner' => null]);
            expect($chain->getExplorerUrl())->toBeNull();
        });

        test('can get transaction URL', function () {
            $txHash = '0x123456789abcdef';
            $expectedUrl = 'https://etherscan.io/tx/' . $txHash;
            expect($this->chain->getTransactionUrl($txHash))->toBe($expectedUrl);
        });

        test('returns null for transaction URL when no scanner', function () {
            $chain = EthChain::factory()->create(['scanner' => null]);
            expect($chain->getTransactionUrl('0x123'))->toBeNull();
        });

        test('can get address URL', function () {
            $address = '0x742d35Cc6634C0532925a3b8D91d9FdB0c9e6b3';
            $expectedUrl = 'https://etherscan.io/address/' . $address;
            expect($this->chain->getAddressUrl($address))->toBe($expectedUrl);
        });

        test('returns null for address URL when no scanner', function () {
            $chain = EthChain::factory()->create(['scanner' => null]);
            expect($chain->getAddressUrl('0x123'))->toBeNull();
        });
    });

    describe('Chain Properties', function () {
        test('can check EIP-1559 support', function () {
            expect($this->chain->supportsEip1559())->toBeTrue();

            $legacyChain = EthChain::factory()->legacyTransactions()->create();
            expect($legacyChain->supportsEip1559())->toBeFalse();
        });

        test('can get native token info', function () {
            expect($this->chain->getNativeSymbol())->toBe('ETH')
                ->and($this->chain->getNativeDecimals())->toBe(18);
        });

        test('can check if chain is active', function () {
            expect($this->chain->isActive())->toBeFalse();

            $activeChain = EthChain::factory()->create(['is_active' => true]);
            expect($activeChain->isActive())->toBeTrue();
        });

        test('can check if chain is default', function () {
            expect($this->chain->isDefault())->toBeFalse();

            $defaultChain = EthChain::default();
            expect($defaultChain->isDefault())->toBeTrue();
        });
    });
});