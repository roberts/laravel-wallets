<?php

namespace Roberts\LaravelWallets\Services;

use Normalizer;

class Bip39Service
{
    private array $wordlist;

    public function __construct()
    {
        $this->wordlist = require __DIR__.'/Bip39/wordlist.php';
    }

    public function generateMnemonic(int $strength = 128): string
    {
        if ($strength % 32 !== 0 || $strength < 128 || $strength > 256) {
            throw new \InvalidArgumentException('Strength must be a multiple of 32 between 128 and 256.');
        }

        $entropy = random_bytes($strength / 8);
        $checksum = hash('sha256', $entropy, true);

        $entropyBits = '';
        foreach (str_split($entropy) as $byte) {
            $entropyBits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }

        $checksumLength = $strength / 32;
        $checksumBits = '';
        foreach (str_split(substr($checksum, 0, (int) ceil($checksumLength / 8))) as $byte) {
            $checksumBits .= str_pad(decbin(ord($byte)), 8, '0', STR_PAD_LEFT);
        }
        $checksumBits = substr($checksumBits, 0, $checksumLength);

        $bits = $entropyBits.$checksumBits;
        $binaryChunks = str_split($bits, 11);

        $mnemonic = [];
        foreach ($binaryChunks as $chunk) {
            $index = bindec($chunk);
            $mnemonic[] = $this->wordlist[$index];
        }

        return implode(' ', $mnemonic);
    }

    public function mnemonicToSeed(string $mnemonic, string $passphrase = ''): string
    {
        $normalizedMnemonic = Normalizer::normalize($mnemonic, Normalizer::FORM_KD);
        $salt = 'mnemonic'.Normalizer::normalize($passphrase, Normalizer::FORM_KD);

        return hash_pbkdf2('sha512', $normalizedMnemonic, $salt, 2048, 64, true);
    }
}
