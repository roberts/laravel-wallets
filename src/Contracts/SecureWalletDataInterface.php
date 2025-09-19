<?php

namespace Roberts\LaravelWallets\Contracts;

use Roberts\LaravelWallets\Security\SecureString;

/**
 * Secure interface for wallet data handling.
 *
 * This interface ensures private keys are handled securely through
 * controlled access patterns and explicit cleanup.
 */
interface SecureWalletDataInterface
{
    /**
     * Get the wallet's public address.
     */
    public function getAddress(): string;

    /**
     * Access the private key through a secure callback.
     *
     * @param  callable  $callback  Function that receives the private key as SecureString
     * @return mixed The result of the callback
     */
    public function withPrivateKey(callable $callback): mixed;

    /**
     * Clear all sensitive data from memory.
     */
    public function clearSensitiveData(): void;

    /**
     * Check if sensitive data has been cleared.
     */
    public function isCleared(): bool;

    /**
     * Access both address and private key through a secure callback.
     *
     * @param  callable  $callback  Function that receives (string $address, SecureString $privateKey)
     * @return mixed The result of the callback
     */
    public function withSecureCallback(callable $callback): mixed;
}
