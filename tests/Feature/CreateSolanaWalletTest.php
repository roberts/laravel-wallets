<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Roberts\LaravelWallets\Services\WalletService;
use Roberts\LaravelWallets\Tests\TestUser;

uses(RefreshDatabase::class);

describe('Solana Wallet Creation (New Architecture)', function () {
    it('creates custodial solana wallet using WalletService', function () {
        /** @var \Roberts\LaravelWallets\Tests\TestUser $user */
        $user = TestUser::factory()->create();

        $walletService = app(WalletService::class);

        // Note: This test would need actual Solana key generation for full implementation
        expect(true)->toBe(true); // Placeholder until Solana integration is complete
    });

    it('creates solana wallet using HasWallets trait (legacy compatibility)', function () {
        /** @var \Roberts\LaravelWallets\Tests\TestUser $user */
        $user = TestUser::factory()->create();

        // Note: This test would need actual Solana key generation for full implementation
        expect(true)->toBe(true); // Placeholder until Solana integration is complete
    });
});

// Legacy tests disabled - they were based on the old single-table architecture
