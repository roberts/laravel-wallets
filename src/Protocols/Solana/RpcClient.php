<?php

namespace Roberts\LaravelWallets\Protocols\Solana;

use GuzzleHttp\Client as HttpClient;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * Comprehensive Solana RPC Client
 *
 * A Laravel-focused wrapper for the Solana RPC API that follows Laravel best practices.
 * This client provides all functionality available through the Solana RPC interface.
 *
 * @link https://docs.solana.com/api/http
 */
class RpcClient
{
    protected HttpClient $httpClient;

    private string $endpoint;

    private array $defaultHeaders;

    private int $timeout;

    private bool $useCache;

    private int $cacheTimeout;

    public function __construct(
        ?string $endpoint = null,
        array $config = []
    ) {
        $this->endpoint = $endpoint ?? config('wallets.drivers.sol.rpc_url', 'https://api.mainnet-beta.solana.com');
        $this->timeout = $config['timeout'] ?? 30;
        $this->useCache = $config['cache'] ?? true;
        $this->cacheTimeout = $config['cache_timeout'] ?? 300; // 5 minutes

        $this->defaultHeaders = [
            'Content-Type' => 'application/json',
            'User-Agent' => 'LaravelWallets/1.0 (Solana RPC Client)',
        ];

        $this->httpClient = new HttpClient([
            'base_uri' => $this->endpoint,
            'timeout' => $this->timeout,
            'headers' => $this->defaultHeaders,
        ]);
    }

    /**
     * Make a generic RPC call to the Solana network
     */
    public function call(string $method, array $params = [], array $options = []): mixed
    {
        $id = $options['id'] ?? time();
        $commitment = $options['commitment'] ?? 'confirmed';

        // Prepare the RPC request
        $payload = [
            'jsonrpc' => '2.0',
            'id' => $id,
            'method' => $method,
            'params' => $params,
        ];

        // Add commitment to params if supported
        if ($this->methodSupportsCommitment($method) && ! $this->hasCommitmentInParams($params)) {
            $payload['params'][] = ['commitment' => $commitment];
        }

        $cacheKey = null;

        // Check cache for read-only methods
        if ($this->useCache && $this->isReadOnlyMethod($method)) {
            $cacheKey = 'solana_rpc_'.md5(json_encode($payload));
            $cached = Cache::get($cacheKey);
            if ($cached !== null) {
                return $cached;
            }
        }

        try {
            $response = $this->httpClient->post('', [
                'json' => $payload,
            ]);

            $body = json_decode($response->getBody()->getContents(), true);

            if (isset($body['error'])) {
                throw new RpcException(
                    $body['error']['message'] ?? 'Unknown RPC error',
                    $body['error']['code'] ?? -1,
                    $body['error']['data'] ?? null
                );
            }

            $result = $body['result'] ?? null;

            // Cache successful read-only results
            if ($cacheKey && $result !== null) {
                Cache::put($cacheKey, $result, $this->cacheTimeout);
            }

            return $result;

        } catch (GuzzleException $e) {
            Log::error('Solana RPC request failed', [
                'method' => $method,
                'params' => $params,
                'error' => $e->getMessage(),
                'endpoint' => $this->endpoint,
            ]);

            throw new RpcException('RPC request failed: '.$e->getMessage(), 0, $e);
        }
    }

    // ====================
    // ACCOUNT METHODS
    // ====================

    /**
     * Get account information for a given Pubkey
     */
    public function getAccountInfo(string $pubkey, array $options = []): ?array
    {
        $params = [$pubkey];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getAccountInfo', $params);
    }

    /**
     * Get account information for multiple accounts
     */
    public function getMultipleAccounts(array $pubkeys, array $options = []): ?array
    {
        $params = [$pubkeys];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getMultipleAccounts', $params);
    }

    /**
     * Get accounts owned by the provided program Pubkey
     */
    public function getProgramAccounts(string $programId, array $options = []): ?array
    {
        $params = [$programId];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getProgramAccounts', $params);
    }

    /**
     * Get the balance of the account of provided Pubkey
     */
    public function getBalance(string $pubkey, array $options = []): ?array
    {
        $params = [$pubkey];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getBalance', $params);
    }

    // ====================
    // BLOCK METHODS
    // ====================

    /**
     * Get a confirmed block
     */
    public function getBlock(int $slot, array $options = []): ?array
    {
        $params = [$slot];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getBlock', $params);
    }

    /**
     * Get the current block height of the node
     */
    public function getBlockHeight(array $options = []): ?int
    {
        $params = [];

        if (! empty($options)) {
            $params[] = $options;
        }

        $result = $this->call('getBlockHeight', $params);
        
        return is_int($result) ? $result : null;
    }

    /**
     * Get block production information
     */
    public function getBlockProduction(array $options = []): ?array
    {
        $params = [];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getBlockProduction', $params);
    }

    /**
     * Get commitment for particular block
     */
    public function getBlockCommitment(int $block): ?array
    {
        return $this->call('getBlockCommitment', [$block]);
    }

    /**
     * Get a list of confirmed blocks between two slots
     */
    public function getBlocks(int $startSlot, ?int $endSlot = null, array $options = []): ?array
    {
        $params = [$startSlot];

        if ($endSlot !== null) {
            $params[] = $endSlot;
        }

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getBlocks', $params);
    }

    /**
     * Get a list of confirmed blocks starting at given slot
     */
    public function getBlocksWithLimit(int $startSlot, int $limit, array $options = []): ?array
    {
        $params = [$startSlot, $limit];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getBlocksWithLimit', $params);
    }

    /**
     * Get estimated production time of a block
     */
    public function getBlockTime(int $block): ?int
    {
        $result = $this->call('getBlockTime', [$block]);
        
        return is_int($result) ? $result : null;
    }

    // ====================
    // TRANSACTION METHODS
    // ====================

    /**
     * Get transaction details for a confirmed transaction
     */
    public function getTransaction(string $signature, array $options = []): ?array
    {
        $params = [$signature];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getTransaction', $params);
    }

    /**
     * Get the statuses of a list of signatures
     */
    public function getSignatureStatuses(array $signatures, bool $searchTransactionHistory = false): ?array
    {
        $params = [$signatures];

        if ($searchTransactionHistory) {
            $params[] = ['searchTransactionHistory' => true];
        }

        return $this->call('getSignatureStatuses', $params);
    }

    /**
     * Get confirmed signatures for transactions involving an address
     */
    public function getSignaturesForAddress(string $address, array $options = []): ?array
    {
        $params = [$address];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getSignaturesForAddress', $params);
    }

    /**
     * Submit a signed transaction to the cluster for processing
     */
    public function sendTransaction(string $transaction, array $options = []): string
    {
        $params = [$transaction];

        if (! empty($options)) {
            $params[] = $options;
        }

        $result = $this->call('sendTransaction', $params);
        
        return is_string($result) ? $result : '';
    }

    /**
     * Simulate a transaction to get information about it
     */
    public function simulateTransaction(string $transaction, array $options = []): ?array
    {
        $params = [$transaction];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('simulateTransaction', $params);
    }

    // ====================
    // NETWORK/CLUSTER METHODS
    // ====================

    /**
     * Get information about the current epoch
     */
    public function getEpochInfo(array $options = []): ?array
    {
        $params = [];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getEpochInfo', $params);
    }

    /**
     * Get epoch schedule information from this cluster's genesis config
     */
    public function getEpochSchedule(): ?array
    {
        return $this->call('getEpochSchedule');
    }

    /**
     * Get the node health status
     */
    public function getHealth(): string
    {
        $result = $this->call('getHealth');
        
        return is_string($result) ? $result : 'unknown';
    }

    /**
     * Get the identity pubkey for the current node
     */
    public function getIdentity(): ?array
    {
        return $this->call('getIdentity');
    }

    /**
     * Get the current inflation governor
     */
    public function getInflationGovernor(array $options = []): ?array
    {
        $params = [];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getInflationGovernor', $params);
    }

    /**
     * Get the specific inflation values for the current epoch
     */
    public function getInflationRate(): ?array
    {
        return $this->call('getInflationRate');
    }

    /**
     * Get the inflation reward for a list of addresses for an epoch
     */
    public function getInflationReward(array $addresses, array $options = []): ?array
    {
        $params = [$addresses];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getInflationReward', $params);
    }

    /**
     * Get the 20 largest accounts, by lamport balance
     */
    public function getLargestAccounts(array $options = []): ?array
    {
        $params = [];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getLargestAccounts', $params);
    }

    /**
     * Get the leader schedule for an epoch
     */
    public function getLeaderSchedule(?int $slot = null, array $options = []): ?array
    {
        $params = [];

        if ($slot !== null) {
            $params[] = $slot;
        }

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getLeaderSchedule', $params);
    }

    /**
     * Get minimum balance required to make account rent exempt
     */
    public function getMinimumBalanceForRentExemption(int $accountDataLength, array $options = []): ?int
    {
        $params = [$accountDataLength];

        if (! empty($options)) {
            $params[] = $options;
        }

        $result = $this->call('getMinimumBalanceForRentExemption', $params);
        
        return is_int($result) ? $result : null;
    }

    /**
     * Get the slot that has reached the given or default commitment level
     */
    public function getSlot(array $options = []): ?int
    {
        $params = [];

        if (! empty($options)) {
            $params[] = $options;
        }

        $result = $this->call('getSlot', $params);
        
        return is_int($result) ? $result : null;
    }

    /**
     * Get the current slot leader
     */
    public function getSlotLeader(array $options = []): ?string
    {
        $params = [];

        if (! empty($options)) {
            $params[] = $options;
        }

        $result = $this->call('getSlotLeader', $params);
        
        return is_string($result) ? $result : null;
    }

    /**
     * Get the slot leaders for a given slot range
     */
    public function getSlotLeaders(int $startSlot, int $limit): ?array
    {
        return $this->call('getSlotLeaders', [$startSlot, $limit]);
    }

    /**
     * Get information about the current supply
     */
    public function getSupply(array $options = []): ?array
    {
        $params = [];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getSupply', $params);
    }

    /**
     * Get the current total transaction count from the ledger
     */
    public function getTransactionCount(array $options = []): ?int
    {
        $params = [];

        if (! empty($options)) {
            $params[] = $options;
        }

        $result = $this->call('getTransactionCount', $params);
        
        return is_int($result) ? $result : null;
    }

    /**
     * Get the current Solana version running on the node
     */
    public function getVersion(): ?array
    {
        return $this->call('getVersion');
    }

    /**
     * Get vote accounts for the current epoch
     */
    public function getVoteAccounts(array $options = []): ?array
    {
        $params = [];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getVoteAccounts', $params);
    }

    // ====================
    // TOKEN METHODS
    // ====================

    /**
     * Get token account balance
     */
    public function getTokenAccountBalance(string $tokenAccount, array $options = []): ?array
    {
        $params = [$tokenAccount];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getTokenAccountBalance', $params);
    }

    /**
     * Get all token accounts by owner
     */
    public function getTokenAccountsByOwner(string $owner, array $filter, array $options = []): ?array
    {
        $params = [$owner, $filter];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getTokenAccountsByOwner', $params);
    }

    /**
     * Get all token accounts by token mint
     */
    public function getTokenAccountsByDelegate(string $delegate, array $filter, array $options = []): ?array
    {
        $params = [$delegate, $filter];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getTokenAccountsByDelegate', $params);
    }

    /**
     * Get the largest accounts for a token
     */
    public function getTokenLargestAccounts(string $tokenMint, array $options = []): ?array
    {
        $params = [$tokenMint];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getTokenLargestAccounts', $params);
    }

    /**
     * Get information about a token's supply
     */
    public function getTokenSupply(string $tokenMint, array $options = []): ?array
    {
        $params = [$tokenMint];

        if (! empty($options)) {
            $params[] = $options;
        }

        return $this->call('getTokenSupply', $params);
    }

    // ====================
    // UTILITY METHODS
    // ====================

    /**
     * Request airdrop of lamports to a Pubkey (only on devnet/testnet)
     */
    public function requestAirdrop(string $pubkey, int $lamports, array $options = []): string
    {
        $params = [$pubkey, $lamports];

        if (! empty($options)) {
            $params[] = $options;
        }

        $result = $this->call('requestAirdrop', $params);
        
        return is_string($result) ? $result : '';
    }

    /**
     * Get cluster nodes
     */
    public function getClusterNodes(): ?array
    {
        return $this->call('getClusterNodes');
    }

    /**
     * Get recent performance samples
     */
    public function getRecentPerformanceSamples(?int $limit = null): ?array
    {
        $params = [];

        if ($limit !== null) {
            $params[] = $limit;
        }

        return $this->call('getRecentPerformanceSamples', $params);
    }

    // ====================
    // CONVENIENCE/HELPER METHODS
    // ====================

    /**
     * Set a custom endpoint
     */
    public function setEndpoint(string $endpoint): self
    {
        $this->endpoint = $endpoint;
        $this->httpClient = new HttpClient([
            'base_uri' => $this->endpoint,
            'timeout' => $this->timeout,
            'headers' => $this->defaultHeaders,
        ]);

        return $this;
    }

    /**
     * Get current endpoint
     */
    public function getEndpoint(): string
    {
        return $this->endpoint;
    }

    /**
     * Enable or disable caching
     */
    public function setCache(bool $useCache): self
    {
        $this->useCache = $useCache;

        return $this;
    }

    /**
     * Set cache timeout in seconds
     */
    public function setCacheTimeout(int $seconds): self
    {
        $this->cacheTimeout = $seconds;

        return $this;
    }

    /**
     * Clear all cached RPC responses
     */
    public function clearCache(): bool
    {
        return Cache::forget('solana_rpc_*');
    }

    /**
     * Convert lamports to SOL
     */
    public function lamportsToSol(int $lamports): float
    {
        return $lamports / 1_000_000_000;
    }

    /**
     * Convert SOL to lamports
     */
    public function solToLamports(float $sol): int
    {
        return (int) ($sol * 1_000_000_000);
    }

    // ====================
    // PRIVATE HELPER METHODS
    // ====================

    /**
     * Check if method supports commitment parameter
     */
    private function methodSupportsCommitment(string $method): bool
    {
        $commitmentMethods = [
            'getAccountInfo',
            'getBalance',
            'getBlock',
            'getBlockHeight',
            'getBlockProduction',
            'getBlocks',
            'getBlocksWithLimit',
            'getConfirmedSignaturesForAddress2',
            'getEpochInfo',
            'getInflationGovernor',
            'getInflationReward',
            'getLargestAccounts',
            'getLeaderSchedule',
            'getMinimumBalanceForRentExemption',
            'getMultipleAccounts',
            'getProgramAccounts',
            'getSlot',
            'getSlotLeader',
            'getSupply',
            'getTokenAccountBalance',
            'getTokenAccountsByDelegate',
            'getTokenAccountsByOwner',
            'getTokenLargestAccounts',
            'getTokenSupply',
            'getTransactionCount',
            'getVoteAccounts',
        ];

        return in_array($method, $commitmentMethods);
    }

    /**
     * Check if params already contain commitment
     */
    private function hasCommitmentInParams(array $params): bool
    {
        foreach ($params as $param) {
            if (is_array($param) && isset($param['commitment'])) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if method is read-only (cacheable)
     */
    private function isReadOnlyMethod(string $method): bool
    {
        $readOnlyMethods = [
            'getAccountInfo',
            'getBalance',
            'getBlock',
            'getBlockCommitment',
            'getBlockHeight',
            'getBlockProduction',
            'getBlocks',
            'getBlocksWithLimit',
            'getBlockTime',
            'getClusterNodes',
            'getEpochInfo',
            'getEpochSchedule',
            'getHealth',
            'getHighestSnapshotSlot',
            'getIdentity',
            'getInflationGovernor',
            'getInflationRate',
            'getInflationReward',
            'getLargestAccounts',
            'getLeaderSchedule',
            'getMaxRetransmitSlot',
            'getMaxShredInsertSlot',
            'getMinimumBalanceForRentExemption',
            'getMultipleAccounts',
            'getProgramAccounts',
            'getRecentPerformanceSamples',
            'getSignatureStatuses',
            'getSignaturesForAddress',
            'getSlot',
            'getSlotLeader',
            'getSlotLeaders',
            'getSupply',
            'getTokenAccountBalance',
            'getTokenAccountsByDelegate',
            'getTokenAccountsByOwner',
            'getTokenLargestAccounts',
            'getTokenSupply',
            'getTransaction',
            'getTransactionCount',
            'getVersion',
            'getVoteAccounts',
        ];

        return in_array($method, $readOnlyMethods);
    }
}
