<?php

use Roberts\LaravelWallets\Services\KeccakService;

describe('KeccakService', function () {
    beforeEach(function () {
        $this->keccakService = new KeccakService();
    });

    it('can hash empty string correctly', function () {
        $result = $this->keccakService->hash('', 256);
        
        // Official Ethereum Keccak-256 hash of empty string
        expect($result)->toBe('c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470');
    });

    it('can hash simple strings correctly', function () {
        // Official Ethereum Keccak-256 test vectors
        $vectors = [
            'abc' => '4e03657aea45a94fc7d47ba826c8d667c0d1e6e33a64a036ec44f58fa12d6c45',
            'hello' => '1c8aff950685c2ed4bc3174f3472287b56d9517b9c948127319a09a7a36deac8',
            'hello world' => '47173285a8d7341e5e972fc677286384f802f8ef42a5ec5f03bbfa254cb01fad',
            'The quick brown fox jumps over the lazy dog' => '4d741b6f1eb29cb2a9b9911c82f56fa8d73b04959d3d9d222895df6c0b28aa15',
        ];

        foreach ($vectors as $input => $expected) {
            $result = $this->keccakService->hash($input, 256);
            expect($result)->toBe($expected, "Failed for input: {$input}");
        }
    });

    it('has convenience keccak256 method', function () {
        $result1 = $this->keccakService->keccak256('hello');
        $result2 = $this->keccakService->hash('hello', 256);
        
        expect($result1)->toBe($result2);
        expect(strlen($result1))->toBe(64); // 256 bits = 64 hex characters
    });

    it('can hash binary data', function () {
        $binaryData = hex2bin('deadbeef');
        $result = $this->keccakService->hash($binaryData, 256);
        
        expect($result)->toBeString();
        expect(strlen($result))->toBe(64);
        expect(ctype_xdigit($result))->toBeTrue();
        
        // Test known binary hash (correct hash for 'deadbeef' hex data)
        expect($result)->toBe('d4fd4e189132273036449fc9e11198c739161b4c0116a9a2dccdfa1c492006f1');
    });

    it('produces consistent results', function () {
        $data = 'test data for consistency';
        
        $result1 = $this->keccakService->hash($data, 256);
        $result2 = $this->keccakService->hash($data, 256);
        $result3 = $this->keccakService->keccak256($data);
        
        expect($result1)->toBe($result2);
        expect($result1)->toBe($result3);
    });

    it('handles various input lengths correctly', function () {
        $inputs = [
            'a',
            'ab',
            'abc',
            'test',
            'message digest',
            'abcdefghijklmnopqrstuvwxyz',
            str_repeat('a', 100),
            str_repeat('data', 50),
        ];
        
        foreach ($inputs as $input) {
            $result = $this->keccakService->hash($input, 256);
            
            expect($result)->toBeString();
            expect(strlen($result))->toBe(64);
            expect(ctype_xdigit($result))->toBeTrue();
        }
    });

    it('can hash with different bit lengths', function () {
        $data = 'test';
        
        $hash224 = $this->keccakService->hash($data, 224);
        $hash256 = $this->keccakService->hash($data, 256);
        $hash384 = $this->keccakService->hash($data, 384);
        $hash512 = $this->keccakService->hash($data, 512);
        
        expect(strlen($hash224))->toBe(56); // 224 bits = 56 hex chars
        expect(strlen($hash256))->toBe(64); // 256 bits = 64 hex chars
        expect(strlen($hash384))->toBe(96); // 384 bits = 96 hex chars
        expect(strlen($hash512))->toBe(128); // 512 bits = 128 hex chars
        
        // All should be different
        expect($hash224)->not->toBe($hash256);
        expect($hash256)->not->toBe($hash384);
        expect($hash384)->not->toBe($hash512);
    });

    it('hashBinary returns binary data', function () {
        $data = 'test';
        $hexResult = $this->keccakService->hash($data, 256);
        $binaryResult = $this->keccakService->hashBinary($data, 256);
        
        expect(bin2hex($binaryResult))->toBe($hexResult);
        expect(strlen($binaryResult))->toBe(32); // 256 bits = 32 bytes
    });

    it('works with ethereum address derivation', function () {
        // Test Ethereum address derivation pattern with known test vector
        // This is a known public key that should produce a specific Ethereum address
        $publicKey = '04' . 
            '79be667ef9dcbbac55a06295ce870b07029bfcdb2dce28d959f2815b16f81798' .
            '483ada7726a3c4655da4fbfc0e1108a8fd17b448a68554199c47d08ffb10d4b8';
        $publicKeyBinary = hex2bin(substr($publicKey, 2)); // Remove '04' prefix
        
        $hash = $this->keccakService->hashBinary($publicKeyBinary, 256);
        $address = '0x' . bin2hex(substr($hash, -20)); // Last 20 bytes for Ethereum address
        
        // This should produce the address: 0x7e5f4552091a69125d5dfcb7b8c2659029395bdf
        expect($address)->toBe('0x7e5f4552091a69125d5dfcb7b8c2659029395bdf');
        expect($address)->toStartWith('0x');
        expect(strlen($address))->toBe(42); // 0x + 40 chars
        expect(ctype_xdigit(substr($address, 2)))->toBeTrue();
    });

    it('matches ethereum keccak-256 test vectors', function () {
        // Official Ethereum Keccak-256 test vectors verified with kornrunner/keccac
        $vectors = [
            '' => 'c5d2460186f7233c927e7db2dcc703c0e500b653ca82273b7bfad8045d85a470',
            'a' => '3ac225168df54212a25c1c01fd35bebfea408fdac2e31ddd6f80a4bbf9a5f1cb',
            'abc' => '4e03657aea45a94fc7d47ba826c8d667c0d1e6e33a64a036ec44f58fa12d6c45',
            'message digest' => '856ab8a3ad0f6168a4d0ba8d77487243f3655db6fc5b0e1669bc05b1287e0147',
            'abcdefghijklmnopqrstuvwxyz' => '9230175b13981da14d2f3334f321eb78fa0473133f6da3de896feb22fb258936',
        ];

        foreach ($vectors as $input => $expected) {
            $result = $this->keccakService->hash($input, 256);
            expect($result)->toBe($expected, "Failed for Ethereum test vector: '{$input}'");
        }
    });

    it('validates against ethereum smart contract compatibility', function () {
        // Test vectors for common Ethereum smart contract operations
        $smartContractVectors = [
            // Function selector: first 4 bytes of keccac256("transfer(address,uint256)")
            'transfer(address,uint256)' => 'a9059cbb2ab09eb219583f4a59a5d0623ade346d962bcd4e46b11da047c9049b',
            
            // Event signature: keccac256("Transfer(address,address,uint256)")
            'Transfer(address,address,uint256)' => 'ddf252ad1be2c89b69c2b068fc378daa952ba7f163c4a11628f55a4df523b3ef',
            
            // Common storage slot calculation inputs
            'balances' => 'a65b1ef8ee6544359221f3cf316f768360e83448109193bdcef77f52a79d95c4',
            'allowances' => '54bf4c436d6f8521e5c6189511c75075de702ad597ce22c1786275e8e5167ec7',
        ];

        foreach ($smartContractVectors as $input => $expected) {
            $result = $this->keccakService->hash($input, 256);
            expect($result)->toBe($expected, "Failed for smart contract vector: '{$input}'");
        }
    });
});
