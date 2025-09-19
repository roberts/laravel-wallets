<?php

namespace Roberts\LaravelWallets\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Wallet Audit Log model for storing security and operational events.
 *
 * This model provides persistent storage for audit events beyond log files,
 * enabling advanced querying and analysis of wallet operations.
 *
 * @property int $id
 * @property string $operation
 * @property string $outcome
 * @property int|null $user_id
 * @property string|null $ip_address
 * @property string|null $user_agent
 * @property array<string, mixed>|null $context_data
 * @property array<string, mixed>|null $security_flags
 * @property string|null $session_id
 * @property string|null $request_id
 * @property Carbon $performed_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 */
class WalletAuditLog extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'operation',
        'outcome',
        'user_id',
        'ip_address',
        'user_agent',
        'context_data',
        'security_flags',
        'session_id',
        'request_id',
        'performed_at',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'context_data' => 'array',
        'security_flags' => 'array',
        'performed_at' => 'datetime',
    ];

    /**
     * Get the user who performed the operation.
     *
     * @return BelongsTo<\Illuminate\Database\Eloquent\Model, $this>
     */
    public function user(): BelongsTo
    {
        /** @var class-string<\Illuminate\Database\Eloquent\Model> $userModel */
        $userModel = config('auth.providers.users.model');

        return $this->belongsTo($userModel, 'user_id');
    }

    /**
     * Scope to filter by operation type.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WalletAuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WalletAuditLog>
     */
    public function scopeForOperation(\Illuminate\Database\Eloquent\Builder $query, string $operation): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('operation', $operation);
    }

    /**
     * Scope to filter by outcome.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WalletAuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WalletAuditLog>
     */
    public function scopeWithOutcome(\Illuminate\Database\Eloquent\Builder $query, string $outcome): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('outcome', $outcome);
    }

    /**
     * Scope to filter by user.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WalletAuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WalletAuditLog>
     */
    public function scopeForUser(\Illuminate\Database\Eloquent\Builder $query, int $userId): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to filter by date range.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WalletAuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WalletAuditLog>
     */
    public function scopeInDateRange(\Illuminate\Database\Eloquent\Builder $query, Carbon $startDate, Carbon $endDate): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereBetween('performed_at', [$startDate, $endDate]);
    }

    /**
     * Scope to find suspicious activities.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WalletAuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WalletAuditLog>
     */
    public function scopeSuspicious(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereNotNull('security_flags')
            ->where('security_flags', '!=', '[]')
            ->where('security_flags', '!=', 'null');
    }

    /**
     * Scope to find failed operations.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WalletAuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WalletAuditLog>
     */
    public function scopeFailed(\Illuminate\Database\Eloquent\Builder $query): \Illuminate\Database\Eloquent\Builder
    {
        return $query->whereIn('outcome', ['failure', 'error']);
    }

    /**
     * Scope for recent events.
     *
     * @param  \Illuminate\Database\Eloquent\Builder<WalletAuditLog>  $query
     * @return \Illuminate\Database\Eloquent\Builder<WalletAuditLog>
     */
    public function scopeRecent(\Illuminate\Database\Eloquent\Builder $query, int $hours = 24): \Illuminate\Database\Eloquent\Builder
    {
        return $query->where('performed_at', '>=', now()->subHours($hours));
    }

    /**
     * Check if this log entry has security flags.
     */
    public function hasSuspiciousActivity(): bool
    {
        return ! empty($this->security_flags);
    }

    /**
     * Get a human-readable description of the operation.
     */
    public function getOperationDescription(): string
    {
        return match ($this->operation) {
            'create_custodial_wallet' => 'Created a new custodial wallet',
            'add_external_wallet' => 'Added external wallet for tracking',
            'export_private_key' => 'Exported private key',
            'bulk_import' => 'Bulk imported wallets',
            'wallet_access' => 'Accessed wallet information',
            'address_validation' => 'Validated wallet address',
            'private_key_validation' => 'Validated private key',
            default => 'Performed wallet operation: '.$this->operation,
        };
    }

    /**
     * Get the risk level of this operation.
     */
    public function getRiskLevel(): string
    {
        // High risk operations
        $highRiskOps = ['export_private_key', 'create_custodial_wallet', 'bulk_import'];

        if (in_array($this->operation, $highRiskOps)) {
            return 'high';
        }

        // Operations with suspicious activity are medium risk
        if ($this->hasSuspiciousActivity()) {
            return 'medium';
        }

        // Failed operations are medium risk
        if (in_array($this->outcome, ['failure', 'error'])) {
            return 'medium';
        }

        return 'low';
    }

    /**
     * Create an audit log entry.
     *
     * @param  string  $operation  The operation being performed
     * @param  string  $outcome  The outcome of the operation
     * @param  int|null  $userId  The user ID performing the operation
     * @param  string|null  $ipAddress  The IP address of the request
     * @param  string|null  $userAgent  The user agent string
     * @param  array<string, mixed>|null  $contextData  Additional context data
     * @param  array<string, mixed>|null  $securityFlags  Security-related flags
     * @param  string|null  $sessionId  The session ID
     * @param  string|null  $requestId  The request ID
     * @return self The created audit log entry
     */
    public static function logOperation(
        string $operation,
        string $outcome = 'success',
        ?int $userId = null,
        ?string $ipAddress = null,
        ?string $userAgent = null,
        ?array $contextData = null,
        ?array $securityFlags = null,
        ?string $sessionId = null,
        ?string $requestId = null
    ): self {
        return self::create([
            'operation' => $operation,
            'outcome' => $outcome,
            'user_id' => $userId,
            'ip_address' => $ipAddress,
            'user_agent' => $userAgent,
            'context_data' => $contextData,
            'security_flags' => $securityFlags,
            'session_id' => $sessionId,
            'request_id' => $requestId,
            'performed_at' => now(),
        ]);
    }
}
