<?php

use Roberts\LaravelWallets\Contracts\EncryptionServiceInterface;
use Roberts\LaravelWallets\Security\SecureString;
use Roberts\LaravelWallets\Services\EncryptionService;

describe('EncryptionService', function () {

    beforeEach(function () {
        $this->encryptionService = new EncryptionService;
        $this->testData = 'This is secret data that needs encryption';
        $this->binaryData = pack('H*', '0123456789abcdef');
        $this->hmacKey = new SecureString('hmac-key-123456789012345678901234567890');
        $this->masterKey = new SecureString('master-key-12345678901234567890');
    });

    describe('Service Configuration', function () {
        it('implements encryption service interface', function () {
            expect($this->encryptionService)->toBeInstanceOf(EncryptionServiceInterface::class);
        });
    });

    describe('Key Generation', function () {
        it('generates secure key', function () {
            $key = $this->encryptionService->generateKey();

            expect($key)->toBeInstanceOf(SecureString::class);

            $key->access(function (string $keyData) {
                expect(strlen($keyData))->toBe(32); // 256 bits
            });
        });

        it('generates custom length key', function () {
            $key = $this->encryptionService->generateKey(16);

            $key->access(function (string $keyData) {
                expect(strlen($keyData))->toBe(16);
            });
        });

        it('rejects short key generation', function () {
            expect(fn () => $this->encryptionService->generateKey(8))
                ->toThrow(RuntimeException::class, 'Key length must be at least 16 bytes for security');
        });
    });

    describe('Salt Generation', function () {
        it('generates secure salt', function () {
            $salt = $this->encryptionService->generateSalt();

            expect(strlen($salt))->toBe(16);

            // Generate another salt and ensure they're different
            $salt2 = $this->encryptionService->generateSalt();
            expect($salt)->not()->toBe($salt2);
        });

        it('rejects short salt generation', function () {
            expect(fn () => $this->encryptionService->generateSalt(8))
                ->toThrow(RuntimeException::class, 'Salt length must be at least 16 bytes for security');
        });
    });

    describe('Key Derivation', function () {
        it('derives key from master key', function () {
            $salt = $this->encryptionService->generateSalt();

            $derivedKey = $this->encryptionService->deriveKey($this->masterKey, $salt);

            expect($derivedKey)->toBeInstanceOf(SecureString::class);

            $derivedKey->access(function (string $keyData) {
                expect(strlen($keyData))->toBe(32);
            });
        });

        it('derived keys are deterministic', function () {
            $salt = 'consistent-salt-16';

            $key1 = $this->encryptionService->deriveKey($this->masterKey, $salt);
            $key2 = $this->encryptionService->deriveKey($this->masterKey, $salt);

            $key1Data = null;
            $key2Data = null;

            $key1->access(function (string $data) use (&$key1Data) {
                $key1Data = $data;
            });

            $key2->access(function (string $data) use (&$key2Data) {
                $key2Data = $data;
            });

            expect($key1Data)->toBe($key2Data);
        });

        it('rejects weak key derivation', function () {
            $masterKey = new SecureString('master-key');
            $salt = 'short-salt';

            expect(fn () => $this->encryptionService->deriveKey($masterKey, $salt, 10000))
                ->toThrow(RuntimeException::class, 'Salt must be at least 16 bytes for security');
        });

        it('rejects few iterations for key derivation', function () {
            $masterKey = new SecureString('master-key');
            $salt = 'salt-16-bytes-min';

            expect(fn () => $this->encryptionService->deriveKey($masterKey, $salt, 1000))
                ->toThrow(RuntimeException::class, 'Key derivation iterations must be at least 10,000 for security');
        });
    });

    describe('Encryption and Decryption', function () {
        beforeEach(function () {
            $this->key = $this->encryptionService->generateKey();
        });

        it('encrypts and decrypts data', function () {
            $encrypted = $this->encryptionService->encrypt($this->testData, $this->key);

            expect($encrypted)->toBeArray()
                ->toHaveKey('data')
                ->toHaveKey('nonce')
                ->toHaveKey('tag')
                ->toHaveKey('cipher');

            $decrypted = $this->encryptionService->decrypt($encrypted, $this->key);

            expect($decrypted)->toBeInstanceOf(SecureString::class);

            $decrypted->access(function (string $data) {
                expect($data)->toBe($this->testData);
            });
        });

        it('encrypts with additional authenticated data', function () {
            $plaintext = 'Secret message';
            $aad = 'additional-auth-data';

            $encrypted = $this->encryptionService->encrypt($plaintext, $this->key, $aad);
            $decrypted = $this->encryptionService->decrypt($encrypted, $this->key, $aad);

            $decrypted->access(function (string $data) use ($plaintext) {
                expect($data)->toBe($plaintext);
            });
        });

        it('fails decryption with wrong aad', function () {
            $plaintext = 'Secret message';
            $correctAad = 'correct-aad';
            $wrongAad = 'wrong-aad';

            $encrypted = $this->encryptionService->encrypt($plaintext, $this->key, $correctAad);

            expect(fn () => $this->encryptionService->decrypt($encrypted, $this->key, $wrongAad))
                ->toThrow(RuntimeException::class, 'Decryption failed or authentication failed');
        });

        it('fails decryption with wrong key', function () {
            $wrongKey = $this->encryptionService->generateKey();
            $plaintext = 'Secret message';

            $encrypted = $this->encryptionService->encrypt($plaintext, $this->key);

            expect(fn () => $this->encryptionService->decrypt($encrypted, $wrongKey))
                ->toThrow(RuntimeException::class, 'Decryption failed or authentication failed');
        });

        it('handles empty data encryption', function () {
            $encrypted = $this->encryptionService->encrypt('', $this->key);
            $decrypted = $this->encryptionService->decrypt($encrypted, $this->key);

            $decrypted->access(function (string $data) {
                expect($data)->toBe('');
            });
        });

        it('handles binary data encryption', function () {
            $encrypted = $this->encryptionService->encrypt($this->binaryData, $this->key);
            $decrypted = $this->encryptionService->decrypt($encrypted, $this->key);

            $decrypted->access(function (string $data) {
                expect($data)->toBe($this->binaryData);
            });
        });

        it('validates encrypted data structure', function () {
            $invalidEncryptedData = ['data' => 'test']; // Missing nonce and tag

            expect(fn () => $this->encryptionService->decrypt($invalidEncryptedData, $this->key))
                ->toThrow(RuntimeException::class, "Missing required key 'nonce' in encrypted data");
        });
    });

    describe('Key Rotation', function () {
        it('rotates encryption key', function () {
            $oldKey = $this->encryptionService->generateKey();
            $newKey = $this->encryptionService->generateKey();
            $plaintext = 'Data to re-encrypt with new key';

            // Encrypt with old key
            $encryptedWithOld = $this->encryptionService->encrypt($plaintext, $oldKey);

            // Rotate to new key
            $encryptedWithNew = $this->encryptionService->rotateKey($encryptedWithOld, $oldKey, $newKey);

            // Should be able to decrypt with new key
            $decrypted = $this->encryptionService->decrypt($encryptedWithNew, $newKey);

            $decrypted->access(function (string $data) use ($plaintext) {
                expect($data)->toBe($plaintext);
            });

            // Should NOT be able to decrypt rotated data with old key
            expect(fn () => $this->encryptionService->decrypt($encryptedWithNew, $oldKey))
                ->toThrow(RuntimeException::class);
        });
    });

    describe('Hashing Functions', function () {
        it('hashes data', function () {
            $data = 'data to hash';
            $hash = $this->encryptionService->hash($data);

            expect(strlen($hash))->toBe(32); // SHA-256 produces 32 bytes

            // Same data should produce same hash
            $hash2 = $this->encryptionService->hash($data);
            expect($hash)->toBe($hash2);

            // Different data should produce different hash
            $hash3 = $this->encryptionService->hash($data.'different');
            expect($hash)->not()->toBe($hash3);
        });

        it('rejects weak hash algorithm', function () {
            expect(fn () => $this->encryptionService->hash('data', 'md5'))
                ->toThrow(RuntimeException::class, 'Hash algorithm not allowed');
        });
    });

    describe('HMAC and Integrity', function () {
        it('generates and verifies hmac', function () {
            $data = 'data to authenticate';

            $hmac = $this->encryptionService->generateHmac($data, $this->hmacKey);

            expect(strlen($hmac))->toBe(32); // SHA-256 HMAC is 32 bytes

            $isValid = $this->encryptionService->verifyIntegrity($data, $hmac, $this->hmacKey);
            expect($isValid)->toBeTrue();

            // Wrong data should fail verification
            $isInvalid = $this->encryptionService->verifyIntegrity($data.'tampered', $hmac, $this->hmacKey);
            expect($isInvalid)->toBeFalse();
        });

        it('secure comparison', function () {
            $string1 = 'identical-string';
            $string2 = 'identical-string';
            $string3 = 'different-string';

            expect($this->encryptionService->secureCompare($string1, $string2))->toBeTrue();
            expect($this->encryptionService->secureCompare($string1, $string3))->toBeFalse();
        });
    });
});
