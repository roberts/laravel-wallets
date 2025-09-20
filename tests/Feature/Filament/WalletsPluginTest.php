<?php

use Roberts\LaravelWallets\Filament\WalletsPlugin;

describe('Filament Wallets Plugin', function () {

    beforeEach(function () {
        // Create plugin instance for testing
        $this->plugin = WalletsPlugin::make();
    });

    afterEach(function () {
        // Clean up any Mockery expectations
        \Mockery::close();
    });

    describe('Plugin Configuration', function () {
        it('can create wallets plugin instance', function () {
            expect($this->plugin)->toBeInstanceOf(WalletsPlugin::class);
            expect($this->plugin->getId())->toBe('roberts-laravel-wallets');
        });

        it('has correct plugin configuration', function () {
            expect($this->plugin->getId())->toBe('roberts-laravel-wallets');

            // Test that the plugin implements the correct interface
            expect($this->plugin)->toBeInstanceOf(\Filament\Contracts\Plugin::class);
        });
    });

    describe('Resource Registration', function () {
        it('can register plugin resources', function () {
            // This is a basic test to ensure the plugin structure is correct
            // In a real Filament environment, this would test resource discovery

            // Create a mock panel that implements the Panel interface methods we need
            $panel = \Mockery::mock(\Filament\Panel::class);
            $panel->shouldReceive('discoverResources')
                ->once()
                ->with(
                    \Mockery::on(function ($in) {
                        return str_ends_with($in, '/Resources');
                    }),
                    'Roberts\\LaravelWallets\\Filament\\Resources'
                )
                ->andReturnSelf();

            $this->plugin->register($panel);

            // If we get here without exceptions, the registration worked
            expect(true)->toBeTrue();
        });
    });
});
