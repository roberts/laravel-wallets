<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Roberts\LaravelWallets\Tests\TestUser;
use Roberts\LaravelWallets\Wallets\EthWallet;

uses(RefreshDatabase::class);

describe('Ethereum Wallet Creation', function () {
    it('creates wallet and saves to database', function () {
        $wallet = EthWallet::create();

        expect($wallet)->toBeInstanceOf(EthWallet::class)
            ->and($wallet->getAddress())->toStartWith('0x')->toHaveLength(42);

        $this->assertDatabaseHas('wallets', [
            'address' => $wallet->getAddress(),
            'protocol' => 'eth',
        ]);
    });

    it('creates wallet with owner', function () {
        /** @var \Roberts\LaravelWallets\Tests\TestUser $user */
        $user = TestUser::factory()->create();

        $wallet = EthWallet::create($user);

        expect($wallet)->toBeInstanceOf(EthWallet::class)
            ->and($wallet->getAddress())->toStartWith('0x')->toHaveLength(42);

        $this->assertDatabaseHas('wallets', [
            'owner_id' => $user->id,
            'protocol' => 'eth',
        ]);
    });
});
