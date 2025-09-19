# Laravel package for Web3 Wallet Management

[![Latest Version on Packagist](https://img.shields.io/packagist/v/roberts/laravel-wallets.svg?style=flat-square)](https://packagist.org/packages/roberts/laravel-wallets)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/roberts/laravel-wallets/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/roberts/laravel-wallets/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/roberts/laravel-wallets/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/roberts/laravel-wallets/actions?query=workflow%3A"Fix+PHP+code+style+issues"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/roberts/laravel-wallets.svg?style=flat-square)](https://packagist.org/packages/roberts/laravel-wallets)

A powerful Laravel package for managing blockchain wallets supporting Ethereum and Solana protocols. Features a modern two-table architecture with global wallet registry and ownership records, providing secure wallet management, multi-tenancy support, and flexible control models.

## Features

- **Two-Table Architecture**: Global wallet registry with separate ownership records for better scalability
- **Multi-Protocol Support**: Create wallets for Ethereum and Solana blockchains with extensible protocol system
- **Flexible Control Models**: Support for custodial, external, and shared wallets
- **Secure Key Management**: Automatic private key generation and encryption with per-ownership storage
- **Multi-Tenancy**: Built-in support for multi-tenant applications using `roberts/laravel-singledb-tenancy`
- **Advanced Wallet Service**: Comprehensive service layer for wallet creation, import, and management
- **Legacy Compatibility**: Maintains backward compatibility through the `HasWallets` trait
- **Test-Driven Development**: Comprehensive test suite covering all architecture components

## Architecture Overview

The package uses a two-table architecture:

- **`wallets` table**: Global registry of all wallet addresses across all protocols
- **`wallet_owners` table**: Controls who has access to which wallets in which tenants

This design enables:
- **Address Deduplication**: Same address can be shared across different owners/tenants
- **Flexible Ownership**: Multiple ownership models (custodial, external, shared)
- **Enhanced Security**: Encrypted private keys stored per ownership record
- **Better Scalability**: Optimized queries and reduced data duplication

## Installation

You can install the package via composer:

```bash
composer require roberts/laravel-wallets
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="laravel-wallets-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="laravel-wallets-config"
```

## Usage

### Setup your Model

First, add the `HasWallets` trait to any model that should own wallets (typically your `User` model):

```php
<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Roberts\LaravelWallets\Concerns\HasWallets;

class User extends Authenticatable
{
    use HasWallets;

    // ... rest of your model
}
```

### Create Wallets (New Service-Based API)

The package provides a powerful `WalletService` for all wallet operations:

```php
use Roberts\LaravelWallets\Services\WalletService;
use Roberts\LaravelWallets\Enums\Protocol;

$walletService = app(WalletService::class);
$user = User::find(1);

// Create a custodial Ethereum wallet (we control the private key)
$result = $walletService->createCustodialWallet(
    protocol: Protocol::ETH,
    owner: $user,
    tenantId: 1,
    metadata: ['purpose' => 'main wallet']
);

echo "Wallet Address: " . $result['wallet']->address;
echo "Private Key Available: " . ($result['walletOwner']->hasControl() ? 'Yes' : 'No');

// Create a custodial Solana wallet
$result = $walletService->createCustodialWallet(
    protocol: Protocol::SOL,
    owner: $user,
    tenantId: 1
);

echo "Solana Address: " . $result['wallet']->address;
```

### Import External Wallets

Import wallets you already have private keys for:

```php
// Import an existing Ethereum wallet
$result = $walletService->importExternalWallet(
    protocol: Protocol::ETH,
    address: '0x742d35Cc0b3E7C3f8f9E7aD0e1C5C3F5e0E8c8B7',
    privateKey: '0xa2fd51b96dc55aeb14b30d55a6b3121c7b9c599500c1bbc92a22208d5dc73134',
    owner: $user,
    tenantId: 1,
    metadata: ['imported_from' => 'MetaMask']
);

echo "Imported Wallet: " . $result['wallet']->address;
echo "Control Type: " . $result['wallet']->control_type->value; // 'EXTERNAL'
```

### Legacy API Support (HasWallets Trait)

The package maintains backward compatibility through the `HasWallets` trait:

```php
// Legacy-style wallet creation (uses new architecture internally)
$result = $user->createEthereumWallet();
echo "Address: " . $result['wallet']->address;

// Access wallets through relationships
$userWallets = $user->wallets()->get(); // All accessible wallets
$custodialWallets = $user->custodialWallets(tenantId: 1); // Only custodial wallets
$externalWallets = $user->externalWallets(tenantId: 1); // Only external wallets
```

### Retrieving Wallets

```php
// Get all wallets for a user in a tenant
$userWallets = $user->walletsForTenant(1);

// Filter by protocol
$ethWallets = $user->walletsForTenant(1)->where('protocol', Protocol::ETH)->get();

// Get wallet ownership details
$ownership = $user->getWalletOwnership($walletId, $tenantId);
if ($ownership && $ownership->hasControl()) {
    echo "User has private key access to this wallet";
}
```

### Multi-Tenancy Support

When using `roberts/laravel-singledb-tenancy`, wallets are automatically scoped to the current tenant:

```php
// Wallets are automatically scoped to the current tenant
$userWallets = $user->walletsForTenant($currentTenantId);

// Check access to specific wallet
if ($user->hasWalletAccess($walletId, $tenantId)) {
    echo "User has access to this wallet in this tenant";
}
```

## Management Commands

The package provides a comprehensive command-line interface for wallet management:

```bash
# List all wallets with filtering options
php artisan wallets list
php artisan wallets list --protocol=eth
php artisan wallets list --control-type=custodial
php artisan wallets list --tenant=1

# Show wallet statistics
php artisan wallets stats
php artisan wallets stats --tenant=1

# Validate wallet data integrity
php artisan wallets validate
```

## Architecture Deep Dive

### Two-Table Design

The package uses a sophisticated two-table architecture:

**`wallets` Table (Global Registry)**:
- `id` - Primary key
- `protocol` - Blockchain protocol (ETH, SOL)
- `address` - Wallet address 
- `control_type` - Control model (CUSTODIAL, EXTERNAL, SHARED)
- `metadata` - JSON metadata
- `created_at`, `updated_at`

**`wallet_owners` Table (Ownership Records)**:
- `id` - Primary key
- `wallet_id` - References wallets table
- `tenant_id` - Tenant identifier
- `owner_id` - Owner model ID
- `owner_type` - Owner model class
- `encrypted_private_key` - Encrypted private key (nullable for watch-only)
- `created_at`, `updated_at`

### Control Types

- **`CUSTODIAL`**: Package generates and controls private keys
- **`EXTERNAL`**: Wallets added through token snapshot or user submission
- **`SHARED`**: Laravel Application shares private key with owner or user

### Wallet Creation Process

1. **Service Layer**: `WalletService` handles all wallet operations
2. **Key Generation**: Protocol-specific key pair generation
3. **Global Registry**: Wallet address stored in `wallets` table
4. **Ownership Records**: Access control stored in `wallet_owners` table
5. **Encryption**: Private keys encrypted per ownership record

### Security Features

- **Encrypted Private Keys**: Per-ownership encryption using Laravel's encryption
- **Secure Key Generation**: Industry-standard libraries (web3p/web3.php, solana-php/solana-php)
- **Address Validation**: Built-in validation for both Ethereum and Solana
- **Tenant Isolation**: Multi-tenancy support with proper data scoping
- **Access Control**: Fine-grained permissions through ownership records

## API Reference

### Protocol & Control Type Enums

```php
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Enums\ControlType;

Protocol::ETH;              // Ethereum protocol
Protocol::SOL;              // Solana protocol

ControlType::CUSTODIAL;     // We control the private key
ControlType::EXTERNAL;      // Wallets not controlled by app
ControlType::SHARED;        // Custodial wallets that have shared private key with user
```

### WalletService Methods

```php
$walletService = app(WalletService::class);

// Create custodial wallet
$walletService->createCustodialWallet($protocol, $owner, $tenantId, $metadata);

// Add external wallets from snapshot
$walletService->addExternalWalletsFromSnapshot($protocol, $addresses, $metadata);
```

### Wallet Model

```php
$wallet->protocol;          // Protocol enum (ETH or SOL)  
$wallet->address;           // Public wallet address
$wallet->control_type;      // Control type enum
$wallet->metadata;          // JSON metadata array
$wallet->owners;            // HasMany relationship to WalletOwner
```

### WalletOwner Model

```php
$walletOwner->wallet;           // BelongsTo relationship to Wallet
$walletOwner->owner;            // MorphTo relationship to owner model
$walletOwner->tenant_id;        // Tenant identifier
$walletOwner->hasControl();     // Has private key access
$walletOwner->getPrivateKey();  // Decrypt and return private key
```

### HasWallets Trait

```php
$model->wallets();                  // HasManyThrough to wallets
$model->walletOwnerships();         // HasMany to wallet owners
$model->walletsForTenant($id);      // Wallets for specific tenant
$model->custodialWallets($id);      // Custodial wallets only
$model->externalWallets($id);       // External wallets only  
$model->createWallet($protocol);    // Legacy-compatible creation
```

## Migration Guide

### From v1.x to v2.x (Two-Table Architecture)

The major change in v2.x is the move from single-table to two-table architecture. Here's how to migrate:

1. **Run New Migrations**:
   ```bash
   php artisan migrate
   ```

2. **Update Usage Patterns**:
   ```php
   // OLD (v1.x) - Single table approach
   $wallet = $user->wallets()->create(['protocol' => Protocol::ETH]);
   
   // NEW (v2.x) - Service-based approach (recommended)
   $result = $walletService->createCustodialWallet(Protocol::ETH, $user, 1);
   $wallet = $result['wallet'];
   
   // NEW (v2.x) - Legacy-compatible approach  
   $result = $user->createEthereumWallet();
   $wallet = $result['wallet'];
   ```

3. **Update Relationships**:
   ```php
   // OLD - Direct relationship
   foreach ($user->wallets as $wallet) { ... }
   
   // NEW - Through ownership records
   foreach ($user->wallets as $wallet) { ... } // Still works!
   // Or more explicit:
   foreach ($user->walletsForTenant(1) as $wallet) { ... }
   ```

## Requirements

- PHP 8.4+
- Laravel 10.0+
- MySQL 8.0+ or PostgreSQL 12.0+
- PHP Extensions: `gmp`, `sodium`

- PHP 8.4+
- Laravel 11.0+
- Required PHP extensions:
  - `sodium` (for Solana wallet operations)
  - `gmp` (for cryptographic operations)

## Testing

```bash
composer test
```

The package includes a comprehensive test suite with:
- Feature tests for wallet creation and management
- Unit tests for all services and models
- Integration tests for multi-tenancy
- Architecture tests to ensure code quality

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

- [Drew Roberts](https://github.com/drewroberts)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
