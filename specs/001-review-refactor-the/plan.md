
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
This plan outlines the refactoring of the wallet creation and storage mechanism to support multiple blockchains, starting with Ethereum and Solana. The core goal is to establish a unified, extensible, and secure system that adheres to DRY principles and is backed by a comprehensive test suite, following a Test-Driven Development (TDD) approach as mandated by the project constitution.

## Technical Context
**Language/Version**: PHP ^8.4 (from `composer.json`)
**Primary Dependencies**: `laravel/framework`, `spatie/laravel-package-tools`, `roberts/laravel-singledb-tenancy`, `kornrunner/keccak`, `web3p/web3.php`, `solana-php/solana-sdk`
**Storage**: Database (via Laravel Eloquent)
**Testing**: Pest (TDD is mandatory)
**Target Platform**: Laravel applications
**Project Type**: Single project (Laravel package)
**Constraints**: Must follow DRY principles and be extensible for future blockchains.
**Scale/Scope**: Initial support for Ethereum and Solana. The design should accommodate adding more protocols.

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
   - Define the `Wallet` model schema, including fields for `address`, `encrypted_private_key`, `protocol`, and `tenant_id`.
   - Define the relationship between `Wallet` and the `User` (or other ownable) model.

2. **Generate API contracts** from functional requirements:
   - Define PHP interfaces for the wallet creation and management system.
   - `WalletAdapterInterface`: Defines methods like `createWallet()`, `getAddress()`, `getPrivateKey()`.
   - `WalletServiceInterface`: Defines the public-facing API for the package, e.g., `create(Protocol $protocol, Model $owner)`.
   - Output these interfaces to `/contracts/`.

3. **Generate contract tests** from contracts:
   - Create Pest tests for the defined interfaces to ensure any implementation will adhere to the contract. These will initially fail.

4. **Extract test scenarios** from user stories:
   - Create feature tests in Pest that follow the user stories (e.g., creating an ETH wallet, creating a SOL wallet).
   - A `quickstart.md` will be generated to demonstrate the primary usage patterns based on these tests.

5. **Update agent file incrementally** (O(1) operation):
   - Run `.specify/scripts/bash/update-agent-context.sh copilot` for your AI assistant
   - If exists: Add only NEW tech from current plan
   - Preserve manual additions between markers
   - Update recent changes (keep last 3)
   - Keep under 150 lines for token efficiency
   - Output to repository root

**Output**: data-model.md, /contracts/*, failing tests, quickstart.md, agent-specific file

## Phase 2: Task Planning Approach
*This section describes what the /tasks command will do - DO NOT execute during /plan*

**Task Generation Strategy**:
- Load `.specify/templates/tasks-template.md` as base
- Generate tasks from Phase 1 design docs (contracts, data model, quickstart)
- Each contract → contract test task [P]
- Each entity → model creation task [P] 
- Each user story → integration test task
- Implementation tasks to make tests pass

**Ordering Strategy**:
- TDD order: Tests before implementation 
- Dependency order: Models before services before UI
- Mark [P] for parallel execution (independent files)

**Estimated Output**: 25-30 numbered, ordered tasks in tasks.md

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
