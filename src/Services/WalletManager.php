<?php

namespace Roberts\LaravelWallets\Services;

use Illuminate\Contracts\Container\Container;
use Illuminate\Database\Eloquent\Model;
use Roberts\LaravelWallets\Contracts\WalletAdapterInterface;
use Roberts\LaravelWallets\Contracts\WalletServiceInterface;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;

class WalletManager implements WalletServiceInterface
{
    public function __construct(protected Container $container) {}

    /**
     * Create a new wallet for a given protocol and associate it with an owner.
     */
    public function create(Protocol $protocol, Model $owner): Wallet
    {
        // Get the appropriate adapter for the protocol
        $adapter = $this->getAdapter($protocol);

        // Generate the wallet data using the adapter
        $walletData = $adapter->createWallet();

        // Create the wallet model
        $wallet = Wallet::create([
            'protocol' => $protocol,
            'address' => $walletData->getAddress(),
        ]);

        // Encrypt the private key and create the ownership record
        $encryptedPrivateKey = $walletData->withPrivateKey(
            fn (string $privateKey) => encrypt($privateKey)
        );

        WalletOwner::create([
            'wallet_id' => $wallet->id,
            'owner_id' => $owner->getKey(),
            'owner_type' => get_class($owner),
            'tenant_id' => 1, // Default tenant for now
            'encrypted_private_key' => $encryptedPrivateKey,
        ]);

        return $wallet;
    }

    /**
     * Get the appropriate wallet adapter for the given protocol.
     */
    public function getAdapter(Protocol $protocol): WalletAdapterInterface
    {
        $drivers = config('wallets.drivers', []);

        $protocolKey = strtolower($protocol->value);

        if (! isset($drivers[$protocolKey])) {
            throw new \InvalidArgumentException("No driver configured for protocol: {$protocol->value}");
        }

        $adapterClass = $drivers[$protocolKey]['adapter'];

        if (! class_exists($adapterClass)) {
            throw new \InvalidArgumentException("Adapter class does not exist: {$adapterClass}");
        }

        $adapter = $this->container->make($adapterClass);

        if (! $adapter instanceof WalletAdapterInterface) {
            throw new \InvalidArgumentException("Adapter must implement WalletAdapterInterface: {$adapterClass}");
        }

        return $adapter;
    }
}
