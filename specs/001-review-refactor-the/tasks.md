# Tasks: Wallet Creation & Storage Refactor

**Input**: Design documents from `/Users/drewroberts/Code/laravel-wallets/specs/001-review-refactor-the/`
**Prerequisites**: plan.md, research.md, data-model.md, contracts/, quickstart.md

## Phase 3.1: Setup & Configuration
- [ ] T001 [P] Install dependencies: `composer require web3p/web3.php solana-php/solana-sdk`.
- [ ] T002 [P] Create the `Protocol` enum in `src/Enums/Protocol.php` with cases for `ETH` and `SOL`.
- [ ] T003 Create the configuration file `config/wallets.php` based on the decisions in `research.md`. This file will define the drivers for the wallet manager.

## Phase 3.2: Tests First (TDD) ⚠️ MUST COMPLETE BEFORE 3.3
**CRITICAL: These tests MUST be written and MUST FAIL before ANY implementation**
- [ ] T004 [P] Create a feature test `tests/Feature/CreateEthereumWalletTest.php` that follows the user story from `quickstart.md` to create an Ethereum wallet for a user and assert it's created correctly.
- [ ] T005 [P] Create a feature test `tests/Feature/CreateSolanaWalletTest.php` that follows the user story from `quickstart.md` to create a Solana wallet for a user and assert it's created correctly.
- [ ] T006 [P] Create a unit test `tests/Unit/WalletModelTest.php` to verify the `Wallet` model's casts, fillable attributes, and relationships as defined in `data-model.md`.

## Phase 3.3: Core Implementation (ONLY after tests are failing)
- [ ] T007 Create the database migration for the `wallets` table based on `data-model.md`.
- [ ] T008 Create the `Wallet` Eloquent model in `src/Models/Wallet.php` as defined in `data-model.md`.
- [ ] T009 Create the `HasWallets` trait in `src/Concerns/HasWallets.php` to provide the `wallets()` relationship to owner models.
- [ ] T010 Create the `WalletData` DTO in `src/Contracts/WalletData.php`.
- [ ] T011 Create the `WalletAdapterInterface` in `src/Contracts/WalletAdapterInterface.php`.
- [ ] T012 Create the `EthereumAdapter` in `src/Protocols/Ethereum/WalletAdapter.php` which implements `WalletAdapterInterface` and contains the logic for creating an Ethereum wallet.
- [ ] T013 Create the `SolanaAdapter` in `src/Protocols/Solana/WalletAdapter.php` which implements `WalletAdapterInterface` and contains the logic for creating a Solana wallet.
- [ ] T014 Create the `WalletManager` service in `src/Services/WalletManager.php`. This class will be responsible for resolving the correct protocol adapter based on the configuration.
- [ ] T015 Create the `WalletServiceInterface` in `src/Contracts/WalletServiceInterface.php` and bind its implementation (`WalletManager`) in the service provider.
- [ ] T016 Update `WalletsServiceProvider.php` to register the `WalletManager` as a singleton and bind the `WalletServiceInterface`.
- [ ] T017 Refactor the existing `EthWallet` and `SolWallet` classes to use the new adapter-based system, or remove them if they are fully replaced.

## Phase 3.4: Polish & Documentation
- [ ] T018 [P] Review all new code for adherence to DRY principles and refactor where necessary.
- [ ] T019 [P] Update the main `README.md` to include instructions on creating wallets using the new unified API, based on `quickstart.md`.
- [ ] T020 Run all tests to ensure the entire suite passes.

## Dependencies
- **T001-T003** (Setup) must be done before all other tasks.
- **T004-T006** (Tests) must be done before **T007-T017** (Implementation).
- **T007** (Migration) must be run before tests can pass.
- **T008** (Model) is a dependency for **T009** (Trait) and most implementation tasks.
- **T011** (Interface) is a dependency for **T012** and **T013** (Adapters).
- **T012-T014** are dependencies for tests **T004** and **T005** to pass.

## Parallel Example
The initial test creation can be done in parallel:
```
# Launch T004-T006 together:
Task: "Create feature test tests/Feature/CreateEthereumWalletTest.php"
Task: "Create feature test tests/Feature/CreateSolanaWalletTest.php"
Task: "Create unit test tests/Unit/WalletModelTest.php"
```
The protocol adapter implementations can also be done in parallel:
```
# Launch T012-T013 together:
Task: "Create the EthereumAdapter in src/Protocols/Ethereum/WalletAdapter.php"
Task: "Create the SolanaAdapter in src/Protocols/Solana/WalletAdapter.php"
```
