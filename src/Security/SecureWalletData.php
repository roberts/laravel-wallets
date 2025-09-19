<?php

namespace Roberts\LaravelWallets\Security;

use Exception;
use Roberts\LaravelWallets\Contracts\SecureWalletDataInterface;

/**
 * Secure implementation of wallet data that protects sensitive information.
 *
 * This class ensures private keys are never stored in plain text in memory
 * and provides controlled access through callbacks.
 */
class SecureWalletData implements SecureWalletDataInterface
{
    private SecureString $privateKey;

    private string $address;

    private bool $cleared = false;

    public function __construct(string $address, string $privateKey)
    {
        $this->address = $address;
        $this->privateKey = new SecureString($privateKey);
    }

    public function getAddress(): string
    {
        $this->ensureNotCleared();

        return $this->address;
    }

    public function withPrivateKey(callable $callback): mixed
    {
        $this->ensureNotCleared();

        return $this->privateKey->withSecureCallback($callback);
    }

    public function clearSensitiveData(): void
    {
        if ($this->cleared) {
            return;
        }

        $this->privateKey->clear();
        $this->address = '';
        $this->cleared = true;
    }

    public function isCleared(): bool
    {
        return $this->cleared;
    }

    public function withSecureCallback(callable $callback): mixed
    {
        $this->ensureNotCleared();

        return $this->privateKey->withSecureCallback(
            fn (string $privateKey) => $callback($this->address, new SecureString($privateKey))
        );
    }

    /**
     * Ensure the data hasn't been cleared.
     */
    private function ensureNotCleared(): void
    {
        if ($this->cleared) {
            throw new Exception('Sensitive data has been cleared and cannot be accessed');
        }
    }

    /**
     * Automatically clear sensitive data when the object is destroyed.
     */
    public function __destruct()
    {
        $this->clearSensitiveData();
    }

    /**
     * Prevent serialization of sensitive data.
     */
    public function __sleep(): array
    {
        throw new Exception('SecureWalletData cannot be serialized');
    }

    /**
     * Prevent unserializing sensitive data.
     */
    public function __wakeup(): void
    {
        throw new Exception('SecureWalletData cannot be unserialized');
    }
}
