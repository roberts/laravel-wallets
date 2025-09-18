
# Implementation Plan: Wallet Creation & Storage Refactor

**Branch**: `001-review-refactor-the` | **Date**: 2025-09-18 | **Spec**: [spec.md](./spec.md)
**Input**: Feature specification from `/Users/drewroberts/Code/laravel-wallets/specs/001-review-refactor-the/spec.md`

## Execution Flow (/plan command scope)
```
1. Load feature spec from Input path
   → ✅ Completed
2. Fill Technical Context (scan for NEEDS CLARIFICATION)
   → ✅ Completed
3. Fill the Constitution Check section based on the content of the constitution document.
   → ✅ Completed
4. Evaluate Constitution Check section below
   → ✅ Completed
5. Execute Phase 0 → research.md
   → ✅ In Progress
6. Execute Phase 1 → contracts, data-model.md, quickstart.md, agent-specific template file (e.g., `CLAUDE.md` for Claude Code, `.github/copilot-instructions.md` for GitHub Copilot, `GEMINI.md` for Gemini CLI, `QWEN.md` for Qwen Code or `AGENTS.md` for opencode).
7. Re-evaluate Constitution Check section
8. Plan Phase 2 → Describe task generation approach (DO NOT create tasks.md)
9. STOP - Ready for /tasks command
```

**IMPORTANT**: The /plan command STOPS at step 7. Phases 2-4 are executed by other commands:
- Phase 2: /tasks command creates tasks.md
- Phase 3-4: Implementation execution (manual or via tools)

## Summary
This plan outlines the refactoring of the wallet creation and storage mechanism to support multiple blockchains using a two-table architecture, starting with Ethereum and Solana. The system separates concerns between a global blockchain address registry (`wallets` table) and ownership/control records (`wallet_owners` table). This design enables implicit watch relationships for external wallets while maintaining clear control boundaries for custodial wallets. The core goal is to establish a unified, extensible, and secure system that adheres to DRY principles and is backed by a comprehensive test suite, following a Test-Driven Development (TDD) approach as mandated by the project constitution.

## Technical Context
**Language/Version**: PHP ^8.4 (from `composer.json`)
**Primary Dependencies**: `laravel/framework`, `spatie/laravel-package-tools`, `roberts/laravel-singledb-tenancy`, `kornrunner/keccak`, `web3p/web3.php`, `solana-php/solana-sdk`
**Storage**: Database (via Laravel Eloquent) - Two-table architecture: `wallets` (global registry) + `wallet_owners` (control records)
**Testing**: Pest (TDD is mandatory)
**Target Platform**: Laravel applications
**Project Type**: Single project (Laravel package)
**Constraints**: Must follow DRY principles and be extensible for future blockchains. Two-table design enables global address uniqueness with tenant-scoped control.
**Scale/Scope**: Initial support for Ethereum and Solana. The design should accommodate adding more protocols.

### Architectural Decisions
- **Two-Table Design**: `wallets` table serves as global blockchain address registry (never deleted), `wallet_owners` table contains control/ownership records with private keys (deletable)
- **Implicit Watch Relationships**: External wallets exist in `wallets` table only, enabling all tenants to watch without explicit relationships
- **Control Type Enum**: `custodial` (system-generated with keys), `external` (imported watch-only), `shared` (multi-signature)
- **Global Address Uniqueness**: Unique constraint on (protocol, address) prevents duplicate blockchain addresses
- **firstOrCreate Pattern**: External wallet imports use firstOrCreate to prevent duplicates while enabling multi-tenant watching

### Unresolved Questions from Spec
- **FR-007**: What authentication/authorization mechanism should protect private key access?
- **Key Entities**: Should protocol-specific configuration like RPC endpoints be part of the `Blockchain/Protocol` model or stored elsewhere (e.g., config file)?

## Constitution Check
*GATE: Must pass before Phase 0 research. Re-check after Phase 1 design.*

- **Multi-Blockchain & Extensible Architecture**: ✅ Plan aligns. The core of the plan is to create a driver-based architecture.
- **Core Wallet Functionality**: ✅ Plan aligns. This feature focuses on the "wallet creation" aspect.
- **Test-First (NON-NEGOTIABLE)**: ✅ Plan aligns. The user explicitly requested a "thorough test suite" and the constitution mandates TDD with Pest.
- **Multi-Tenancy**: ✅ Plan aligns. The data model will need to incorporate tenancy, likely via a trait on the Eloquent models.
- **Technical Stack & Exclusions**: ✅ Plan aligns. The plan uses the prescribed stack and does not include UI or smart contract deployment.
- **Database Design**: ✅ Plan aligns. The data model phase will address the need for a flexible schema.

## Project Structure

### Documentation (this feature)
```
specs/001-review-refactor-the/
├── plan.md              # This file (/plan command output)
├── research.md          # Phase 0 output (/plan command)
├── data-model.md        # Phase 1 output (/plan command)
├── quickstart.md        # Phase 1 output (/plan command)
├── contracts/           # Phase 1 output (/plan command)
└── tasks.md             # Phase 2 output (/tasks command - NOT created by /plan)
```

### Source Code (repository root)
```
# Option 1: Single project (DEFAULT)
src/
├── Models/              # Eloquent models (e.g., Wallet)
├── Contracts/           # Interfaces (e.g., WalletAdapter)
├── Protocols/           # Blockchain-specific logic (e.g., Ethereum, Solana adapters)
├── Services/            # Core business logic (e.g., WalletService)
├── Enums/               # Protocol enums
├── Facades/             # Laravel Facades
└── WalletsServiceProvider.php # Service provider
tests/
├── Feature/
├── Unit/
└── Pest.php
```

**Structure Decision**: **Option 1: Single project**. This is a Laravel package, so a single, well-organized `src` directory is appropriate.

## Phase 0: Outline & Research
1. **Extract unknowns from Technical Context** above:
   - Research secure storage and retrieval patterns for private keys in a Laravel application.
   - Research best practices for implementing a driver-based (strategy) pattern in Laravel for different blockchain protocols.
   - Decide on the location for protocol-specific configuration (config file vs. database).
   - Define an authentication/authorization strategy for accessing private keys.

2. **Generate and dispatch research agents**:
   ```
   For each unknown in Technical Context:
     Task: "Research {unknown} for {feature context}"
   For each technology choice:
     Task: "Find best practices for {tech} in {domain}"
   ```

3. **Consolidate findings** in `research.md` using format:
   - Decision: [what was chosen]
   - Rationale: [why chosen]
   - Alternatives considered: [what else evaluated]

**Output**: research.md with all NEEDS CLARIFICATION resolved

## Phase 1: Design & Contracts
*Prerequisites: research.md complete*

1. **Extract entities from feature spec** → `data-model.md`:
   - Define the two-table architecture: `wallets` table (global address registry) and `wallet_owners` table (control/ownership records)
   - Define the `Wallet` model schema with fields for `protocol`, `address`, `control_type`, and `metadata`. Global uniqueness on (protocol, address).
   - Define the `WalletOwner` model schema with fields for `wallet_id`, `tenant_id`, `owner_id`, `owner_type`, and `encrypted_private_key`.
   - Define relationships between `Wallet`, `WalletOwner`, and User models enabling both controlled and watch-only access patterns.
   - Document the implicit watch relationship pattern (wallets without corresponding wallet_owners records).

2. **Generate API contracts** from functional requirements:
   - Define PHP interfaces for the wallet creation and management system supporting both custodial and watch-only patterns.
   - `WalletAdapterInterface`: Defines methods like `createWallet()`, `getAddress()`, `getPrivateKey()`.
   - `WalletServiceInterface`: Defines the public-facing API including `createCustodialWallet()` and `watchExternalWallet()` methods.
   - Output these interfaces to `/contracts/`.

3. **Generate contract tests** from contracts:
   - Create Pest tests for the defined interfaces to ensure any implementation will adhere to the two-table contract pattern.
   - Include tests for both custodial wallet creation (both tables) and external wallet watching (wallets table only).

4. **Extract test scenarios** from user stories:
   - Create feature tests in Pest for custodial wallet creation (ETH/SOL) asserting both wallets and wallet_owners records.
   - Create feature tests for external wallet watching using firstOrCreate pattern.
   - Create unit tests for both Wallet and WalletOwner models and their relationships.
   - A `quickstart.md` will demonstrate both custodial creation and external watching patterns.

5. **Update agent file incrementally** (O(1) operation):
   - Run `.specify/scripts/bash/update-agent-context.sh copilot` for your AI assistant
   - If exists: Add only NEW tech from current plan
   - Preserve manual additions between markers
   - Update recent changes (keep last 3)
   - Keep under 150 lines for token efficiency
   - Output to repository root

**Output**: data-model.md (two-table design), /contracts/*, failing tests (custodial + watch-only patterns), quickstart.md (both usage patterns), agent-specific file

## Phase 2: Task Planning Approach
*This section describes what the /tasks command will do - DO NOT execute during /plan*

**Task Generation Strategy**:
- Load `.specify/templates/tasks-template.md` as base
- Generate tasks from Phase 1 design docs (contracts, two-table data model, quickstart patterns)
- Each table → migration creation task
- Each model → model creation + unit test task [P] 
- Each contract → contract test task [P]
- Each user story → integration test task (custodial creation + external watching)
- Implementation tasks to make tests pass
- Service layer tasks for both custodial and watch-only functionality

**Ordering Strategy**:
- TDD order: Tests before implementation 
- Dependency order: Migrations → Models → Services → Integration
- Two-table dependencies: wallets table → wallet_owners table → models → relationships
- Mark [P] for parallel execution (independent files/tests)

**Estimated Output**: 30-35 numbered, ordered tasks in tasks.md (increased from single-table due to two-table complexity)

**IMPORTANT**: This phase is executed by the /tasks command, NOT by /plan

## Phase 3+: Future Implementation
*These phases are beyond the scope of the /plan command*

**Phase 3**: Task execution (/tasks command creates tasks.md)  
**Phase 4**: Implementation (execute tasks.md following constitutional principles)  
**Phase 5**: Validation (run tests, execute quickstart.md, performance validation)

## Complexity Tracking
*Fill ONLY if Constitution Check has violations that must be justified*

| Violation | Why Needed | Simpler Alternative Rejected Because |
|-----------|------------|-------------------------------------|
| [e.g., 4th project] | [current need] | [why 3 projects insufficient] |
| [e.g., Repository pattern] | [specific problem] | [why direct DB access insufficient] |


## Progress Tracking
*This checklist is updated during execution flow*

**Phase Status**:
- [ ] Phase 0: Research complete (/plan command)
- [ ] Phase 1: Design complete (/plan command)
- [ ] Phase 2: Task planning complete (/plan command - describe approach only)
- [ ] Phase 3: Tasks generated (/tasks command)
- [ ] Phase 4: Implementation complete
- [ ] Phase 5: Validation passed

**Gate Status**:
- [ ] Initial Constitution Check: PASS
- [ ] Post-Design Constitution Check: PASS
- [ ] All NEEDS CLARIFICATION resolved
- [ ] Complexity deviations documented

---
*Based on Constitution v2.1.1 - See `/memory/constitution.md`*
