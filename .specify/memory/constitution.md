# Laravel Wallets Package Constitution

## 1. Package Overview

This document outlines the requirements for a Laravel PHP package designed to manage cryptocurrency wallets on multiple blockchains. The package will be utilized in Laravel web applications, particularly those where AI agents control wallets or that function as token and NFT deployers. The deployer-specific logic will reside in a separate package that depends on this one.

## 2. Core Functionality

The package must provide the following core features:

-   **Wallet Management:** Programmatic creation of new wallets for supported blockchains.
-   **Native Token Transactions:** Ability to send the native token of a given blockchain (e.g., ETH, SOL).
-   **Contract Storage:** A system to store and manage smart contract addresses and their associated details (like ABI for Ethereum).
-   **Token & NFT Details:** Functionality to query contract details to get information about specific tokens (ERC-20, SPL) and NFTs (ERC-721, ERC-1155, Metaplex).
-   **Token & NFT Transfers:** Methods to facilitate the transfer of tokens and NFTs between wallets.
-   **Holder Snapshots:** A feature to take a snapshot of all wallet addresses holding a specific token or NFT at a given point in time.

## 3. Blockchain Support

-   **Initial Chains:** The package must launch with support for:
    -   Ethereum and EVM-compatible chains.
    -   Solana.
-   **Extensibility:** The architecture must be flexible to allow for the seamless addition of other Layer 1 blockchains in the future. A driver-based or strategy pattern approach is recommended, where each blockchain has its own implementation of a common interface for core functionalities.

## 4. Database Schema

The database tables must be designed with flexibility to store data from various blockchains. The core tables should include:

-   `wallets`: To store wallet addresses, encrypted private keys/seed phrases, and the associated blockchain identifier.
-   `contracts`: To store contract addresses, metadata (like ABI), and the blockchain identifier.
-   `transactions`: A log of all outgoing and incoming transactions, with a flexible `meta` column to store chain-specific data.

The schema should be designed to work with the `roberts/laravel-singledb-tenancy` package.

## 5. Technical Stack & Dependencies

-   **Framework:** Laravel
-   **Language:** PHP
-   **Testing:** Pest v4.1 will be used for all tests.
-   **Multi-Tenancy:** The package must integrate with `roberts/laravel-singledb-tenancy` to ensure data is scoped to the correct tenant.

## 6. Exclusions

-   This package should **not** include any logic related to deploying new smart contracts. That functionality will be handled by a separate, dependent package.
-   User interface components are outside the scope of this package. It should provide a backend API only.

**Version**: 1.0.0 | **Ratified**: 2025-09-18 | **Last Amended**: 2025-09-18