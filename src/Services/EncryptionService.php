<?php

namespace Roberts\LaravelWallets\Services;

use Roberts\LaravelWallets\Contracts\EncryptionServiceInterface;
use Roberts\LaravelWallets\Security\SecureString;
use RuntimeException;

/**
 * Secure implementation of encryption services using modern cryptographic practices.
 *
 * This implementation uses authenticated encryption, secure key derivation,
 * and constant-time operations to maintain security best practices.
 */
class EncryptionService implements EncryptionServiceInterface
{
    private const DEFAULT_CIPHER = 'aes-256-gcm';

    private const KEY_LENGTH = 32; // 256 bits

    private const NONCE_LENGTH = 12; // 96 bits for GCM

    private const TAG_LENGTH = 16; // 128 bits for GCM

    public function deriveKey(SecureString $masterKey, string $salt, int $iterations = 100000): SecureString
    {
        if ($iterations < 10000) {
            throw new RuntimeException('Key derivation iterations must be at least 10,000 for security');
        }

        if (strlen($salt) < 16) {
            throw new RuntimeException('Salt must be at least 16 bytes for security');
        }

        return $masterKey->withSecureCallback(function (string $keyMaterial) use ($salt, $iterations): SecureString {
            $derivedKey = hash_pbkdf2('sha256', $keyMaterial, $salt, $iterations, self::KEY_LENGTH, true);

            if ($derivedKey === false) {
                throw new RuntimeException('Key derivation failed');
            }

            return new SecureString($derivedKey);
        });
    }

    /**
     * Encrypt data using authenticated encryption with optional additional data.
     *
     * @param  string  $data  The data to encrypt
     * @param  SecureString  $key  The encryption key
     * @param  string|null  $additionalData  Optional additional authenticated data
     * @return array{data: string, nonce: string, tag: string, cipher: string, aad: string|null} Encrypted data components
     */
    public function encrypt(string $data, SecureString $key, ?string $additionalData = null): array
    {
        return $key->withSecureCallback(function (string $keyMaterial) use ($data, $additionalData): array {
            if (strlen($keyMaterial) !== self::KEY_LENGTH) {
                throw new RuntimeException('Invalid key length for encryption');
            }

            $nonce = random_bytes(self::NONCE_LENGTH);
            $tag = '';

            $encrypted = openssl_encrypt(
                $data,
                self::DEFAULT_CIPHER,
                $keyMaterial,
                OPENSSL_RAW_DATA,
                $nonce,
                $tag,
                $additionalData ?? '',
                self::TAG_LENGTH
            );

            if ($encrypted === false) {
                throw new RuntimeException('Encryption failed: '.openssl_error_string());
            }

            return [
                'data' => base64_encode($encrypted),
                'nonce' => base64_encode($nonce),
                'tag' => base64_encode($tag),
                'cipher' => self::DEFAULT_CIPHER,
                'aad' => $additionalData,
            ];
        });
    }

    public function decrypt(array $encryptedData, SecureString $key, ?string $additionalData = null): SecureString
    {
        return $key->withSecureCallback(function (string $keyMaterial) use ($encryptedData, $additionalData): SecureString {
            $this->validateEncryptedDataStructure($encryptedData);

            if (strlen($keyMaterial) !== self::KEY_LENGTH) {
                throw new RuntimeException('Invalid key length for decryption');
            }

            $encrypted = base64_decode($encryptedData['data'], true);
            $nonce = base64_decode($encryptedData['nonce'], true);
            $tag = base64_decode($encryptedData['tag'], true);

            if ($encrypted === false || $nonce === false || $tag === false) {
                throw new RuntimeException('Invalid base64 encoding in encrypted data');
            }

            // Use AAD from encrypted data if not provided as parameter
            $aad = $additionalData ?? ($encryptedData['aad'] ?? '');

            $decrypted = openssl_decrypt(
                $encrypted,
                $encryptedData['cipher'] ?? self::DEFAULT_CIPHER,
                $keyMaterial,
                OPENSSL_RAW_DATA,
                $nonce,
                $tag,
                $aad
            );

            if ($decrypted === false) {
                throw new RuntimeException('Decryption failed or authentication failed');
            }

            return new SecureString($decrypted);
        });
    }

    public function generateKey(int $length = 32): SecureString
    {
        if ($length < 16) {
            throw new RuntimeException('Key length must be at least 16 bytes for security');
        }

        $key = random_bytes($length);

        return new SecureString($key);
    }

    public function generateSalt(int $length = 16): string
    {
        if ($length < 16) {
            throw new RuntimeException('Salt length must be at least 16 bytes for security');
        }

        return random_bytes($length);
    }

    public function hash(string $data, string $algorithm = 'sha256'): string
    {
        $allowedAlgorithms = ['sha256', 'sha384', 'sha512', 'sha3-256', 'sha3-384', 'sha3-512'];

        if (! in_array($algorithm, $allowedAlgorithms)) {
            throw new RuntimeException('Hash algorithm not allowed. Use one of: '.implode(', ', $allowedAlgorithms));
        }

        $hash = hash($algorithm, $data, true);

        if ($hash === false) {
            throw new RuntimeException('Hashing failed');
        }

        return $hash;
    }

    public function verifyIntegrity(string $data, string $expectedHmac, SecureString $key): bool
    {
        return $key->access(function (string $keyData) use ($data, $expectedHmac) {
            $computedHmac = hash_hmac('sha256', $data, $keyData, true);

            return $this->secureCompare($expectedHmac, $computedHmac);
        });
    }

    public function generateHmac(string $data, SecureString $key, string $algorithm = 'sha256'): string
    {
        $allowedAlgorithms = ['sha256', 'sha384', 'sha512'];

        if (! in_array($algorithm, $allowedAlgorithms)) {
            throw new RuntimeException('HMAC algorithm not allowed. Use one of: '.implode(', ', $allowedAlgorithms));
        }

        return $key->access(function (string $keyData) use ($data, $algorithm) {
            $hmac = hash_hmac($algorithm, $data, $keyData, true);

            if ($hmac === false) {
                throw new RuntimeException('HMAC generation failed');
            }

            return $hmac;
        });
    }

    public function rotateKey(array $encryptedData, SecureString $oldKey, SecureString $newKey, ?string $additionalData = null): array
    {
        // First decrypt with the old key
        $decryptedData = $this->decrypt($encryptedData, $oldKey, $additionalData);

        // Then encrypt with the new key
        return $decryptedData->access(function (string $plaintext) use ($newKey, $additionalData) {
            return $this->encrypt($plaintext, $newKey, $additionalData);
        });
    }

    public function secureCompare(string $known, string $user): bool
    {
        // Use hash_equals for constant-time comparison
        return hash_equals($known, $user);
    }

    /**
     * Validate the structure of encrypted data array.
     */
    /**
     * Validate that encrypted data has the required structure.
     *
     * @param  array<string, mixed>  $encryptedData
     *
     * @throws RuntimeException
     */
    private function validateEncryptedDataStructure(array $encryptedData): void
    {
        $requiredKeys = ['data', 'nonce', 'tag'];

        foreach ($requiredKeys as $key) {
            if (! isset($encryptedData[$key])) {
                throw new RuntimeException("Missing required key '{$key}' in encrypted data");
            }

            if (! is_string($encryptedData[$key])) {
                throw new RuntimeException("Key '{$key}' must be a string in encrypted data");
            }
        }
    }
}
