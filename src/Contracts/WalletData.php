<?php

namespace Roberts\LaravelWallets\Contracts;

use Roberts\LaravelWallets\Security\SecureWalletData;

/**
 * @deprecated Use SecureWalletData instead for better security.
 *
 * A simple Data Transfer Object (DTO) to hold newly created wallet data
 * before it is persisted.
 *
 * WARNING: This class exposes private keys in plain text which is a security risk.
 * It has been deprecated in favor of SecureWalletData.
 */
final readonly class WalletData
{
    public function __construct(
        public string $address,
        public string $privateKey,
    ) {
        // Log a warning when the insecure class is used
        if (config('app.debug')) {
            error_log('WARNING: WalletData class exposes private keys in plain text. Use SecureWalletData instead.');
        }
    }

    /**
     * Create a secure version of this wallet data.
     */
    public function toSecure(): SecureWalletData
    {
        return new SecureWalletData($this->address, $this->privateKey);
    }

    /**
     * Create from secure wallet data (for backwards compatibility).
     */
    public static function fromSecure(SecureWalletData $secure): self
    {
        return $secure->withSecureCallback(
            fn (string $address, $privateKey) => $privateKey->access(
                fn (string $key) => new self($address, $key)
            )
        );
    }
}
