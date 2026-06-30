<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', config('starter-kit.default_workspace.slug'))
            ->firstOrFail();

        collect($this->permissions())->each(fn (array $permission): Permission => Permission::query()->firstOrCreate(
            ['name' => $permission['name'], 'guard_name' => 'web'],
            ['group' => $permission['group']],
        ));

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        $allPermissions = Permission::query()->pluck('name')->all();

        collect($this->roles())->each(function (array $roleDefinition) use ($allPermissions, $organization): void {
            $role = Role::query()->firstOrCreate(
                [
                    'name' => $roleDefinition['name'],
                    'guard_name' => 'web',
                    'team_id' => $organization->id,
                ],
                ['description' => $roleDefinition['description']],
            );

            $role->syncPermissions(
                $roleDefinition['permissions'] === ['*']
                    ? $allPermissions
                    : $roleDefinition['permissions'],
            );
        });
    }

    /**
     * @return array<int, array{name: string, group: string}>
     */
    private function permissions(): array
    {
        return [
            ['name' => 'dashboard.view', 'group' => 'Dashboard'],
            ['name' => 'profile.view', 'group' => 'Account'],
            ['name' => 'site-administration.view', 'group' => 'Site Administration'],
            ['name' => 'authentication.view', 'group' => 'Authentication'],
            ['name' => 'access.view', 'group' => 'Access'],
            ['name' => 'access.organizations.view', 'group' => 'Access'],
            ['name' => 'access.organizations.create', 'group' => 'Access'],
            ['name' => 'access.organizations.update', 'group' => 'Access'],
            ['name' => 'access.organizations.delete', 'group' => 'Access'],
            ['name' => 'access.roles.view', 'group' => 'Access'],
            ['name' => 'access.roles.create', 'group' => 'Access'],
            ['name' => 'access.roles.update', 'group' => 'Access'],
            ['name' => 'access.roles.delete', 'group' => 'Access'],
            ['name' => 'access.permissions.view', 'group' => 'Access'],
            ['name' => 'access.permissions.create', 'group' => 'Access'],
            ['name' => 'access.permissions.update', 'group' => 'Access'],
            ['name' => 'access.permissions.delete', 'group' => 'Access'],
            ['name' => 'access.role-permissions.view', 'group' => 'Access'],
            ['name' => 'access.role-permissions.update', 'group' => 'Access'],
            ['name' => 'access.user-access.view', 'group' => 'Access'],
            ['name' => 'access.user-access.update', 'group' => 'Access'],
            ['name' => 'users.view', 'group' => 'Users'],
            ['name' => 'users.create', 'group' => 'Users'],
            ['name' => 'users.update', 'group' => 'Users'],
            ['name' => 'users.delete', 'group' => 'Users'],
            ['name' => 'users.change-password', 'group' => 'Users'],
            ['name' => 'users.reset-password', 'group' => 'Users'],
            ['name' => 'users.lock', 'group' => 'Users'],
            ['name' => 'users.unlock', 'group' => 'Users'],
            ['name' => 'users.impersonate', 'group' => 'Users'],
            ['name' => 'users.impersonate.privileged', 'group' => 'Users'],
            ['name' => 'lookups.view', 'group' => 'Lookups'],
            ['name' => 'lookups.create', 'group' => 'Lookups'],
            ['name' => 'lookups.update', 'group' => 'Lookups'],
            ['name' => 'lookups.delete', 'group' => 'Lookups'],
            ['name' => 'settings.view', 'group' => 'Site Settings'],
            ['name' => 'settings.create', 'group' => 'Site Settings'],
            ['name' => 'settings.update', 'group' => 'Site Settings'],
            ['name' => 'settings.delete', 'group' => 'Site Settings'],
        ];
    }

    /**
     * @return array<int, array{name: string, description: string, permissions: list<string>}>
     */
    private function roles(): array
    {
        return [
            [
                'name' => 'super_admin',
                'description' => 'Full organization-scoped administration access.',
                'permissions' => ['*'],
            ],
            [
                'name' => 'Site Administrator',
                'description' => 'Full site administration access for the active organization.',
                'permissions' => ['*'],
            ],
            [
                'name' => 'user',
                'description' => 'Default application user access.',
                'permissions' => [
                    'dashboard.view',
                    'profile.view',
                ],
            ],
            [
                'name' => 'employee',
                'description' => 'Default employee self-service access.',
                'permissions' => [
                    'dashboard.view',
                    'profile.view',
                ],
            ],
        ];
    }
}
