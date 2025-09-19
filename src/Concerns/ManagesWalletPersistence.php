<?php

namespace Roberts\LaravelWallets\Concerns;

use Illuminate\Database\Eloquent\Model;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Services\WalletService;

trait ManagesWalletPersistence
{
    /**
     * Create custodial wallet using the two-table architecture.
     */
    /**
     * Persist a custodial wallet to the database.
     *
     * @param  array<string, mixed>  $metadata
     * @return array<string, mixed>
     */
    protected function persistCustodial(
        Protocol $protocol,
        string $address,
        string $privateKey,
        \Illuminate\Database\Eloquent\Model $owner,
        int $tenantId,
        array $metadata = []
    ): array {
        $walletService = app(WalletService::class);

        return $walletService->createCustodialWallet(
            protocol: $protocol,
            owner: $owner,
            tenantId: $tenantId,
            metadata: $metadata,
            privateKey: $privateKey,
            address: $address
        );
    }

    /**
     * Create external wallet using the two-table architecture.
     *
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>
     */
    protected static function persistExternal(
        Protocol $protocol,
        string $address,
        Model $owner,
        int $tenantId,
        ?string $privateKey = null,
        ?array $metadata = null
    ): array {
        $walletService = app(WalletService::class);

        // External wallet functionality has been simplified to bulk address addition only
        throw new \InvalidArgumentException('External wallet import functionality has been removed. Use WalletService::addExternalWalletsFromSnapshot for bulk address addition.');
    }

    /**
     * Get wallet by address and protocol from the global registry.
     */
    protected static function findWalletByAddress(Protocol $protocol, string $address): ?Wallet
    {
        return Wallet::where('protocol', $protocol)
            ->where('address', $address)
            ->first();
    }

    /**
     * Get wallet ownership for a specific owner in a tenant.
     */
    protected static function findOwnership(int $walletId, Model $owner, int $tenantId): ?WalletOwner
    {
        /** @var mixed $ownerId */
        $ownerId = $owner->getKey();

        return WalletOwner::where('wallet_id', $walletId)
            ->where('owner_id', $ownerId)
            ->where('owner_type', get_class($owner))
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Get all wallets owned by a specific owner in a tenant.
     *
     * @return \Illuminate\Support\Collection<int, WalletOwner>
     */
    protected static function getWalletsForOwner(Model $owner, int $tenantId, ?Protocol $protocol = null): \Illuminate\Support\Collection
    {
        /** @var mixed $ownerId */
        $ownerId = $owner->getKey();

        $query = WalletOwner::with('wallet')
            ->where('owner_id', $ownerId)
            ->where('owner_type', get_class($owner))
            ->where('tenant_id', $tenantId);

        if ($protocol) {
            $query->whereHas('wallet', function ($q) use ($protocol) {
                $q->where('protocol', $protocol);
            });
        }

        return $query->get()->pluck('wallet');
    }

    /**
     * Delete ownership record (for stopping wallet watching).
     */
    protected static function deleteOwnership(int $walletId, Model $owner, int $tenantId): bool
    {
        $ownership = static::findOwnership($walletId, $owner, $tenantId);

        return $ownership ? (bool) $ownership->delete() : false;
    }

    /**
     * Update wallet metadata.
     *
     * @param  array<string, mixed>  $metadata
     */
    protected static function updateWalletMetadata(int $walletId, array $metadata): bool
    {
        $wallet = Wallet::find($walletId);

        if (! $wallet) {
            return false;
        }

        return $wallet->update(['metadata' => $metadata]);
    }
}
