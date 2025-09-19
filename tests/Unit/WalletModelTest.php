<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;

uses(RefreshDatabase::class);

describe('Wallet Model', function () {

    // Shared test data using beforeEach
    beforeEach(function () {
        $this->validWalletData = [
            'protocol' => Protocol::ETH,
            'address' => '0x1234567890123456789012345678901234567890',
            'control_type' => ControlType::CUSTODIAL,
        ];
    });

    describe('Model Configuration', function () {
        it('has correct fillable attributes', function () {
            expect((new Wallet)->getFillable())->toBe([
                'protocol',
                'address',
                'control_type',
                'metadata',
            ]);
        });

        it('generates uuid on creation', function () {
            $wallet = Wallet::create($this->validWalletData);

            expect($wallet->uuid)
                ->not()->toBeNull()
                ->and(strlen($wallet->uuid))->toBe(36); // Standard UUID length
        });
    });

    describe('Attribute Casting', function () {
        it('casts protocol to enum', function () {
            $wallet = new Wallet($this->validWalletData);

            expect($wallet->protocol)
                ->toBeInstanceOf(Protocol::class)
                ->toBe(Protocol::ETH);
        });

        it('casts control_type to enum', function () {
            $wallet = new Wallet($this->validWalletData);

            expect($wallet->control_type)
                ->toBeInstanceOf(ControlType::class)
                ->toBe(ControlType::CUSTODIAL);
        });

        it('casts metadata to array', function () {
            $metadata = ['label' => 'My Wallet', 'chain_id' => 1];

            $wallet = Wallet::create([
                ...$this->validWalletData,
                'metadata' => $metadata,
            ]);

            expect($wallet->metadata)->toBe($metadata);
        });
    });

    describe('Relationships', function () {
        beforeEach(function () {
            $this->wallet = Wallet::create($this->validWalletData);
        });

        it('has owners relationship', function () {
            expect($this->wallet->owners())
                ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\HasMany::class);
        });

        it('can have multiple owners', function () {
            // Create multiple ownership records using factory pattern
            collect([1, 2])->each(fn ($i) => WalletOwner::create([
                'wallet_id' => $this->wallet->id,
                'tenant_id' => $i,
                'owner_id' => $i,
                'owner_type' => 'App\\Models\\User',
                'encrypted_private_key' => encrypt("private_key_{$i}"),
            ])
            );

            expect($this->wallet->owners)->toHaveCount(2);
        });
    });

    describe('Database Constraints', function () {
        it('enforces unique protocol and address constraint', function () {
            Wallet::create($this->validWalletData);

            expect(fn () => Wallet::create($this->validWalletData))
                ->toThrow(\Illuminate\Database\QueryException::class);
        });
    });
});
