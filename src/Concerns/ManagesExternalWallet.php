<?php

namespace Roberts\LaravelWallets\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\DB;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Enums\WalletType;

trait ManagesExternalWallet
{
    use ManagesWalletPersistence;

    /**
     * Add an external wallet by address.
     */
    public static function addExternal(string $address, ?Authenticatable $user = null): static
    {
        // 1. Validate address format for this protocol
        static::validateAddressFormat($address);

        // 2. Attempt to derive public key (if possible for the protocol)
        $publicKey = static::derivePublicKeyFromAddress($address) ?? '';

        // 3. Get the protocol for this wallet class
        $protocol = static::getProtocol();

        // 4. Use firstOrCreate pattern
        $walletData = static::firstOrCreateExternalWallet($protocol, $address, $publicKey, $user);

        // 5. Return wallet instance
        /** @phpstan-ignore new.static */
        return new static(
            $walletData['address'],
            $walletData['public_key'],
            '', // No private key for external wallets
            $user
        );
    }

    /**
     * Create or find existing external wallet in database.
     * 
     * @return array{address: string, public_key: string}
     */
    protected static function firstOrCreateExternalWallet(Protocol $protocol, string $address, string $publicKey, ?Authenticatable $user): array
    {
        /** @var \stdClass|null $existingRecord */
        $existingRecord = DB::table('wallets')
            ->where('protocol', $protocol)
            ->where('address', $address)
            ->where('owner_id', $user?->getAuthIdentifier())
            ->first();

        if ($existingRecord) {
            return [
                'address' => (string) $existingRecord->address,
                'public_key' => (string) $existingRecord->public_key,
            ];
        }

        DB::table('wallets')->insert([
            'protocol' => $protocol,
            'address' => $address,
            'wallet_type' => WalletType::EXTERNAL,
            'public_key' => $publicKey,
            'private_key' => null, // No private key for external wallets
            'owner_id' => $user?->getAuthIdentifier(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return [
            'address' => $address,
            'public_key' => $publicKey,
        ];
    }

    /**
     * Validate the address format for this specific protocol.
     * Must be implemented by each wallet class.
     */
    abstract protected static function validateAddressFormat(string $address): void;

    /**
     * Derive public key from address if possible for this protocol.
     * Returns null if public key cannot be derived from address.
     * Must be implemented by each wallet class.
     */
    abstract protected static function derivePublicKeyFromAddress(string $address): ?string;

    /**
     * Get the protocol enum for this wallet class.
     * Must be implemented by each wallet class.
     */
    abstract protected static function getProtocol(): Protocol;
}
