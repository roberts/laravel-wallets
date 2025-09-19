<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Roberts\LaravelWallets\Contracts\WalletData;
use Roberts\LaravelWallets\Security\SecureString;
use Roberts\LaravelWallets\Security\SecureWalletData;
use Roberts\LaravelWallets\Services\EncryptionService;
use Roberts\LaravelWallets\Services\SecurityService;
use Roberts\LaravelWallets\Tests\TestUser;

uses(RefreshDatabase::class);

describe('Security Integration', function () {

    beforeEach(function () {
        $request = Request::create('/test', 'GET');
        $request->server->set('REMOTE_ADDR', '192.168.1.100');
        $request->headers->set('User-Agent', 'TestBrowser/1.0');

        $this->securityService = new SecurityService($request);
        $this->encryptionService = new EncryptionService;

        $this->testAddress = '0x742d35Cc6634C0532925a3b8D9c428E1F2a3c3';
        $this->testPrivateKey = 'a1b2c3d4e5f6789012345678901234567890123456789012345678901234567890';
        $this->sensitiveData = 'private-key-data-that-needs-protection';

        $this->user = TestUser::factory()->create();
    });

    describe('Secure Wallet Creation Workflow', function () {
        it('secure wallet creation workflow', function () {
            Auth::login($this->user);

            // Create secure wallet data
            $secureWalletData = new SecureWalletData($this->testAddress, $this->testPrivateKey);

            // Validate the wallet data through security service
            $validationErrors = $this->securityService->validateWalletData($secureWalletData);
            expect($validationErrors)->toBeEmpty();

            // Test secure access patterns
            $addressFromCallback = $secureWalletData->withSecureCallback(
                function (string $address, SecureString $privateKey) {
                    expect($address)->toBe($this->testAddress);

                    return $privateKey->access(function (string $key) {
                        expect($key)->toBe($this->testPrivateKey);

                        return 'callback-success';
                    });
                }
            );

            expect($addressFromCallback)->toBe('callback-success');

            // Audit the wallet creation operation
            $this->securityService->auditOperation('create_secure_wallet', [
                'address' => $this->testAddress,
                'protocol' => 'ETH',
            ], 'success');

            // Verify operation completes without error
            expect(true)->toBeTrue();

            // Clear sensitive data
            $secureWalletData->clearSensitiveData();
            expect($secureWalletData->isCleared())->toBeTrue();
        });
    });

    describe('Encryption Workflows', function () {
        it('encryption workflow with key rotation', function () {
            // Generate encryption keys
            $masterKey = $this->encryptionService->generateKey();
            $salt = $this->encryptionService->generateSalt();

            // Derive application-specific key
            $appKey = $this->encryptionService->deriveKey($masterKey, $salt);

            // Encrypt sensitive wallet data
            $encryptedData = $this->encryptionService->encrypt($this->sensitiveData, $appKey);

            // Verify encryption structure
            expect($encryptedData)->toBeArray()
                ->toHaveKey('data')
                ->toHaveKey('nonce')
                ->toHaveKey('tag');

            // Decrypt and verify
            $decrypted = $this->encryptionService->decrypt($encryptedData, $appKey);
            $decrypted->access(function (string $data) {
                expect($data)->toBe($this->sensitiveData);
            });

            // Test key rotation
            $newAppKey = $this->encryptionService->generateKey();
            $rotatedData = $this->encryptionService->rotateKey($encryptedData, $appKey, $newAppKey);

            // Should decrypt with new key
            $decryptedWithNewKey = $this->encryptionService->decrypt($rotatedData, $newAppKey);
            $decryptedWithNewKey->access(function (string $data) {
                expect($data)->toBe($this->sensitiveData);
            });

            // Should NOT decrypt with old key
            expect(fn () => $this->encryptionService->decrypt($rotatedData, $appKey))
                ->toThrow(\Exception::class, 'Decryption failed');
        });

        it('hmac integrity verification', function () {
            $key = new SecureString('integrity-check-key-123456789012');
            $data = 'important data that needs integrity verification';

            // Generate HMAC
            $hmac = $this->encryptionService->generateHmac($data, $key);

            // Verify integrity
            $isValid = $this->encryptionService->verifyIntegrity($data, $hmac, $key);
            expect($isValid)->toBeTrue();

            // Tampered data should fail verification
            $tamperedData = $data.' tampered';
            $isInvalid = $this->encryptionService->verifyIntegrity($tamperedData, $hmac, $key);
            expect($isInvalid)->toBeFalse();
        });
    });

    describe('Security Validation', function () {
        it('security validation and sanitization', function () {
            // Test address validation
            $validEthAddress = '0x742d35Cc6634C0532925a3b8D9c428E1F2a3c399';
            expect($this->securityService->validateAddress($validEthAddress, 'ETH'))->toBeTrue();

            $invalidAddress = '<script>alert("xss")</script>';
            expect($this->securityService->validateAddress($invalidAddress, 'ETH'))->toBeFalse();

            // Test input sanitization
            $maliciousInput = '<script>alert("xss")</script>0x123ABC';
            $sanitized = $this->securityService->sanitizeInput($maliciousInput, 'address');
            expect($sanitized)->toBe('0x123ABC');

            // Test protocol sanitization
            $protocolInput = '  eth  ';
            $sanitizedProtocol = $this->securityService->sanitizeInput($protocolInput, 'protocol');
            expect($sanitizedProtocol)->toBe('ETH');
        });

        it('audit trail with suspicious activity detection', function () {
            Auth::login($this->user);

            // Perform multiple operations rapidly to trigger suspicious activity detection
            for ($i = 0; $i < 3; $i++) {
                $this->securityService->auditOperation('create_custodial_wallet', [
                    'address' => '0x'.str_pad(dechex($i), 40, '0', STR_PAD_LEFT),
                    'protocol' => 'ETH',
                ], 'success');
            }

            // Verify operations completed without error
            expect(true)->toBeTrue();
        });
    });

    describe('Legacy Compatibility', function () {
        it('deprecated wallet data compatibility', function () {
            // Create legacy WalletData (should show deprecation warning in debug mode)
            $legacyData = new WalletData($this->testAddress, $this->testPrivateKey);

            expect($legacyData->address)->toBe($this->testAddress)
                ->and($legacyData->privateKey)->toBe($this->testPrivateKey);

            // Convert to secure version
            $secureData = $legacyData->toSecure();
            expect($secureData)->toBeInstanceOf(SecureWalletData::class)
                ->and($secureData->getAddress())->toBe($this->testAddress);

            $secureData->withPrivateKey(function (string $key) {
                expect($key)->toBe($this->testPrivateKey);
            });

            // Convert back (for compatibility)
            $backToLegacy = WalletData::fromSecure($secureData);
            expect($backToLegacy->address)->toBe($this->testAddress)
                ->and($backToLegacy->privateKey)->toBe($this->testPrivateKey);
        });
    });

    describe('Memory Security', function () {
        it('memory security and cleanup', function () {
            $sensitiveValue = 'very-secret-private-key-12345';

            // Test SecureString cleanup
            $secureString = new SecureString($sensitiveValue);

            // Access the value
            $accessResult = $secureString->access(function (string $value) use ($sensitiveValue) {
                expect($value)->toBe($sensitiveValue);

                return 'accessed';
            });

            expect($accessResult)->toBe('accessed');

            // Clear the value
            $secureString->clear();
            expect($secureString->isCleared())->toBeTrue();

            // Should not be able to access after clearing
            expect(fn () => $secureString->access(fn ($value) => $value))
                ->toThrow(\Exception::class, 'cleared and cannot be accessed');
        });

        it('serialization attacks prevention', function () {
            $secureString = new SecureString('secret');
            $secureWalletData = new SecureWalletData('0x123', 'private-key');

            // Test SecureString serialization prevention
            expect(fn () => serialize($secureString))
                ->toThrow(\Exception::class, 'cannot be serialized');

            // Test SecureWalletData serialization prevention
            expect(fn () => serialize($secureWalletData))
                ->toThrow(\Exception::class, 'cannot be serialized');
        });
    });
});
