<?php

use Roberts\LaravelWallets\Protocols\Ethereum\Client;

describe('EthereumClient', function () {
    beforeEach(function () {
        $this->client = new Client;
    });

    it('generates valid private keys', function () {
        $privateKey = $this->client->generatePrivateKey();

        expect($privateKey)->toBeString()
            ->and(strlen($privateKey))->toBe(64); // 32 bytes * 2 (hex)
    });

    it('derives public key from private key', function () {
        $privateKey = $this->client->generatePrivateKey();
        $publicKey = $this->client->derivePublicKey($privateKey);

        expect($publicKey)->toBeString()
            ->and(strlen($publicKey))->toBe(130) // 04 prefix + 64 bytes * 2 (hex)
            ->and(str_starts_with($publicKey, '04'))->toBeTrue();
    });

    it('derives address from public key', function () {
        $privateKey = $this->client->generatePrivateKey();
        $publicKey = $this->client->derivePublicKey($privateKey);
        $address = $this->client->deriveAddress($publicKey);

        expect($address)->toBeString()
            ->and(strlen($address))->toBe(42) // 0x + 20 bytes * 2 (hex)
            ->and(str_starts_with($address, '0x'))->toBeTrue();
    });
});
