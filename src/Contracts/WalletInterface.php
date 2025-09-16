<?php

namespace Roberts\LaravelWallets\Contracts;

interface WalletInterface
{
    public static function create(): self;
    public function getAddress(): string;
    public function getPublicKey(): string;
    public function getPrivateKey(): string;
}
