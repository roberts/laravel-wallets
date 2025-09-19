<?php

namespace Roberts\LaravelWallets\Wallets;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Roberts\LaravelWallets\Concerns\ManagesExternalWallet;
use Roberts\LaravelWallets\Concerns\ManagesWalletPersistence;
use Roberts\LaravelWallets\Contracts\WalletInterface;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Services\WalletService;

abstract class BaseWallet implements WalletInterface
{
    use ManagesExternalWallet;
    use ManagesWalletPersistence;

    protected Wallet $wallet;

    protected ?WalletOwner $walletOwner;

    protected ?Authenticatable $mockOwner = null;

    public function __construct(
        Wallet $wallet,
        ?WalletOwner $walletOwner = null,
        ?Authenticatable $mockOwner = null
    ) {
        if ($wallet->protocol !== $this->getProtocol()) {
            throw new \InvalidArgumentException(sprintf('Wallet must be %s protocol', $this->getProtocol()->value));
        }

        $this->wallet = $wallet;
        $this->walletOwner = $walletOwner;
        $this->mockOwner = $mockOwner;
    }

    /**
     * Get the protocol for this wallet type.
     */
    public function getProtocol(): Protocol
    {
        return $this->wallet->protocol;
    }

    /**
     * Get the protocol for this wallet type (static version).
     */
    abstract protected static function getStaticProtocol(): Protocol;

    /**
     * Create a wallet instance from existing wallet and ownership models.
     * This is an abstract method that concrete classes must implement.
     */
    abstract public static function createFromWallet(Wallet $wallet, ?WalletOwner $walletOwner = null): static;

    /**
     * Add an external wallet for tracking/watching (no private key).
     *
     * @param  array<string, mixed>|null  $metadata
     * @return array<string, mixed>
     */
    public static function addExternal(
        Protocol $protocol,
        string $address,
        Model $owner,
        int $tenantId,
        ?array $metadata = null
    ): array {
        $walletService = app(WalletService::class);

        return $walletService->addExternalWallet(
            protocol: $protocol,
            address: $address,
            owner: $owner,
            tenantId: $tenantId,
            metadata: $metadata
        );
    }

    /**
     * Create a custodial wallet with generated keys.
     */
    public static function createCustodial(
        Protocol $protocol,
        Model $owner,
        int $tenantId,
        ?array $metadata = null
    ): array {
        $walletService = app(WalletService::class);

        return $walletService->createCustodialWallet(
            protocol: $protocol,
            owner: $owner,
            tenantId: $tenantId,
            metadata: $metadata
        );
    }

    /**
     * Find existing wallet by address.
     */
    public static function findByAddress(string $address, ?Authenticatable $user = null, ?int $tenantId = null): ?static
    {
        $protocol = static::getStaticProtocol();
        $tenantId = $tenantId ?? static::getCurrentTenantId();

        $wallet = Wallet::where('protocol', $protocol)
            ->where('address', $address)
            ->first();

        if (! $wallet) {
            return null;
        }

        $walletOwner = null;
        if ($user instanceof Model) {
            $walletOwner = $wallet->ownershipFor($user, $tenantId);
        }

        /** @phpstan-ignore new.static */
        return new static($wallet, $walletOwner, $user);
    }

    /**
     * Get current tenant ID from context.
     */
    protected static function getCurrentTenantId(): int
    {
        $tenantId = config('tenancy.tenant_id');

        return $tenantId ?: 1; // Default to 1 if no tenant context
    }

    /**
     * Get wallet address.
     */
    public function getAddress(): string
    {
        return $this->wallet->address;
    }

    /**
     * Get control type.
     */
    public function getControlType(): ControlType
    {
        return $this->wallet->control_type;
    }

    /**
     * Get wallet metadata.
     */
    public function getMetadata(): array
    {
        return $this->wallet->metadata ?? [];
    }

    /**
     * Check if this wallet has control (private key access).
     */
    public function hasControl(): bool
    {
        return $this->walletOwner?->hasControl() ?? false;
    }

    /**
     * Get private key (if available).
     * Throws exception if no access to private key.
     */
    public function getPrivateKey(): string
    {
        $privateKey = $this->walletOwner?->getPrivateKey();

        if (! $privateKey) {
            throw new \RuntimeException('No private key access for this wallet');
        }

        return $privateKey;
    }

    /**
     * Get wallet owner.
     */
    public function getOwner(): ?Authenticatable
    {
        return $this->mockOwner ?? $this->walletOwner?->owner;
    }

    /**
     * Get the underlying Wallet model.
     */
    public function getWalletModel(): Wallet
    {
        return $this->wallet;
    }

    /**
     * Get the underlying WalletOwner model.
     */
    public function getWalletOwnerModel(): ?WalletOwner
    {
        return $this->walletOwner;
    }

    /**
     * Update wallet metadata.
     */
    public function updateMetadata(array $metadata): bool
    {
        try {
            $this->wallet->metadata = array_merge($this->wallet->metadata ?? [], $metadata);

            return $this->wallet->save();
        } catch (\Exception) {
            return false;
        }
    }

    /**
     * Legacy create method for backward compatibility.
     */
    public static function create(?Authenticatable $user = null, ?int $tenantId = null, ?array $metadata = null): static
    {
        if (! $user instanceof Model) {
            throw new \InvalidArgumentException('User must be an Eloquent model for wallet ownership');
        }

        $tenantId = $tenantId ?? static::getCurrentTenantId();
        $protocol = static::getStaticProtocol();

        $result = static::createCustodial($protocol, $user, $tenantId, $metadata);

        /** @phpstan-ignore new.static */
        return new static($result['wallet'], $result['walletOwner'], $user);
    }
}
