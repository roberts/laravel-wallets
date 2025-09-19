<?php

namespace Roberts\LaravelWallets\Contracts;

use Illuminate\Database\Eloquent\Model;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;

/**
 * Interface for wallet implementations in the two-table architecture.
 *
 * This interface defines the contract for wallet classes that work with
 * the global wallet registry (wallets table) and ownership records (wallet_owners table).
 */
interface WalletInterface
{
    /**
     * Create a new custodial wallet for an owner.
     *
     * @param  \Roberts\LaravelWallets\Enums\Protocol  $protocol  The wallet protocol
     * @param  \Illuminate\Database\Eloquent\Model  $owner  The wallet owner
     * @param  int  $tenantId  The tenant ID
     * @param  array<string, mixed>|null  $metadata  Additional metadata for the wallet
     * @return array<string, mixed> The created wallet data
     */
    public static function createCustodial(
        Protocol $protocol,
        Model $owner,
        int $tenantId,
        ?array $metadata = null
    ): array;

    /**
     * Add an external wallet for an owner.
     *
     * @param  \Roberts\LaravelWallets\Enums\Protocol  $protocol  The wallet protocol
     * @param  string  $address  The wallet address
     * @param  \Illuminate\Database\Eloquent\Model  $owner  The wallet owner
     * @param  int  $tenantId  The tenant ID
     * @param  array<string, mixed>|null  $metadata  Additional metadata for the wallet
     * @return array<string, mixed> The added wallet data
     */
    public static function addExternal(
        Protocol $protocol,
        string $address,
        Model $owner,
        int $tenantId,
        ?array $metadata = null
    ): array;

    /**
     * Get the wallet's blockchain address.
     */
    public function getAddress(): string;

    /**
     * Get the wallet's public key.
     */
    public function getPublicKey(): string;

    /**
     * Get the wallet's private key (if accessible).
     * Should throw exception if no access to private key.
     */
    public function getPrivateKey(): string;

    /**
     * Get the blockchain protocol for this wallet.
     */
    public function getProtocol(): Protocol;

    /**
     * Get the control type for this wallet.
     */
    public function getControlType(): ControlType;

    /**
     * Validate an address for the specific protocol.
     */
    public static function validateAddress(string $address): bool;

    /**
     * Validate a private key for the specific protocol.
     */
    public static function validatePrivateKey(string $privateKey): bool;

    /**
     * Generate a cryptographic key pair for this protocol.
     *
     * @return array{publicKey: string, privateKey: string, address: string} The generated key pair and address
     */
    public static function generateKeyPair(): array;

    /**
     * Get the public key from a private key.
     */
    public static function getPublicKeyFromPrivate(string $privateKey): string;

    /**
     * Get the address from a private key.
     */
    public static function getAddressFromPrivate(string $privateKey): string;

    /**
     * Check if this wallet implementation supports the given protocol.
     */
    public static function supportsProtocol(Protocol $protocol): bool;

    /**
     * Get wallet metadata.
     *
     * @return array<string, mixed> The wallet metadata
     */
    public function getMetadata(): array;

    /**
     * Update wallet metadata.
     *
     * @param  array<string, mixed>  $metadata  The metadata to update
     * @return bool Whether the update was successful
     */
    public function updateMetadata(array $metadata): bool;
}
