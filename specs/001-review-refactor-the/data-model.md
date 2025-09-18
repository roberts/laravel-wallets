# Data Model: Wallet

This document details the data model for the `Wallet` entity, which is central to the wallet creation and storage feature.

## `wallets` Table Schema

The `wallets` table will store the core information for each generated wallet.

| Column Name             | Data Type                | Modifiers/Indexes                               | Description                                                                 |
|-------------------------|--------------------------|-------------------------------------------------|-----------------------------------------------------------------------------|
| `id`                    | `bigint` / `unsigned`    | `primary`, `auto_increment`                     | The unique identifier for the wallet record.                                |
| `uuid`                  | `uuid`                   | `unique`                                        | A unique, non-sequential identifier for public-facing references.           |
| `tenant_id`             | `bigint` / `unsigned`    | `index`, `foreign` (on `tenants.id`)            | Foreign key for multi-tenancy, linking the wallet to a specific tenant.     |
| `owner_id`              | `bigint` / `unsigned`    | `index`                                         | The ID of the owning model (e.g., `User`).                                  |
| `owner_type`            | `string`                 | `index`                                         | The class name of the owning model (for polymorphic relations).             |
| `protocol`              | `string`                 | `index`                                         | The blockchain protocol identifier (e.g., 'ETH', 'SOL'). Stored as an enum. |
| `address`               | `string`                 | `index`                                         | The public wallet address.                                                  |
| `encrypted_private_key` | `text`                   |                                                 | The encrypted private key or seed phrase for the wallet.                    |
| `created_at`            | `timestamp`              |                                                 | Timestamp of when the record was created.                                   |
| `updated_at`            | `timestamp`              |                                                 | Timestamp of when the record was last updated.                              |

## Eloquent Model: `Wallet`

The `Wallet` Eloquent model will represent the `wallets` table.

```php
namespace Roberts\Wallets\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Roberts\Wallets\Enums\Protocol;
use Roberts\LaravelSingledbTenancy\Concerns\BelongsToTenant;

class Wallet extends Model
{
    use BelongsToTenant; // From roberts/laravel-singledb-tenancy

    protected $fillable = [
        'protocol',
        'address',
        'encrypted_private_key',
    ];

    protected $casts = [
        'protocol' => Protocol::class, // Cast to the Protocol Enum
        'encrypted_private_key' => 'encrypted', // Automatically encrypt/decrypt
    ];

    /**
     * Get the parent owner model (e.g., a User).
     */
    public function owner(): MorphTo
    {
        return $this->morphTo();
    }
}
```

### Key Design Choices:
- **Multi-Tenancy**: The `BelongsToTenant` trait is used to satisfy the constitutional requirement for multi-tenancy.
- **Polymorphic Ownership**: A polymorphic relationship (`owner`) is used to allow any model (e.g., `User`, `Team`) to own a wallet, providing flexibility for the developer using the package.
- **Protocol Enum**: The `protocol` attribute is cast to a `Protocol` enum. This ensures type safety and makes the code more readable and maintainable.
- **Automatic Encryption**: The `encrypted_private_key` attribute is added to the `$casts` array with the `encrypted` cast. This tells Laravel to automatically encrypt the value when setting it and decrypt it when accessing it, simplifying the implementation and enforcing security.

## Relationships

- **`Wallet` to `Tenant`**: A `Wallet` belongs to a `Tenant` (via the `BelongsToTenant` trait).
- **`Wallet` to `Owner`**: A `Wallet` has a polymorphic `belongsTo` relationship to an `owner` (e.g., `App\Models\User`). This is a flexible and common pattern in Laravel packages.
