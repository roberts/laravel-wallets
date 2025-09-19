<?php

namespace Roberts\LaravelWallets\Protocols\Ethereum;

use Elliptic\EC;
use Roberts\LaravelWallets\Services\KeccakService;

class Client
{
    protected EC $ec;

    protected KeccakService $keccakService;

    public function __construct()
    {
        $this->ec = new EC('secp256k1');
        $this->keccakService = new KeccakService;
    }

    public function generatePrivateKey(): string
    {
        $key = $this->ec->genKeyPair();

        return $key->getPrivate('hex');
    }

    public function derivePublicKey(string $privateKey): string
    {
        $key = $this->ec->keyFromPrivate($privateKey, 'hex');

        return $key->getPublic(false, 'hex');
    }

    public function deriveAddress(string $publicKey): string
    {
        $binaryKey = hex2bin($publicKey);
        if ($binaryKey === false) {
            throw new \InvalidArgumentException('Invalid hexadecimal public key');
        }

        $hash = $this->keccakService->hash(substr($binaryKey, 1), 256);

        return '0x'.substr($hash, -40);
    }
}
