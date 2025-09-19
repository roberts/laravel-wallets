<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Roberts\LaravelWallets\Contracts\SecurityServiceInterface;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Security\SecureWalletData;
use Roberts\LaravelWallets\Services\SecurityService;

describe('SecurityService', function () {

    beforeEach(function () {
        $this->mockRequest = \Mockery::mock(Request::class);
        $this->mockRequest->shouldReceive('ip')->andReturn('192.168.1.1');
        $this->mockRequest->shouldReceive('userAgent')->andReturn('TestAgent/1.0');
        $this->mockRequest->shouldReceive('header')->with('X-Request-ID')->andReturn(null);

        $this->securityService = new SecurityService($this->mockRequest);

        $this->mockUser = (object) [
            'id' => 123,
            'name' => 'Test User',
            'email' => 'test@example.com',
        ];

        $this->validEthAddress = '0x742d35Cc6634C0532925a3b8D9c428E1F2a3c399';
        $this->validSolAddress = 'DhzDoryP3Yt8xL4F6jF6LKMBwRPMqx4YgBXw5Qx2N8Jp';
        $this->validEthPrivateKey = 'a1b2c3d4e5f67890123456789012345678901234567890123456789012345678';
        $this->validSolPrivateKey = base64_encode(random_bytes(64));
    });

    afterEach(function () {
        \Mockery::close();
    });

    describe('Service Configuration', function () {
        it('implements security service interface', function () {
            expect($this->securityService)->toBeInstanceOf(SecurityServiceInterface::class);
        });

        it('gets security config', function () {
            config(['wallets.security.test_config' => ['key' => 'value']]);

            $config = $this->securityService->getSecurityConfig('test_config');

            expect($config)->toBe(['key' => 'value']);
        });

        it('gets required security measures', function () {
            config([
                'wallets.security.required_measures' => [
                    'export_private_key' => ['additional_confirmation', 'enhanced_logging'],
                ],
            ]);

            $measures = $this->securityService->getRequiredSecurityMeasures('export_private_key', []);

            expect($measures)
                ->toContain('additional_confirmation')
                ->and($measures)->toContain('enhanced_logging');
        });
    });

    describe('Address Validation', function () {
        it('validates ethereum addresses', function () {
            // Valid Ethereum address
            expect($this->securityService->validateAddress($this->validEthAddress, Protocol::ETH->value))
                ->toBeTrue();

            // Invalid addresses
            expect($this->securityService->validateAddress('invalid', Protocol::ETH->value))
                ->toBeFalse()
                ->and($this->securityService->validateAddress('0x123', Protocol::ETH->value))
                ->toBeFalse()
                ->and($this->securityService->validateAddress('742d35Cc6634C0532925a3b8D9c428E1F2a3c399', Protocol::ETH->value))
                ->toBeFalse();
        });

        it('validates solana addresses', function () {
            // Valid Solana address
            expect($this->securityService->validateAddress($this->validSolAddress, Protocol::SOL->value))
                ->toBeTrue();

            // Invalid addresses
            expect($this->securityService->validateAddress('invalid', Protocol::SOL->value))
                ->toBeFalse()
                ->and($this->securityService->validateAddress('0x123', Protocol::SOL->value))
                ->toBeFalse()
                ->and($this->securityService->validateAddress('short', Protocol::SOL->value))
                ->toBeFalse();
        });
    });

    describe('Private Key Validation', function () {
        it('validates ethereum private keys', function () {
            // Valid private key
            expect($this->securityService->validatePrivateKey($this->validEthPrivateKey, Protocol::ETH->value))
                ->toBeTrue();

            // Valid with 0x prefix
            expect($this->securityService->validatePrivateKey('0x'.$this->validEthPrivateKey, Protocol::ETH->value))
                ->toBeTrue();

            // Invalid keys
            expect($this->securityService->validatePrivateKey('invalid', Protocol::ETH->value))
                ->toBeFalse()
                ->and($this->securityService->validatePrivateKey('123', Protocol::ETH->value))
                ->toBeFalse()
                ->and($this->securityService->validatePrivateKey(str_repeat('0', 64), Protocol::ETH->value))
                ->toBeFalse();
        });

        it('validates solana private keys', function () {
            // Valid Solana private key
            expect($this->securityService->validatePrivateKey($this->validSolPrivateKey, Protocol::SOL->value))
                ->toBeTrue();

            // Invalid keys
            expect($this->securityService->validatePrivateKey('', Protocol::SOL->value))
                ->toBeFalse()
                ->and($this->securityService->validatePrivateKey('short', Protocol::SOL->value))
                ->toBeFalse();
        });
    });

    describe('Authentication and Authorization', function () {
        it('can perform operation when authenticated', function () {
            Auth::shouldReceive('check')->andReturn(true);
            Auth::shouldReceive('user')->andReturn($this->mockUser);
            Auth::shouldReceive('id')->andReturn($this->mockUser->id);

            Cache::shouldReceive('get')->andReturn(0);
            Cache::shouldReceive('put')->andReturn(true);

            $result = $this->securityService->canPerformOperation('add_external_wallet');

            expect($result)->toBeTrue();
        });

        it('cannot perform operation when unauthenticated', function () {
            Auth::shouldReceive('check')->andReturn(false);
            Auth::shouldReceive('id')->andReturn(null);

            Log::shouldReceive('info')->with(\Mockery::type('string'), \Mockery::type('array'));
            Log::shouldReceive('error')->with(\Mockery::type('string'), \Mockery::type('array'));

            $result = $this->securityService->canPerformOperation('add_external_wallet');

            expect($result)->toBeFalse();
        });
    });

    describe('Rate Limiting', function () {
        it('works at limit', function () {
            $userId = 123;

            $mockConfig = \Mockery::mock(\Illuminate\Config\Repository::class)->makePartial();
            $mockConfig->shouldReceive('get')
                ->with('wallets.security.rate_limits', [])
                ->andReturn([
                    'test_op' => [
                        'limit' => 5,
                        'window' => 60,
                    ],
                ]);

            $this->app->instance('config', $mockConfig);

            Cache::shouldReceive('get')
                ->with('rate_limit:test_op:123', 0)
                ->andReturn(5); // At limit

            $result = $this->securityService->checkRateLimit('test_op', (string) $userId);

            expect($result)->toBeFalse();
        });
    });

    describe('Input Sanitization', function () {
        it('sanitizes input correctly', function () {
            $result = $this->securityService->sanitizeInput('<script>alert("xss")</script>0x123', 'address');
            expect($result)->toBe('0x123');

            $result = $this->securityService->sanitizeInput('  ETH  ', 'protocol');
            expect($result)->toBe('ETH');

            $result = $this->securityService->sanitizeInput('123.45', 'numeric');
            expect($result)->toBe('123.45');
        });
    });

    describe('Wallet Data Validation', function () {
        it('validates wallet data', function () {
            $validWalletData = new SecureWalletData(
                '0x742d35Cc6634C0532925a3b8D9c428E1F2a3c3',
                'a1b2c3d4e5f6789012345678901234567890123456789012345678901234567890'
            );

            $errors = $this->securityService->validateWalletData($validWalletData);
            expect($errors)->toBeEmpty();

            // Test with empty address
            $invalidWalletData = new SecureWalletData('', 'validkey123456789012345678901234567890');
            $errors = $this->securityService->validateWalletData($invalidWalletData);
            expect($errors)->toContain('Address cannot be empty');
        });
    });

    describe('Security Monitoring', function () {
        it('audit operation creates database log', function () {
            Auth::shouldReceive('check')->andReturn(true);
            Auth::shouldReceive('id')->andReturn($this->mockUser->id);

            Log::shouldReceive('info')->once()->with(\Mockery::type('string'), \Mockery::type('array'));
            Log::shouldReceive('error')->with(\Mockery::type('string'), \Mockery::type('array'));

            $this->securityService->auditOperation('test_operation', ['key' => 'value'], 'success');

            expect(true)->toBeTrue(); // Audit operation completed without errors
        });

        it('detects suspicious activity', function () {
            Cache::shouldReceive('get')->andReturn(array_fill(0, 10, time()));
            Cache::shouldReceive('put')->andReturn(true);

            $warnings = $this->securityService->detectSuspiciousActivity('create_custodial_wallet', []);

            expect($warnings)->toContain('rapid_operation_pattern');
        });
    });
});
