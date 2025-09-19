<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Tests\TestUser;

uses(RefreshDatabase::class);

describe('WalletOwner Model', function () {

    beforeEach(function () {
        $this->wallet = Wallet::create([
            'protocol' => Protocol::ETH,
            'address' => '0x1234567890123456789012345678901234567890',
            'control_type' => ControlType::CUSTODIAL,
        ]);

        $this->user = TestUser::factory()->create();

        $this->validOwnershipData = [
            'wallet_id' => $this->wallet->id,
            'tenant_id' => 1,
            'owner_id' => $this->user->id,
            'owner_type' => TestUser::class,
            'encrypted_private_key' => encrypt('private_key'),
        ];
    });

    describe('Model Configuration', function () {
        it('has correct fillable attributes', function () {
            expect((new WalletOwner)->getFillable())->toBe([
                'wallet_id',
                'tenant_id',
                'owner_id',
                'owner_type',
                'encrypted_private_key',
            ]);
        });

        it('generates uuid on creation', function () {
            $walletOwner = WalletOwner::create($this->validOwnershipData);

            expect($walletOwner->uuid)
                ->not()->toBeNull()
                ->and(strlen($walletOwner->uuid))->toBe(36);
        });

        it('has tenant relationship via BelongsToTenant trait', function () {
            $walletOwner = new WalletOwner;

            expect(method_exists($walletOwner, 'tenant'))->toBeTrue()
                ->and(method_exists($walletOwner, 'scopeForTenant'))->toBeTrue();
        });
    });

    describe('Relationships', function () {
        beforeEach(function () {
            $this->walletOwner = WalletOwner::create($this->validOwnershipData);
        });

        it('belongs to wallet', function () {
            expect($this->walletOwner->wallet())
                ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\BelongsTo::class)
                ->and($this->walletOwner->wallet)->toBeInstanceOf(Wallet::class)
                ->and($this->walletOwner->wallet->id)->toBe($this->wallet->id);
        });

        it('has polymorphic owner relationship', function () {
            expect($this->walletOwner->owner())
                ->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphTo::class)
                ->and($this->walletOwner->owner)->toBeInstanceOf(TestUser::class)
                ->and($this->walletOwner->owner->id)->toBe($this->user->id);
        });
    });

    describe('Data Encryption', function () {
        it('automatically encrypts private key', function () {
            $plainPrivateKey = 'test-private-key';

            $walletOwner = WalletOwner::create([
                ...$this->validOwnershipData,
                'encrypted_private_key' => $plainPrivateKey,
            ]);

            $rawValue = \Illuminate\Support\Facades\DB::table('wallet_owners')
                ->where('id', $walletOwner->id)
                ->value('encrypted_private_key');

            expect($rawValue)->not()->toBe($plainPrivateKey)
                ->and($walletOwner->encrypted_private_key)->toBe($plainPrivateKey);
        });
    });

    describe('Database Constraints', function () {
        it('enforces unique ownership constraint', function () {
            WalletOwner::create($this->validOwnershipData);

            expect(fn () => WalletOwner::create($this->validOwnershipData))
                ->toThrow(\Illuminate\Database\QueryException::class);
        });

        it('allows same user to own different wallets', function () {
            $wallet2 = Wallet::create([
                'protocol' => Protocol::ETH,
                'address' => '0x2222222222222222222222222222222222222222',
                'control_type' => ControlType::CUSTODIAL,
            ]);

            $ownership1 = WalletOwner::create($this->validOwnershipData);
            $ownership2 = WalletOwner::create([
                ...$this->validOwnershipData,
                'wallet_id' => $wallet2->id,
                'encrypted_private_key' => encrypt('private_key_2'),
            ]);

            expect([$ownership1, $ownership2])
                ->each->toBeInstanceOf(WalletOwner::class)
                ->and($ownership1->owner->id)->toBe($ownership2->owner->id)
                ->and($ownership1->wallet_id)->not()->toBe($ownership2->wallet_id);
        });

        it('allows same wallet to have different owners in different tenants', function () {
            $user2 = TestUser::factory()->create();

            $ownership1 = WalletOwner::create($this->validOwnershipData);
            $ownership2 = WalletOwner::create([
                'wallet_id' => $this->wallet->id,
                'tenant_id' => 2, // Different tenant
                'owner_id' => $user2->id,
                'owner_type' => TestUser::class,
                'encrypted_private_key' => encrypt('private_key_2'),
            ]);

            expect([$ownership1, $ownership2])
                ->each->toBeInstanceOf(WalletOwner::class)
                ->and($ownership1->wallet->id)->toBe($ownership2->wallet->id)
                ->and($ownership1->tenant_id)->not()->toBe($ownership2->tenant_id);
        });
    });
});
