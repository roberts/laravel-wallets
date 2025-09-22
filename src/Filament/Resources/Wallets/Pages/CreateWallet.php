<?php

namespace Roberts\LaravelWallets\Filament\Resources\Wallets\Pages;

use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Colors\Color;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Roberts\LaravelWallets\Enums\Protocol;
use Roberts\LaravelWallets\Filament\Resources\Wallets\WalletResource;
use Roberts\LaravelWallets\Services\WalletService;

class CreateWallet extends CreateRecord
{
    protected static string $resource = WalletResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generateCustodialWallet')
                ->label('Generate Custodial Wallet')
                ->icon('heroicon-o-key')
                ->color(Color::Green)
                ->action(function () {
                    $this->handleCustodialWalletCreation();
                })
                ->requiresConfirmation()
                ->modalHeading('Generate Custodial Wallet')
                ->modalDescription('This will create a new wallet with full private key control. Make sure you have selected a protocol in the form below.')
                ->modalSubmitActionLabel('Generate Wallet'),
        ];
    }

    protected function handleCreate(array $data): Model
    {
        // This handles the external wallet creation via the form
        return $this->handleExternalWalletCreation($data);
    }

    protected function handleExternalWalletCreation(array $data): Model
    {
        $user = Auth::user();
        if (! $user instanceof Model) {
            throw new \Exception('User not authenticated or not a valid model');
        }

        $walletService = app(WalletService::class);
        $protocol = Protocol::from($data['protocol']);
        $address = $data['address'];

        // Get current tenant ID (defaulting to 1)
        $tenantId = $this->getCurrentTenantId();

        $result = $walletService->addExternalWallet(
            protocol: $protocol,
            address: $address,
            owner: $user,
            tenantId: $tenantId,
            metadata: [
                'source' => 'filament_admin',
                'created_by' => $user->getKey(),
                'created_at' => now()->toISOString(),
            ]
        );

        Notification::make()
            ->title('External Wallet Added')
            ->body("Successfully added {$protocol->value} wallet: {$address}")
            ->success()
            ->send();

        return $result['wallet'];
    }

    protected function handleCustodialWalletCreation(): void
    {
        try {
            $user = Auth::user();
            if (! $user instanceof Model) {
                throw new \Exception('User not authenticated or not a valid model');
            }

            // Get protocol from current form data
            $protocol = isset($this->data['protocol']) ? Protocol::from($this->data['protocol']) : Protocol::ETH;

            $walletService = app(WalletService::class);

            // Get current tenant ID (defaulting to 1)
            $tenantId = $this->getCurrentTenantId();

            $result = $walletService->createCustodialWallet(
                protocol: $protocol,
                owner: $user,
                tenantId: $tenantId,
                metadata: [
                    'source' => 'filament_admin',
                    'generated_by' => $user->getKey(),
                    'created_at' => now()->toISOString(),
                ]
            );

            Notification::make()
                ->title('Custodial Wallet Generated')
                ->body("Successfully generated {$protocol->value} wallet: {$result['wallet']->address}")
                ->success()
                ->send();

            // Redirect to the created wallet
            $this->redirect($this->getResource()::getUrl('view', ['record' => $result['wallet']]));

        } catch (\Exception $e) {
            Notification::make()
                ->title('Error Generating Wallet')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    protected function getCurrentTenantId(): int
    {
        // Try to get from Laravel single-db tenancy context
        $tenantId = config('tenancy.tenant_id');

        if ($tenantId) {
            return $tenantId;
        }

        // Fallback to default tenant
        return 1;
    }

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }
}
