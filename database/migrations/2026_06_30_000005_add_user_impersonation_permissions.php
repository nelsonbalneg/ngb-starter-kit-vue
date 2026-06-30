<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        collect([
            'users.impersonate',
            'users.impersonate.privileged',
        ])->each(function (string $name) use ($now): void {
            DB::table('permissions')->updateOrInsert(
                ['name' => $name, 'guard_name' => 'web'],
                ['group' => 'Users', 'updated_at' => $now, 'created_at' => $now],
            );
        });

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }

    public function down(): void
    {
        DB::table('permissions')
            ->whereIn('name', ['users.impersonate', 'users.impersonate.privileged'])
            ->where('guard_name', 'web')
            ->delete();

        app('cache')
            ->store(config('permission.cache.store') !== 'default' ? config('permission.cache.store') : null)
            ->forget(config('permission.cache.key'));
    }
};
