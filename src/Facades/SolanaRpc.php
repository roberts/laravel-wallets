<?php

namespace Roberts\LaravelWallets\Facades;

use Illuminate\Support\Facades\Facade;
use Roberts\LaravelWallets\Protocols\Solana\RpcClient;

/**
 * @method static array call(string $method, array $params = [], array $options = [])
 * @method static array|null getAccountInfo(string $pubkey, array $options = [])
 * @method static array|null getMultipleAccounts(array $pubkeys, array $options = [])
 * @method static array|null getProgramAccounts(string $programId, array $options = [])
 * @method static array|null getBalance(string $pubkey, array $options = [])
 * @method static array|null getBlock(int $slot, array $options = [])
 * @method static int|null getBlockHeight(array $options = [])
 * @method static array|null getBlockProduction(array $options = [])
 * @method static array|null getBlockCommitment(int $block)
 * @method static array|null getBlocks(int $startSlot, ?int $endSlot = null, array $options = [])
 * @method static array|null getBlocksWithLimit(int $startSlot, int $limit, array $options = [])
 * @method static int|null getBlockTime(int $block)
 * @method static array|null getTransaction(string $signature, array $options = [])
 * @method static array|null getSignatureStatuses(array $signatures, bool $searchTransactionHistory = false)
 * @method static array|null getSignaturesForAddress(string $address, array $options = [])
 * @method static string sendTransaction(string $transaction, array $options = [])
 * @method static array|null simulateTransaction(string $transaction, array $options = [])
 * @method static array|null getEpochInfo(array $options = [])
 * @method static array|null getEpochSchedule()
 * @method static string getHealth()
 * @method static array|null getIdentity()
 * @method static array|null getInflationGovernor(array $options = [])
 * @method static array|null getInflationRate()
 * @method static array|null getInflationReward(array $addresses, array $options = [])
 * @method static array|null getLargestAccounts(array $options = [])
 * @method static array|null getLeaderSchedule(?int $slot = null, array $options = [])
 * @method static int|null getMinimumBalanceForRentExemption(int $accountDataLength, array $options = [])
 * @method static int|null getSlot(array $options = [])
 * @method static string|null getSlotLeader(array $options = [])
 * @method static array|null getSlotLeaders(int $startSlot, int $limit)
 * @method static array|null getSupply(array $options = [])
 * @method static int|null getTransactionCount(array $options = [])
 * @method static array|null getVersion()
 * @method static array|null getVoteAccounts(array $options = [])
 * @method static array|null getTokenAccountBalance(string $tokenAccount, array $options = [])
 * @method static array|null getTokenAccountsByOwner(string $owner, array $filter, array $options = [])
 * @method static array|null getTokenAccountsByDelegate(string $delegate, array $filter, array $options = [])
 * @method static array|null getTokenLargestAccounts(string $tokenMint, array $options = [])
 * @method static array|null getTokenSupply(string $tokenMint, array $options = [])
 * @method static string requestAirdrop(string $pubkey, int $lamports, array $options = [])
 * @method static array|null getClusterNodes()
 * @method static array|null getRecentPerformanceSamples(?int $limit = null)
 * @method static RpcClient setEndpoint(string $endpoint)
 * @method static string getEndpoint()
 * @method static RpcClient setCache(bool $useCache)
 * @method static RpcClient setCacheTimeout(int $seconds)
 * @method static bool clearCache()
 * @method static float lamportsToSol(int $lamports)
 * @method static int solToLamports(float $sol)
 *
 * @see \Roberts\LaravelWallets\Protocols\Solana\RpcClient
 */
class SolanaRpc extends Facade
{
    /**
     * Get the registered name of the component.
     */
    protected static function getFacadeAccessor(): string
    {
        return RpcClient::class;
    }
}
