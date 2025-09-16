<?php

namespace Roberts\LaravelWallets\Services;

use kornrunner\Keccak;

class KeccakService
{
    /**
     * Hash a string using Keccak algorithm.
     * Wrapper around kornrunner/keccak that ensures Ethereum-compatible Keccak-256.
     *
     * @param string $input The string to hash
     * @param int $outputLength Output length in bits (default 256)
     * @return string Hex string of the hash
     */
    public function hash(string $input, int $outputLength = 256): string
    {
        // Use kornrunner/keccak for reliable Ethereum-compatible hashing
        return Keccak::hash($input, $outputLength);
    }

    /**
     * Convenience method for Keccak-256.
     *
     * @param string $input The string to hash
     * @return string Hex string of the hash
     */
    public function keccak256(string $input): string
    {
        return $this->hash($input, 256);
    }

    /**
     * Hash a string and return binary result.
     *
     * @param string $input The string to hash
     * @param int $outputLength Output length in bits
     * @return string Binary hash
     */
    public function hashBinary(string $input, int $outputLength = 256): string
    {
        $hexHash = $this->hash($input, $outputLength);
        return hex2bin($hexHash);
    }
}