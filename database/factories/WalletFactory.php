<?php

namespace Roberts\LaravelWallets\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\Roberts\LaravelWallets\Models\Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    public function definition(): array
    {
        $protocol = $this->faker->randomElement(Protocol::cases());

        // Generate appropriate address for protocol
        $address = match ($protocol) {
            Protocol::ETH => $this->generateEthereumAddress(),
            Protocol::SOL => $this->generateSolanaAddress(),
            default => throw new \InvalidArgumentException("Unsupported protocol: {$protocol->value}"),
        };

        return [
            'protocol' => $protocol,
            'address' => $address,
            'control_type' => $this->faker->randomElement(ControlType::cases()),
            'metadata' => [
                'created_via' => 'factory',
                'network' => match ($protocol) {
                    Protocol::ETH => 'mainnet',
                    Protocol::SOL => 'mainnet-beta',
                    default => 'mainnet',
                },
            ],
        ];
    }

    /**
     * Create a custodial wallet state.
     */
    public function custodial(): static
    {
        return $this->state(fn (array $attributes) => [
            'control_type' => ControlType::CUSTODIAL,
        ]);
    }

    /**
     * Create an external wallet state.
     */
    public function external(): static
    {
        return $this->state(fn (array $attributes) => [
            'control_type' => ControlType::EXTERNAL,
        ]);
    }

    /**
     * Create a shared wallet state.
     */
    public function shared(): static
    {
        return $this->state(fn (array $attributes) => [
            'control_type' => ControlType::SHARED,
        ]);
    }

    /**
     * Create an Ethereum wallet state.
     */
    public function ethereum(): static
    {
        return $this->state(fn (array $attributes) => [
            'protocol' => Protocol::ETH,
            'address' => $this->generateEthereumAddress(),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'network' => 'mainnet',
                'chain_id' => 1,
            ]),
        ]);
    }

    /**
     * Create a Solana wallet state.
     */
    public function solana(): static
    {
        return $this->state(fn (array $attributes) => [
            'protocol' => Protocol::SOL,
            'address' => $this->generateSolanaAddress(),
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'cluster' => 'mainnet-beta',
            ]),
        ]);
    }

    /**
     * Create an Ethereum testnet wallet state.
     */
    public function ethereumTestnet(): static
    {
        return $this->ethereum()->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'network' => 'sepolia',
                'chain_id' => 11155111,
            ]),
        ]);
    }

    /**
     * Create a Solana devnet wallet state.
     */
    public function solanaDevnet(): static
    {
        return $this->solana()->state(fn (array $attributes) => [
            'metadata' => array_merge($attributes['metadata'] ?? [], [
                'cluster' => 'devnet',
            ]),
        ]);
    }

    /**
     * Generate a valid-looking Ethereum address.
     */
    private function generateEthereumAddress(): string
    {
        // Generate a random 40-character hex string prefixed with 0x
        return '0x'.$this->faker->regexify('[a-f0-9]{40}');
    }

    /**
     * Generate a valid-looking Solana address.
     */
    private function generateSolanaAddress(): string
    {
        // Generate a Base58-encoded string that looks like a Solana address
        // This is not cryptographically valid, just for testing
        $chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $length = $this->faker->numberBetween(32, 44);
        $address = '';

        for ($i = 0; $i < $length; $i++) {
            $address .= $chars[$this->faker->numberBetween(0, strlen($chars) - 1)];
        }

        return $address;
    }
}
