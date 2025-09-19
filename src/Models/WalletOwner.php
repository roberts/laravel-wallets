<?php

namespace Roberts\LaravelWallets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Roberts\LaravelWallets\Database\Factories\WalletOwnerFactory;

/**
 * Wallet ownership records - controls who has access to what wallets in which tenants
 *
 * @property int $id
 * @property string $uuid
 * @property int $wallet_id
 * @property int $tenant_id
 * @property int $owner_id
 * @property string $owner_type
 * @property string $encrypted_private_key
 * @property \Illuminate\Support\Carbon $created_at
 * @property \Illuminate\Support\Carbon $updated_at
 * @property Wallet $wallet
 * @property Model $owner
 */
class WalletOwner extends Model
{
    /** @use HasFactory<WalletOwnerFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): WalletOwnerFactory
    {
        return WalletOwnerFactory::new();
    }

    protected $fillable = [
        'wallet_id',
        'tenant_id',
        'owner_id',
        'owner_type',
        'encrypted_private_key',
    ];

    protected $casts = [
        'encrypted_private_key' => 'encrypted',
    ];

    /**
     * Get the wallet this ownership record belongs to
     *
     * @return BelongsTo<Wallet, $this>
     */
    public function wallet(): BelongsTo
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Get the parent owner model (e.g., a User)
     *
     * @return MorphTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Generate a UUID for the wallet owner when creating
     */
    protected static function booted(): void
    {
        static::creating(function (WalletOwner $walletOwner) {
            // Generate UUID if not set
            if (empty($walletOwner->uuid)) {
                $walletOwner->uuid = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    /**
     * Check if this ownership record allows wallet control (has private key)
     */
    public function hasControl(): bool
    {
        return ! empty($this->encrypted_private_key);
    }

    /**
     * Get decrypted private key
     */
    public function getPrivateKey(): ?string
    {
        return $this->encrypted_private_key;
    }

    /**
     * Basic tenant method for testing compatibility
     *
     * @return object{id: int|null}
     */
    public function tenant(): object
    {
        // In a real implementation, this would return the tenant relationship
        // For now, we'll just return a simple object for testing
        return (object) ['id' => $this->tenant_id];
    }

    /**
     * Scope to filter by tenant (for testing compatibility)
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WalletOwner>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WalletOwner>
     */
    public function scopeForTenant(\Illuminate\Database\Eloquent\Builder $query, ?int $tenantId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('tenant_id', $tenantId);
    }
}
