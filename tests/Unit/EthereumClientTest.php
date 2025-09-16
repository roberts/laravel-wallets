<?php

use Roberts\LaravelWallets\Protocols\Ethereum\Client;

it('generates a valid private key', function () {
    $client = new Client;
    $privateKey = $client->generatePrivateKey();

    expect($privateKey)->toBeString()
        ->and(strlen($privateKey))->toBe(64); // 32 bytes * 2 (hex)
});

it('derives a public key from a private key', function () {
    $client = new Client;
    $privateKey = $client->generatePrivateKey();
    $publicKey = $client->derivePublicKey($privateKey);

    expect($publicKey)->toBeString()
        ->and(strlen($publicKey))->toBe(130) // 04 prefix + 64 bytes * 2 (hex)
        ->and(str_starts_with($publicKey, '04'))->toBeTrue();
});

it('derives an address from a public key', function () {
    $client = new Client;
    $privateKey = $client->generatePrivateKey();
    $publicKey = $client->derivePublicKey($privateKey);
    $address = $client->deriveAddress($publicKey);

    expect($address)->toBeString()
        ->and(strlen($address))->toBe(42) // 0x + 20 bytes * 2 (hex)
        ->and(str_starts_with($address, '0x'))->toBeTrue();
});
