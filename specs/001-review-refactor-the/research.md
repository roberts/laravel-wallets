# Research: Wallet Creation & Storage Refactor

This document outlines the research and decisions made to resolve ambiguities identified in the planning phase for the wallet creation and storage refactor.

## 1. Secure Private Key Storage and Retrieval

### Question
What is the best practice for securely storing and retrieving private keys in a Laravel application? (Corresponds to spec requirement FR-007)

### Research
- **Laravel's Built-in Encryption**: Laravel's `encrypt()` and `decrypt()` helpers provide strong, AES-256 and AES-128 encryption out of the box, using the application's `APP_KEY`. This is the standard and most secure method for encrypting sensitive data within a Laravel application.
- **Hardware Security Modules (HSMs)**: For enterprise-grade security, HSMs (like AWS KMS, Azure Key Vault) are the gold standard. They manage cryptographic keys and perform operations without exposing the keys themselves. Integrating this would add significant complexity but offers the highest level of security.
- **Database-level Encryption**: Some databases offer transparent data encryption (TDE). However, this protects data at rest on the disk but not from application-level access if the application itself is compromised.

### Decision
**Use Laravel's built-in `encrypt()` and `decrypt()` functions.**

### Rationale
- It provides a robust and sufficient level of security for the majority of use cases for this package.
- It is tightly integrated with the framework, easy to use, and requires no additional external dependencies.
- It aligns with the principle of keeping the package lightweight and easy to adopt.
- For users requiring higher security, they can and should implement additional measures at the infrastructure level (e.g., HSMs, stricter access controls), but the package will provide a secure default.

### Authorization for Decryption
- **The Problem**: When should the application be allowed to decrypt the private key?
- **Decision**: Decryption should not be a publicly accessible route or method. It should be an internal, programmatic-only function. The package will provide a method like `getDecryptedPrivateKey()`, but its use will be fire-walled by a temporary, in-memory "unlock" mechanism. A developer must explicitly call a method like `Wallet::unlockForSession($password)` which would then allow subsequent calls to `getDecryptedPrivateKey()` for a limited time or number of uses. The responsibility for calling `unlockForSession` securely (e.g., after re-authenticating the user) lies with the consuming application developer. This provides a clear, intentional barrier to private key access.

## 2. Protocol-Specific Configuration

### Question
Should protocol-specific configuration like RPC endpoints be part of a database model or stored in a configuration file?

### Research
- **Config File (`config/wallets.php`)**: This is the standard Laravel way of handling package configuration. It's simple, version-controllable, and allows developers to easily manage settings across different environments (.env files).
- **Database Model (`protocols` table)**: This would allow for dynamic management of protocols without code deployments. However, it adds database overhead and complexity. For a package like this, where the supported protocols are hard-coded into the adapters, a dynamic database-driven approach is unnecessary.

### Decision
**Store protocol-specific configuration in a dedicated `config/wallets.php` file.**

### Rationale
- It follows Laravel conventions, making it intuitive for developers.
- It keeps configuration separate from application data.
- Environments (local, staging, production) can have different RPC endpoints and settings by using the `.env` file, which is a critical requirement.
- Since new protocols require adding new adapter classes (code changes), tying their configuration to the codebase (config files) is logical.

## 3. Driver-Based Architecture for Protocols

### Question
What is the best practice for implementing a driver-based (strategy) pattern in Laravel for different blockchain protocols?

### Research
- **Laravel's Manager Class**: Laravel itself uses a "Manager" pattern extensively (e.g., `CacheManager`, `SessionManager`, `FilesystemManager`). This pattern involves a manager class that is responsible for creating and resolving "driver" instances based on configuration.
- **Spatie Packages**: Many Spatie packages use a similar pattern, often simplified, where a central class resolves a specific implementation from an array of registered classes based on a config value.

### Decision
**Implement a `WalletManager` class that functions as a factory for protocol-specific wallet adapters.**

### Rationale
- This pattern is idiomatic for Laravel and highly extensible.
- The `WalletManager` will have a `create($protocol)` method. Based on the `$protocol` enum, it will look up the corresponding adapter class (e.g., `EthereumAdapter`, `SolanaAdapter`) from the `config/wallets.php` file.
- Each adapter will implement a common `WalletAdapterInterface`, ensuring they all adhere to the same contract for creating wallets, getting addresses, etc.
- This makes adding a new blockchain protocol as simple as:
    1. Creating a new `NewProtocolAdapter` class.
    2. Adding it to the `drivers` array in the `wallets.php` config file.
- This perfectly aligns with the constitutional requirement for an extensible architecture.
