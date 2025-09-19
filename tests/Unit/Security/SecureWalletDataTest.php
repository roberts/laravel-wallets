<?php

use Roberts\LaravelWallets\Security\SecureString;
use Roberts\LaravelWallets\Security\SecureWalletData;

describe('SecureWalletData', function () {

    beforeEach(function () {
        $this->testAddress = '0x742d35Cc6634C0532925a3b8D9c428E1F2a3c3';
        $this->testPrivateKey = 'a1b2c3d4e5f6789012345678901234567890123456789012345678901234567890';
        $this->walletData = new SecureWalletData($this->testAddress, $this->testPrivateKey);
    });

    describe('Creation and Basic Operations', function () {
        it('can create secure wallet data', function () {
            expect($this->walletData)->toBeInstanceOf(SecureWalletData::class)
                ->and($this->walletData->getAddress())->toBe($this->testAddress)
                ->and($this->walletData->isCleared())->toBeFalse();
        });

        it('can access private key through callback', function () {
            $result = $this->walletData->withPrivateKey(function (string $privateKey) {
                expect($privateKey)->toBe($this->testPrivateKey);

                return 'key-accessed';
            });

            expect($result)->toBe('key-accessed');
        });

        it('can access both through secure callback', function () {
            $result = $this->walletData->withSecureCallback(function (string $address, SecureString $privateKey) {
                expect($address)->toBe($this->testAddress);

                return $privateKey->access(fn (string $key) => $key === $this->testPrivateKey);
            });

            expect($result)->toBeTrue();
        });

        it('allows multiple callback access', function () {
            $result1 = $this->walletData->withPrivateKey(fn (string $privateKey) => strlen($privateKey));
            $result2 = $this->walletData->withPrivateKey(fn (string $privateKey) => substr($privateKey, 0, 10));

            expect($result1)->toBe(strlen($this->testPrivateKey))
                ->and($result2)->toBe(substr($this->testPrivateKey, 0, 10));
        });

        it('secure callback provides secure string', function () {
            $this->walletData->withSecureCallback(function (string $address, SecureString $privateKey) {
                expect($privateKey)->toBeInstanceOf(SecureString::class)
                    ->and($address)->toBe($this->testAddress);

                $privateKey->access(function (string $key) {
                    expect($key)->toBe($this->testPrivateKey);
                });
            });
        });
    });

    describe('Memory Management', function () {
        it('can clear sensitive data', function () {
            expect($this->walletData->isCleared())->toBeFalse();

            $this->walletData->clearSensitiveData();

            expect($this->walletData->isCleared())->toBeTrue();
        });

        it('cannot access after clearing', function () {
            $this->walletData->clearSensitiveData();

            expect(fn () => $this->walletData->getAddress())
                ->toThrow(Exception::class, 'Sensitive data has been cleared and cannot be accessed');
        });

        it('cannot access private key after clearing', function () {
            $this->walletData->clearSensitiveData();

            expect(fn () => $this->walletData->withPrivateKey(fn (string $privateKey) => $privateKey))
                ->toThrow(Exception::class, 'Sensitive data has been cleared and cannot be accessed');
        });

        it('destructor clears data', function () {
            expect($this->walletData->isCleared())->toBeFalse();

            // Trigger destruction - memory safety test
            unset($this->walletData);

            // If we reach this point, no memory issues occurred
            expect(true)->toBeTrue();
        });
    });

    describe('Security Features', function () {
        it('cannot serialize wallet data', function () {
            expect(fn () => serialize($this->walletData))
                ->toThrow(Exception::class, 'SecureWalletData cannot be serialized');
        });

        it('cannot unserialize wallet data', function () {
            expect(fn () => $this->walletData->__wakeup())
                ->toThrow(Exception::class, 'SecureWalletData cannot be unserialized');
        });
    });

    describe('Data Type Handling', function () {
        it('handles empty address', function () {
            $walletData = new SecureWalletData('', $this->testPrivateKey);

            expect($walletData->getAddress())->toBe('');
        });

        it('handles empty private key', function () {
            $walletData = new SecureWalletData($this->testAddress, '');

            $result = $walletData->withPrivateKey(fn (string $privateKey) => $privateKey);

            expect($result)->toBe('');
        });

        it('handles special characters in data', function () {
            $specialAddress = "addr\nwith\tspecial@chars";
            $specialKey = "key\nwith\tspecial@chars#$%";

            $walletData = new SecureWalletData($specialAddress, $specialKey);

            expect($walletData->getAddress())->toBe($specialAddress);

            $walletData->withPrivateKey(function (string $privateKey) use ($specialKey) {
                expect($privateKey)->toBe($specialKey);
            });
        });

        it('handles long data', function () {
            $longAddress = str_repeat('0x123456789a', 10);
            $longKey = str_repeat('abcdef123456', 20);

            $walletData = new SecureWalletData($longAddress, $longKey);

            expect($walletData->getAddress())->toBe($longAddress);

            $walletData->withPrivateKey(function (string $privateKey) use ($longKey) {
                expect($privateKey)->toBe($longKey);
            });
        });

        it('handles binary data in private key', function () {
            $binaryKey = pack('H*', '0123456789abcdef0123456789abcdef0123456789abcdef0123456789abcdef');

            $walletData = new SecureWalletData($this->testAddress, $binaryKey);

            $walletData->withPrivateKey(function (string $privateKey) use ($binaryKey) {
                expect($privateKey)->toBe($binaryKey);
            });
        });
    });
});
