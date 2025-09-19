<?php

namespace Roberts\LaravelWallets\Protocols\Solana;

use Roberts\LaravelWallets\Contracts\WalletAdapterInterface;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Security\SecureWalletData;
use Roberts\LaravelWallets\Services\Bip39Service;

class WalletAdapter implements WalletAdapterInterface
{
    public function __construct(
        protected Client $client,
        protected Bip39Service $bip39Service
    ) {}

    /**
     * Generate a new Solana wallet using secure practices.
     */
    public function createWallet(): SecureWalletData
    {
        // Generate mnemonic and seed
        $mnemonic = $this->bip39Service->generateMnemonic();
        $seed = $this->bip39Service->mnemonicToSeed($mnemonic);

        // Generate keypair from seed
        $keypair = $this->client->generateKeypairFromSeed($seed);

        // Get the address from public key
        $address = $this->client->getAddressFromPublicKey($keypair['public_key']);

        // For Solana, we'll store the private key (which contains both public and private key data)
        $privateKey = base64_encode($keypair['private_key']);

        return new SecureWalletData($address, $privateKey);
    }

    public function validateAddress(string $address): bool
    {
        // Solana addresses are base58 encoded and typically 32-44 characters
        if (strlen($address) < 32 || strlen($address) > 44) {
            return false;
        }

        // Check if it's valid base58
        try {
            $decoded = $this->decodeBase58($address);

            // Solana addresses should decode to exactly 32 bytes
            return strlen($decoded) === 32;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function validatePrivateKey(string $privateKey): bool
    {
        try {
            // Solana private keys are typically base64 encoded 64-byte arrays
            $decoded = base64_decode($privateKey, true);

            if ($decoded === false) {
                return false;
            }

            // Should be 64 bytes (32 bytes private key + 32 bytes public key)
            if (strlen($decoded) !== 64) {
                return false;
            }

            // Try to derive a public key to validate
            $keypair = ['private_key' => $decoded];
            $this->client->getAddressFromPublicKey(substr($decoded, 32)); // Last 32 bytes are public key

            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    public function getProtocol(): string
    {
        return Protocol::SOL->value;
    }

    public function deriveAddress(string $privateKey): string
    {
        if (! $this->validatePrivateKey($privateKey)) {
            throw new \InvalidArgumentException('Invalid private key provided');
        }

        $decoded = base64_decode($privateKey, true);
        $publicKey = substr($decoded, 32); // Last 32 bytes are the public key

        return $this->client->getAddressFromPublicKey($publicKey);
    }

    /**
     * Decode a base58 string.
     *
     * @param  string  $base58  The base58 encoded string
     * @return string The decoded binary data
     *
     * @throws \InvalidArgumentException If the string is not valid base58
     */
    private function decodeBase58(string $base58): string
    {
        $alphabet = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        $base = strlen($alphabet);

        $num = gmp_init(0);

        for ($i = 0; $i < strlen($base58); $i++) {
            $char = $base58[$i];
            $position = strpos($alphabet, $char);

            if ($position === false) {
                throw new \InvalidArgumentException('Invalid base58 character: '.$char);
            }

            $num = gmp_add(gmp_mul($num, $base), $position);
        }

        $hex = gmp_strval($num, 16);

        // Pad with leading zero if necessary
        if (strlen($hex) % 2 !== 0) {
            $hex = '0'.$hex;
        }

        return hex2bin($hex);
    }
}
