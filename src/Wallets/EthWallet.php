<?php

namespace Roberts\LaravelWallets\Wallets;

use Roberts\LaravelWallets\Contracts\WalletInterface;
use Roberts\LaravelWallets\Protocols\Ethereum\Client;

class EthWallet implements WalletInterface
{
    public string $address;

    public string $publicKey;

    private string $privateKey;

    public function __construct(string $address, string $publicKey, string $privateKey)
    {
        $this->address = $address;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
    }

    public static function create(): self
    {
        $client = new Client;

        $privateKey = $client->generatePrivateKey();
        $publicKey = $client->derivePublicKey($privateKey);
        $address = $client->deriveAddress($publicKey);

        return new self($address, $publicKey, $privateKey);
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }
}
