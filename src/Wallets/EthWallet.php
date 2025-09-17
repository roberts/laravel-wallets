<?php

namespace Roberts\LaravelWallets\Wallets;

use Illuminate\Contracts\Auth\Authenticatable;
use Roberts\LaravelWallets\Contracts\WalletInterface;
use Roberts\LaravelWallets\Concerns\ManagesWalletPersistence;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Enums\WalletType;
use Roberts\LaravelWallets\Protocols\Ethereum\Client;

class EthWallet implements WalletInterface
{
    use ManagesWalletPersistence;

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
}
