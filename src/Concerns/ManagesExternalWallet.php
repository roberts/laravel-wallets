<?php

namespace Roberts\LaravelWallets\Concerns;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Services\WalletService;
use Roberts\LaravelWallets\Wallets\EthWallet;
use Roberts\LaravelWallets\Wallets\SolWallet;

/**
 * Trait for managing external wallet operations.
 *
 * This trait provides functionality for adding external wallets for tracking.
 * External wallets are blockchain addresses without private key control.
 */
trait ManagesExternalWallet
{
    /**
     * Add an external wallet for tracking (no private key).
     * Generic method that routes to protocol-specific methods.
     *
     * @param  array<string, mixed>|null  $metadata
     */
    public static function addExternal(
        string $address,
        ?Authenticatable $user = null,
        ?int $tenantId = null,
        ?array $metadata = null
    ): static {
        $user = $user ?? Auth::user();
        $tenantId = $tenantId ?? static::getCurrentTenantId();
        $metadata = $metadata ?? [];

        if (! $user instanceof Model) {
            throw new \InvalidArgumentException('User must be an Eloquent Model');
        }

        // Determine protocol from address format
        if (str_starts_with($address, '0x') && strlen($address) === 42) {
            return static::addEthereumExternal($address, $user, $tenantId, $metadata);
        } elseif (strlen($address) >= 32 && strlen($address) <= 44) {
            return static::addSolanaExternal($address, $user, $tenantId, $metadata);
        }

        throw new \InvalidArgumentException('Invalid address format. Unable to determine protocol.');
    }

    /**
     * Add an external Ethereum wallet for tracking.
     */
    /**
     * Add an external Ethereum wallet for a specific user and tenant.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function addEthereumExternal(
        string $address,
        \Illuminate\Database\Eloquent\Model $owner,
        int $tenantId,
        array $metadata = []
    ): EthWallet {
        $walletService = app(WalletService::class);
        $result = $walletService->addExternalWallet(
            protocol: Protocol::ETH,
            address: $address,
            owner: $owner,
            tenantId: $tenantId,
            metadata: $metadata
        );

        return EthWallet::createFromWallet($result['wallet'], $result['walletOwner']);
    }

    /**
     * Add an external Solana wallet for tracking.
     *
     * @param  array<string, mixed>  $metadata
     */
    public static function addSolanaExternal(
        string $address,
        \Illuminate\Database\Eloquent\Model $owner,
        int $tenantId,
        array $metadata = []
    ): SolWallet {
        $walletService = app(WalletService::class);
        $result = $walletService->addExternalWallet(
            protocol: Protocol::SOL,
            address: $address,
            owner: $owner,
            tenantId: $tenantId,
            metadata: $metadata
        );

        return SolWallet::createFromWallet($result['wallet'], $result['walletOwner']);
    }

    /**
     * Get current tenant ID from context.
     */
    protected static function getCurrentTenantId(): int
    {
        // Try to get from Laravel single-db tenancy context
        $tenantId = config('tenancy.tenant_id');

        if ($tenantId) {
            return $tenantId;
        }

        // Fallback to default tenant
        return 1;
    }
}
