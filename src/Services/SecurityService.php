<?php

namespace Roberts\LaravelWallets\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Log;
use Roberts\LaravelWallets\Contracts\SecurityServiceInterface;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\WalletAuditLog;
use Roberts\LaravelWallets\Security\SecureWalletData;

/**
 * Default implementation of the security service.
 *
 * Provides comprehensive security features including input validation,
 * permission checking, audit logging, rate limiting, and intrusion detection.
 */
class SecurityService implements SecurityServiceInterface
{
    private Request $request;

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function validateAddress(string $address, string $protocol): bool
    {
        // Basic validation
        if (empty(trim($address))) {
            return false;
        }

        $protocol = strtolower(trim($protocol));

        return match ($protocol) {
            Protocol::ETH->value => $this->validateEthereumAddress($address),
            Protocol::SOL->value => $this->validateSolanaAddress($address),
            default => false,
        };
    }

    public function validatePrivateKey(string $privateKey, string $protocol): bool
    {
        // Basic validation - protocol adapters should handle detailed validation
        if (empty(trim($privateKey))) {
            return false;
        }

        $protocol = strtolower(trim($protocol));

        return match ($protocol) {
            Protocol::ETH->value => $this->validateEthereumPrivateKey($privateKey),
            Protocol::SOL->value => $this->validateSolanaPrivateKey($privateKey),
            default => false,
        };
    }

    public function canPerformOperation(string $operation, array $context = []): bool
    {
        // Check if user is authenticated
        if (! Auth::check()) {
            $this->auditOperation($operation, $context, 'permission_denied_unauthenticated');

            return false;
        }

        $user = Auth::user();

        // Basic permission checks
        $permissions = $this->getSecurityConfig('permissions');

        if (! isset($permissions[$operation])) {
            // If operation is not defined in permissions, deny by default
            $this->auditOperation($operation, $context, 'permission_denied_undefined');

            return false;
        }

        $requiredRole = $permissions[$operation]['required_role'] ?? null;

        if ($requiredRole && ! $this->userHasRole($user, $requiredRole)) {
            $this->auditOperation($operation, $context, 'permission_denied_insufficient_role');

            return false;
        }

        // Check rate limiting
        if (! $this->checkRateLimit($operation, (string) ($user->id ?? 'anonymous'))) {
            $this->auditOperation($operation, $context, 'permission_denied_rate_limited');

            return false;
        }

        return true;
    }

    public function auditOperation(string $operation, array $data, ?string $outcome = null): void
    {
        $userId = Auth::check() ? (int) Auth::id() : null;
        $outcome = $outcome ?? 'success';

        $logData = [
            'user_id' => $userId,
            'operation' => $operation,
            'context' => json_encode($data),
            'outcome' => $outcome,
            'risk_level' => $this->calculateRiskLevel($operation, $data),
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'performed_at' => now(),
        ];

        // Log to file
        Log::info("Security audit: {$operation}", $logData);

        // Store in database
        try {
            WalletAuditLog::create($logData);
        } catch (\Exception $e) {
            // If database logging fails, continue - file log is our backup
            Log::error('Failed to create audit log entry: '.$e->getMessage(), ['context' => $logData]);
        }

        // Check for suspicious activity
        $this->detectSuspiciousActivity($operation, $data);
    }

    public function checkRateLimit(string $operation, ?string $identifier = null): bool
    {
        $identifier = $identifier ?? Auth::id() ?? $this->request->ip();
        $config = $this->getSecurityConfig('rate_limits');

        if (! isset($config[$operation])) {
            return true; // No rate limit defined
        }

        $limit = $config[$operation]['limit'];
        $window = $config[$operation]['window']; // in seconds

        $cacheKey = "rate_limit:{$operation}:{$identifier}";
        $attempts = Cache::get($cacheKey, 0);

        if ($attempts >= $limit) {
            return false;
        }

        Cache::put($cacheKey, $attempts + 1, $window);

        return true;
    }

    public function detectSuspiciousActivity(string $operation, array $context): array
    {
        $warnings = [];

        // Check for unusual IP activity
        if ($this->isUnusualIpActivity()) {
            $warnings[] = 'unusual_ip_activity';
        }

        // Check for rapid successive operations
        if ($this->isRapidOperationPattern($operation)) {
            $warnings[] = 'rapid_operation_pattern';
        }

        // Check for high-risk operations outside normal hours
        if ($this->isOffHoursHighRiskOperation($operation)) {
            $warnings[] = 'off_hours_high_risk';
        }

        // Check for operations from new devices
        if ($this->isNewDeviceOperation()) {
            $warnings[] = 'new_device_operation';
        }

        return $warnings;
    }

    public function sanitizeInput(mixed $input, string $type): mixed
    {
        return match ($type) {
            'address' => $this->sanitizeAddress($input),
            'protocol' => is_string($input) ? strtoupper(trim(strip_tags($input))) : '',
            'numeric' => is_numeric($input) ? $input : 0,
            'string' => is_string($input) ? trim(strip_tags($input)) : '',
            'array' => is_array($input) ? $input : [],
            default => $input,
        };
    }

    /**
     * Sanitize address input by extracting valid address patterns.
     */
    private function sanitizeAddress(mixed $input): string
    {
        if (! is_string($input)) {
            return '';
        }

        $cleaned = strip_tags($input);

        // Extract Ethereum address pattern (0x followed by hex chars)
        if (preg_match('/0x[a-fA-F0-9]+/', $cleaned, $matches)) {
            return $matches[0];
        }

        // Extract Solana address pattern (Base58 characters)
        if (preg_match('/[1-9A-HJ-NP-Za-km-z]+/', $cleaned, $matches)) {
            return $matches[0];
        }

        // If no valid address pattern found, return cleaned string
        return trim($cleaned);
    }

    /**
     * Get security configuration value.
     *
     * @return array<string, mixed>
     */
    public function getSecurityConfig(string $context): array
    {
        /** @var array<string, mixed> $config */
        $config = Config::get("wallets.security.{$context}", []);

        return $config;
    }

    public function validateWalletData(SecureWalletData $walletData): array
    {
        $errors = [];

        try {
            $address = $walletData->getAddress();

            // Validate address format
            if (empty($address)) {
                $errors[] = 'Address cannot be empty';
            }

            // Validate private key (through secure callback)
            $walletData->withPrivateKey(function ($privateKey) use (&$errors) {
                if (strlen($privateKey) < 32) {
                    $errors[] = 'Private key is too short';
                }

                // Additional private key validations can be added here
            });

        } catch (\Exception $e) {
            $errors[] = 'Failed to validate wallet data: '.$e->getMessage();
        }

        return $errors;
    }

    /**
     * Get required security measures for the given operation and context.
     *
     * @param  string  $operation  The operation being performed
     * @param  array<string, mixed>  $context  The context to check
     * @return array<int, string> Array of required security measures
     */
    public function getRequiredSecurityMeasures(string $operation, array $context): array
    {
        $measures = [];
        $config = $this->getSecurityConfig('required_measures');

        if (isset($config[$operation])) {
            $measures = $config[$operation];
        }

        // Add dynamic measures based on context
        if ($this->isHighRiskOperation($operation, $context)) {
            $measures[] = 'additional_confirmation';
            $measures[] = 'enhanced_logging';
        }

        return $measures;
    }

    /**
     * Validate Ethereum address format.
     */
    private function validateEthereumAddress(string $address): bool
    {
        return preg_match('/^0x[a-fA-F0-9]{40}$/', $address) === 1;
    }

    /**
     * Validate Solana address format.
     */
    private function validateSolanaAddress(string $address): bool
    {
        // Solana addresses are base58 encoded and typically 32-44 characters
        return preg_match('/^[1-9A-HJ-NP-Za-km-z]{32,44}$/', $address) === 1;
    }

    /**
     * Validate Ethereum private key format.
     */
    private function validateEthereumPrivateKey(string $privateKey): bool
    {
        // Ethereum private keys are 64 hex characters (with or without 0x prefix)
        $key = str_starts_with($privateKey, '0x') ? substr($privateKey, 2) : $privateKey;

        if (preg_match('/^[a-fA-F0-9]{64}$/', $key) !== 1) {
            return false;
        }

        // Reject invalid private keys (all zeros, all ones, etc.)
        if ($key === str_repeat('0', 64) || $key === str_repeat('f', 64) || $key === str_repeat('F', 64)) {
            return false;
        }

        return true;
    }

    /**
     * Validate Solana private key format.
     */
    private function validateSolanaPrivateKey(string $privateKey): bool
    {
        // Solana private keys are typically 64 bytes encoded in various formats
        // This is a basic check - the protocol adapter should do detailed validation
        return strlen($privateKey) >= 64;
    }

    /**
     * Check if user has the required role.
     */
    private function userHasRole($user, string $role): bool
    {
        // This would depend on your authorization system
        // For Laravel Breeze/Sanctum, you might check against a roles relationship
        // For now, assume all authenticated users can perform basic operations
        return true;
    }

    /**
     * Detect unusual IP activity.
     */
    private function isUnusualIpActivity(): bool
    {
        // Implementation would check against known IPs for the user
        return false;
    }

    /**
     * Check for rapid operation patterns.
     */
    private function isRapidOperationPattern(string $operation): bool
    {
        $userId = Auth::id() ?? $this->request->ip();
        $cacheKey = "operation_pattern:{$operation}:{$userId}";
        $recentOps = Cache::get($cacheKey, []);

        // Check if more than 5 operations in the last minute
        $recent = array_filter($recentOps, fn ($time) => $time > now()->subMinute()->timestamp);

        if (count($recent) > 5) {
            return true;
        }

        // Add current operation
        $recentOps[] = now()->timestamp;
        Cache::put($cacheKey, array_slice($recentOps, -10), 300); // Keep last 10, for 5 minutes

        return false;
    }

    /**
     * Check for high-risk operations outside normal hours.
     */
    private function isOffHoursHighRiskOperation(string $operation): bool
    {
        $hour = now()->hour;
        $isOffHours = $hour < 6 || $hour > 22; // Outside 6 AM - 10 PM
        $highRiskOps = ['create_custodial_wallet', 'export_private_key'];

        return $isOffHours && in_array($operation, $highRiskOps);
    }

    /**
     * Check if the operation is from a new device.
     */
    private function isNewDeviceOperation(): bool
    {
        if (! Auth::check()) {
            return true; // Unauthenticated is always "new"
        }

        $deviceFingerprint = hash('sha256', $this->request->userAgent().$this->request->ip());
        $userId = Auth::id();

        $knownDevices = Cache::get("known_devices:{$userId}", []);

        if (! in_array($deviceFingerprint, $knownDevices)) {
            // Add to known devices
            $knownDevices[] = $deviceFingerprint;
            Cache::put("known_devices:{$userId}", array_slice($knownDevices, -10), 86400 * 30); // Keep 10 devices for 30 days

            return true;
        }

        return false;
    }

    /**
     * Check if an operation is considered high risk.
     */
    private function isHighRiskOperation(string $operation, array $context): bool
    {
        $highRiskOps = ['create_custodial_wallet', 'export_private_key', 'bulk_import'];

        if (in_array($operation, $highRiskOps)) {
            return true;
        }

        // Check context for high-risk indicators
        if (isset($context['amount']) && $context['amount'] > 1000000) { // Large amounts
            return true;
        }

        return false;
    }

    /**
     * Calculate risk level based on operation and data
     */
    private function calculateRiskLevel(string $operation, array $data): string
    {
        // High risk operations
        if (in_array($operation, ['wallet_created', 'private_key_access', 'wallet_export'])) {
            return 'high';
        }

        // Medium risk operations
        if (in_array($operation, ['wallet_imported', 'transaction_signed', 'key_derived'])) {
            return 'medium';
        }

        // Check for suspicious patterns in data
        if (isset($data['user_agent']) && $this->isSuspiciousUserAgent((string) $data['user_agent'])) {
            return 'high';
        }

        if (isset($data['ip_address']) && $this->isSuspiciousIP((string) $data['ip_address'])) {
            return 'high';
        }

        // Default to low risk
        return 'low';
    }

    /**
     * Check if user agent appears suspicious
     */
    private function isSuspiciousUserAgent(string $userAgent): bool
    {
        $suspiciousPatterns = [
            'bot', 'crawler', 'spider', 'scraper', 'curl', 'wget',
            'automated', 'script', 'python', 'ruby', 'java',
        ];

        $userAgentLower = strtolower($userAgent);

        foreach ($suspiciousPatterns as $pattern) {
            if (strpos($userAgentLower, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if IP address appears suspicious
     */
    private function isSuspiciousIP(string $ipAddress): bool
    {
        // Check for known malicious IP ranges (this would typically be a more comprehensive list)
        $suspiciousRanges = [
            '10.0.0.0/8',     // Private network (suspicious for public app)
            '192.168.0.0/16', // Private network
            '127.0.0.0/8',    // Loopback (could indicate local testing/attack)
        ];

        foreach ($suspiciousRanges as $range) {
            if ($this->ipInRange($ipAddress, $range)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if an IP is within a given range
     */
    private function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) {
            return $ip === $range;
        }

        [$subnet, $bits] = explode('/', $range);

        if (! filter_var($ip, FILTER_VALIDATE_IP) || ! filter_var($subnet, FILTER_VALIDATE_IP)) {
            return false;
        }

        $ip = ip2long($ip);
        $subnet = ip2long($subnet);
        $mask = -1 << (32 - (int) $bits);
        $subnet &= $mask;

        return ($ip & $mask) === $subnet;
    }
}
