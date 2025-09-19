<?php

namespace Roberts\LaravelWallets\Services;

use Illuminate\Database\Eloquent\Model;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;
use Roberts\LaravelWallets\Wallets\EthWallet;
use Roberts\LaravelWallets\Wallets\SolWallet;

class WalletService
{
    public function __construct(
        private SecurityService $securityService
    ) {}

    /**
     * Create a custodial wallet (full private key control).
     */
    public function createCustodialWallet(
        Protocol|string $protocol,
        Model $owner,
        int $tenantId,
        array $metadata = [],
        ?string $privateKey = null,
        ?string $address = null
    ): array {
        $protocol = $protocol instanceof Protocol ? $protocol : Protocol::from($protocol);

        if (! $this->isProtocolSupported($protocol)) {
            throw new \InvalidArgumentException('Unsupported protocol: '.$protocol->value);
        }

        // Validate inputs
        $this->securityService->auditOperation('create_custodial_wallet', [
            'protocol' => $protocol->value,
            'owner_type' => get_class($owner),
            'tenant_id' => $tenantId,
        ]);

        // Generate wallet address and private key if not provided
        if (! $privateKey) {
            $keyPair = match ($protocol) {
                Protocol::ETH => EthWallet::generateKeyPair(),
                Protocol::SOL => SolWallet::generateKeyPair(),
            };
            $privateKey = $keyPair['privateKey'];
            $address = $keyPair['address'];
        }

        if (! $address) {
            $address = match ($protocol) {
                Protocol::ETH => EthWallet::getAddressFromPrivate($privateKey),
                Protocol::SOL => SolWallet::getAddressFromPrivate($privateKey),
            };
        }

        // Validate the generated/provided private key and address
        if (! $this->securityService->validatePrivateKey($privateKey, $protocol->value)) {
            throw new \InvalidArgumentException('Invalid private key format for protocol: '.$protocol->value);
        }

        if (! $this->securityService->validateAddress($address, $protocol->value)) {
            throw new \InvalidArgumentException('Invalid address format for protocol: '.$protocol->value);
        }

        // Check if wallet already exists
        $existingWallet = Wallet::where('protocol', $protocol)
            ->where('address', $address)
            ->first();

        if ($existingWallet) {
            throw new \InvalidArgumentException('Wallet with this address already exists');
        }

        // Create wallet registry entry
        $wallet = Wallet::create([
            'protocol' => $protocol,
            'address' => $address,
            'control_type' => ControlType::CUSTODIAL,
            'metadata' => $metadata,
        ]);

        // Create ownership record with encrypted private key
        $walletOwner = WalletOwner::create([
            'wallet_id' => $wallet->id,
            'tenant_id' => $tenantId,
            'owner_id' => $owner->getKey(),
            'owner_type' => get_class($owner),
            'encrypted_private_key' => $privateKey, // This will be automatically encrypted by the model
        ]);

        return [
            'wallet' => $wallet->fresh(),
            'walletOwner' => $walletOwner->fresh(),
        ];
    }

    /**
     * Add an external wallet for watching.
     */
    public function addExternalWallet(
        Protocol|string $protocol,
        string $address,
        Model $owner,
        int $tenantId,
        ?array $metadata = null
    ): array {
        $protocol = $protocol instanceof Protocol ? $protocol : Protocol::from($protocol);

        if (! $this->isProtocolSupported($protocol)) {
            throw new \InvalidArgumentException('Unsupported protocol: '.$protocol->value);
        }

        // Validate the address format
        if (! $this->securityService->validateAddress($address, $protocol->value)) {
            $protocolName = match ($protocol) {
                Protocol::ETH => 'Ethereum',
                Protocol::SOL => 'Solana',
            };
            throw new \InvalidArgumentException("Invalid {$protocolName} address format");
        }

        // Audit the operation
        $this->securityService->auditOperation('add_external_wallet', [
            'protocol' => $protocol->value,
            'address' => $address,
            'owner_type' => get_class($owner),
            'tenant_id' => $tenantId,
        ]);

        // Check if wallet already exists in registry
        $wallet = Wallet::firstOrCreate(
            [
                'protocol' => $protocol,
                'address' => $address,
            ],
            [
                'control_type' => ControlType::EXTERNAL,
                'metadata' => $metadata ?? [],
            ]
        );

        // Check if this owner already has access to this wallet in this tenant
        $existingOwnership = WalletOwner::where('wallet_id', $wallet->id)
            ->where('owner_id', $owner->getKey())
            ->where('owner_type', get_class($owner))
            ->where('tenant_id', $tenantId)
            ->first();

        if ($existingOwnership) {
            return [
                'wallet' => $wallet->fresh(),
                'walletOwner' => $existingOwnership->fresh(),
                'created' => false,
            ];
        }

        // Create new ownership record (external wallets don't have private keys)
        $walletOwner = WalletOwner::create([
            'wallet_id' => $wallet->id,
            'tenant_id' => $tenantId,
            'owner_id' => $owner->getKey(),
            'owner_type' => get_class($owner),
            'encrypted_private_key' => null,
        ]);

        return [
            'wallet' => $wallet->fresh(),
            'walletOwner' => $walletOwner->fresh(),
            'created' => true,
        ];
    }

    /**
     * Check if a protocol is supported.
     */
    private function isProtocolSupported(Protocol $protocol): bool
    {
        return in_array($protocol, [Protocol::ETH, Protocol::SOL]);
    }
}
