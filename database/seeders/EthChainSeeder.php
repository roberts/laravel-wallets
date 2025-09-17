<?php

namespace Database\Seeders;

use Roberts\LaravelWallets\Models\EthChain;
use Illuminate\Database\Seeder;

class EthChainSeeder extends Seeder
{
    public function run(): void
    {
        $chains = [
            [
                'id' => 1,
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
                    'https://ethereum.blockpi.network/v1/rpc/public'
                ],
                'is_active' => true,
                'is_default' => true,
            ],
            [
                'id' => 2,
                'name' => 'Sepolia',
                'abbreviation' => 'SEP',
                'chain_id' => 11155111,
                'rpc' => 'https://ethereum-sepolia-rpc.publicnode.com',
                'scanner' => 'https://sepolia.etherscan.io',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://rpc.sepolia.org',
                    'https://sepolia.gateway.tenderly.co',
                    'https://rpc-sepolia.rockx.com'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 3,
                'name' => 'Base',
                'abbreviation' => 'BASE',
                'chain_id' => 8453,
                'rpc' => 'https://mainnet.base.org',
                'scanner' => 'https://basescan.org',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://base-rpc.publicnode.com',
                    'https://base.gateway.tenderly.co',
                    'https://rpc.ankr.com/base'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 4,
                'name' => 'Unichain',
                'abbreviation' => 'UNI',
                'chain_id' => 130,
                'rpc' => 'https://mainnet.unichain.org',
                'scanner' => 'https://uniscan.xyz',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://rpc.unichain.org'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 5,
                'name' => 'Optimism',
                'abbreviation' => 'OP',
                'chain_id' => 10,
                'rpc' => 'https://mainnet.optimism.io',
                'scanner' => 'https://optimistic.etherscan.io',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://optimism-rpc.publicnode.com',
                    'https://rpc.ankr.com/optimism',
                    'https://optimism.gateway.tenderly.co'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 6,
                'name' => 'Polygon',
                'abbreviation' => 'MATIC',
                'chain_id' => 137,
                'rpc' => 'https://polygon-rpc.com',
                'scanner' => 'https://polygonscan.com',
                'supports_eip1559' => true,
                'native_symbol' => 'POL',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://polygon-mainnet.public.blastapi.io',
                    'https://rpc.ankr.com/polygon',
                    'https://polygon-bor-rpc.publicnode.com'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 7,
                'name' => 'Arbitrum One',
                'abbreviation' => 'ARB',
                'chain_id' => 42161,
                'rpc' => 'https://arb1.arbitrum.io/rpc',
                'scanner' => 'https://arbiscan.io',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://arbitrum-one-rpc.publicnode.com',
                    'https://rpc.ankr.com/arbitrum',
                    'https://arbitrum.gateway.tenderly.co'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 8,
                'name' => 'Arbitrum Nova',
                'abbreviation' => 'ARB-NOVA',
                'chain_id' => 42170,
                'rpc' => 'https://nova.arbitrum.io/rpc',
                'scanner' => 'https://nova.arbiscan.io',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://arbitrum-nova-rpc.publicnode.com',
                    'https://rpc.ankr.com/arbitrumnova'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 9,
                'name' => 'zkSync Era',
                'abbreviation' => 'ZK',
                'chain_id' => 324,
                'rpc' => 'https://mainnet.era.zksync.io',
                'scanner' => 'https://explorer.zksync.io',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://zksync-era.blockpi.network/v1/rpc/public',
                    'https://zksync.gateway.tenderly.co'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 10,
                'name' => 'Linea',
                'abbreviation' => 'LINEA',
                'chain_id' => 59144,
                'rpc' => 'https://rpc.linea.build',
                'scanner' => 'https://lineascan.build',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://linea-mainnet.public.blastapi.io',
                    'https://rpc.ankr.com/linea'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 11,
                'name' => 'Scroll',
                'abbreviation' => 'SCROLL',
                'chain_id' => 534352,
                'rpc' => 'https://rpc.scroll.io',
                'scanner' => 'https://scrollscan.com',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://scroll-mainnet.public.blastapi.io',
                    'https://rpc.ankr.com/scroll'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 12,
                'name' => 'Mantle',
                'abbreviation' => 'MNT',
                'chain_id' => 5000,
                'rpc' => 'https://rpc.mantle.xyz',
                'scanner' => 'https://mantlescan.xyz',
                'supports_eip1559' => true,
                'native_symbol' => 'MNT',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://mantle-mainnet.public.blastapi.io',
                    'https://rpc.ankr.com/mantle'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 13,
                'name' => 'Blast',
                'abbreviation' => 'BLAST',
                'chain_id' => 81457,
                'rpc' => 'https://rpc.blast.io',
                'scanner' => 'https://blastscan.io',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://blast-mainnet.public.blastapi.io',
                    'https://rpc.ankr.com/blast'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 14,
                'name' => 'Mode',
                'abbreviation' => 'MODE',
                'chain_id' => 34443,
                'rpc' => 'https://mainnet.mode.network',
                'scanner' => 'https://modescan.io',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://mode.gateway.tenderly.co'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 15,
                'name' => 'Manta Pacific',
                'abbreviation' => 'MANTA',
                'chain_id' => 169,
                'rpc' => 'https://pacific-rpc.manta.network/http',
                'scanner' => 'https://pacific-explorer.manta.network',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://manta-pacific.api.onfinality.io/public'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 16,
                'name' => 'Starknet',
                'abbreviation' => 'STRK',
                'chain_id' => 393402129659,
                'rpc' => 'https://starknet-mainnet.public.blastapi.io/rpc/v0_7',
                'scanner' => 'https://starkscan.co',
                'supports_eip1559' => false,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://rpc.starknet.lava.build'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 17,
                'name' => 'Apechain',
                'abbreviation' => 'APE',
                'chain_id' => 33139,
                'rpc' => 'https://apechain.calderachain.xyz/http',
                'scanner' => 'https://apescan.io',
                'supports_eip1559' => true,
                'native_symbol' => 'APE',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://rpc.apechain.com'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 18,
                'name' => 'Abstract',
                'abbreviation' => 'ABS',
                'chain_id' => 2741,
                'rpc' => 'https://api.mainnet.abs.xyz',
                'scanner' => 'https://abscan.org',
                'supports_eip1559' => true,
                'native_symbol' => 'ETH',
                'native_decimals' => 18,
                'rpc_alternates' => json_encode([
                    'https://rpc.abstract.foundation'
                ]),
                'is_active' => true,
                'is_default' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        foreach ($chains as $chain) {
            EthChain::create($chain);
        }
    }
}
