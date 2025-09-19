<?php

namespace Roberts\LaravelWallets\Wallets;

use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Services\KeccakService;

class EthWallet extends BaseWallet
{
    /**
     * Get the protocol for this wallet type.
     */
    public function getProtocol(): Protocol
    {
        return Protocol::ETH;
    }

    /**
     * Get the protocol for this wallet type (static version).
     */
    protected static function getStaticProtocol(): Protocol
    {
        return Protocol::ETH;
    }

    /**
     * Create an Ethereum wallet instance from existing wallet and ownership models.
     */
    public static function createFromWallet(Wallet $wallet, ?WalletOwner $walletOwner = null): static
    {
        return new self($wallet, $walletOwner);
    }

    /**
     * Get public key (if available).
     * For external wallets, we can't derive public key from address.
     */
    public function getPublicKey(): string
    {
        // For Ethereum, public keys cannot be derived from addresses
        // This would need to be stored separately if needed
        return '';
    }

    /**
     * Validate Ethereum address format.
     */
    public static function validateAddressFormat(string $address): void
    {
        // Check if it's 42 characters (0x + 40 hex chars)
        if (! preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            throw new \InvalidArgumentException('Invalid Ethereum address format');
        }

        // Validate EIP-55 checksum if present (mixed case indicates checksum)
        if (static::hasChecksum($address) && ! static::isValidChecksum($address)) {
            throw new \InvalidArgumentException('Invalid Ethereum address checksum');
        }
    }

    /**
     * Check if address has mixed case (indicating checksum).
     */
    protected static function hasChecksum(string $address): bool
    {
        $hex = substr($address, 2); // Remove 0x prefix

        return $hex !== strtolower($hex) && $hex !== strtoupper($hex);
    }

    /**
     * Validate EIP-55 checksum for Ethereum address.
     */
    protected static function isValidChecksum(string $address): bool
    {
        $address = substr($address, 2); // Remove 0x prefix
        $keccakService = app(KeccakService::class);
        $hash = $keccakService->hash(strtolower($address));

        for ($i = 0; $i < 40; $i++) {
            $char = $address[$i];
            $hashChar = $hash[$i];

            if (ctype_alpha($char)) {
                if ((hexdec($hashChar) >= 8 && ctype_lower($char)) ||
                    (hexdec($hashChar) < 8 && ctype_upper($char))) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Validate an Ethereum address.
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
     * Validate an Ethereum private key.
     */
    public static function validatePrivateKey(string $privateKey): bool
    {
        // Remove 0x prefix if present
        $privateKey = str_starts_with($privateKey, '0x') ? substr($privateKey, 2) : $privateKey;

        // Check if it's 64 hex characters
        return preg_match('/^[a-fA-F0-9]{64}$/', $privateKey) === 1;
    }

    /**
     * Generate a new Ethereum wallet address and private key pair.
     */
    public static function generateKeyPair(): array
    {
        // Generate a random 32-byte private key
        $privateKey = bin2hex(random_bytes(32));
        $publicKey = static::getPublicKeyFromPrivate($privateKey);
        $address = static::getAddressFromPrivate($privateKey);

        return [
            'address' => $address,
            'privateKey' => '0x'.$privateKey,
            'publicKey' => $publicKey,
        ];
    }

    /**
     * Get the public key from a private key.
     */
    public static function getPublicKeyFromPrivate(string $privateKey): string
    {
        // Remove 0x prefix if present
        $privateKey = str_starts_with($privateKey, '0x') ? substr($privateKey, 2) : $privateKey;

        // This is a simplified version - in a real implementation,
        // you would use secp256k1 to derive the public key
        // For now, return empty string as placeholder
        return '';
    }

    /**
     * Get the address from a private key.
     */
    public static function getAddressFromPrivate(string $privateKey): string
    {
        // Remove 0x prefix if present
        $privateKey = str_starts_with($privateKey, '0x') ? substr($privateKey, 2) : $privateKey;

        // For testing purposes, create a deterministic address based on the private key
        // This is NOT a real secp256k1 implementation - just for testing
        $hash = hash('sha256', $privateKey);
        $addressBytes = substr($hash, 0, 40); // Take first 20 bytes (40 hex chars)

        return '0x'.$addressBytes;
    }

    /**
     * Check if this wallet implementation supports the given protocol.
     */
    public static function supportsProtocol(Protocol $protocol): bool
    {
        return $protocol === Protocol::ETH;
    }
}
