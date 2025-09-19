<?php

namespace Roberts\LaravelWallets\Services\Solana;

use Roberts\LaravelWallets\Protocols\Solana\RpcClient;
use Roberts\LaravelWallets\Protocols\Solana\RpcException;
use Illuminate\Support\Collection;

/**
 * High-level Solana service using the RPC client
 * 
 * Provides convenient methods for common Solana operations
 */
class SolanaService
{
    public function __construct(
        private RpcClient $rpcClient
    ) {}

    /**
     * Get account information with balance in both lamports and SOL
     */
    public function getAccountDetails(string $pubkey): ?array
    {
        $accountInfo = $this->rpcClient->getAccountInfo($pubkey);
        
        if (!$accountInfo) {
            return null;
        }

        $balanceInfo = $this->rpcClient->getBalance($pubkey);
        $lamports = $balanceInfo['value'] ?? 0;

        return [
            'pubkey' => $pubkey,
            'account_info' => $accountInfo,
            'balance' => [
                'lamports' => $lamports,
                'sol' => $this->rpcClient->lamportsToSol($lamports),
            ],
        ];
    }

    /**
     * Get token accounts for an owner
     */
    public function getTokenAccountsForOwner(string $ownerPubkey): Collection
    {
        try {
            // Get all token accounts for this owner
            $tokenAccounts = $this->rpcClient->getTokenAccountsByOwner(
                $ownerPubkey,
                ['programId' => 'TokenkegQfeZyiNwAJbNbGKPFXCWuBvf9Ss623VQ5DA'] // SPL Token program
            );

            if (!$tokenAccounts || !isset($tokenAccounts['value'])) {
                return collect([]);
            }

            return collect($tokenAccounts['value'])->map(function ($account) {
                $accountInfo = $account['account'] ?? [];
                $pubkey = $account['pubkey'] ?? '';
                
                // Decode token account data if available
                $data = $accountInfo['data']['parsed'] ?? null;
                
                return [
                    'pubkey' => $pubkey,
                    'mint' => $data['info']['mint'] ?? null,
                    'owner' => $data['info']['owner'] ?? null,
                    'balance' => $data['info']['tokenAmount'] ?? null,
                    'account_info' => $accountInfo,
                ];
            });

        } catch (RpcException $e) {
            // Log the error or handle it as needed
            return collect([]);
        }
    }

    /**
     * Get transaction history for an address
     */
    public function getTransactionHistory(string $address, int $limit = 10): Collection
    {
        try {
            $signatures = $this->rpcClient->getSignaturesForAddress($address, [
                'limit' => $limit
            ]);

            if (!$signatures) {
                return collect([]);
            }

            return collect($signatures)->map(function ($signatureInfo) {
                return [
                    'signature' => $signatureInfo['signature'],
                    'slot' => $signatureInfo['slot'],
                    'block_time' => $signatureInfo['blockTime'] ?? null,
                    'confirmation_status' => $signatureInfo['confirmationStatus'] ?? null,
                    'err' => $signatureInfo['err'] ?? null,
                    'memo' => $signatureInfo['memo'] ?? null,
                ];
            });

        } catch (RpcException $e) {
            return collect([]);
        }
    }

    /**
     * Get detailed transaction information
     */
    public function getTransactionDetails(string $signature): ?array
    {
        try {
            $transaction = $this->rpcClient->getTransaction($signature, [
                'encoding' => 'jsonParsed',
                'maxSupportedTransactionVersion' => 0
            ]);

            if (!$transaction) {
                return null;
            }

            return [
                'signature' => $signature,
                'slot' => $transaction['slot'] ?? null,
                'block_time' => $transaction['blockTime'] ?? null,
                'transaction' => $transaction['transaction'] ?? null,
                'meta' => $transaction['meta'] ?? null,
            ];

        } catch (RpcException $e) {
            return null;
        }
    }

    /**
     * Check if an address is a valid Solana public key
     */
    public function isValidPublicKey(string $address): bool
    {
        // Basic validation: Solana addresses are base58 encoded and 32-44 characters
        if (strlen($address) < 32 || strlen($address) > 44) {
            return false;
        }

        // Check if it's valid base58
        $base58Chars = '123456789ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz';
        for ($i = 0; $i < strlen($address); $i++) {
            if (strpos($base58Chars, $address[$i]) === false) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get current epoch information with additional calculations
     */
    public function getCurrentEpochInfo(): ?array
    {
        try {
            $epochInfo = $this->rpcClient->getEpochInfo();
            
            if (!$epochInfo) {
                return null;
            }

            // Calculate additional useful information
            $slotsInEpoch = $epochInfo['slotsInEpoch'] ?? 0;
            $slotIndex = $epochInfo['slotIndex'] ?? 0;
            
            $progressPercent = $slotsInEpoch > 0 ? ($slotIndex / $slotsInEpoch) * 100 : 0;
            $slotsRemaining = $slotsInEpoch - $slotIndex;

            return array_merge($epochInfo, [
                'progress_percent' => round($progressPercent, 2),
                'slots_remaining' => $slotsRemaining,
            ]);

        } catch (RpcException $e) {
            return null;
        }
    }

    /**
     * Get network stats
     */
    public function getNetworkStats(): array
    {
        try {
            $health = $this->rpcClient->getHealth();
            $version = $this->rpcClient->getVersion();
            $epochInfo = $this->rpcClient->getEpochInfo();
            $supply = $this->rpcClient->getSupply();
            $transactionCount = $this->rpcClient->getTransactionCount();

            return [
                'health' => $health,
                'version' => $version,
                'epoch_info' => $epochInfo,
                'supply' => $supply,
                'transaction_count' => $transactionCount,
                'network_endpoint' => $this->rpcClient->getEndpoint(),
            ];

        } catch (RpcException $e) {
            return [
                'error' => $e->getMessage(),
                'network_endpoint' => $this->rpcClient->getEndpoint(),
            ];
        }
    }

    /**
     * Send and confirm a transaction
     */
    public function sendAndConfirmTransaction(string $transaction, array $options = []): array
    {
        try {
            // Send the transaction
            $signature = $this->rpcClient->sendTransaction($transaction, $options);

            // Wait for confirmation
            $maxRetries = $options['max_retries'] ?? 30;
            $retryDelay = $options['retry_delay'] ?? 1; // seconds

            for ($i = 0; $i < $maxRetries; $i++) {
                sleep($retryDelay);
                
                $status = $this->rpcClient->getSignatureStatuses([$signature]);
                
                if ($status && isset($status['value'][0])) {
                    $signatureStatus = $status['value'][0];
                    
                    if ($signatureStatus) {
                        if ($signatureStatus['err']) {
                            throw new RpcException('Transaction failed: ' . json_encode($signatureStatus['err']));
                        }
                        
                        if ($signatureStatus['confirmationStatus'] === 'confirmed' || 
                            $signatureStatus['confirmationStatus'] === 'finalized') {
                            
                            return [
                                'signature' => $signature,
                                'status' => $signatureStatus,
                                'confirmed' => true,
                            ];
                        }
                    }
                }
            }

            throw new RpcException('Transaction confirmation timeout');

        } catch (RpcException $e) {
            return [
                'signature' => $signature ?? null,
                'error' => $e->getMessage(),
                'confirmed' => false,
            ];
        }
    }

    /**
     * Get token metadata if available
     */
    public function getTokenMetadata(string $mintAddress): ?array
    {
        try {
            // Get token supply first
            $supply = $this->rpcClient->getTokenSupply($mintAddress);
            
            if (!$supply) {
                return null;
            }

            // Try to get token account information
            $accountInfo = $this->rpcClient->getAccountInfo($mintAddress);

            return [
                'mint' => $mintAddress,
                'supply' => $supply,
                'account_info' => $accountInfo,
            ];

        } catch (RpcException $e) {
            return null;
        }
    }

    /**
     * Get the minimum balance required for rent exemption
     */
    public function getRentExemptionBalance(int $dataLength): ?int
    {
        try {
            return $this->rpcClient->getMinimumBalanceForRentExemption($dataLength);
        } catch (RpcException $e) {
            return null;
        }
    }

    /**
     * Check if account exists and is rent exempt
     */
    public function isAccountRentExempt(string $pubkey): ?array
    {
        try {
            $accountInfo = $this->rpcClient->getAccountInfo($pubkey);
            
            if (!$accountInfo || !isset($accountInfo['value'])) {
                return [
                    'exists' => false,
                    'rent_exempt' => false,
                    'balance' => 0,
                ];
            }

            $lamports = $accountInfo['value']['lamports'] ?? 0;
            $dataLength = isset($accountInfo['value']['data']) 
                ? strlen(base64_decode($accountInfo['value']['data'][0] ?? '')) 
                : 0;

            $minBalance = $this->getRentExemptionBalance($dataLength);
            $rentExempt = $minBalance ? $lamports >= $minBalance : false;

            return [
                'exists' => true,
                'rent_exempt' => $rentExempt,
                'balance' => $lamports,
                'min_balance_required' => $minBalance,
                'data_length' => $dataLength,
            ];

        } catch (RpcException $e) {
            return null;
        }
    }

    /**
     * Get the RPC client instance
     */
    public function getRpcClient(): RpcClient
    {
        return $this->rpcClient;
    }
}