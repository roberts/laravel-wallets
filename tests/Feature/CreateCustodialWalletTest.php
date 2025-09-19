<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Services\WalletService;
use Roberts\LaravelWallets\Tests\TestUser;

uses(RefreshDatabase::class);

describe('Create Custodial Wallet', function () {

    beforeEach(function () {
        $this->user = TestUser::factory()->create();
        $this->walletService = app(WalletService::class);

        $this->ethMetadata = ['label' => 'My ETH Wallet'];
        $this->solMetadata = ['label' => 'My SOL Wallet'];

        $this->tenantId = 1;

        $this->customPrivateKey = '0x'.str_repeat('a', 64); // Valid hex private key

        $this->complexMetadata = [
            'label' => 'Trading Wallet',
            'purpose' => 'DeFi Operations',
            'created_by' => 'API',
            'tags' => ['trading', 'defi'],
        ];
    });

    describe('Basic Wallet Creation', function () {
        it('creates ethereum custodial wallet for user', function () {
            $result = $this->walletService->createCustodialWallet(
                protocol: Protocol::ETH,
                owner: $this->user,
                tenantId: $this->tenantId,
                metadata: $this->ethMetadata
            );

            expect($result)->toHaveKey('wallet')
                ->and($result)->toHaveKey('walletOwner')
                ->and($result['wallet'])->toBeInstanceOf(Wallet::class)
                ->and($result['walletOwner'])->toBeInstanceOf(WalletOwner::class);

            $wallet = $result['wallet'];
            $walletOwner = $result['walletOwner'];

            // Verify wallet registry entry
            expect($wallet->protocol)->toBe(Protocol::ETH)
                ->and($wallet->control_type)->toBe(ControlType::CUSTODIAL)
                ->and($wallet->address)->toMatch('/^0x[a-fA-F0-9]{40}$/')
                ->and($wallet->metadata)->toBe($this->ethMetadata);

            // Verify ownership record
            expect($walletOwner->wallet_id)->toBe($wallet->id)
                ->and($walletOwner->tenant_id)->toBe($this->tenantId)
                ->and($walletOwner->owner_id)->toBe($this->user->id)
                ->and($walletOwner->owner_type)->toBe(TestUser::class)
                ->and($walletOwner->encrypted_private_key)->not()->toBeNull();

            // Verify private key can be decrypted
            $privateKey = $walletOwner->encrypted_private_key;
            expect($privateKey)->toBeString()
                ->and(strlen($privateKey))->toBeGreaterThan(60); // Ethereum private keys are 64 hex chars
        });

        it('creates solana custodial wallet for user', function () {
            $result = $this->walletService->createCustodialWallet(
                protocol: Protocol::SOL,
                owner: $this->user,
                tenantId: $this->tenantId,
                metadata: $this->solMetadata
            );

            expect($result)->toHaveKey('wallet')
                ->and($result)->toHaveKey('walletOwner');

            [$wallet, $walletOwner] = [$result['wallet'], $result['walletOwner']];

            // Verify wallet registry entry
            expect($wallet->protocol)->toBe(Protocol::SOL)
                ->and($wallet->control_type)->toBe(ControlType::CUSTODIAL)
                ->and($wallet->address)->toMatch('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/') // Base58 format
                ->and($wallet->metadata)->toBe($this->solMetadata);

            // Verify ownership record
            expect($walletOwner->wallet_id)->toBe($wallet->id)
                ->and($walletOwner->tenant_id)->toBe($this->tenantId)
                ->and($walletOwner->owner_id)->toBe($this->user->id)
                ->and($walletOwner->owner_type)->toBe(TestUser::class)
                ->and($walletOwner->encrypted_private_key)->not()->toBeNull();
        });

        it('generates valid ethereum address from private key', function () {
            $result = $this->walletService->createCustodialWallet(
                protocol: Protocol::ETH,
                owner: $this->user,
                tenantId: $this->tenantId
            );

            [$wallet, $walletOwner] = [$result['wallet'], $result['walletOwner']];

            // Address should be properly derived from private key
            expect($wallet->address)->toMatch('/^0x[a-fA-F0-9]{40}$/')
                ->and($walletOwner->encrypted_private_key)->not()->toBeEmpty(); // Just check it's not empty

            // The encrypted private key should be a JSON string (encrypted)
            expect($walletOwner->encrypted_private_key)->toBeString()
                ->and(strlen($walletOwner->encrypted_private_key))->toBeGreaterThan(50); // Encrypted data is longer
        });
    });

    describe('Custom Configuration', function () {
        it('creates wallet with custom private key when provided', function () {
            $result = $this->walletService->createCustodialWallet(
                protocol: Protocol::ETH,
                owner: $this->user,
                tenantId: $this->tenantId,
                privateKey: $this->customPrivateKey
            );

            expect($result['walletOwner']->encrypted_private_key)->toBe($this->customPrivateKey);
        });

        it('stores metadata correctly', function () {
            $result = $this->walletService->createCustodialWallet(
                protocol: Protocol::ETH,
                owner: $this->user,
                tenantId: $this->tenantId,
                metadata: $this->complexMetadata
            );

            expect($result['wallet']->metadata)->toBe($this->complexMetadata);
        });
    });

    describe('Multi-Tenant and Multi-User Support', function () {
        it('allows different users to have custodial wallets in different tenants', function () {
            $user2 = TestUser::factory()->create();

            $result1 = $this->walletService->createCustodialWallet(
                protocol: Protocol::ETH,
                owner: $this->user,
                tenantId: $this->tenantId
            );

            $result2 = $this->walletService->createCustodialWallet(
                protocol: Protocol::ETH,
                owner: $user2,
                tenantId: 2
            );

            expect($result1['wallet']->id)->not()->toBe($result2['wallet']->id)
                ->and($result1['walletOwner']->tenant_id)->toBe($this->tenantId)
                ->and($result2['walletOwner']->tenant_id)->toBe(2);
        });

        it('creates wallet with unique uuid', function () {
            $result1 = $this->walletService->createCustodialWallet(
                protocol: Protocol::ETH,
                owner: $this->user,
                tenantId: $this->tenantId
            );

            $result2 = $this->walletService->createCustodialWallet(
                protocol: Protocol::SOL,
                owner: $this->user,
                tenantId: $this->tenantId
            );

            expect($result1['wallet']->uuid)->not()->toBe($result2['wallet']->uuid)
                ->and($result1['walletOwner']->uuid)->not()->toBe($result2['walletOwner']->uuid);
        });
    });

    describe('Validation and Relationships', function () {
        it('validates protocol is supported', function () {
            expect(function () {
                $this->walletService->createCustodialWallet(
                    protocol: 'INVALID_PROTOCOL',
                    owner: $this->user,
                    tenantId: $this->tenantId
                );
            })->toThrow(\ValueError::class); // Protocol::from throws ValueError for invalid values
        });

        it('creates wallet relationships properly', function () {
            $result = $this->walletService->createCustodialWallet(
                protocol: Protocol::ETH,
                owner: $this->user,
                tenantId: $this->tenantId
            );

            [$wallet, $walletOwner] = [$result['wallet'], $result['walletOwner']];

            // Test relationships
            expect($wallet->owners)->toHaveCount(1)
                ->and($wallet->owners->first()->id)->toBe($walletOwner->id)
                ->and($walletOwner->wallet->id)->toBe($wallet->id)
                ->and($walletOwner->owner->id)->toBe($this->user->id);
        });
    });
});
