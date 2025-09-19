<?php

namespace Roberts\LaravelWallets\Contracts;

use Illuminate\Database\Eloquent\Model;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;

/**
 * Defines the public-facing contract for the Wallet Service.
 *
 * This interface is the primary entry point for interacting with the
 * wallet package. It abstracts the underlying complexity of wallet
 * creation across different blockchain protocols.
 */
interface WalletServiceInterface
{
    /**
     * Create a new wallet for a given protocol and associate it with an owner.
     *
     * @param  Protocol  $protocol  The blockchain protocol for the new wallet (e.g., ETH, SOL).
     * @param  Model  $owner  The Eloquent model that will own this wallet.
     * @return Wallet The newly created and persisted Wallet model.
     */
    public function create(Protocol $protocol, Model $owner): Wallet;
}
