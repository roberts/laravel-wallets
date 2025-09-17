<?php

namespace Roberts\LaravelWallets\Wallets;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Roberts\LaravelWallets\Contracts\WalletInterface;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Enums\WalletType;
use Roberts\LaravelWallets\Protocols\Solana\Client as SolanaClient;
use Roberts\LaravelWallets\Services\Bip39Service;

class SolWallet implements WalletInterface
{
    public function __construct(
        private string $address,
        private string $publicKey,
        private string $privateKey,
        private ?Authenticatable $owner = null,
    ) {}

    public static function create(?Authenticatable $user = null): self
    {
        $bip39Service = app(Bip39Service::class);
        $solanaClient = app(SolanaClient::class);

        $mnemonic = $bip39Service->generateMnemonic();
        $seed = $bip39Service->mnemonicToSeed($mnemonic);
        $keypair = $solanaClient->generateKeypairFromSeed($seed);
        $address = $solanaClient->getAddressFromPublicKey($keypair['public_key']);

        DB::table('wallets')->insert([
            'protocol' => Protocol::SOL,
            'wallet_type' => WalletType::CUSTODIAL,
            'address' => $address,
            'public_key' => $keypair['public_key'],
            'private_key' => Crypt::encryptString($keypair['private_key']),
            'owner_id' => $user?->getAuthIdentifier(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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

    public function getPrivateKey(): string
    {
        return $this->privateKey;
    }

    public function getOwner(): ?Authenticatable
    {
        return $this->owner;
    }
}
