<?php

namespace Roberts\LaravelWallets\Protocols\Solana;

use Roberts\LaravelWallets\Services\Base58Service;

class Client
{
    public function __construct(private Base58Service $base58Service) {}

    /**
     * Generates a Solana keypair from a 64-byte seed.
     *
     * @param  string  $seed  The 64-byte seed.
     * @return array An array containing the public key and private key.
     */
    public function generateKeypairFromSeed(string $seed): array
    {
        if (!extension_loaded('sodium')) {
            throw new \RuntimeException(
                'The sodium PHP extension is required for Solana wallet operations. ' .
                'Please install the sodium extension: https://www.php.net/manual/en/sodium.installation.php'
            );
        }

        if (strlen($seed) !== 64) {
            throw new \InvalidArgumentException('Seed must be 64 bytes long.');
        }

        $keyPair = sodium_crypto_sign_seed_keypair(substr($seed, 0, 32));
        $privateKey = sodium_crypto_sign_secretkey($keyPair);

        return [
            'public_key' => sodium_crypto_sign_publickey($keyPair),
            'private_key' => $privateKey,
        ];
    }

    /**
     * Get the address from a public key.
     * In Solana, the address is the Base58 encoded public key.
     */
    public function getAddressFromPublicKey(string $publicKey): string
    {
        return $this->base58Service->encode($publicKey);
    }
}
