<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('organization_units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            
            $foreign = $table->foreignId('parent_id')
                ->nullable()
                ->constrained('organization_units');

            if (\Illuminate\Support\Facades\DB::connection()->getDriverName() === 'sqlsrv') {
                $foreign->noActionOnDelete();
            } else {
                $foreign->cascadeOnDelete();
            }

            $table->string('type')->index();
            $table->string('name')->index();
            $table->string('logo_path')->nullable();
            $table->string('address')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->index(['organization_id', 'parent_id', 'type']);
        });

        Schema::create('organization_unit_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();
            $table->unique(['organization_unit_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('organization_unit_user');
        Schema::dropIfExists('organization_units');
    }
};
