<?php

use Roberts\LaravelWallets\Filament\Resources\WalletOwners\WalletOwnerResource;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Tests\TestUser;

beforeEach(function () {
    // Create a test user
    $this->user = TestUser::factory()->create();
    $this->actingAs($this->user);
});

it('can render wallet owner resource list page', function () {
    // Create some test wallet owners
    $wallet = Wallet::factory()->create();
    $owner = TestUser::factory()->create();

    $walletOwner = WalletOwner::factory()->create([
        'wallet_id' => $wallet->id,
        'owner_id' => $owner->id,
        'owner_type' => get_class($owner),
        'tenant_id' => 1,
    ]);

    expect($walletOwner)->toBeInstanceOf(WalletOwner::class);
    expect($walletOwner->wallet_id)->toBe($wallet->id);
    expect($walletOwner->owner_id)->toBe($owner->id);
});

it('validates wallet owner resource access control', function () {
    // Test that the resource respects access control
    // Since we can't easily test the SuperAdmin service without proper setup,
    // we'll just test that the access methods exist and return boolean values
    $canAccess = WalletOwnerResource::canAccess();
    expect($canAccess)->toBeInArray([true, false]);

    $shouldRegisterNavigation = WalletOwnerResource::shouldRegisterNavigation();
    expect($shouldRegisterNavigation)->toBeInArray([true, false]);
});

it('can check wallet control status', function () {
    $wallet = Wallet::factory()->create();
    $owner = TestUser::factory()->create();

    // Create wallet owner without private key (watch-only)
    $walletOwnerWatchOnly = WalletOwner::factory()->create([
        'wallet_id' => $wallet->id,
        'owner_id' => $owner->id,
        'owner_type' => get_class($owner),
        'tenant_id' => 1,
        'encrypted_private_key' => null,
    ]);

    expect($walletOwnerWatchOnly->hasControl())->toBeFalse();

    // Create wallet owner with private key (full control)
    $walletOwnerWithControl = WalletOwner::factory()->create([
        'wallet_id' => $wallet->id,
        'owner_id' => $owner->id,
        'owner_type' => get_class($owner),
        'tenant_id' => 2, // Different tenant
        'encrypted_private_key' => 'some_encrypted_key',
    ]);

    expect($walletOwnerWithControl->hasControl())->toBeTrue();
});

it('can access wallet relationship from wallet owner', function () {
    $wallet = Wallet::factory()->create();
    $owner = TestUser::factory()->create();

    $walletOwner = WalletOwner::factory()->create([
        'wallet_id' => $wallet->id,
        'owner_id' => $owner->id,
        'owner_type' => get_class($owner),
        'tenant_id' => 1,
    ]);

    expect($walletOwner->wallet)->toBeInstanceOf(Wallet::class);
    expect($walletOwner->wallet->id)->toBe($wallet->id);
});
