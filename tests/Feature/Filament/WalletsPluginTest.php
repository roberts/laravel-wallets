<?php

use Roberts\LaravelWallets\Filament\WalletsPlugin;

it('can create wallets plugin instance', function () {
    $plugin = WalletsPlugin::make();

    expect($plugin)->toBeInstanceOf(WalletsPlugin::class);
    expect($plugin->getId())->toBe('roberts-laravel-wallets');
});

it('has correct plugin configuration', function () {
    $plugin = WalletsPlugin::make();

    expect($plugin->getId())->toBe('roberts-laravel-wallets');

    // Test that the plugin implements the correct interface
    expect($plugin)->toBeInstanceOf(\Filament\Contracts\Plugin::class);
});

it('can register plugin resources', function () {
    // This is a basic test to ensure the plugin structure is correct
    // In a real Filament environment, this would test resource discovery

    $plugin = WalletsPlugin::make();

    // Mock a panel to test registration
    $panel = new class
    {
        public $discoveredResources = [];

        public function discoverResources(string $in, string $for): self
        {
            $this->discoveredResources[] = ['in' => $in, 'for' => $for];

            return $this;
        }
    };

    $plugin->register($panel);

    expect($panel->discoveredResources)->toHaveCount(1);
    expect($panel->discoveredResources[0]['for'])->toBe('Roberts\\LaravelWallets\\Filament\\Resources');
});
