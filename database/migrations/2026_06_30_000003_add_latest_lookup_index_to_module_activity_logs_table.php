<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('module_activity_logs', function (Blueprint $table): void {
            $table->index(['module', 'created_at', 'id'], 'module_activity_logs_module_created_id_index');
        });
    }

    public function down(): void
    {
        Schema::table('module_activity_logs', function (Blueprint $table): void {
            $table->dropIndex('module_activity_logs_module_created_id_index');
        });
    }
};
