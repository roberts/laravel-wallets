# Laravel Wallets Package Development Guidelines

Laravel package for Web3 Wallet Management supporting Ethereum and Solana protocols with a modern two-table architecture. Features global wallet registry and ownership records, providing secure wallet management, multi-tenancy support, and flexible control models.

Always reference these instructions first and fallback to search or bash commands only when you encounter unexpected information that does not match the info here.

## Working Effectively

### Prerequisites and Environment Setup
- Install PHP 8.4+ with required extensions:
  - `apt-get update && apt-get install -y php8.4-cli php8.4-gmp php8.4-sodium php8.4-curl php8.4-mbstring php8.4-zip php8.4-dom php8.4-libxml php8.4-bcmath php8.4-intl php8.4-gd php8.4-fileinfo`
- Verify extensions: `php -m | grep -E "(gmp|sodium)"` - both must be present
- Extensions are CRITICAL for cryptographic wallet operations - do not proceed without them

### Bootstrap, Build, and Test
- **NEVER CANCEL BUILDS OR TESTS** - Package testing can take up to 5 minutes per workflow as seen in CI
- Install dependencies: `composer install` -- takes 2-3 minutes. NEVER CANCEL. Set timeout to 10+ minutes.
- Run tests: `vendor/bin/pest --ci` -- takes up to 5 minutes per matrix combination. NEVER CANCEL. Set timeout to 15+ minutes.
- Static analysis: `vendor/bin/phpstan --error-format=github` -- takes 1-2 minutes. Set timeout to 5+ minutes.
- Code formatting: `vendor/bin/pint` -- quick, 30 seconds typically

### Development Workflow
- ALWAYS run these validation steps after making changes:
  1. `vendor/bin/pint` (code formatting)
  2. `vendor/bin/phpstan --error-format=github` (static analysis)
  3. `vendor/bin/pest --ci` (full test suite)
- The CI runs tests on both Ubuntu and Windows with prefer-lowest and prefer-stable stability
- Tests run against Laravel 12.* with Orchestra Testbench 10.*

## Validation Scenarios

### CRITICAL: Manual Validation Requirements
After making any code changes, you MUST test actual wallet functionality by running these scenarios:

1. **Wallet Creation Test** (Feature: CreateCustodialWalletTest):
   ```php
   $walletService = app(WalletService::class);
   $result = $walletService->createCustodialWallet(
       protocol: Protocol::ETH,
       owner: $user,
       tenantId: 1
   );
   // Verify: address format, private key encryption, database records
   ```

2. **Multi-Protocol Support Test** (Feature: CreateEthereumWalletTest, CreateSolanaWalletTest):
   ```php
   // Test both Ethereum and Solana wallet creation
   $ethResult = $walletService->createCustodialWallet(Protocol::ETH, $user, 1);
   $solResult = $walletService->createCustodialWallet(Protocol::SOL, $user, 1);
   // Verify: different address formats, both protocols working
   ```

3. **External Wallet Management** (Feature: AddExternalWalletTest):
   ```php
   // Test adding watch-only wallets
   $result = $walletService->addExternalWalletsFromSnapshot(
       Protocol::ETH, 
       ['0x742d35Cc0b3E7C3f8f9E7aD0e1C5C3F5e0E8c8B7'], 
       ['source' => 'snapshot']
   );
   // Verify: external control type, no private key storage
   ```

4. **Security Integration Test** (Feature: SecurityIntegrationTest):
   ```php
   // Test encryption/decryption and key management
   $ownership = WalletOwner::find($ownershipId);
   $privateKey = $ownership->getPrivateKey();
   // Verify: secure key handling, proper encryption
   ```

5. **Legacy Compatibility Test** (HasWallets trait):
   ```php
   // Test HasWallets trait methods still work
   $result = $user->createEthereumWallet();
   $userWallets = $user->walletsForTenant(1);
   // Verify: backward compatibility maintained
   ```

## Project Structure
```
src/
├── Commands/           # Artisan commands for wallet management
├── Concerns/          # Traits (HasWallets, ManagesExternalWallet, etc.)
├── Contracts/         # Interfaces and contracts
├── Enums/            # Protocol and ControlType enums
├── Exceptions/       # Package-specific exceptions
├── Models/           # Wallet and WalletOwner models
├── Protocols/        # Protocol-specific implementations
├── Security/         # Security and encryption services
├── Services/         # Core business logic (WalletService, etc.)
├── Wallets/          # Wallet implementations (EthWallet, SolWallet)
└── WalletsServiceProvider.php

tests/
├── Feature/          # Integration tests for full workflows
├── Unit/            # Unit tests for individual components
├── Factories/       # Test data factories
└── TestCase.php     # Base test case

config/wallets.php    # Package configuration
database/migrations/  # Database schema for two-table architecture
```

## Common Commands and Expected Times

### Installation and Setup
```bash
composer install                    # 1-2 minutes, NEVER CANCEL, timeout: 10+ minutes
```

### Testing (CRITICAL: Never cancel these)
```bash
vendor/bin/pest                    # Full test suite: 1-2 minutes, timeout: 10+ minutes
vendor/bin/pest --ci               # CI format: 1-2 minutes, timeout: 10+ minutes  
vendor/bin/pest --coverage         # With coverage: 3-5 minutes, timeout: 15+ minutes
```

### Code Quality (Run before committing)
```bash
vendor/bin/pint                    # Format code: 30 seconds, timeout: 2+ minutes
vendor/bin/phpstan                 # Static analysis: 1-2 minutes, timeout: 5+ minutes
vendor/bin/phpstan --error-format=github  # CI format: 1-2 minutes, timeout: 5+ minutes
```

### Package Commands
```bash
php artisan wallets list           # List all wallets
php artisan wallets list --protocol=eth --tenant=1  # Filter by protocol and tenant
php artisan wallets stats          # Show wallet statistics  
php artisan wallets stats --tenant=1  # Stats for specific tenant
php artisan wallets validate       # Validate data integrity
```

## Key Architecture Components

### Two-Table Design
- **`wallets` table**: Global registry (id, protocol, address, control_type, metadata)
- **`wallet_owners` table**: Ownership records (wallet_id, tenant_id, owner_id, encrypted_private_key)
- This enables address deduplication and flexible ownership models

### Protocol Support
- **Ethereum (ETH)**: Uses web3p/web3.php library
- **Solana (SOL)**: Uses custom implementation with sodium extension
- **Extensible**: New protocols can be added via Protocol enum

### Control Types
- **CUSTODIAL**: Package generates and controls private keys
- **EXTERNAL**: Wallets not controlled by app (watch-only)
- **SHARED**: Custodial wallets with shared private key access

## Dependencies and Requirements
- PHP 8.4+ (CRITICAL: Earlier versions will fail)
- Laravel 12.0+
- Required extensions: `gmp`, `sodium` (for cryptographic operations)
- Database: MySQL 8.0+ or PostgreSQL 12.0+
- Additional Laravel packages:
  - `roberts/laravel-singledb-tenancy ^12.1` (multi-tenancy)
  - `roberts/support ^12.1` (utilities)
  - `filament/filament ^4.0.1` (admin interface)

## Build Timeouts and CI Configuration
Based on `.github/workflows/` and actual run data:
- **Test Matrix**: Ubuntu + Windows, PHP 8.4, Laravel 12.*, prefer-lowest + prefer-stable
- **Actual Runtime**: Complete CI runs take 1.5-2 minutes per job in practice
- **Timeout Settings**: All workflows use 5-minute timeouts as safety margins
- **NEVER CANCEL** any build or test operation - they may legitimately take the full timeout period
- **Use these timeout values**: `composer install` (10+ min), `pest` tests (10+ min), `phpstan` (5+ min)
- If commands seem to hang, wait minimum 5 minutes before considering alternatives

## Troubleshooting Common Issues
1. **Missing Extensions**: Verify `php -m | grep -E "(gmp|sodium)"` shows both extensions
2. **PHP Version**: Must be 8.4+ - check with `php --version`
3. **Memory Issues**: Increase memory_limit for large test suites
4. **Network Issues**: Some dependencies may require specific network access
5. **Database**: Ensure proper SQLite/MySQL configuration for tests

## Security Considerations
- Private keys are encrypted per ownership record using Laravel's encryption
- Address validation is enforced for both Ethereum and Solana formats
- Tenant isolation prevents cross-tenant data access
- BIP39 wordlist validation for mnemonic generation

## Testing Best Practices
- Always run the full test suite (`vendor/bin/pest --ci`) before committing
- Test both custodial and external wallet scenarios
- Verify multi-tenancy behavior with different tenant IDs
- Test both Ethereum and Solana protocol paths
- Validate encryption/decryption of private keys
- Check legacy compatibility through HasWallets trait methods

### Test Framework Details
- Uses **Pest** testing framework (not PHPUnit directly)
- Test configuration in `phpunit.xml.dist` with SQLite for testing
- Orchestra Testbench provides Laravel environment for package testing
- Tests automatically use `APP_ENV=testing` with in-memory database
- Coverage reports can be generated with `--coverage` flag

Remember: This is a financial/crypto package - thorough testing and validation are CRITICAL for security and correctness.