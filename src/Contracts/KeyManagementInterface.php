<?php

namespace Roberts\LaravelWallets\Contracts;

use Roberts\LaravelWallets\Security\SecureString;

/**
 * Interface for managing encryption keys in the wallet system.
 *
 * This interface provides methods for secure key storage, retrieval,
 * rotation, and lifecycle management within the wallet context.
 */
interface KeyManagementInterface
{
    /**
     * Store a key securely with optional metadata.
     *
     * @param  string  $keyId  Unique identifier for the key
     * @param  SecureString  $keyData  The key data to store
     * @param  array<string, mixed>  $metadata  Optional metadata associated with the key
     * @return bool True if the key was stored successfully
     */
    public function storeKey(string $keyId, SecureString $keyData, array $metadata = []): bool;

    /**
     * Retrieve an encryption key securely.
     *
     * @param  string  $keyId  The unique identifier for the key
     * @return SecureString|null The key if found, null otherwise
     */
    public function retrieveKey(string $keyId): ?SecureString;

    /**
     * Check if a key exists.
     *
     * @param  string  $keyId  The unique identifier for the key
     * @return bool True if the key exists
     */
    public function keyExists(string $keyId): bool;

    /**
     * Rotate a key by generating a new key and updating references.
     *
     * @param  string  $keyId  The unique identifier for the key to rotate
     * @return SecureString The new key
     */
    public function rotateKey(string $keyId): SecureString;

    /**
     * Delete a key securely.
     *
     * @param  string  $keyId  The unique identifier for the key to delete
     * @return bool True if the key was deleted successfully
     */
    public function deleteKey(string $keyId): bool;

    /**
     * Get metadata associated with a key.
     *
     * @param  string  $keyId  The key identifier
     * @return array<string, mixed> The key metadata
     *
     * @throws \Exception If the key is not found
     */
    public function getKeyMetadata(string $keyId): array;

    /**
     * List all stored keys.
     *
     * @return array<string, array{id: string, created_at: string, metadata: array<string, mixed>}> Array of key information
     */
    public function listKeys(): array;

    /**
     * Generate a master key for wallet encryption.
     *
     * @param  string  $purpose  The purpose of the master key (e.g., 'wallet_encryption')
     * @return SecureString The generated master key
     */
    public function generateMasterKey(string $purpose): SecureString;

    /**
     * Derive a specific key from a master key.
     *
     * @param  string  $masterKeyId  The master key identifier
     * @param  string  $derivationPath  The derivation path/context
     * @param  string  $salt  Salt for key derivation
     * @return SecureString The derived key
     */
    public function deriveKey(string $masterKeyId, string $derivationPath, string $salt): SecureString;

    /**
     * Backup key metadata (without the actual keys) for recovery purposes.
     *
     * @return array<string, mixed> Backup data containing key metadata
     */
    public function backupKeyMetadata(): array;

    /**
     * Restore key metadata from backup (keys must be restored separately).
     *
     * @param  array<string, mixed>  $backupData  The backup data to restore
     * @return bool True if restoration was successful
     */
    public function restoreKeyMetadata(array $backupData): bool;

    /**
     * Get the current version/generation of a key for tracking purposes.
     *
     * @param  string  $keyId  The unique identifier for the key
     * @return int|null The key version/generation, null if key not found
     */
    public function getKeyVersion(string $keyId): ?int;

    /**
     * Archive old key versions during rotation (for gradual migration).
     *
     * @param  string  $keyId  The unique identifier for the key
     * @param  int  $versionsToKeep  Number of old versions to keep
     * @return bool True if archival was successful
     */
    public function archiveOldKeyVersions(string $keyId, int $versionsToKeep = 1): bool;
}
