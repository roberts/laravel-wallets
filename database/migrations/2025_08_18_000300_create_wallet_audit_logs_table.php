<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('wallet_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('operation', 100)->index(); // The operation being performed
            $table->string('outcome', 50)->default('success')->index(); // success, failure, error
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('ip_address', 45)->nullable(); // Support IPv6
            $table->text('user_agent')->nullable();
            $table->json('context_data')->nullable(); // Operation-specific context
            $table->json('security_flags')->nullable(); // Suspicious activity flags
            $table->string('session_id', 255)->nullable();
            $table->string('request_id', 255)->nullable(); // For request tracing
            $table->timestamp('performed_at')->useCurrent()->index();
            $table->timestamps();

            // Indexes for common queries
            $table->index(['user_id', 'performed_at']);
            $table->index(['operation', 'performed_at']);
            $table->index(['outcome', 'performed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('wallet_audit_logs');
    }
};
