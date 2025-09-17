<?php

namespace Roberts\LaravelWallets\Database\Factories;

use Roberts\LaravelWallets\Models\EthChain;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EthChain>
 */
class EthChainFactory extends Factory
{
    protected $model = EthChain::class;

    public function definition(): array
    {
        $chainId = $this->faker->unique()->numberBetween(1, 999999);
        $networkName = $this->faker->unique()->word();
        $abbreviation = strtoupper(substr($networkName, 0, 5));
        
        return [
            'name' => ucwords($networkName) . ' Network',
            'abbreviation' => $abbreviation,
            'chain_id' => $chainId,
            'rpc' => 'https://rpc.' . strtolower($networkName) . '.io',
            'scanner' => 'https://' . strtolower($networkName) . 'scan.io',
            'supports_eip1559' => $this->faker->boolean(80), // 80% chance of supporting EIP-1559
            'native_symbol' => $this->faker->randomElement(['ETH', 'MATIC', 'BNB', 'AVAX', 'FTM']),
            'native_decimals' => 18,
            'rpc_alternates' => $this->faker->optional(0.7)->randomElements([
                'https://rpc.ankr.com/' . strtolower($abbreviation),
                'https://rpc.publicnode.com/' . strtolower($abbreviation),
                'https://' . strtolower($abbreviation) . '.gateway.tenderly.co',
            ], $this->faker->numberBetween(1, 3)),
            'is_active' => $this->faker->boolean(90), // 90% chance of being active
            'is_default' => false, // Only one should be default, set manually in tests
        ];
    }

    /**
     * Create a mainnet chain.
     */
    public function mainnet(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Ethereum Mainnet',
            'abbreviation' => 'ETH',
            'chain_id' => 1,
            'rpc' => 'https://ethereum-rpc.publicnode.com',
            'scanner' => 'https://etherscan.io',
            'supports_eip1559' => true,
            'native_symbol' => 'ETH',
            'native_decimals' => 18,
            'rpc_alternates' => [
                'https://rpc.ankr.com/eth',
                'https://eth-mainnet.public.blastapi.io',
            ],
            'is_active' => true,
            'is_default' => true,
        ]);
    }

    /**
     * Create a testnet chain.
     */
    public function testnet(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Sepolia Testnet',
            'abbreviation' => 'SEP',
            'chain_id' => 11155111,
            'rpc' => 'https://ethereum-sepolia-rpc.publicnode.com',
            'scanner' => 'https://sepolia.etherscan.io',
            'supports_eip1559' => true,
            'native_symbol' => 'ETH',
            'native_decimals' => 18,
            'rpc_alternates' => [
                'https://rpc.sepolia.org',
                'https://sepolia.gateway.tenderly.co',
            ],
            'is_active' => true,
            'is_default' => false,
        ]);
    }

    /**
     * Create a Layer 2 chain.
     */
    public function layer2(): static
    {
        return $this->state(fn (array $attributes) => [
            'supports_eip1559' => true,
            'native_symbol' => 'ETH',
            'is_active' => true,
        ]);
    }

    /**
     * Create an inactive chain.
     */
    public function inactive(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
        ]);
    }

    /**
     * Create the default chain.
     */
    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }

    /**
     * Create a chain without EIP-1559 support.
     */
    public function legacyTransactions(): static
    {
        return $this->state(fn (array $attributes) => [
            'supports_eip1559' => false,
        ]);
    }
}
