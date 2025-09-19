# Security Configuration

## Logging Channel Setup

To properly use the audit logging features of Laravel Wallets, add a dedicated security logging channel to your application's `config/logging.php`:

```php
'channels' => [
    // ... existing channels

    'security' => [
        'driver' => 'daily',
        'path' => storage_path('logs/security/security.log'),
        'level' => env('LOG_LEVEL', 'info'),
        'days' => 90, // Keep security logs for 90 days
        'permission' => 0640, // Restrict file permissions
    ],
],
```

## Environment Variables

Add these environment variables to your `.env` file:

```bash
# Security Settings
WALLETS_LOG_PRIVATE_KEY_ACCESS=true
WALLETS_UNLOCK_TIMEOUT=300
WALLETS_ENABLE_SUSPICIOUS_ACTIVITY_DETECTION=true
WALLETS_ENABLE_AUDIT_LOGGING=true
WALLETS_ENABLE_RATE_LIMITING=true
```

## Required Permissions

The security service checks user roles for certain operations. If you're using a package like Spatie Permission, ensure your users have appropriate roles:

- `admin` role is required for:
  - Exporting private keys
  - Bulk wallet imports

## Rate Limiting

The package includes built-in rate limiting for security-sensitive operations. The limits are configurable in `config/wallets.php` under the `security.rate_limits` section.

## Audit Trail

All wallet operations are automatically logged to the security channel when audit logging is enabled. The logs include:

- Operation type
- User ID and IP address
- Timestamp
- Operation outcome
- Sanitized operation data (sensitive fields are redacted)

## Suspicious Activity Detection

The security service can detect potentially suspicious patterns:

- Unusual IP activity
- Rapid successive operations
- High-risk operations outside normal hours
- Operations from new devices

When suspicious activity is detected, additional security measures may be required and the activity is logged for review.