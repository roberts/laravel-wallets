<?php

use Roberts\LaravelWallets\Protocols\Solana\Client;
use Roberts\LaravelWallets\Services\Bip39Service;
use Roberts\LaravelWallets\Services\Base58Service;

it('generates a keypair from a seed', function () {
    $bip39Service = new Bip39Service();
    $base58Service = new Base58Service();
    $client = new Client($base58Service);

    $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
    $seed = $bip39Service->mnemonicToSeed($mnemonic);

    $keypair = $client->generateKeypairFromSeed($seed);

    expect($keypair)->toBeArray();
    expect($keypair)->toHaveKeys(['public_key', 'private_key']);
    expect(strlen($keypair['public_key']))->toBe(32);
    expect(strlen($keypair['private_key']))->toBe(64);
});

it('derives an address from a public key', function () {
    $base58Service = new Base58Service();
    $client = new Client($base58Service);
    $publicKey = random_bytes(32);

    $address = $client->getAddressFromPublicKey($publicKey);
    $expectedAddress = $base58Service->encode($publicKey);

    expect($address)->toBe($expectedAddress);
});
