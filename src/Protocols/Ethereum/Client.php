<?php

namespace Roberts\LaravelWallets\Protocols\Ethereum;

use Elliptic\EC;
use kornrunner\Keccak;

class Client
{
    protected EC $ec;

    public function __construct()
    {
        $this->ec = new EC('secp256k1');
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
        $hash = Keccak::hash(substr(hex2bin($publicKey), 1), 256);

        return '0x'.substr($hash, -40);
    }
}
