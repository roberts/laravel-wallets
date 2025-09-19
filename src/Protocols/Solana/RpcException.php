<?php

namespace Roberts\LaravelWallets\Protocols\Solana;

use Exception;
use Throwable;

/**
 * Exception class for Solana RPC errors
 */
class RpcException extends Exception
{
    private ?array $rpcData;

    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null, ?array $rpcData = null)
    {
        parent::__construct($message, $code, $previous);
        $this->rpcData = $rpcData;
    }

    /**
     * Get additional RPC error data
     */
    public function getRpcData(): ?array
    {
        return $this->rpcData;
    }

    /**
     * Check if this is a specific Solana RPC error code
     */
    public function isRpcError(int $rpcCode): bool
    {
        return $this->getCode() === $rpcCode;
    }

    /**
     * Common Solana RPC error codes
     */
    public const ERROR_PARSE_ERROR = -32700;

    public const ERROR_INVALID_REQUEST = -32600;

    public const ERROR_METHOD_NOT_FOUND = -32601;

    public const ERROR_INVALID_PARAMS = -32602;

    public const ERROR_INTERNAL_ERROR = -32603;

    // Solana-specific error codes
    public const ERROR_BLOCK_NOT_AVAILABLE = -32004;

    public const ERROR_NODE_UNHEALTHY = -32005;

    public const ERROR_TRANSACTION_SIGNATURE_VERIFICATION_FAILURE = -32006;

    public const ERROR_BLOCK_CLEANED_UP = -32007;

    public const ERROR_SLOT_SKIPPED = -32008;

    public const ERROR_NO_SNAPSHOT = -32009;

    public const ERROR_LONG_TERM_STORAGE_SLOT_SKIPPED = -32010;

    public const ERROR_KEY_EXCLUDED_FROM_SECONDARY_INDEX = -32011;

    public const ERROR_TRANSACTION_HISTORY_NOT_AVAILABLE = -32012;

    public const ERROR_SCAN_ERROR = -32013;

    public const ERROR_TRANSACTION_SIGNATURE_LEN_MISMATCH = -32014;

    public const ERROR_BLOCK_STATUS_NOT_AVAILABLE_YET = -32015;

    /**
     * Get a human-readable error message for known RPC error codes
     */
    public function getRpcErrorDescription(): string
    {
        return match ($this->getCode()) {
            self::ERROR_PARSE_ERROR => 'Parse error: Invalid JSON was received by the server',
            self::ERROR_INVALID_REQUEST => 'Invalid Request: The JSON sent is not a valid Request object',
            self::ERROR_METHOD_NOT_FOUND => 'Method not found: The method does not exist / is not available',
            self::ERROR_INVALID_PARAMS => 'Invalid params: Invalid method parameter(s)',
            self::ERROR_INTERNAL_ERROR => 'Internal error: Internal JSON-RPC error',
            self::ERROR_BLOCK_NOT_AVAILABLE => 'Block not available: The requested block is not available',
            self::ERROR_NODE_UNHEALTHY => 'Node unhealthy: RPC node is unhealthy',
            self::ERROR_TRANSACTION_SIGNATURE_VERIFICATION_FAILURE => 'Transaction signature verification failure',
            self::ERROR_BLOCK_CLEANED_UP => 'Block cleaned up: The requested block has been cleaned up',
            self::ERROR_SLOT_SKIPPED => 'Slot skipped: The requested slot was skipped',
            self::ERROR_NO_SNAPSHOT => 'No snapshot: No snapshot available for the requested slot',
            self::ERROR_LONG_TERM_STORAGE_SLOT_SKIPPED => 'Long-term storage slot skipped',
            self::ERROR_KEY_EXCLUDED_FROM_SECONDARY_INDEX => 'Key excluded from secondary index',
            self::ERROR_TRANSACTION_HISTORY_NOT_AVAILABLE => 'Transaction history not available',
            self::ERROR_SCAN_ERROR => 'Scan error: Error occurred during account scan',
            self::ERROR_TRANSACTION_SIGNATURE_LEN_MISMATCH => 'Transaction signature length mismatch',
            self::ERROR_BLOCK_STATUS_NOT_AVAILABLE_YET => 'Block status not available yet',
            default => $this->getMessage(),
        };
    }

    /**
     * Check if this is a retryable error
     */
    public function isRetryable(): bool
    {
        $retryableCodes = [
            self::ERROR_NODE_UNHEALTHY,
            self::ERROR_INTERNAL_ERROR,
            self::ERROR_BLOCK_NOT_AVAILABLE,
            self::ERROR_NO_SNAPSHOT,
            self::ERROR_BLOCK_STATUS_NOT_AVAILABLE_YET,
        ];

        return in_array($this->getCode(), $retryableCodes);
    }
}
