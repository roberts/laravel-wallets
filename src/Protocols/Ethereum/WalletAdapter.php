<?php

namespace Roberts\LaravelWallets\Protocols\Ethereum;

use Roberts\LaravelWallets\Contracts\WalletAdapterInterface;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Security\SecureWalletData;

class WalletAdapter implements WalletAdapterInterface
{
    public function __construct(
        protected Client $client
    ) {}

    /**
     * Generate a new Ethereum wallet using secure practices.
     */
    public function createWallet(): SecureWalletData
    {
        $privateKey = $this->client->generatePrivateKey();
        $publicKey = $this->client->derivePublicKey($privateKey);
        $address = $this->client->deriveAddress($publicKey);

        return new SecureWalletData($address, $privateKey);
    }

    public function validateAddress(string $address): bool
    {
        // Ethereum addresses are 42 characters long, start with 0x, followed by 40 hex characters
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
    }

    public function validatePrivateKey(string $privateKey): bool
    {
        // Remove 0x prefix if present
        $key = str_starts_with($privateKey, '0x') ? substr($privateKey, 2) : $privateKey;

        // Ethereum private keys are 64 hex characters (256 bits)
        if (strlen($key) !== 64) {
            return false;
        }

        // Must be valid hex
        if (! ctype_xdigit($key)) {
            return false;
        }

        // Must not be zero (invalid private key)
        if ($key === str_repeat('0', 64)) {
            return false;
        }

        // Additional validation through the client
        try {
            $this->client->derivePublicKey($privateKey);

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getProtocol(): string
    {
        return Protocol::ETH->value;
    }

    public function deriveAddress(string $privateKey): string
    {
        if (! $this->validatePrivateKey($privateKey)) {
            throw new \InvalidArgumentException('Invalid private key provided');
        }

        $publicKey = $this->client->derivePublicKey($privateKey);

        return $this->client->deriveAddress($publicKey);
    }
}
