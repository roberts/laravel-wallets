<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Roberts\LaravelWallets\Tests\TestUser;
use Roberts\LaravelWallets\Wallets\EthWallet;

uses(RefreshDatabase::class);

it('can create an ethereum wallet and save it to the database', function () {
    $wallet = EthWallet::create();

    expect($wallet)->toBeInstanceOf(EthWallet::class);
    expect($wallet->getAddress())->toStartWith('0x')->toHaveLength(42);

    $this->assertDatabaseHas('wallets', [
        'address' => $wallet->getAddress(),
        'protocol' => 'eth',
    ]);
});

it('can create an ethereum wallet with an owner', function () {
    /** @var \Roberts\LaravelWallets\Tests\TestUser $user */
    $user = TestUser::factory()->create();

    $wallet = EthWallet::create($user);

    expect($wallet)->toBeInstanceOf(EthWallet::class);
    expect($wallet->getAddress())->toStartWith('0x')->toHaveLength(42);

    $this->assertDatabaseHas('wallets', [
        'owner_id' => $user->id,
        'protocol' => 'eth',
    ]);
});
