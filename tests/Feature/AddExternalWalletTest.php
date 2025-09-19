<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Services\WalletService;
use Roberts\LaravelWallets\Tests\TestUser;

uses(RefreshDatabase::class);

describe('Add External Wallet', function () {
    it('adds external ethereum wallet for tracking', function () {
        $user = TestUser::factory()->create();
        $walletService = app(WalletService::class);

        $result = $walletService->addExternalWallet(
            protocol: Protocol::ETH,
            address: '0x742d35Cc69fF8D7aFF6C13Ac43Ca6d4BFEa77Eee',
            owner: $user,
            tenantId: 1,
            metadata: ['source' => 'user_import', 'label' => 'My External Wallet']
        );

        expect($result)->toHaveKey('wallet')
            ->and($result)->toHaveKey('walletOwner')
            ->and($result['wallet'])->toBeInstanceOf(Wallet::class)
            ->and($result['walletOwner'])->toBeInstanceOf(WalletOwner::class);

        $wallet = $result['wallet'];
        $walletOwner = $result['walletOwner'];

        // Verify wallet registry entry
        expect($wallet->protocol)->toBe(Protocol::ETH)
            ->and($wallet->address)->toBe('0x742d35Cc69fF8D7aFF6C13Ac43Ca6d4BFEa77Eee')
            ->and($wallet->control_type)->toBe(ControlType::EXTERNAL)
            ->and($wallet->metadata)->toEqual(['source' => 'user_import', 'label' => 'My External Wallet']);

        // Verify ownership record
        expect($walletOwner->wallet_id)->toBe($wallet->id)
            ->and($walletOwner->owner_id)->toBe($user->id)
            ->and($walletOwner->tenant_id)->toBe(1)
            ->and($walletOwner->encrypted_private_key)->toBeNull(); // External wallets have no private key
    });

    it('adds external solana wallet for tracking', function () {
        $user = TestUser::factory()->create();
        $walletService = app(WalletService::class);

        $result = $walletService->addExternalWallet(
            protocol: Protocol::SOL,
            address: 'DRiUebGdWwVKVFNKjkNm5fmJdqGbA5oF6rqhgHDrEkp2',
            owner: $user,
            tenantId: 1,
            metadata: ['source' => 'snapshot', 'token' => 'USDC']
        );

        $wallet = $result['wallet'];
        $walletOwner = $result['walletOwner'];

        expect($wallet->protocol)->toBe(Protocol::SOL)
            ->and($wallet->address)->toBe('DRiUebGdWwVKVFNKjkNm5fmJdqGbA5oF6rqhgHDrEkp2')
            ->and($wallet->control_type)->toBe(ControlType::EXTERNAL)
            ->and($walletOwner->encrypted_private_key)->toBeNull();
    });

    it('reuses existing external wallet but creates new ownership', function () {
        $user1 = TestUser::factory()->create();
        $user2 = TestUser::factory()->create();
        $walletService = app(WalletService::class);
        $address = '0x742d35Cc69fF8D7aFF6C13Ac43Ca6d4BFEa77Eee';

        // First user adds the wallet
        $result1 = $walletService->addExternalWallet(
            protocol: Protocol::ETH,
            address: $address,
            owner: $user1,
            tenantId: 1
        );

        // Second user adds the same wallet
        $result2 = $walletService->addExternalWallet(
            protocol: Protocol::ETH,
            address: $address,
            owner: $user2,
            tenantId: 1
        );

        // Should reuse the same wallet but create separate ownership
        expect($result1['wallet']->id)->toBe($result2['wallet']->id)
            ->and($result1['walletOwner']->id)->not->toBe($result2['walletOwner']->id)
            ->and($result1['walletOwner']->owner_id)->toBe($user1->id)
            ->and($result2['walletOwner']->owner_id)->toBe($user2->id);
    });

    it('validates ethereum address format', function () {
        $user = TestUser::factory()->create();
        $walletService = app(WalletService::class);

        expect(fn () => $walletService->addExternalWallet(
            protocol: Protocol::ETH,
            address: 'invalid-address',
            owner: $user,
            tenantId: 1
        ))->toThrow(\InvalidArgumentException::class, 'Invalid Ethereum address format');
    });

    it('validates solana address format', function () {
        $user = TestUser::factory()->create();
        $walletService = app(WalletService::class);

        expect(fn () => $walletService->addExternalWallet(
            protocol: Protocol::SOL,
            address: '0x742d35Cc69fF8D7aFF6C13Ac43Ca6d4BFEa77Eee', // ETH address for SOL protocol
            owner: $user,
            tenantId: 1
        ))->toThrow(\InvalidArgumentException::class, 'Invalid Solana address format');
    });
});
