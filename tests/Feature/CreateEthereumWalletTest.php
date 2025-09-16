<?php

use Roberts\LaravelWallets\Wallets\EthWallet;

it('can create an ethereum wallet', function () {
    $wallet = EthWallet::create();

    expect($wallet)->toBeInstanceOf(EthWallet::class)
        ->and($wallet->getAddress())->toStartWith('0x')->toHaveLength(42)
        ->and($wallet->getPublicKey())->toStartWith('04')->toHaveLength(130)
        ->and($wallet->getPrivateKey())->toBeString()->toHaveLength(64);
});
