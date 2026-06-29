<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $organization = Organization::query()
            ->where('slug', config('starter-kit.default_workspace.slug'))
            ->firstOrFail();

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);

        collect($this->users())->each(function (array $seedUser) use ($organization): void {
            $user = User::query()->updateOrCreate(
                ['email' => $seedUser['email']],
                [
                    'name' => $seedUser['name'],
                    'password' => Hash::make((string) config('starter-kit.default_password')),
                    'is_active' => true,
                    'locked_at' => null,
                ],
            );

            $user->organizations()->syncWithoutDetaching([
                $organization->id => ['is_default' => true],
            ]);

            $role = Role::query()
                ->where('team_id', $organization->id)
                ->where('name', $seedUser['role'])
                ->firstOrFail();

            $user
                ->unsetRelation('roles')
                ->unsetRelation('permissions');

            $user->syncRoles([$role]);
        });
    }

    /**
     * @return array<int, array{name: string, email: string, role: string}>
     */
    private function users(): array
    {
        return config('starter-kit.users');
    }
}
