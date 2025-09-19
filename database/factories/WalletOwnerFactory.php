<?php

namespace Roberts\LaravelWallets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Crypt;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Roberts\LaravelWallets\Models\WalletOwner>
 */
class WalletOwnerFactory extends Factory
{
    protected $model = WalletOwner::class;

    public function definition(): array
    {
        return [
            'wallet_id' => Wallet::factory(),
            'tenant_id' => 1,
            'owner_id' => 1, // Default to user ID 1
            'owner_type' => 'App\\Models\\User', // Default to standard Laravel User model
            'encrypted_private_key' => Crypt::encrypt($this->generatePrivateKey()),
        ];
    }

    /**
     * Create a wallet owner for a specific tenant.
     */
    public function forTenant(int $tenantId): static
    {
        return $this->state(fn (array $attributes) => [
            'tenant_id' => $tenantId,
        ]);
    }

    /**
     * Create a wallet owner for a specific wallet.
     */
    public function forWallet(Wallet $wallet): static
    {
        return $this->state(fn (array $attributes) => [
            'wallet_id' => $wallet->id,
        ]);
    }

    /**
     * Create a wallet owner for a specific owner.
     */
    public function forOwner($owner): static
    {
        return $this->state(fn (array $attributes) => [
            'owner_id' => $owner->id ?? $owner,
            'owner_type' => is_object($owner) ? get_class($owner) : 'App\\Models\\User',
        ]);
    }

    /**
     * Create a wallet owner without a private key (watch-only).
     */
    public function watchOnly(): static
    {
        return $this->state(fn (array $attributes) => [
            'encrypted_private_key' => null,
        ]);
    }

    /**
     * Create a wallet owner with a specific private key.
     */
    public function withPrivateKey(string $privateKey): static
    {
        return $this->state(fn (array $attributes) => [
            'encrypted_private_key' => Crypt::encrypt($privateKey),
        ]);
    }

    /**
     * Create a wallet owner for an Ethereum wallet.
     */
    public function ethereum(): static
    {
        return $this->state(fn (array $attributes) => [
            'wallet_id' => Wallet::factory()->ethereum(),
            'encrypted_private_key' => Crypt::encrypt($this->generateEthereumPrivateKey()),
        ]);
    }

    /**
     * Create a wallet owner for a Solana wallet.
     */
    public function solana(): static
    {
        return $this->state(fn (array $attributes) => [
            'wallet_id' => Wallet::factory()->solana(),
            'encrypted_private_key' => Crypt::encrypt($this->generateSolanaPrivateKey()),
        ]);
    }

    /**
     * Generate a mock private key (not cryptographically secure).
     */
    private function generatePrivateKey(): string
    {
        return $this->faker->regexify('[a-f0-9]{64}');
    }

    /**
     * Generate a mock Ethereum private key.
     */
    private function generateEthereumPrivateKey(): string
    {
        return '0x'.$this->generatePrivateKey();
    }

    /**
     * Generate a mock Solana private key.
     */
    private function generateSolanaPrivateKey(): string
    {
        // Solana private keys are typically Base58 encoded
        $chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $length = 64; // Typical length for Base58 encoded 32-byte key
        $key = '';

        for ($i = 0; $i < $length; $i++) {
            $key .= $chars[$this->faker->numberBetween(0, strlen($chars) - 1)];
        }

        return $key;
    }
}
