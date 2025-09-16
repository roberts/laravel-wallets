<?php

use Roberts\LaravelWallets\Services\Base58Service;

it('encodes and decodes a string correctly', function () {
    $service = new Base58Service;
    $original = 'Hello World';

    $encoded = $service->encode($original);
    $decoded = $service->decode($encoded);

    expect($encoded)->toBe('JxF12TrwUP45BMd');
    expect($decoded)->toBe($original);
});

it('handles leading zero bytes', function () {
    $service = new Base58Service;
    $original = "\0\0Hello World";

    $encoded = $service->encode($original);
    $decoded = $service->decode($encoded);

    expect($encoded)->toBe('11JxF12TrwUP45BMd');
    expect($decoded)->toBe($original);
});

it('encodes and decodes a bitcoin address', function () {
    $service = new Base58Service;
    $hex = '00010966776006953D5567439E5E39F86A0D273BEED61967F6';
    $base58 = '16UwLL9Risc3QfPqBUvKofHmBQ7wMtjvM';

    $encoded = $service->encode(hex2bin($hex));
    $decoded = bin2hex($service->decode($base58));

    expect($encoded)->toBe($base58);
    expect(strtolower($decoded))->toBe(strtolower($hex));
});
