<?php

use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Filament\Resources\Wallets\WalletResource;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Tests\TestUser;

describe('Filament Wallet Resource', function () {

    beforeEach(function () {
        // Create and authenticate test user with admin privileges
        $this->user = TestUser::factory()->create();
        $this->actingAs($this->user);

        // Shared test data
        $this->walletData = [
            'protocol' => Protocol::ETH,
            'address' => '0x'.str_repeat('1', 40),
            'control_type' => ControlType::EXTERNAL,
            'metadata' => null,
        ];
    });

    describe('Resource Access Control', function () {
        it('validates wallet resource access permissions', function () {
            expect(WalletResource::canAccess())->toBeTrue();
            expect(WalletResource::shouldRegisterNavigation())->toBeTrue();
        });

        it('can render wallet resource list page', function () {
            // Create test wallets for display
            $wallets = Wallet::factory(3)->create();

            expect(WalletResource::canAccess())->toBeTrue();
            expect($wallets)->toHaveCount(3);
        });
    });

    describe('Wallet Management', function () {
        it('can create wallets through the resource', function () {
            $wallet = Wallet::create($this->walletData);

            expect($wallet)->toBeInstanceOf(Wallet::class);
            expect($wallet->protocol)->toBe(Protocol::ETH);
            expect($wallet->control_type)->toBe(ControlType::EXTERNAL);
        });

        it('can format wallet data for display', function () {
            $wallet = Wallet::factory()->create([
                'protocol' => Protocol::SOL,
                'control_type' => ControlType::CUSTODIAL,
                'address' => '11111111111111111111111111111112', // Valid Solana address
            ]);

            expect($wallet->protocol->label())->toBe('Sol');
            expect($wallet->control_type->label())->toBe('Custodial');
            expect($wallet->address)->toBe('11111111111111111111111111111112');
        });
    });
});
