<?php

namespace Roberts\LaravelWallets\Wallets;

use Illuminate\Contracts\Auth\Authenticatable;
use Roberts\LaravelWallets\Contracts\WalletInterface;
use Roberts\LaravelWallets\Concerns\ManagesWalletPersistence;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Enums\WalletType;
use Roberts\LaravelWallets\Protocols\Solana\Client as SolanaClient;
use Roberts\LaravelWallets\Services\Bip39Service;

class SolWallet implements WalletInterface
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
}
