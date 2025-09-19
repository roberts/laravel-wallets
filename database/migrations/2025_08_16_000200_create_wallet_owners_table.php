<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_owners', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('wallet_id')->constrained('wallets'); // NO cascade delete
            $table->unsignedBigInteger('tenant_id')->index();
            $table->unsignedBigInteger('owner_id')->nullable()->index();
            $table->string('owner_type')->nullable()->index();
            $table->text('encrypted_private_key')->nullable(); // Custodial wallets store private key
            $table->timestamps();

            // Unique ownership per wallet/tenant/owner combination
            $table->unique(['wallet_id', 'tenant_id', 'owner_id', 'owner_type'], 'wallet_owners_unique');

            // Add foreign key constraint if tenants table exists
            if (Schema::hasTable('tenants')) {
                $table->foreign('tenant_id')->references('id')->on('tenants');
            }
        });
    }
};
