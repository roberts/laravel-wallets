<?php

namespace Roberts\LaravelWallets\Contracts;

use Roberts\LaravelWallets\Security\SecureWalletData;

/**
 * Interface for a blockchain-specific wallet adapter.
 *
 * Each supported blockchain protocol must have an adapter that implements
 * this interface. The adapter is responsible for the protocol-specific
 * logic of creating a wallet.
 */
interface WalletAdapterInterface
{
    /**
     * Generate a new wallet using secure practices.
     *
     * This is the preferred method that returns secure wallet data
     * with proper memory protection for private keys.
     *
     * @return SecureWalletData A secure data transfer object containing the address and private key.
     */
    public function createWallet(): SecureWalletData;

    /**
     * Validate an address for this protocol.
     *
     * @param  string  $address  The address to validate
     * @return bool True if the address is valid for this protocol
     */
    public function validateAddress(string $address): bool;

    /**
     * Validate a private key for this protocol.
     *
     * @param  string  $privateKey  The private key to validate
     * @return bool True if the private key is valid for this protocol
     */
    public function validatePrivateKey(string $privateKey): bool;

    /**
     * Get the protocol identifier for this adapter.
     *
     * @return string The protocol identifier (e.g., 'ETH', 'SOL')
     */
    public function getProtocol(): string;

    /**
     * Derive the public address from a private key securely.
     *
     * @param  string  $privateKey  The private key
     * @return string The derived public address
     */
    public function deriveAddress(string $privateKey): string;
}
