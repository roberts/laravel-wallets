<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Roberts\LaravelWallets\Tests\TestUser;
use Roberts\LaravelWallets\Wallets\SolWallet;

uses(RefreshDatabase::class);

it('can create a solana wallet and save it to the database', function () {
    $wallet = SolWallet::create();

    expect($wallet)->toBeInstanceOf(SolWallet::class);
    expect(strlen($wallet->getAddress()))->toBeGreaterThan(30); // Solana addresses are base58 and vary in length

    $this->assertDatabaseHas('wallets', [
        'address' => $wallet->getAddress(),
        'protocol' => 'sol',
    ]);
});

it('can create a solana wallet with an owner', function () {
    /** @var \Roberts\LaravelWallets\Tests\TestUser $user */
    $user = TestUser::factory()->create();

    $wallet = SolWallet::create($user);

    $this->assertDatabaseHas('wallets', [
        'address' => $wallet->getAddress(),
        'owner_id' => $user->id,
    ]);
});
