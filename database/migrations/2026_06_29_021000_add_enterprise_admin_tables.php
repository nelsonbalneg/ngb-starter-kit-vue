<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->boolean('is_active')->default(true)->after('email_verified_at')->index();
            $table->timestamp('locked_at')->nullable()->after('is_active')->index();
        });

        Schema::create('organization_user', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('is_default')->default(false)->index();
            $table->timestamps();
            $table->unique(['organization_id', 'user_id']);
            $table->index(['user_id', 'is_default']);
        });

        Schema::table('roles', function (Blueprint $table): void {
            $table->string('description')->nullable()->after('guard_name');
        });

        Schema::table('permissions', function (Blueprint $table): void {
            $table->string('group')->default('General')->after('guard_name')->index();
            $table->string('description')->nullable()->after('group');
        });

        Schema::create('lookups', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->index();
            $table->string('code');
            $table->string('name')->index();
            $table->text('description')->nullable();
            $table->unsignedInteger('sort_order')->default(0)->index();
            $table->boolean('is_active')->default(true)->index();
            $table->timestamps();
            $table->unique(['group', 'code']);
        });

        Schema::create('site_settings', function (Blueprint $table): void {
            $table->id();
            $table->string('group')->index();
            $table->string('key');
            $table->json('value')->nullable();
            $table->string('type')->default('string')->index();
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false)->index();
            $table->timestamps();
            $table->unique(['group', 'key']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('site_settings');
        Schema::dropIfExists('lookups');
        Schema::table('permissions', function (Blueprint $table): void {
            $table->dropColumn(['group', 'description']);
        });
        Schema::table('roles', function (Blueprint $table): void {
            $table->dropColumn('description');
        });
        Schema::dropIfExists('organization_user');
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['is_active', 'locked_at']);
        });
    }
};
