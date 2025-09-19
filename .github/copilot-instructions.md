# Laravel Wallets Package - GitHub Copilot Development Guide

Auto-generated from constitutional requirements and spec-driven development system. Last updated: 2024-12-19

## Project Overview

The Laravel Wallets package is a comprehensive Web3 wallet management solution for Laravel applications. It provides multi-blockchain support with an extensible architecture designed for enterprise-scale applications with security as the primary concern.

## Constitutional Requirements

Based on `.specify/memory/constitution.md` (Version 1.0.1):

### Core Principles (NON-NEGOTIABLE)

#### I. Multi-Blockchain & Extensible Architecture
- **MUST support**: Ethereum (EVM chains) and Solana at launch
- **MUST use**: Driver-based or strategy pattern for extensibility
- **MUST allow**: Future addition of other Layer 1 blockchains

#### II. Core Wallet Functionality
- Wallet creation and management
- Native token sending capabilities
- Contract address/details storage
- Token/NFT querying and details retrieval
- Token/NFT transfers
- Token/NFT holder snapshotting

#### III. Test-First Development (MANDATORY)
- **MUST use**: Pest v4.1 for all testing
- **MUST follow**: Test-Driven Development (TDD) approach
- **MUST achieve**: Full test coverage
- **MUST write**: Tests before implementation

#### IV. Multi-Tenancy Integration
- **MUST integrate**: `roberts/laravel-singledb-tenancy` package
- **MUST ensure**: Proper data scoping to tenants

### Technical Stack
- **Framework**: Laravel (following `spatie/laravel-package-tools` patterns)
- **Language**: PHP 8.4+
- **Testing**: Pest v4.1
- **Architecture**: Driver-based extensible pattern
- **Dependencies**: See composer.json for complete list

### Exclusions
- **NO smart contract deployment logic**
- **NO user interface components** (handled in other packages)

## Spec-Driven Development Process

### GitHub Commands

When issues are assigned to Copilot, **ALWAYS** follow this workflow:

#### 1. Initial Planning Phase
```
/plan [issue details]
```
- Executes `.specify/scripts/bash/setup-plan.sh`
- Loads feature specification from issue
- Generates implementation plan using `.specify/templates/plan-template.md`
- Creates research.md, data-model.md, contracts/, quickstart.md
- Performs constitutional compliance checks
- Updates agent context files

#### 2. Task Generation Phase
```
/tasks [implementation context]
```
- Executes tasks template from `.specify/templates/tasks-template.md`
- Generates numbered, dependency-ordered task list
- Marks parallel-executable tasks with [P]
- Follows TDD ordering (tests before implementation)
- Creates executable task specifications

### Execution Flow

1. **Feature Specification Analysis**
   - Load feature spec from `/specs/[###-feature-name]/spec.md`
   - Extract functional and non-functional requirements
   - Identify user stories and acceptance criteria

2. **Constitutional Compliance Check**
   - Validate against all core principles
   - Document any complexity deviations
   - Ensure security requirements are met

3. **Design Phase**
   - Generate data models with multi-blockchain flexibility
   - Create API contracts following REST patterns
   - Design test scenarios from user stories
   - Plan driver-based architecture components

4. **Task Planning**
   - Create dependency-ordered task list
   - Apply TDD principles (tests first)
   - Mark parallel-executable tasks
   - Validate completeness against contracts and models

## Project Structure

```
laravel-wallets/
├── .github/
│   ├── copilot-instructions.md    # This file
│   └── prompts/                   # GitHub command definitions
├── .specify/
│   ├── memory/constitution.md     # Package constitution
│   ├── templates/                 # Spec-driven templates
│   └── scripts/bash/             # Automation scripts
├── specs/                        # Feature specifications
│   └── [###-feature-name]/       # Individual feature docs
├── src/                          # Package source code
│   ├── Commands/                 # Artisan commands
│   ├── Contracts/                # Interface definitions
│   ├── Models/                   # Eloquent models
│   ├── Protocols/                # Blockchain protocol drivers
│   ├── Services/                 # Business logic services
│   └── Security/                 # Security utilities
├── tests/                        # Pest test suite
│   ├── Feature/                  # Integration tests
│   └── Unit/                     # Unit tests
├── config/                       # Configuration files
└── database/                     # Migrations and factories
```

## Development Commands

### Testing & Quality Assurance
```bash
# Run all tests with Pest
composer test

# Run tests with coverage
composer test-coverage

# Static analysis with PHPStan
composer analyse

# Code formatting with Laravel Pint
composer format
```

### Spec-Driven Development
```bash
# Create new feature specification
.specify/scripts/bash/create-new-feature.sh --json "[feature description]"

# Setup implementation planning
.specify/scripts/bash/setup-plan.sh --json

# Check task prerequisites
.specify/scripts/bash/check-task-prerequisites.sh --json

# Update agent context
.specify/scripts/bash/update-agent-context.sh copilot
```

## Security Considerations

### Private Key Management
- **MUST use**: Laravel's encryption services
- **MUST implement**: Secure key derivation
- **MUST provide**: Multi-signature support options
- **MUST ensure**: Tenant-scoped key isolation

### Multi-Blockchain Security
- **MUST validate**: All transaction parameters
- **MUST implement**: Protocol-specific validations
- **MUST provide**: Secure RPC endpoint management
- **MUST handle**: Cross-chain security considerations

## Code Style & Best Practices

### PHP Standards
- Follow PSR-12 coding standards
- Use Laravel conventions and patterns
- Implement Spatie package best practices
- Utilize PHP 8.4+ features appropriately

### Testing Requirements
- **EVERY feature MUST have tests**
- Write tests BEFORE implementation (TDD)
- Achieve 100% code coverage
- Use Pest's descriptive test syntax
- Test both success and failure scenarios

### Architecture Patterns
- Use driver-based pattern for blockchain protocols
- Implement dependency injection throughout
- Follow SOLID principles
- Utilize Laravel's service container effectively

### Documentation
- Document all public methods and classes
- Provide clear usage examples
- Maintain up-to-date README
- Document security considerations

## Multi-Blockchain Architecture

### Protocol Driver Interface
```php
interface WalletAdapterInterface
{
    public function createWallet(array $options = []): WalletInterface;
    public function getAddress(string $publicKey): string;
    public function signTransaction(array $transaction, string $privateKey): string;
    public function sendTransaction(string $signedTransaction): string;
    // Additional protocol-specific methods
}
```

### Supported Protocols
- **Ethereum & EVM Chains**: Primary implementation
- **Solana**: Secondary implementation
- **Extensible Framework**: For future blockchain additions

## Task Execution Guidelines

### Parallel Task Execution
Tasks marked with [P] can be executed simultaneously:
- Different file modifications
- Independent test creation
- Separate protocol implementations
- Isolated service developments

### Sequential Dependencies
Tasks without [P] must be executed in order:
- Tests before implementation (TDD requirement)
- Models before services
- Services before controllers/commands
- Core functionality before integrations

## Error Handling & Recovery

### Common Issues
- **Missing Prerequisites**: Ensure all dependencies are installed
- **Constitutional Violations**: Review and document deviations
- **Test Failures**: Fix failing tests before proceeding
- **Security Concerns**: Address immediately with security review

### Recovery Procedures
1. **Failed Tests**: Analyze failure, fix implementation, verify success
2. **Architecture Violations**: Refactor to comply with constitutional requirements
3. **Security Issues**: Implement fixes, add security tests, document changes
4. **Performance Problems**: Profile, optimize, add performance tests

## Version Control & Branching

### Branch Naming
- Feature branches: `###-feature-name` (from spec system)
- Bug fixes: `fix-issue-number`
- Security patches: `security-patch-description`

### Commit Standards
- Follow conventional commit format
- Reference issue numbers
- Include test evidence
- Document breaking changes

---

**Constitutional Compliance**: This document reflects Constitution v1.0.1 requirements
**Last Reviewed**: 2024-12-19
**Next Review**: Upon constitutional amendments or major feature additions

<!-- MANUAL ADDITIONS START -->
<!-- Reserved for manual additions between updates -->
<!-- MANUAL ADDITIONS END -->