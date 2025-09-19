# Tasks: Wallet Creation & Sto- [x] Update ManagesExternalWallet Trait
  - Update src/Concerns/ManagesExternalWallet.php to work with new two-table architecture
- [x] Update ManagesWalletPersistence Trait
  - Update src/Concerns/ManagesWalletPersistence.php to handle two-table persistence logicage Refactor (Two-Table Architecture)

**Input**: Design documents from `/Users/drewroberts/Code/laravel-wallets/specs/001-review-refactor-the/`
**Prerequisites**: plan.md, research.md, data-model.md, contracts/, quickstart.md

## Phase 3.1: Setup & Configuration
- [x] T001 [P] Install dependencies: `composer require web3p/web3.php solana-php/solana-sdk`.
- [x] T002 [P] Create the `Protocol` enum in `src/Enums/Protocol.php` with cases for `ETH` and `SOL`.
- [x] T003 [P] Create the `ControlType` enum in `src/Enums/ControlType.php` with cases for `custodial`, `external`, and `shared`.
- [x] T004 Create the configuration file `config/wallets.php` based on the decisions in `research.md`. This file will define the drivers for the wallet manager.

## Phase 3.2: Database Architecture (Two-Table Design)
- [x] T005 Create the database migration for the `wallets` table (global address registry) with fields: id, uuid, protocol, address, control_type, metadata, timestamps. Unique constraint on (protocol, address).
- [x] T006 Create the database migration for the `wallet_owners` table (ownership/control records) with fields: id, uuid, wallet_id, tenant_id, owner_id, owner_type, encrypted_private_key, timestamps. Unique constraint on (wallet_id, tenant_id, owner_id, owner_type).

## Phase 3.3: Tests First (TDD) ⚠️ MUST COMPLETE BEFORE 3.4
**CRITICAL: These tests MUST be written and MUST FAIL before ANY implementation**
- [x] T007 [P] Create a unit test `tests/Unit/WalletModelTest.php` to verify the `Wallet` model's casts, fillable attributes, and relationships for the global registry.
- [x] T008 [P] Create a unit test `tests/Unit/WalletOwnerModelTest.php` to verify the `WalletOwner` model's casts, fillable attributes, and relationships.
- [x] T009 [P] Create a feature test `tests/Feature/CreateEthereumWalletTest.php` that follows the user story from `quickstart.md` to create a custodial Ethereum wallet for a user and assert both wallets and wallet_owners records are created correctly.
- [x] T010 [P] Create a feature test `tests/Feature/CreateSolanaWalletTest.php` that follows the user story from `quickstart.md` to create a custodial Solana wallet for a user and assert both wallets and wallet_owners records are created correctly.
- [x] T011 [P] Create a feature test `tests/Feature/WatchExternalWalletTest.php` to verify external wallet watching creates only wallets records (no wallet_owners) and supports implicit watch relationships.

## Phase 3.4: Core Models (ONLY after tests are failing)
- [x] Create Wallet Model
  - Update src/Models/Wallet.php to implement the global registry model with protocol, address, control_type, metadata, and hasMany owners relationship
- [x] Create WalletOwner Model
  - Create src/Models/WalletOwner.php with belongsTo wallet, polymorphic owner, and encrypted private key handling
- [x] Create WalletService
  - Create src/Services/WalletService.php with methods for creating custodial wallets, adding external wallets, and managing shared wallets
- [ ] T014 Create the `HasWallets` trait in `src/Concerns/HasWallets.php` to provide relationships for both controlled and external wallets.

## Phase 3.5: Service Layer Implementation
- [ ] T015 Create the `WalletData` DTO in `src/Contracts/WalletData.php`.
- [ ] T016 Create the `WalletAdapterInterface` in `src/Contracts/WalletAdapterInterface.php`.
- [ ] T017 Create the `EthereumAdapter` in `src/Protocols/Ethereum/WalletAdapter.php` which implements `WalletAdapterInterface` and contains the logic for creating an Ethereum wallet.
- [ ] T018 Create the `SolanaAdapter` in `src/Protocols/Solana/WalletAdapter.php` which implements `WalletAdapterInterface` and contains the logic for creating a Solana wallet.
- [ ] T019 Create the `WalletManager` service in `src/Services/WalletManager.php`. This class will handle both custodial wallet creation (creating both wallets + wallet_owners records) and external wallet watching (creating only wallets records).
- [ ] T020 Create the `WalletServiceInterface` in `src/Contracts/WalletServiceInterface.php` and bind its implementation (`WalletManager`) in the service provider.
- [ ] T021 Update `WalletsServiceProvider.php` to register the `WalletManager` as a singleton and bind the `WalletServiceInterface`.

## Phase 3.6: Legacy Compatibility & API Design
- [ ] T023 Refactor the existing `EthWallet` and `SolWallet` classes to use the new two-table architecture while maintaining backward compatibility.

## Phase 3.7: Polish & Documentation
- [ ] T024 [P] Review all new code for adherence to DRY principles and refactor where necessary, focusing on the two-table query patterns.
- [ ] T025 [P] Update the main `README.md` to include instructions on creating custodial wallets and watching external wallets using the new unified API, based on `quickstart.md`.
- [ ] T026 Run all tests to ensure the entire suite passes with the new two-table architecture.

## Dependencies
- **T001-T004** (Setup) must be done before all other tasks.
- **T005-T006** (Database migrations) must be run before model and test creation.
- **T007-T011** (Tests) must be done before **T012-T023** (Implementation).
- **T012-T013** (Models) are dependencies for **T014** (Trait) and most service tasks.
- **T016** (Interface) is a dependency for **T017** and **T018** (Adapters).
- **T019-T021** are dependencies for feature tests **T009-T011** to pass.
- **T022** (Unified service) must be done before **T023** (Legacy compatibility).

## Parallel Execution Opportunities
The initial test creation can be done in parallel:
```
# Launch T007-T011 together:
Task: "Create unit test tests/Unit/WalletModelTest.php"
Task: "Create unit test tests/Unit/WalletOwnerModelTest.php" 
Task: "Create feature test tests/Feature/CreateEthereumWalletTest.php"
Task: "Create feature test tests/Feature/CreateSolanaWalletTest.php"
Task: "Create feature test tests/Feature/WatchExternalWalletTest.php"
```
The protocol adapter implementations can also be done in parallel:
```
# Launch T017-T018 together:
Task: "Create the EthereumAdapter in src/Protocols/Ethereum/WalletAdapter.php"
Task: "Create the SolanaAdapter in src/Protocols/Solana/WalletAdapter.php"
```

## Two-Table Architecture Notes
- **wallets table**: Global blockchain address registry, never deleted, supports implicit watch relationships
- **wallet_owners table**: Control records with encrypted private keys, can be deleted without affecting global registry  
- **Custodial wallets**: Records in both tables (control + global registry)
- **firstOrCreate pattern**: Used for external wallet imports to prevent duplicates while allowing multi-tenant watching
