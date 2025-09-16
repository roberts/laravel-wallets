<?php

use Roberts\LaravelWallets\Protocols\Solana\Client;
use Roberts\LaravelWallets\Services\Base58Service;
use Roberts\LaravelWallets\Services\Bip39Service;

describe('SolanaClient', function () {
    beforeEach(function () {
        $this->base58Service = new Base58Service;
        $this->bip39Service = new Bip39Service;
        $this->client = new Client($this->base58Service);
    });

    it('generates keypair from seed', function () {
        $mnemonic = 'abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon abandon about';
        $seed = $this->bip39Service->mnemonicToSeed($mnemonic);

        $keypair = $this->client->generateKeypairFromSeed($seed);

        expect($keypair)->toBeArray()
            ->toHaveKeys(['public_key', 'private_key'])
            ->and(strlen($keypair['public_key']))->toBe(32)
            ->and(strlen($keypair['private_key']))->toBe(64);
    });

    it('derives address from public key', function () {
        $publicKey = random_bytes(32);
        $expectedAddress = $this->base58Service->encode($publicKey);

        $address = $this->client->getAddressFromPublicKey($publicKey);

        expect($address)->toBe($expectedAddress);
    });
});
