<?php

namespace Roberts\LaravelWallets\Concerns;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Services\WalletService;

/**
 * Trait to provide wallet functionality to owner models.
 * This trait is designed for external use by package consumers.
 *
 * Add this trait to any model that can own wallets (e.g., User, Team, etc.)
 * Updated for two-table architecture: wallets (global registry) + wallet_owners (ownership)
 *
 * @phpstan-ignore trait.unused
 */
trait HasWallets
{
    /**
     * Get wallet ownership records for this owner.
     */
    public function walletOwnerships(): HasMany
    {
        return $this->hasMany(WalletOwner::class, 'owner_id')
            ->where('owner_type', static::class);
    }

    /**
     * Get all wallets accessible by this owner through ownership records.
     */
    public function wallets(): HasManyThrough
    {
        return $this->hasManyThrough(
            Wallet::class,
            WalletOwner::class,
            'owner_id', // Foreign key on wallet_owners
            'id', // Foreign key on wallets
            'id', // Local key on this model
            'wallet_id' // Local key on wallet_owners
        )->where('wallet_owners.owner_type', static::class);
    }

    /**
     * Get wallets for a specific tenant.
     */
    public function walletsForTenant(int $tenantId)
    {
        return $this->wallets()->whereHas('owners', function ($query) use ($tenantId) {
            $query->where('tenant_id', $tenantId)
                ->where('owner_id', $this->id)
                ->where('owner_type', static::class);
        });
    }

    /**
     * Get custodial wallets (we control the private keys).
     */
    public function custodialWallets(int $tenantId)
    {
        return $this->walletsForTenant($tenantId)
            ->where('control_type', ControlType::CUSTODIAL);
    }

    /**
     * Get external wallets (imported or watched).
     */
    public function externalWallets(int $tenantId)
    {
        return $this->walletsForTenant($tenantId)
            ->where('control_type', ControlType::EXTERNAL);
    }

    /**
     * Create a new custodial wallet for this owner.
     * Legacy compatibility method that uses new WalletService.
     */
    public function createWallet(Protocol $protocol, ?int $tenantId = null, ?array $metadata = null): array
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        $walletService = app(WalletService::class);

        return $walletService->createCustodialWallet(
            protocol: $protocol,
            owner: $this,
            tenantId: $tenantId,
            metadata: $metadata ?? []
        );
    }

    /**
     * Create a custodial Ethereum wallet (legacy convenience method).
     */
    public function createEthereumWallet(?int $tenantId = null, ?array $metadata = null): array
    {
        return $this->createWallet(Protocol::ETH, $tenantId, $metadata);
    }

    /**
     * Create a custodial Solana wallet (legacy convenience method).
     */
    public function createSolanaWallet(?int $tenantId = null, ?array $metadata = null): array
    {
        return $this->createWallet(Protocol::SOL, $tenantId, $metadata);
    }

    /**
     * Add an external wallet for tracking (no private key).
     */
    public function addExternalWallet(
        Protocol $protocol,
        string $address,
        ?int $tenantId = null,
        ?array $metadata = null
    ): array {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        $walletService = app(WalletService::class);

        return $walletService->addExternalWallet(
            protocol: $protocol,
            address: $address,
            owner: $this,
            tenantId: $tenantId,
            metadata: $metadata
        );
    }

    /**
     * Get wallet ownership record for a specific wallet.
     */
    public function getWalletOwnership(int $walletId, ?int $tenantId = null): ?WalletOwner
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        return $this->walletOwnerships()
            ->where('wallet_id', $walletId)
            ->where('tenant_id', $tenantId)
            ->first();
    }

    /**
     * Check if this owner has access to a specific wallet.
     */
    public function hasWalletAccess(int $walletId, ?int $tenantId = null): bool
    {
        return $this->getWalletOwnership($walletId, $tenantId) !== null;
    }

    /**
     * Get current tenant ID from context.
     */
    protected function getCurrentTenantId(): int
    {
        // Try to get from Laravel single-db tenancy context
        $tenantId = config('tenancy.tenant_id');

        if ($tenantId) {
            return $tenantId;
        }

        // Fallback to default tenant
        return 1;
    }

    /**
     * Legacy method: Get all wallets (backward compatibility).
     * Note: This returns wallet ownership records, not wallet models directly.
     */
    public function getAllWallets(?int $tenantId = null)
    {
        $tenantId = $tenantId ?? $this->getCurrentTenantId();

        return $this->walletOwnerships()
            ->with('wallet')
            ->where('tenant_id', $tenantId)
            ->get();
    }
}
