<?php

namespace Roberts\LaravelWallets\Security;

use Exception;

/**
 * Secure string handling for sensitive data like private keys.
 *
 * This class provides basic secure string operations with automatic cleanup
 * and protection against accidental logging or serialization.
 */
final class SecureString
{
    private ?string $value = null;

    private bool $cleared = false;

    public function __construct(string $value)
    {
        $this->value = $value;
    }

    /**
     * Expose the string temporarily for a specific operation.
     * The string is cleared after the callback completes.
     *
     * @param  callable  $callback  Function that receives the string value
     * @return mixed The result of the callback
     */
    public function access(callable $callback): mixed
    {
        $this->ensureNotCleared();

        // Create a temporary copy for the callback
        $tempValue = $this->value;

        try {
            return $callback($tempValue);
        } finally {
            // Clear the temporary copy
            if (is_string($tempValue) && $tempValue !== '') {
                for ($i = 0; $i < strlen($tempValue); $i++) {
                    $tempValue[$i] = "\0";
                }
            }
            unset($tempValue);
        }
    }

    /**
     * Alias for access() to provide consistent naming with SecureWalletData.
     *
     * @param  callable  $callback  Function that receives the string value
     * @return mixed The result of the callback
     */
    public function withSecureCallback(callable $callback): mixed
    {
        return $this->access($callback);
    }

    /**
     * Clear the string from memory securely.
     */
    public function clear(): void
    {
        if ($this->cleared) {
            return;
        }

        // Overwrite the string data
        if ($this->value !== null && $this->value !== '') {
            for ($i = 0; $i < strlen($this->value); $i++) {
                $this->value[$i] = "\0";
            }
        }

        $this->value = null;
        $this->cleared = true;
    }

    /**
     * Check if the string has been cleared.
     */
    public function isCleared(): bool
    {
        return $this->cleared;
    }

    /**
     * Ensure the string hasn't been cleared.
     */
    private function ensureNotCleared(): void
    {
        if ($this->cleared) {
            throw new Exception('SecureString has been cleared and cannot be accessed');
        }
    }

    /**
     * Create a copy of this SecureString.
     */
    public function copy(): self
    {
        if ($this->cleared) {
            throw new \RuntimeException('Cannot copy a cleared SecureString');
        }

        return new self($this->value);
    }

    /**
     * Prevent serialization of sensitive data.
     */
    public function __sleep(): array
    {
        throw new Exception('SecureString cannot be serialized');
    }

    /**
     * Prevent unserializing sensitive data.
     */
    public function __wakeup(): void
    {
        throw new Exception('SecureString cannot be unserialized');
    }

    /**
     * Prevent accidental string conversion.
     */
    public function __toString(): string
    {
        return '[SecureString - '.($this->cleared ? 'CLEARED' : 'PROTECTED').']';
    }

    /**
     * Clear on destruction.
     */
    public function __destruct()
    {
        $this->clear();
    }
}
