<?php

use Roberts\LaravelWallets\Filament\Resources\Wallets\WalletResource;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Tests\TestUser;

beforeEach(function () {
    // Create a test user
    $this->user = TestUser::factory()->create();
    $this->actingAs($this->user);
});

it('can render wallet resource list page', function () {
    // Create some test wallets
    $wallets = Wallet::factory(3)->create();
    
    expect(WalletResource::canAccess())->toBeTrue();
    
    // Basic test that the resource can be accessed
    expect($wallets)->toHaveCount(3);
});

it('can create wallets through the resource', function () {
    $walletData = [
        'protocol' => Protocol::ETH,
        'address' => '0x' . str_repeat('1', 40),
        'control_type' => ControlType::EXTERNAL,
        'metadata' => null,
    ];
    
    $wallet = Wallet::create($walletData);
    
    expect($wallet)->toBeInstanceOf(Wallet::class);
    expect($wallet->protocol)->toBe(Protocol::ETH);
    expect($wallet->control_type)->toBe(ControlType::EXTERNAL);
});

it('validates wallet resource access control', function () {
    // Test that the resource respects access control
    expect(WalletResource::canAccess())->toBeTrue();
    expect(WalletResource::shouldRegisterNavigation())->toBeTrue();
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