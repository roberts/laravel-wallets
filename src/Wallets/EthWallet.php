<?php

namespace Roberts\LaravelWallets\Wallets;

use Illuminate\Contracts\Auth\Authenticatable;
use Roberts\LaravelWallets\Contracts\WalletInterface;
use Roberts\LaravelWallets\Concerns\ManagesWalletPersistence;
use Roberts\LaravelWallets\Concerns\ManagesExternalWallet;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Enums\WalletType;
use Roberts\LaravelWallets\Protocols\Ethereum\Client;
use Roberts\LaravelWallets\Services\KeccakService;

class EthWallet implements WalletInterface
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
        $client = new Client;

        $privateKey = $client->generatePrivateKey();
        $publicKey = $client->derivePublicKey($privateKey);
        $address = $client->deriveAddress($publicKey);

        static::persist(Protocol::ETH, WalletType::CUSTODIAL, $address, $publicKey, $privateKey, $user);

        return new self($address, $publicKey, $privateKey, $user);
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
     * Validate Ethereum address format.
     */
    protected static function validateAddressFormat(string $address): void
    {
        // Check if it's 42 characters (0x + 40 hex chars)
        if (!preg_match('/^0x[a-fA-F0-9]{40}$/', $address)) {
            throw new \InvalidArgumentException('Invalid Ethereum address format');
        }

        // Validate EIP-55 checksum if present (mixed case indicates checksum)
        if (static::hasChecksum($address) && !static::isValidChecksum($address)) {
            throw new \InvalidArgumentException('Invalid Ethereum address checksum');
        }
    }

    /**
     * For Ethereum, public key cannot be derived from address.
     */
    protected static function derivePublicKeyFromAddress(string $address): ?string
    {
        return null;
    }

    /**
     * Get the protocol for Ethereum wallets.
     */
    protected static function getProtocol(): Protocol
    {
        return Protocol::ETH;
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
}
