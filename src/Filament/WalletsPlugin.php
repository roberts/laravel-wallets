<?php

namespace Roberts\LaravelWallets\Filament;

use Filament\Contracts\Plugin;
use Filament\Panel;

class WalletsPlugin implements Plugin
{
    public static function make(): self
    {
        return new self;
    }

    public function getId(): string
    {
        return 'roberts-laravel-wallets';
    }

    public function register(Panel $panel): void
    {
        $panel->discoverResources(
            in: __DIR__.'/Resources',
            for: 'Roberts\\LaravelWallets\\Filament\\Resources'
        );
    }

    public function boot(Panel $panel): void
    {
        // no-op
    }
}
