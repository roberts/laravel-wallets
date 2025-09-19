<?php

namespace Roberts\LaravelWallets\Contracts;

use Roberts\LaravelWallets\Security\SecureWalletData;

/**
 * Comprehensive security service for wallet operations.
 *
 * This interface defines the contract for validating inputs,
 * checking permissions, auditing operations, and managing
 * security configuration across the wallet system.
 */
interface SecurityServiceInterface
{
    /**
     * Validate a wallet address for the given protocol.
     *
     * @param  string  $address  The wallet address to validate
     * @param  string  $protocol  The protocol identifier (ETH, SOL, etc.)
     * @return bool True if the address is valid
     */
    public function validateAddress(string $address, string $protocol): bool;

    /**
     * Validate a private key for the given protocol.
     *
     * @param  string  $privateKey  The private key to validate
     * @param  string  $protocol  The protocol identifier
     * @return bool True if the private key is valid
     */
    public function validatePrivateKey(string $privateKey, string $protocol): bool;

    /**
     * Check if the current user can perform the specified wallet operation.
     *
     * @param  string  $operation  The operation being attempted
     * @param  array<string, mixed>  $context  Additional context for the permission check
     * @return bool True if the operation is permitted
     */
    public function canPerformOperation(string $operation, array $context = []): bool;

    /**
     * Audit a wallet operation for security logging.
     *
     * @param  string  $operation  The operation being performed
     * @param  array<string, mixed>  $data  Relevant data for the operation
     * @param  string|null  $outcome  The outcome (success/failure/error)
     */
    public function auditOperation(string $operation, array $data, ?string $outcome = null): void;

    /**
     * Check if the current request should be rate limited.
     *
     * @param  string  $operation  The operation being performed
     * @param  string|null  $identifier  Optional identifier for rate limiting
     * @return bool True if the request should be allowed
     */
    public function checkRateLimit(string $operation, ?string $identifier = null): bool;

    /**
     * Detect potentially suspicious activity.
     *
     * @param  string  $operation  The operation being performed
     * @param  array<string, mixed>  $context  Context data for analysis
     * @return array<int, string> Array of security warnings/flags
     */
    public function detectSuspiciousActivity(string $operation, array $context): array;

    /**
     * Sanitize input data for security.
     *
     * @param  mixed  $input  The input to sanitize
     * @param  string  $type  The expected type/format
     * @return mixed The sanitized input
     */
    public function sanitizeInput(mixed $input, string $type): mixed;

    /**
     * Get security configuration for a specific context.
     *
     * @param  string  $context  The configuration context
     * @return array<string, mixed> The security configuration
     */
    public function getSecurityConfig(string $context): array;

    /**
     * Validate wallet data integrity and security.
     *
     * @param  SecureWalletData  $walletData  The wallet data to validate
     * @return array<int, string> Array of validation results/errors
     */
    public function validateWalletData(SecureWalletData $walletData): array;

    /**
     * Check if an operation requires additional security measures.
     *
     * @param  string  $operation  The operation being performed
     * @param  array<string, mixed>  $context  Context for the security check
     * @return array<int, string> Required additional security measures
     */
    public function getRequiredSecurityMeasures(string $operation, array $context): array;
}
