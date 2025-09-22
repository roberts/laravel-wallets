# Laravel Wallets Filament Admin

This package provides Filament Admin Panel resources for managing wallets and wallet ownership records.

## Resources

### WalletOwner Resource

**Access**: Superadmin users on primary tenancy domain only  
**Location**: `/admin/wallet-owners`  
**Purpose**: Manage wallet ownership records across all tenants

**Features**:
- View all wallet ownership records globally
- Create new ownership relationships
- Edit existing ownership records
- Secure private key handling (encrypted)
- Tenant scoping visibility
- Control type indicators (has control vs watch-only)

**Security**:
- Only accessible by superadmin users
- Restricted to primary tenancy domain
- Private keys are encrypted and handled securely
- Visual indicators for control status

### Wallet Resource

**Access**: Admin users on all tenants  
**Location**: `/admin/wallets`  
**Purpose**: Manage the global wallet registry

**Features**:
- View wallets relevant to current tenant
- Create new wallet entries
- Edit wallet metadata and properties
- Protocol and control type management
- Tenant access status indicators
- Address validation and uniqueness enforcement

**Tenant Scoping**:
- Shows wallets owned by current tenant
- Shows external wallets (available for watching by all tenants)
- Indicates access level: Owned, Watch, or None

## Installation

The Filament resources are automatically registered when the package is installed and Filament is detected.

### Manual Registration

If you need to manually register the plugin:

```php
use Roberts\LaravelWallets\Filament\WalletsPlugin;

// In a Filament Panel configuration
$panel->plugins([
    WalletsPlugin::make(),
]);
```

## Configuration

### Access Control

#### WalletOwner Resource
- Requires superadmin privileges
- Must be accessed from primary tenancy domain
- Uses `Roberts\LaravelSingledbTenancy\Services\SuperAdmin` if available

#### Wallet Resource  
- Requires admin status (configurable)
- Available on all tenants
- Respects tenant context for data filtering

### Customization

You can extend or override the resources by creating your own classes that extend the provided resources:

```php
use Roberts\LaravelWallets\Filament\Resources\Wallets\WalletResource;

class CustomWalletResource extends WalletResource
{
    public static function canAccessResource(): bool
    {
        // Your custom access logic
        return auth()->user()->hasPermission('custom-wallet-access');
    }
}
```

## Data Flow

### Wallet Creation Flow
1. **Wallet Resource**: Create global wallet record (protocol + address)
2. **WalletOwner Resource**: Create ownership record linking wallet to user/tenant
3. **Result**: Wallet accessible to specific tenant with appropriate control level

### Tenant Scoping
- **Primary Domain**: Superadmin can see all data across tenants
- **Tenant Domains**: Admin users see tenant-scoped data plus external wallets

## Security Considerations

- Private keys are encrypted at rest
- Access control enforced at resource level  
- Tenant scoping prevents cross-tenant data access
- Audit trails available through related models
- Visual indicators for control vs watch-only status

## Extending Resources

### Custom Fields

Add custom fields to forms:

```php
// In a custom schema class extending WalletForm
TextInput::make('custom_field')
    ->label('Custom Field')
    ->helperText('Your custom field description'),
```

### Custom Filters

Add custom table filters:

```php
// In a custom table class extending WalletsTable
SelectFilter::make('custom_filter')
    ->label('Custom Filter')
    ->options([...])
```

### Custom Actions

Add custom actions to resources:

```php
// In your resource class
protected static function getHeaderActions(): array
{
    return [
        Action::make('custom_action')
            ->label('Custom Action')
            ->action(fn () => /* your logic */),
    ];
}
```