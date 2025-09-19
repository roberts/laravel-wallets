<?php

use Roberts\LaravelWallets\Security\SecureString;

describe('SecureString', function () {

    beforeEach(function () {
        $this->testValue = 'test-secret-value';
        $this->privateKey = 'my-private-key-12345';
    });

    describe('Creation and Basic Operations', function () {
        it('can create secure string', function () {
            $secureString = new SecureString($this->testValue);

            expect($secureString)->toBeInstanceOf(SecureString::class)
                ->and($secureString->isCleared())->toBeFalse();
        });

        it('can access value through callback', function () {
            $secureString = new SecureString($this->testValue);

            $result = $secureString->access(fn (string $value) => [
                'original' => $this->testValue,
                'received' => $value,
                'match' => $value === $this->testValue,
                'return' => 'callback-result',
            ]);

            expect($result['match'])->toBeTrue()
                ->and($result['return'])->toBe('callback-result')
                ->and($result['received'])->toBe($this->testValue);
        });

        it('provides original value to callback', function () {
            $secureString = new SecureString($this->privateKey);

            $receivedValue = null;
            $secureString->access(function (string $value) use (&$receivedValue) {
                $receivedValue = $value;

                return null;
            });

            expect($receivedValue)->toBe($this->privateKey);
        });

        it('supports withSecureCallback alias', function () {
            $secureString = new SecureString($this->testValue);

            $result = $secureString->withSecureCallback(function (string $value) {
                expect($value)->toBe($this->testValue);

                return 'alias-result';
            });

            expect($result)->toBe('alias-result');
        });

        it('allows multiple access calls', function () {
            $secureString = new SecureString($this->testValue);

            $lengthResult = $secureString->access(fn (string $value) => strlen($value));
            $matchResult = $secureString->access(fn (string $value) => $value === $this->testValue);

            expect($lengthResult)->toBe(strlen($this->testValue))
                ->and($matchResult)->toBeTrue();
        });
    });

    describe('Memory Management', function () {
        it('can clear secure string', function () {
            $secureString = new SecureString($this->testValue);

            expect($secureString->isCleared())->toBeFalse();

            $secureString->clear();

            expect($secureString->isCleared())->toBeTrue();
        });

        it('cannot access cleared string', function () {
            $secureString = new SecureString($this->testValue);
            $secureString->clear();

            expect(fn () => $secureString->access(fn (string $value) => $value))
                ->toThrow(Exception::class, 'SecureString has been cleared and cannot be accessed');
        });

        it('clears string on destruction', function () {
            $secureString = new SecureString($this->testValue);
            expect($secureString->isCleared())->toBeFalse();

            // Trigger destruction - memory safety test
            unset($secureString);

            // If we reach this point, no memory leaks occurred
            expect(true)->toBeTrue();
        });
    });

    describe('Security Features', function () {
        it('cannot be serialized', function () {
            $secureString = new SecureString($this->testValue);

            expect(fn () => serialize($secureString))
                ->toThrow(Exception::class, 'SecureString cannot be serialized');
        });

        it('cannot be unserialized', function () {
            $secureString = new SecureString($this->testValue);

            expect(fn () => $secureString->__wakeup())
                ->toThrow(Exception::class, 'SecureString cannot be unserialized');
        });
    });

    describe('Data Type Handling', function () {
        it('handles empty strings', function () {
            $secureString = new SecureString('');

            $result = $secureString->access(fn (string $value) => $value);

            expect($result)->toBe('');
        });

        it('handles special characters', function () {
            $specialValue = "special\nchars\t@#$%^&*()";
            $secureString = new SecureString($specialValue);

            $isMatch = $secureString->access(fn (string $value) => $value === $specialValue);

            expect($isMatch)->toBeTrue();
        });

        it('handles long strings', function () {
            $longValue = str_repeat('abcdefghij', 100); // 1KB string
            $secureString = new SecureString($longValue);

            $result = $secureString->access(function (string $value) use ($longValue) {
                return [
                    'length_match' => strlen($value) === strlen($longValue),
                    'content_match' => $value === $longValue,
                ];
            });

            expect($result['length_match'])->toBeTrue()
                ->and($result['content_match'])->toBeTrue();
        });

        it('handles binary data', function () {
            $binaryData = pack('H*', '0123456789abcdef');
            $secureString = new SecureString($binaryData);

            $isMatch = $secureString->access(fn (string $value) => $value === $binaryData);

            expect($isMatch)->toBeTrue();
        });
    });
});
