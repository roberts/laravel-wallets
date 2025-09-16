<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Roberts\LaravelWallets\Tests\TestUser;
use Roberts\LaravelWallets\Wallets\SolWallet;

uses(RefreshDatabase::class);

describe('Solana Wallet Creation', function () {
    it('creates wallet and saves to database', function () {
        $wallet = SolWallet::create();

        expect($wallet)->toBeInstanceOf(SolWallet::class)
            ->and(strlen($wallet->getAddress()))->toBeGreaterThan(30); // Solana addresses are base58 and vary in length

        $this->assertDatabaseHas('wallets', [
            'address' => $wallet->getAddress(),
            'protocol' => 'sol',
        ]);
    });

    it('creates wallet with owner', function () {
        /** @var \Roberts\LaravelWallets\Tests\TestUser $user */
        $user = TestUser::factory()->create();

        $wallet = SolWallet::create($user);

        $this->assertDatabaseHas('wallets', [
            'address' => $wallet->getAddress(),
            'owner_id' => $user->id,
        ]);
    });
});
