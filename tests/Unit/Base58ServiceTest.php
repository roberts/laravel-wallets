<?php

use Roberts\LaravelWallets\Services\Base58Service;

describe('Base58Service', function () {
    beforeEach(function () {
        $this->service = new Base58Service;
    });

    it('encodes and decodes strings correctly', function () {
        $original = 'Hello World';

        $encoded = $this->service->encode($original);
        $decoded = $this->service->decode($encoded);

        expect($encoded)->toBe('JxF12TrwUP45BMd')
            ->and($decoded)->toBe($original);
    });

    it('handles leading zero bytes', function () {
        $original = "\0\0Hello World";

        $encoded = $this->service->encode($original);
        $decoded = $this->service->decode($encoded);

        expect($encoded)->toBe('11JxF12TrwUP45BMd')
            ->and($decoded)->toBe($original);
    });

    it('encodes and decodes Bitcoin addresses', function () {
        $hex = '00010966776006953D5567439E5E39F86A0D273BEED61967F6';
        $base58 = '16UwLL9Risc3QfPqBUvKofHmBQ7wMtjvM';

        $encoded = $this->service->encode(hex2bin($hex));
        $decoded = bin2hex($this->service->decode($base58));

        expect($encoded)->toBe($base58)
            ->and(strtolower($decoded))->toBe(strtolower($hex));
    });
});
