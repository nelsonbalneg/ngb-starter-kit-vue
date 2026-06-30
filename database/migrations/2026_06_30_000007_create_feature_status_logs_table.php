<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_status_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('system_feature_id');
            $table->enum('old_status', ['active', 'maintenance', 'disabled'])->nullable();
            $table->enum('new_status', ['active', 'maintenance', 'disabled']);
            $table->text('reason')->nullable();
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->foreign('system_feature_id')->references('id')->on('system_features')->cascadeOnDelete();
            $table->foreign('changed_by')->references('id')->on('users')->noActionOnDelete();

            $table->index(['system_feature_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_status_logs');
    }
};
