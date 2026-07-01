<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $foreign = $table->foreignId('parent_id')
                ->nullable()
                ->after('id')
                ->constrained('organizations');

            if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlsrv') {
                $foreign->noActionOnDelete();
            } else {
                $foreign->nullOnDelete();
            }

            $table->string('type')->default('campus')->after('parent_id')->index();
            $table->string('address')->nullable()->after('logo_path');
            $table->index(['parent_id', 'type']);
        });
    }

    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('parent_id');
            $table->dropColumn(['type', 'address']);
        });
    }
};
