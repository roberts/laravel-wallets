<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Services\WalletService;
use Roberts\LaravelWallets\Tests\TestUser;

uses(RefreshDatabase::class);

describe('Ethereum Wallet Creation (New Architecture)', function () {
    it('creates custodial ethereum wallet using WalletService', function () {
        /** @var \Roberts\LaravelWallets\Tests\TestUser $user */
        $user = TestUser::factory()->create();

        $walletService = app(WalletService::class);
        $result = $walletService->createCustodialWallet(
            protocol: Protocol::ETH,
            owner: $user,
            tenantId: 1
        );

        expect($result['wallet'])->toBeInstanceOf(Wallet::class)
            ->and($result['wallet']->address)->toStartWith('0x')->toHaveLength(42)
            ->and($result['wallet']->protocol)->toBe(Protocol::ETH);

        expect($result['walletOwner'])->toBeInstanceOf(\Roberts\LaravelWallets\Models\WalletOwner::class)
            ->and($result['walletOwner']->owner_id)->toBe($user->id)
            ->and($result['walletOwner']->owner_type)->toBe(TestUser::class);

        $this->assertDatabaseHas('wallets', [
            'address' => $result['wallet']->address,
            'protocol' => 'eth',
        ]);

        $this->assertDatabaseHas('wallet_owners', [
            'wallet_id' => $result['wallet']->id,
            'owner_id' => $user->id,
            'owner_type' => TestUser::class,
            'tenant_id' => 1,
        ]);
    });

    it('creates ethereum wallet using HasWallets trait (legacy compatibility)', function () {
        /** @var \Roberts\LaravelWallets\Tests\TestUser $user */
        $user = TestUser::factory()->create();

        // Use the legacy-compatible method
        $result = $user->createEthereumWallet();

        expect($result['wallet'])->toBeInstanceOf(Wallet::class)
            ->and($result['wallet']->address)->toStartWith('0x')
            ->and($result['wallet']->protocol)->toBe(Protocol::ETH);
    });
});

// Legacy tests disabled - they were based on the old single-table architecture
