<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('user_impersonation_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('admin_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('impersonated_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reference_number', 100)->index();
            $table->text('reason');
            $table->timestamp('started_at')->index();
            $table->timestamp('ended_at')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 1024)->nullable();
            $table->string('status')->default('active')->index();
            $table->timestamps();

            $table->index(['admin_user_id', 'status']);
            $table->index(['impersonated_user_id', 'started_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_impersonation_logs');
    }
};
