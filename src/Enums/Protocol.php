<?php

namespace Roberts\LaravelWallets\Enums;

enum Protocol: string
{
    case ETH = 'eth';
    case SOL = 'sol';
    // case BTC = 'btc';
    // case SUI = 'sui';
    // case XRP = 'xrp';
    // case ADA = 'ada';
    // case HBAR = 'hbar';
    // case TON = 'ton';

    public function label(): string
    {
        return match ($this) {
            self::ETH => 'Eth',
            self::SOL => 'Sol',
        };
    }

    public function title(): string
    {
        return match ($this) {
            self::ETH => 'Ethereum',
            self::SOL => 'Solana',
        };
    }

    public function isEth(): bool
    {
        return $this === self::ETH;
    }

    public function isSol(): bool
    {
        return $this === self::SOL;
    }

}
