<?php

namespace Roberts\LaravelWallets\Wallets;

use Illuminate\Contracts\Auth\Authenticatable;
use Roberts\LaravelWallets\Contracts\WalletInterface;
use Roberts\LaravelWallets\Concerns\ManagesWalletPersistence;
use Roberts\LaravelWallets\Concerns\ManagesExternalWallet;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Enums\WalletType;
use Roberts\LaravelWallets\Protocols\Solana\Client as SolanaClient;
use Roberts\LaravelWallets\Services\Bip39Service;
use Roberts\LaravelWallets\Services\Base58Service;

class SolWallet implements WalletInterface
{
    use ManagesWalletPersistence;
    use ManagesExternalWallet;

    public string $address;

    public string $publicKey;

    /**
     * @phpstan-ignore property.onlyWritten
     */
    private string $privateKey;

    private ?Authenticatable $owner;

    public function __construct(string $address, string $publicKey, string $privateKey, ?Authenticatable $owner = null)
    {
        $this->address = $address;
        $this->publicKey = $publicKey;
        $this->privateKey = $privateKey;
        $this->owner = $owner;
    }

    public static function create(?Authenticatable $user = null): self
    {
        $bip39Service = app(Bip39Service::class);
        $solanaClient = app(SolanaClient::class);

        $mnemonic = $bip39Service->generateMnemonic();
        $seed = $bip39Service->mnemonicToSeed($mnemonic);
        $keypair = $solanaClient->generateKeypairFromSeed($seed);
        $address = $solanaClient->getAddressFromPublicKey($keypair['public_key']);

        static::persist(
            Protocol::SOL,
            WalletType::CUSTODIAL,
            $address,
            $keypair['public_key'],
            $keypair['private_key'],
            $user
        );

        return new self(
            address: $address,
            publicKey: $keypair['public_key'],
            privateKey: $keypair['private_key'],
            owner: $user,
        );
    }

    public function getAddress(): string
    {
        return $this->address;
    }

    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    public function getOwner(): ?Authenticatable
    {
        return $this->owner;
    }

    /**
     * Validate Solana address format.
     */
    protected static function validateAddressFormat(string $address): void
    {
        if (!extension_loaded('sodium')) {
            throw new \RuntimeException('The sodium PHP extension is required for Solana address validation.');
        }

        $base58Service = app(Base58Service::class);

        try {
            $decoded = $base58Service->decode($address);
        } catch (\InvalidArgumentException $e) {
            throw new \InvalidArgumentException('Invalid Solana address format: ' . $e->getMessage());
        }

        // Solana public keys are exactly 32 bytes
        if (strlen($decoded) !== 32) {
            throw new \InvalidArgumentException('Invalid Solana address: must be 32 bytes when decoded');
        }

        // Additional validation: ensure it's a valid Ed25519 point
        if (!static::isValidEd25519Point($decoded)) {
            throw new \InvalidArgumentException('Invalid Solana address: not a valid Ed25519 point');
        }
    }

    /**
     * For Solana, the address IS the public key (Base58 encoded).
     */
    protected static function derivePublicKeyFromAddress(string $address): ?string
    {
        $base58Service = app(Base58Service::class);
        return $base58Service->decode($address);
    }

    /**
     * Get the protocol for Solana wallets.
     */
    protected static function getProtocol(): Protocol
    {
        return Protocol::SOL;
    }

    /**
     * Validate that the decoded bytes represent a valid Ed25519 point.
     */
    protected static function isValidEd25519Point(string $bytes): bool
    {
        try {
            // Simple validation: check that it's 32 bytes and doesn't contain all zeros
            if (strlen($bytes) !== 32) {
                return false;
            }
            
            // Check that it's not all zeros (invalid public key)
            if ($bytes === str_repeat("\0", 32)) {
                return false;
            }
            
            // For now, we'll accept any 32-byte value that's not all zeros
            // More sophisticated Ed25519 curve validation could be added here
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
