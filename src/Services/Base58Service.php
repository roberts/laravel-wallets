<?php

namespace Roberts\LaravelWallets\Services;

use Brick\Math\BigInteger;

class Base58Service
{
    private const ALPHABET = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';

    public function encode(string $bytes): string
    {
        $value = BigInteger::fromBase(bin2hex($bytes), 16);
        $base = BigInteger::of(58);
        $encoded = '';

        while ($value->isGreaterThan(0)) {
            [$value, $remainder] = $value->quotientAndRemainder($base);
            $encoded = self::ALPHABET[$remainder->toInt()].$encoded;
        }

        // Preserve leading zero bytes
        for ($i = 0; $i < strlen($bytes) && $bytes[$i] === "\0"; $i++) {
            $encoded = '1'.$encoded;
        }

        return $encoded;
    }

    public function decode(string $encoded): string
    {
        $value = BigInteger::zero();
        $base = BigInteger::of(58);

        for ($i = 0; $i < strlen($encoded); $i++) {
            $char = $encoded[$i];
            $charValue = strpos(self::ALPHABET, $char);

            if ($charValue === false) {
                throw new \InvalidArgumentException("Invalid Base58 character found: {$char}");
            }

            $value = $value->multipliedBy($base)->plus($charValue);
        }

        $hex = $value->toBase(16);

        if (strlen($hex) % 2 !== 0) {
            $hex = '0'.$hex;
        }

        $decoded = hex2bin($hex);

        // Preserve leading '1's as zero bytes
        for ($i = 0; $i < strlen($encoded) && $encoded[$i] === '1'; $i++) {
            $decoded = "\0".$decoded;
        }

        return $decoded;
    }
}
