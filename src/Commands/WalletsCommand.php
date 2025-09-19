<?php

namespace Roberts\LaravelWallets\Commands;

use Illuminate\Console\Command;
use Roberts\LaravelWallets\Enums\ControlType;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Models\Wallet;
use Roberts\LaravelWallets\Models\WalletOwner;

class WalletsCommand extends Command
{
    public $signature = 'wallets
                        {action : The action to perform (list|stats|validate)}
                        {--tenant= : Filter by tenant ID}
                        {--protocol= : Filter by protocol (eth|sol)}
                        {--control-type= : Filter by control type (custodial|external|shared)}';

    public $description = 'Manage and inspect wallet data in the two-table architecture';

    public function handle(): int
    {
        $action = $this->argument('action');

        return match ($action) {
            'list' => $this->listWallets(),
            'stats' => $this->showStats(),
            'validate' => $this->validateWallets(),
            default => $this->showHelp(),
        };
    }

    private function listWallets(): int
    {
        $query = Wallet::query();

        // Apply filters
        if ($protocol = $this->option('protocol')) {
            $protocolEnum = Protocol::tryFrom(strtoupper($protocol));
            if (! $protocolEnum) {
                $this->error("Invalid protocol: {$protocol}. Use 'eth' or 'sol'");

                return self::FAILURE;
            }
            $query->where('protocol', $protocolEnum);
        }

        if ($controlType = $this->option('control-type')) {
            $controlTypeEnum = ControlType::tryFrom(strtoupper($controlType));
            if (! $controlTypeEnum) {
                $this->error("Invalid control type: {$controlType}. Use 'custodial', 'external', or 'shared'");

                return self::FAILURE;
            }
            $query->where('control_type', $controlTypeEnum);
        }

        $wallets = $query->with('owners')->paginate(25);

        if ($wallets->isEmpty()) {
            $this->info('No wallets found matching the criteria.');

            return self::SUCCESS;
        }

        $this->info("Found {$wallets->total()} wallets:");
        $this->newLine();

        $headers = ['ID', 'Protocol', 'Address', 'Control Type', 'Owners', 'Created'];
        $rows = [];

        foreach ($wallets as $wallet) {
            $ownersCount = $wallet->owners->count();
            $rows[] = [
                $wallet->id,
                $wallet->protocol->value,
                $this->truncateAddress($wallet->address),
                $wallet->control_type->value,
                $ownersCount,
                $wallet->created_at->format('Y-m-d H:i:s'),
            ];
        }

        $this->table($headers, $rows);

        if ($wallets->hasMorePages()) {
            $this->info("Showing page {$wallets->currentPage()} of {$wallets->lastPage()}");
        }

        return self::SUCCESS;
    }

    private function showStats(): int
    {
        $totalWallets = Wallet::count();
        $totalOwners = WalletOwner::count();

        $this->info('Wallet Statistics (Two-Table Architecture)');
        $this->newLine();

        // Overall stats
        $this->line("Total Wallets: {$totalWallets}");
        $this->line("Total Ownership Records: {$totalOwners}");
        $this->newLine();

        // By Protocol
        $this->line('By Protocol:');
        foreach (Protocol::cases() as $protocol) {
            $count = Wallet::where('protocol', $protocol)->count();
            $this->line("  {$protocol->value}: {$count}");
        }
        $this->newLine();

        // By Control Type
        $this->line('By Control Type:');
        foreach (ControlType::cases() as $controlType) {
            $count = Wallet::where('control_type', $controlType)->count();
            $this->line("  {$controlType->value}: {$count}");
        }
        $this->newLine();

        // Tenant distribution (if applicable)
        if ($tenantId = $this->option('tenant')) {
            $tenantOwners = WalletOwner::where('tenant_id', $tenantId)->count();
            $this->line("Tenant {$tenantId} Ownership Records: {$tenantOwners}");
        } else {
            /** @var \Illuminate\Support\Collection<int, object{tenant_id: int, count: int}> $tenantStats */
            $tenantStats = WalletOwner::selectRaw('tenant_id, COUNT(*) as count')
                ->groupBy('tenant_id')
                ->orderBy('tenant_id')
                ->get();

            if ($tenantStats->isNotEmpty()) {
                $this->line('By Tenant:');
                foreach ($tenantStats as $stat) {
                    $this->line("  Tenant {$stat->tenant_id}: {$stat->count} ownership records");
                }
            }
        }

        return self::SUCCESS;
    }

    private function validateWallets(): int
    {
        $this->info('Validating wallet data integrity...');
        $this->newLine();

        $errors = [];
        $warnings = [];

        // Check for wallets without owners
        $orphanedWallets = Wallet::doesntHave('owners')->count();
        if ($orphanedWallets > 0) {
            $warnings[] = "{$orphanedWallets} wallets have no ownership records";
        }

        // Check for ownership records with missing wallets
        $orphanedOwners = WalletOwner::whereDoesntHave('wallet')->count();
        if ($orphanedOwners > 0) {
            $errors[] = "{$orphanedOwners} ownership records reference non-existent wallets";
        }

        // Check for invalid protocols
        $invalidProtocols = Wallet::whereNotIn('protocol', array_map(fn ($p) => $p->value, Protocol::cases()))->count();
        if ($invalidProtocols > 0) {
            $errors[] = "{$invalidProtocols} wallets have invalid protocol values";
        }

        // Check for invalid control types
        $invalidControlTypes = Wallet::whereNotIn('control_type', array_map(fn ($c) => $c->value, ControlType::cases()))->count();
        if ($invalidControlTypes > 0) {
            $errors[] = "{$invalidControlTypes} wallets have invalid control_type values";
        }

        // Display results
        if (empty($errors) && empty($warnings)) {
            $this->info('✅ All wallet data is valid!');
        } else {
            if (! empty($errors)) {
                $this->error('❌ Errors found:');
                foreach ($errors as $error) {
                    $this->line("  • {$error}");
                }
                $this->newLine();
            }

            if (! empty($warnings)) {
                $this->warn('⚠️  Warnings:');
                foreach ($warnings as $warning) {
                    $this->line("  • {$warning}");
                }
            }
        }

        return empty($errors) ? self::SUCCESS : self::FAILURE;
    }

    private function showHelp(): int
    {
        $this->info('Laravel Wallets Command - Two-Table Architecture');
        $this->newLine();
        $this->line('Available actions:');
        $this->line('  list      - List wallets with optional filters');
        $this->line('  stats     - Show wallet statistics');
        $this->line('  validate  - Validate wallet data integrity');
        $this->newLine();
        $this->line('Options:');
        $this->line('  --tenant=ID         Filter by tenant ID');
        $this->line('  --protocol=PROTO    Filter by protocol (eth|sol)');
        $this->line('  --control-type=TYPE Filter by control type (custodial|external|shared)');
        $this->newLine();
        $this->line('Examples:');
        $this->line('  php artisan wallets list --protocol=eth');
        $this->line('  php artisan wallets stats --tenant=1');
        $this->line('  php artisan wallets validate');

        return self::SUCCESS;
    }

    private function truncateAddress(string $address): string
    {
        if (strlen($address) <= 20) {
            return $address;
        }

        return substr($address, 0, 8).'...'.substr($address, -8);
    }
}
