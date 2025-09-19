<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallets', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->string('protocol', 25)->default('eth')->index(); // BlockchainProtocol: eth|sol|btc|sui|xrp|ada|ton|hbar
            $table->string('address')->unique()->index();
            $table->string('control_type', 25)->index(); // 'custodial', 'external', 'shared'
            $table->json('metadata')->nullable(); // Chain-specific data, labels, etc.
            $table->timestamps();
        });
    }
};
