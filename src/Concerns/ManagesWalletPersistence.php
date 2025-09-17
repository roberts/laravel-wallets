<?php

namespace Roberts\LaravelWallets\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Enums\WalletType;

trait ManagesWalletPersistence
{
    protected static function persist(Protocol $protocol, WalletType $walletType, string $address, string $publicKey, string $privateKey, ?Authenticatable $user): void
    {
        DB::table('wallets')->insert([
            'protocol' => $protocol,
            'wallet_type' => $walletType,
            'address' => $address,
            'public_key' => $publicKey,
            'private_key' => Crypt::encryptString($privateKey),
            'owner_id' => $user?->getAuthIdentifier(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
