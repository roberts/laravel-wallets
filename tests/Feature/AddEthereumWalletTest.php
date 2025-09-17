<?php

use Roberts\LaravelWallets\Wallets\EthWallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Contracts\Auth\Authenticatable;

uses(RefreshDatabase::class);

// Helper function for creating test user
function mockUser(int $id = 123): Authenticatable
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

describe('Ethereum Address Validation', function () {
    test('accepts valid ethereum address format', function () {
        expect(fn() => EthWallet::addExternal('0x742d35cc0b3e7c3f8f9e7ad0e1c5c3f5e0e8c8b7'))
            ->not->toThrow(\Exception::class);
    });

    test('rejects invalid ethereum address formats', function () {
        $invalidAddresses = [
            '0x742d35cc0b3e7c3f8f9e7ad0e1c5c3f5e0e8c8',      // Too short
            '742d35cc0b3e7c3f8f9e7ad0e1c5c3f5e0e8c8b7',       // No 0x prefix
            '0x742d35cc0b3e7c3f8f9e7ad0e1c5c3f5e0e8c8G7',      // Invalid character 'G'
        ];

        foreach ($invalidAddresses as $address) {
            expect(fn() => EthWallet::addExternal($address))
                ->toThrow(InvalidArgumentException::class, 'Invalid Ethereum address format');
        }
    });

    test('accepts valid ethereum address without checksum validation', function () {
        expect(fn() => EthWallet::addExternal('0x742d35cc6dd4dc3f8f9e7ad0e1c5c3f5e0e8c8b7'))
            ->not->toThrow(\Exception::class);
    });
});

describe('Ethereum Wallet Database Operations', function () {
    test('adds ethereum wallet to database with correct attributes', function () {
        $address = '0x742d35cc6dd4dc3f8f9e7ad0e1c5c3f5e0e8c8b7';
        
        $wallet = EthWallet::addExternal($address);
        
        expect($wallet)->toBeInstanceOf(EthWallet::class)
            ->and($wallet->getAddress())->toBe($address)
            ->and($wallet->getPublicKey())->toBe('') // No public key derivable from ETH address
            ->and($wallet->getOwner())->toBeNull();
        
        // Verify database record
        $record = DB::table('wallets')->where('address', $address)->first();
        expect($record)->not->toBeNull()
            ->and($record->protocol)->toBe('eth')
            ->and($record->wallet_type)->toBe('external')
            ->and($record->public_key)->toBe('')
            ->and($record->private_key)->toBeNull();
    });

    test('prevents duplicate ethereum wallet additions', function () {
        $address = '0x742d35cc6dd4dc3f8f9e7ad0e1c5c3f5e0e8c8b7';
        
        $wallet1 = EthWallet::addExternal($address);
        $wallet2 = EthWallet::addExternal($address); // Should return existing
        
        expect($wallet1->getAddress())->toBe($wallet2->getAddress());
        
        // Should only be one record in database
        expect(DB::table('wallets')->where('address', $address)->count())->toBe(1);
    });

    test('adds ethereum wallet with owner', function () {
        $user = mockUser(123);
        $address = '0x742d35cc6dd4dc3f8f9e7ad0e1c5c3f5e0e8c8b7';
        
        $wallet = EthWallet::addExternal($address, $user);
        
        expect($wallet->getOwner())->toBe($user);
        
        $record = DB::table('wallets')->where('address', $address)->first();
        expect($record->owner_id)->toBe(123);
    });
});
