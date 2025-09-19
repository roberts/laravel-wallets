<?php

namespace Roberts\LaravelWallets\Enums;

enum ControlType: string
{
    case CUSTODIAL = 'custodial';
    case SHARED = 'shared';
    case EXTERNAL = 'external';

    public function label(): string
    {
        return match ($this) {
            self::CUSTODIAL => 'Custodial',
            self::SHARED => 'Shared',
            self::EXTERNAL => 'External',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::CUSTODIAL => 'Fully managed wallet by specific tenant with private key stored securely',
            self::SHARED => 'Wallet with application control plus a shared private key for user access',
            self::EXTERNAL => 'External wallet (User submitted or pulled from token holder list)',
        };
    }

    /**
     * Check if this wallet type can store private keys.
     */
    public function canStorePrivateKey(): bool
    {
        return match ($this) {
            self::CUSTODIAL => true,
            self::SHARED => true,
            self::EXTERNAL => false,
        };
    }

    /**
     * Check if this wallet type requires external signing.
     */
    public function requiresExternalSigning(): bool
    {
        return match ($this) {
            self::CUSTODIAL => false,
            self::SHARED => false,
            self::EXTERNAL => true,
        };
    }
}
