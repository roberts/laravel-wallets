<?php

namespace Roberts\LaravelWallets\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Roberts\LaravelWallets\Database\Factories\EthChainFactory;

/**
 * @property int $id
 * @property string $name
 * @property string $abbreviation
 * @property int $chain_id
 * @property string $rpc
 * @property string|null $scanner
 * @property bool $supports_eip1559
 * @property string $native_symbol
 * @property int $native_decimals
 * @property array<string>|null $rpc_alternates
 * @property bool $is_active
 * @property bool $is_default
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 */
class EthChain extends Model
{
    /** @use HasFactory<\Roberts\LaravelWallets\Database\Factories\EthChainFactory> */
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): EthChainFactory
    {
        return EthChainFactory::new();
    }

    protected $fillable = [
        'name',
        'abbreviation',
        'chain_id',
        'rpc',
        'scanner',
        'supports_eip1559',
        'native_symbol',
        'native_decimals',
        'rpc_alternates',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'chain_id' => 'integer',
        'supports_eip1559' => 'boolean',
        'native_decimals' => 'integer',
        'rpc_alternates' => 'array',
        'is_active' => 'boolean',
        'is_default' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the default chain.
     */
    public static function default(): ?self
    {
        return static::where('is_default', true)->first();
    }

    /**
     * Get all active chains.
     * 
     * @return \Illuminate\Database\Eloquent\Collection<int, EthChain>
     */
    public static function active(): \Illuminate\Database\Eloquent\Collection
    {
        return static::where('is_active', true)->get();
    }

    /**
     * Get chain by chain ID.
     */
    public static function byChainId(int $chainId): ?self
    {
        return static::where('chain_id', $chainId)->first();
    }

    /**
     * Get the primary RPC URL.
     */
    public function getPrimaryRpc(): string
    {
        return $this->rpc;
    }

    /**
     * Get all available RPC URLs (primary + alternates).
     * 
     * @return array<string>
     */
    public function getAllRpcs(): array
    {
        $rpcs = [$this->rpc];
        
        if ($this->rpc_alternates) {
            $rpcs = array_merge($rpcs, $this->rpc_alternates);
        }
        
        return array_unique($rpcs);
    }

    /**
     * Check if the chain supports EIP-1559 (type 2) transactions.
     */
    public function supportsEip1559(): bool
    {
        return $this->supports_eip1559;
    }

    /**
     * Get the native token symbol.
     */
    public function getNativeSymbol(): string
    {
        return $this->native_symbol;
    }

    /**
     * Get the native token decimals.
     */
    public function getNativeDecimals(): int
    {
        return $this->native_decimals;
    }

    /**
     * Get the block explorer URL.
     */
    public function getExplorerUrl(): ?string
    {
        return $this->scanner;
    }

    /**
     * Get transaction URL on block explorer.
     */
    public function getTransactionUrl(string $txHash): ?string
    {
        return $this->scanner ? $this->scanner . '/tx/' . $txHash : null;
    }

    /**
     * Get address URL on block explorer.
     */
    public function getAddressUrl(string $address): ?string
    {
        return $this->scanner ? $this->scanner . '/address/' . $address : null;
    }

    /**
     * Check if the chain is active.
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * Check if the chain is the default chain.
     */
    public function isDefault(): bool
    {
        return $this->is_default;
    }
}
