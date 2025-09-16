<?php

namespace Roberts\LaravelWallets\Contracts;

use Illuminate\Contracts\Auth\Authenticatable;

interface WalletInterface
{
    public static function create(?Authenticatable $user = null): self;

    public function getAddress(): string;

    public function getPublicKey(): string;

    public function getPrivateKey(): string;

    public function getOwner(): ?Authenticatable;
}
