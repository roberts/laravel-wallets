<?php

namespace Roberts\LaravelWallets\Wallets;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Roberts\LaravelWallets\Contracts\WalletInterface;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Enums\WalletType;
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

    public static function create(?Authenticatable $user = null): self
    {
        $client = new Client;

        $privateKey = $client->generatePrivateKey();
        $publicKey = $client->derivePublicKey($privateKey);
        $address = $client->deriveAddress($publicKey);

        DB::table('wallets')->insert([
            'protocol' => Protocol::ETH,
            'wallet_type' => WalletType::CUSTODIAL,
            'address' => $address,
            'public_key' => $publicKey,
            'private_key' => Crypt::encryptString($privateKey),
            'owner_id' => $user?->getAuthIdentifier(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

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
