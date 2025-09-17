<?php

use Roberts\LaravelWallets\Wallets\SolWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable;

uses(RefreshDatabase::class);

// Helper function for creating test user (Solana tests)
function mockSolUser(int $id = 456): Authenticatable
{
    return new class($id) implements Authenticatable {
        public function __construct(private int $id) {}
        public function getAuthIdentifierName() { return 'id'; }
        public function getAuthIdentifier() { return $this->id; }
        public function getAuthPassword() { return 'password'; }
        public function getRememberToken() { return null; }
        public function setRememberToken($value) {}
        public function getRememberTokenName() { return null; }
        public function getAuthPasswordName() { return 'password'; }
    };
}

describe('Solana Address Validation', function () {
    test('accepts valid solana address format', function () {
        $validAddress = '11111111111111111111111111111112'; // Valid Base58 encoded 32 bytes
        
        expect(fn() => SolWallet::addExternal($validAddress))
            ->not->toThrow(\Exception::class);
    });

    test('rejects invalid solana address formats', function () {
        $invalidCases = [
            ['11111111111111111111111111111110', 'contains invalid Base58 character "0"'],
            ['1111111', 'too short when decoded'],
        ];

        foreach ($invalidCases as [$address, $reason]) {
            expect(fn() => SolWallet::addExternal($address))
                ->toThrow(InvalidArgumentException::class);
        }
    });

    test('requires sodium extension for validation', function () {
        expect(extension_loaded('sodium'))->toBeTrue();
    });
});

describe('Solana Wallet Database Operations', function () {
    test('adds solana wallet to database with public key derivation', function () {
        $address = '11111111111111111111111111111112';
        
        $wallet = SolWallet::addExternal($address);
        
        expect($wallet)->toBeInstanceOf(SolWallet::class)
            ->and($wallet->getAddress())->toBe($address)
            ->and($wallet->getPublicKey())->not->toBe('') // Solana can derive public key from address
            ->and($wallet->getOwner())->toBeNull();
        
        // Verify database record
        $record = DB::table('wallets')->where('address', $address)->first();
        expect($record)->not->toBeNull()
            ->and($record->protocol)->toBe('sol')
            ->and($record->wallet_type)->toBe('external')
            ->and($record->public_key)->not->toBe('') // Should store the derived public key
            ->and($record->private_key)->toBeNull();
    });

    test('prevents duplicate solana wallet additions', function () {
        $address = '11111111111111111111111111111112';
        
        $wallet1 = SolWallet::addExternal($address);
        $wallet2 = SolWallet::addExternal($address); // Should return existing
        
        expect($wallet1->getAddress())->toBe($wallet2->getAddress());
        
        // Should only be one record in database
        expect(DB::table('wallets')->where('address', $address)->count())->toBe(1);
    });

    test('adds solana wallet with owner', function () {
        $user = mockSolUser(456);
        $address = '11111111111111111111111111111112';
        
        $wallet = SolWallet::addExternal($address, $user);
        
        expect($wallet->getOwner())->toBe($user);
        
        $record = DB::table('wallets')->where('address', $address)->first();
        expect($record->owner_id)->toBe(456);
    });
});
