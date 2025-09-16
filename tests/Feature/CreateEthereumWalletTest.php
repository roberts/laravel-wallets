<?php

use Illuminate\Foundation\Auth\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Roberts\LaravelWallets\Wallets\EthWallet;

it('can create an ethereum wallet and save it to the database', function () {
    $wallet = EthWallet::create();

    expect($wallet)->toBeInstanceOf(EthWallet::class)
        ->and($wallet->getAddress())->toStartWith('0x')->toHaveLength(42);

    $this->assertDatabaseHas('wallets', [
        'address' => $wallet->getAddress(),
        'owner_id' => null,
    ]);

    // Verify encryption
    $dbWallet = DB::table('wallets')->where('address', $wallet->getAddress())->first();
    expect($dbWallet->private_key)->not->toBe($wallet->getPrivateKey())
        ->and(Crypt::decryptString($dbWallet->private_key))->toBe($wallet->getPrivateKey());
});

it('can create an ethereum wallet with an owner', function () {
    $user = User::create(['name' => 'Test User', 'email' => 'test@test.com', 'password' => 'password']);

    $wallet = EthWallet::create($user);

    $this->assertDatabaseHas('wallets', [
        'address' => $wallet->getAddress(),
        'owner_id' => $user->id,
    ]);
});
