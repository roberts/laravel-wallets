# Quickstart: Creating Wallets

This guide demonstrates how to use the `laravel-wallets` package to create Ethereum and Solana wallets.

## Prerequisites
1. Install the package via Composer:
   ```bash
   composer require roberts/laravel-wallets
   ```
2. Publish and run the migrations:
   ```bash
   php artisan vendor:publish --tag="wallets-migrations"
   php artisan migrate
   ```
3. Ensure your `User` model (or any other model you want to own a wallet) uses the `HasWallets` trait:
   ```php
   <?php

   namespace App\Models;

   use Illuminate\Foundation\Auth\User as Authenticatable;
   use Roberts\Wallets\Concerns\HasWallets; // Import the trait

   class User extends Authenticatable
   {
       use HasWallets; // Use the trait

       // ... rest of your model
   }
   ```

## Creating a Wallet

You can create a wallet for a user (or any model with the `HasWallets` trait) directly from the model instance.

### Create an Ethereum Wallet

```php
use Roberts\Wallets\Enums\Protocol;

// Assuming you have a user instance
$user = User::find(1);

// Create an Ethereum wallet for the user
$ethWallet = $user->wallets()->create(['protocol' => Protocol::ETH]);

echo "Ethereum Wallet Address: " . $ethWallet->address;
```

### Create a Solana Wallet

```php
use Roberts\Wallets\Enums\Protocol;

// Assuming you have a user instance
$user = User::find(1);

// Create a Solana wallet for the user
$solWallet = $user->wallets()->create(['protocol' => Protocol::SOL]);

echo "Solana Wallet Address: " . $solWallet->address;
```

## How It Works

When you call `create()` on the `wallets` relationship:
1. The `protocol` is used to determine which blockchain-specific adapter to use (e.g., `EthereumAdapter` or `SolanaAdapter`).
2. The adapter generates a new, secure key pair and public address.
3. A new `Wallet` model is created and saved to the database.
4. The `owner_id` and `owner_type` are automatically set to the user instance.
5. The private key is automatically encrypted via Laravel's built-in encryption before being stored in the `encrypted_private_key` column.

## Retrieving Wallets

You can easily retrieve all wallets associated with a user:

```php
$user = User::find(1);

foreach ($user->wallets as $wallet) {
    echo "Protocol: " . $wallet->protocol->value . ", Address: " . $wallet->address . "\n";
}
```
