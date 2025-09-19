<?php

// config for Roberts/LaravelWallets
return [

    /*
    |--------------------------------------------------------------------------
    | Default Wallet Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default wallet "driver" that will be used when
    | creating wallets without specifying a protocol. You can set this to any
    | of the drivers defined in the "drivers" array below.
    |
    */

    'default' => 'ethereum',

    /*
    |--------------------------------------------------------------------------
    | Wallet Drivers
    |--------------------------------------------------------------------------
    |
    | Here you may configure the wallet drivers for your application. Each
    | blockchain protocol should have a corresponding driver that implements
    | the WalletAdapterInterface. You can add new protocols by creating a
    | new adapter class and adding it to this array.
    |
    */

    'drivers' => [
        'eth' => [
            'adapter' => \Roberts\LaravelWallets\Protocols\Ethereum\WalletAdapter::class,
            'rpc_url' => 'https://mainnet.infura.io/v3/your-project-id',
            'testnet_rpc_url' => 'https://sepolia.infura.io/v3/your-project-id',
            'use_testnet' => false,
        ],

        'sol' => [
            'adapter' => \Roberts\LaravelWallets\Protocols\Solana\WalletAdapter::class,
            'rpc_url' => 'https://api.mainnet-beta.solana.com',
            'testnet_rpc_url' => 'https://api.testnet.solana.com',
            'use_testnet' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Configure security-related settings for wallet operations.
    |
    */

    'security' => [
        /*
         * Enable or disable private key access logging for audit purposes
         */
        'log_private_key_access' => true,

        /*
         * Maximum time (in seconds) a wallet can remain "unlocked" for
         * private key access. After this time, the wallet will need to be
         * unlocked again.
         */
        'unlock_timeout' => 300, // 5 minutes

        /*
         * Permission configuration for wallet operations
         */
        'permissions' => [
            'create_custodial_wallet' => [
                'required_role' => null, // No specific role required for now
            ],
            'add_external_wallet' => [
                'required_role' => null,
            ],
            'export_private_key' => [
                'required_role' => 'admin', // Only admins can export private keys
            ],
            'bulk_import' => [
                'required_role' => 'admin',
            ],
        ],

        /*
         * Rate limiting configuration
         */
        'rate_limits' => [
            'create_custodial_wallet' => [
                'limit' => 10,     // Max 10 wallet creations
                'window' => 3600,  // Per hour
            ],
            'add_external_wallet' => [
                'limit' => 50,     // Max 50 external wallet additions
                'window' => 3600,  // Per hour
            ],
            'export_private_key' => [
                'limit' => 3,      // Max 3 private key exports
                'window' => 3600,  // Per hour
            ],
        ],

        /*
         * Required security measures for specific operations
         */
        'required_measures' => [
            'export_private_key' => [
                'additional_confirmation',
                'enhanced_logging',
                'admin_notification',
            ],
            'bulk_import' => [
                'enhanced_logging',
                'admin_notification',
            ],
        ],

        /*
         * Enable security features
         */
        'enable_suspicious_activity_detection' => true,
        'enable_audit_logging' => true,
        'enable_rate_limiting' => true,
    ],

];
