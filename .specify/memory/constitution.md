# Laravel Wallets Package Constitution

## Core Principles

### I. Multi-Blockchain & Extensible Architecture
The package must support Ethereum (and EVM chains) and Solana at launch. The design must be extensible, using a driver-based or strategy pattern to allow for the future addition of other Layer 1 blockchains.

### II. Core Wallet Functionality
The package will provide functionality for: wallet creation, sending native tokens, storing contract addresses/details, querying token/NFT details, transferring tokens/NFTs, and snapshotting token/NFT holders.

### III. Test-First (NON-NEGOTIABLE)
All features must be tested using Pest v4.1. Test-Driven Development (TDD) is mandatory.

### IV. Multi-Tenancy
The package must integrate with `roberts/laravel-singledb-tenancy` to ensure data may be properly scoped to a tenant.

## Technical Stack & Exclusions

- **Stack:** The package is built for the Laravel framework using PHP following the best practices for `spatie/laravel-package-tools` and the rest of spatie's laravel packages.
- **Exclusions:** This package will not include any smart contract deployment logic or any user interface components. These will be handled in other packages.

## Database Design

The database schema must be flexible enough to accommodate data from various blockchains. Key tables will include `wallets`, `contracts`, and `transactions`. The design must be compatible with the multi-tenancy requirement.

## Governance

This constitution outlines the fundamental requirements. Any amendments must be documented and approved. All development must adhere to these principles.

**Version**: 1.0.1 | **Ratified**: 2025-09-18 | **Last Amended**: 2025-09-18
