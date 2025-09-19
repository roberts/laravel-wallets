<?php

namespace Roberts\LaravelWallets\Contracts;

use Roberts\LaravelWallets\Security\SecureString;

/**
 * Interface for secure key management and cryptographic operations.
 *
 * This interface provides a contract for handling sensitive cryptographic
 * operations while maintaining security best practices.
 */
interface EncryptionServiceInterface
{
    /**
     * Derive a key from a master key using a secure derivation function.
     *
     * @param  SecureString  $masterKey  The master key for derivation
     * @param  string  $salt  Salt for the key derivation
     * @param  int  $iterations  Number of iterations for key stretching
     * @return SecureString The derived key
     */
    public function deriveKey(SecureString $masterKey, string $salt, int $iterations = 100000): SecureString;

    /**
     * Encrypt data using authenticated encryption.
     *
     * @param  string  $data  The data to encrypt
     * @param  SecureString  $key  The encryption key
     * @param  string|null  $additionalData  Additional authenticated data (AAD)
     * @return array{data: string, nonce: string, tag: string, cipher: string, aad: string|null} Encrypted data with metadata
     */
    public function encrypt(string $data, SecureString $key, ?string $additionalData = null): array;

    /**
     * Decrypt data that was encrypted with authenticated encryption.
     *
     * @param  array{data: string, nonce: string, tag: string, cipher?: string, aad?: string|null}  $encryptedData  The encrypted data array
     * @param  SecureString  $key  The decryption key
     * @param  string|null  $additionalData  Additional authenticated data (AAD)
     * @return SecureString The decrypted data
     *
     * @throws \Exception If decryption fails or authentication fails
     */
    public function decrypt(array $encryptedData, SecureString $key, ?string $additionalData = null): SecureString;

    /**
     * Generate a cryptographically secure random key.
     *
     * @param  int  $length  The length of the key in bytes
     * @return SecureString The generated key
     */
    public function generateKey(int $length = 32): SecureString;

    /**
     * Generate a cryptographically secure random salt.
     *
     * @param  int  $length  The length of the salt in bytes
     * @return string The generated salt
     */
    public function generateSalt(int $length = 16): string;

    /**
     * Hash data using a secure hash function.
     *
     * @param  string  $data  The data to hash
     * @param  string  $algorithm  The hash algorithm to use
     * @return string The hash digest
     */
    public function hash(string $data, string $algorithm = 'sha256'): string;

    /**
     * Verify data integrity using HMAC.
     *
     * @param  string  $data  The data to verify
     * @param  string  $expectedHmac  The expected HMAC
     * @param  SecureString  $key  The HMAC key
     * @return bool True if the HMAC is valid
     */
    public function verifyIntegrity(string $data, string $expectedHmac, SecureString $key): bool;

    /**
     * Generate HMAC for data integrity verification.
     *
     * @param  string  $data  The data to generate HMAC for
     * @param  SecureString  $key  The HMAC key
     * @param  string  $algorithm  The HMAC algorithm
     * @return string The HMAC digest
     */
    public function generateHmac(string $data, SecureString $key, string $algorithm = 'sha256'): string;

    /**
     * Rotate an encryption key by re-encrypting data with a new key.
     *
     * @param  array{data: string, nonce: string, tag: string, cipher?: string, aad?: string|null}  $encryptedData  The data encrypted with the old key
     * @param  SecureString  $oldKey  The old encryption key
     * @param  SecureString  $newKey  The new encryption key
     * @param  string|null  $additionalData  Additional authenticated data
     * @return array{data: string, nonce: string, tag: string, cipher: string, aad: string|null} The data re-encrypted with the new key
     */
    public function rotateKey(array $encryptedData, SecureString $oldKey, SecureString $newKey, ?string $additionalData = null): array;

    /**
     * Securely compare two strings in constant time to prevent timing attacks.
     *
     * @param  string  $known  The known string
     * @param  string  $user  The user-provided string
     * @return bool True if the strings match
     */
    public function secureCompare(string $known, string $user): bool;
}
