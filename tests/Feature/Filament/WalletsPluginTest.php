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

    // Create a mock panel that implements the Panel interface methods we need
    $panel = Mockery::mock(\Filament\Panel::class);
    $panel->shouldReceive('discoverResources')
        ->once()
        ->with(
            Mockery::on(function ($in) {
                return str_ends_with($in, '/Resources');
            }),
            'Roberts\\LaravelWallets\\Filament\\Resources'
        )
        ->andReturnSelf();

    $plugin->register($panel);

    // If we get here without exceptions, the registration worked
    expect(true)->toBeTrue();
});
