<?php

namespace Roberts\LaravelWallets\Wallets;

use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Services\Base58Service;

class SolWallet extends BaseWallet
{
    /**
     * Get the protocol for this wallet type.
     */
    public function getProtocol(): Protocol
    {
        return Protocol::SOL;
    }

    /**
     * Get the protocol for this wallet type (static version).
     */
    protected static function getStaticProtocol(): Protocol
    {
        return Protocol::SOL;
    }

    /**
     * Create a Solana wallet instance from existing wallet and ownership models.
     */
    public static function createFromWallet(Wallet $wallet, ?WalletOwner $walletOwner = null): static
    {
        return new self($wallet, $walletOwner);
    }

    /**
     * Get public key.
     * For Solana, the address IS the public key (Base58 encoded).
     */
    public function getPublicKey(): string
    {
        return $this->wallet->address;
    }

    /**
     * Validate Solana address format.
     */
    public static function validateAddressFormat(string $address): void
    {
        if (! extension_loaded('sodium')) {
            throw new \RuntimeException('The sodium PHP extension is required for Solana address validation.');
        }

        $base58Service = app(Base58Service::class);

        try {
            $decoded = $base58Service->decode($address);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Invalid Solana address format: '.$e->getMessage());
        }

        // Solana public keys are exactly 32 bytes
        if (strlen($decoded) !== 32) {
            throw new \InvalidArgumentException('Invalid Solana address: must be 32 bytes when decoded');
        }

        // Additional validation: ensure it's a valid Base58 string
        if (! preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address)) {
            throw new \InvalidArgumentException('Invalid Solana address: invalid Base58 format');
        }
    }

    /**
     * Get cluster/network info from metadata.
     */
    public function getCluster(): string
    {
        $metadata = $this->getMetadata();

        return $metadata['cluster'] ?? 'mainnet-beta';
    }

    /**
     * Check if this is a mainnet wallet.
     */
    public function isMainnet(): bool
    {
        return $this->getCluster() === 'mainnet-beta';
    }

    /**
     * Check if this is a testnet wallet.
     */
    public function isTestnet(): bool
    {
        $cluster = $this->getCluster();

        return in_array($cluster, ['testnet', 'devnet']);
    }

    /**
     * Check if this is a devnet wallet.
     */
    public function isDevnet(): bool
    {
        return $this->getCluster() === 'devnet';
    }

    /**
     * Get network name based on cluster.
     */
    public function getNetworkName(): string
    {
        return match ($this->getCluster()) {
            'mainnet-beta' => 'Solana Mainnet',
            'testnet' => 'Solana Testnet',
            'devnet' => 'Solana Devnet',
            default => 'Unknown Solana Network',
        };
    }

    /**
     * Decode the Base58 address to raw bytes.
     */
    public function getAddressBytes(): string
    {
        $base58Service = app(Base58Service::class);

        return $base58Service->decode($this->wallet->address);
    }

    /**
     * Validate a Solana address.
     */
    public static function validateAddress(string $address): bool
    {
        try {
            static::validateAddressFormat($address);

            return true;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Validate a Solana private key.
     */
    public static function validatePrivateKey(string $privateKey): bool
    {
        if (! extension_loaded('sodium')) {
            return false;
        }

        $base58Service = app(Base58Service::class);

        try {
            $decoded = $base58Service->decode($privateKey);

            // Solana private keys are 64 bytes (32-byte secret key + 32-byte public key)
            return strlen($decoded) === 64;
        } catch (\InvalidArgumentException) {
            return false;
        }
    }

    /**
     * Generate a new Solana wallet address and private key pair.
     */
    public static function generateKeyPair(): array
    {
        if (! extension_loaded('sodium')) {
            throw new \RuntimeException('The sodium PHP extension is required for Solana key generation.');
        }

        // Generate Ed25519 keypair
        $keyPair = sodium_crypto_sign_keypair();
        $privateKeyRaw = sodium_crypto_sign_secretkey($keyPair);
        $publicKeyRaw = sodium_crypto_sign_publickey($keyPair);

        $base58Service = app(Base58Service::class);
        $privateKey = $base58Service->encode($privateKeyRaw);
        $publicKey = $base58Service->encode($publicKeyRaw);

        return [
            'address' => $publicKey, // In Solana, the address IS the public key
            'privateKey' => $privateKey,
            'publicKey' => $publicKey,
        ];
    }

    /**
     * Get the public key from a private key.
     */
    public static function getPublicKeyFromPrivate(string $privateKey): string
    {
        if (! extension_loaded('sodium')) {
            throw new \RuntimeException('The sodium PHP extension is required for Solana key derivation.');
        }

        $base58Service = app(Base58Service::class);
        $privateKeyRaw = $base58Service->decode($privateKey);

        // Extract the public key from the 64-byte private key
        $publicKeyRaw = substr($privateKeyRaw, 32);

        return $base58Service->encode($publicKeyRaw);
    }

    /**
     * Get the address from a private key.
     */
    public static function getAddressFromPrivate(string $privateKey): string
    {
        // In Solana, the address IS the public key
        return static::getPublicKeyFromPrivate($privateKey);
    }

    /**
     * Check if this wallet implementation supports the given protocol.
     */
    public static function supportsProtocol(Protocol $protocol): bool
    {
        return $protocol === Protocol::SOL;
    }
}
