<?php

namespace Roberts\LaravelWallets\Exceptions;

use Exception;

class InvalidPrivateKey extends Exception
{
    public static function message(): self
    {
        return new self('The provided private key is invalid.');
    }
}
