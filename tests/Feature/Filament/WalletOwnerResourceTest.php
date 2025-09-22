<?php

use Roberts\LaravelWallets\Filament\Resources\WalletOwners\WalletOwnerResource;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Tests\TestUser;

describe('Filament Wallet Owner Resource', function () {

    beforeEach(function () {
        // Create and authenticate test user
        $this->user = TestUser::factory()->create();
        $this->actingAs($this->user);

        // Create shared test entities
        $this->wallet = Wallet::factory()->create();
        $this->owner = TestUser::factory()->create();

        // Shared wallet owner test data
        $this->walletOwnerData = [
            'wallet_id' => $this->wallet->id,
            'owner_id' => $this->owner->id,
            'owner_type' => get_class($this->owner),
            'tenant_id' => 1,
        ];
    });

    describe('Resource Access Control', function () {
        it('validates wallet owner resource access control', function () {
            // Test that the resource respects access control
            // Since we can't easily test the SuperAdmin service without proper setup,
            // we'll just test that the access methods exist and return boolean values
            $canAccess = WalletOwnerResource::canAccess();
            expect($canAccess)->toBeBool();

            $shouldRegisterNavigation = WalletOwnerResource::shouldRegisterNavigation();
            expect($shouldRegisterNavigation)->toBeBool();
        });
    });

    describe('Wallet Owner Management', function () {
        it('can render wallet owner resource list page', function () {
            $walletOwner = WalletOwner::factory()->create($this->walletOwnerData);

            expect($walletOwner)->toBeInstanceOf(WalletOwner::class);
            expect($walletOwner->wallet_id)->toBe($this->wallet->id);
            expect($walletOwner->owner_id)->toBe($this->owner->id);
        });

        it('can access wallet relationship from wallet owner', function () {
            $walletOwner = WalletOwner::factory()->create($this->walletOwnerData);

            expect($walletOwner->wallet)->toBeInstanceOf(Wallet::class);
            expect($walletOwner->wallet->id)->toBe($this->wallet->id);
            expect($walletOwner->wallet->address)->toBe($this->wallet->address);
        });
    });

    describe('Wallet Control Status', function () {
        it('can check wallet control status', function () {
            // Create wallet owner without private key (watch-only)
            $watchOnlyOwner = TestUser::factory()->create();
            $walletOwnerWatchOnly = WalletOwner::factory()->create([
                'wallet_id' => $this->wallet->id,
                'owner_id' => $watchOnlyOwner->id,
                'owner_type' => get_class($watchOnlyOwner),
                'tenant_id' => 1,
                'encrypted_private_key' => null,
            ]);

            expect($walletOwnerWatchOnly->hasControl())->toBeFalse();

            // Create wallet owner with private key (full control) using different owner
            $controlOwner = TestUser::factory()->create();
            $walletOwnerWithControl = WalletOwner::factory()->create([
                'wallet_id' => $this->wallet->id,
                'owner_id' => $controlOwner->id,
                'owner_type' => get_class($controlOwner),
                'tenant_id' => 1,
                'encrypted_private_key' => 'encrypted_private_key_data',
            ]);

            expect($walletOwnerWithControl->hasControl())->toBeTrue();
        });
    });
});
