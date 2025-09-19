<?php

namespace Roberts\LaravelWallets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Roberts\LaravelWallets\Database\Factories\WalletFactory;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;

/**
 * Global wallet registry - one record per blockchain address
 *
 * @property int $id
 * @property string $uuid
 * @property Protocol $protocol
 * @property string $address
 * @property ControlType $control_type
 * @property array<string, mixed>|null $metadata
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 */
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WalletFactory
    {
        return WalletFactory::new();
    }

    protected $fillable = [
        'protocol',
        'address',
        'control_type',
        'metadata',
    ];

    protected $casts = [
        'protocol' => Protocol::class,
        'control_type' => ControlType::class,
        'metadata' => 'array',
    ];

    /**
     * Get all ownership records for this wallet
     *
     * @return HasMany<WalletOwner, $this>
     */
    public function owners(): HasMany
    {
        return $this->hasMany(WalletOwner::class);
    }

    /**
     * Get ownership records for a specific tenant
     *
     * @return HasMany<WalletOwner, $this>
     */
    public function ownersForTenant(int $tenantId): HasMany
    {
        return $this->hasMany(WalletOwner::class)->where('tenant_id', $tenantId);
    }

    /**
     * Get ownership record for a specific owner in a specific tenant
     */
    public function ownershipFor(\Illuminate\Database\Eloquent\Model $owner, int $tenantId): ?WalletOwner
    {
        /** @var WalletOwner|null */
        return $this->owners()
            ->where('tenant_id', $tenantId)
            ->where('owner_id', $owner->getKey())
            ->where('owner_type', get_class($owner))
            ->first();
    }

    /**
     * Check if an owner has access to this wallet in a specific tenant
     */
    public function hasOwner(\Illuminate\Database\Eloquent\Model $owner, int $tenantId): bool
    {
        return $this->ownershipFor($owner, $tenantId) !== null;
    }

    /**
     * Generate a UUID for the wallet when creating
     */
    protected static function booted(): void
    {
        static::creating(function (Wallet $wallet) {
            // Generate UUID if not set
            if (empty($wallet->uuid)) {
                $wallet->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }
}
