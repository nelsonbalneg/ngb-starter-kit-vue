<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_features', function (Blueprint $table) {
            $table->id();
            $table->string('module_name', 100)->comment('Top-level module grouping, e.g. Security, Settings');
            $table->string('menu_name', 150)->comment('Menu section within the module');
            $table->string('feature_name', 200)->comment('Human-readable feature label');
            $table->string('feature_key', 200)->unique()->comment('Unique dot-notation key, mirrors route name');
            $table->string('route_name', 200)->nullable()->comment('The named Laravel route, if any');
            $table->text('description')->nullable();
            $table->enum('status', ['active', 'maintenance', 'disabled'])->default('active')->index();
            $table->text('maintenance_message')->nullable();
            $table->boolean('is_visible_in_menu')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0)->index();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('updated_by')->references('id')->on('users')->noActionOnDelete();

            $table->index(['module_name', 'status']);
            $table->index(['is_visible_in_menu', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_features');
    }
};
