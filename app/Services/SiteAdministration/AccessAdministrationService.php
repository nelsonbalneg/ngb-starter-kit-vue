<?php

namespace App\Services\SiteAdministration;

use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class AccessAdministrationService
{
    private const AUTHENTICATION_MODULE = 'authentication';

    private const ORGANIZATIONS_MODULE = 'organizations';

    public function __construct(
        private readonly OrganizationHierarchyService $hierarchy,
        private readonly ModuleActivityLogger $activity,
    ) {}

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Organization>
     */
    public function organizations(array $filters): LengthAwarePaginator
    {
        return Organization::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->withCount('users')
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return Collection<int, Organization>
     */
    public function organizationHierarchy(array $filters): Collection
    {
        $organizations = Organization::query()
            ->whereNull('parent_id')
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('slug', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")
                    ->orWhereHas('children', fn (Builder $query): Builder => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%"))
                    ->orWhereHas('children.units', fn (Builder $query): Builder => $query
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('address', 'like', "%{$search}%"))))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->with('children')
            ->withCount('users')
            ->orderBy('name')
            ->get();

        $this->attachRecursiveUnits($organizations);

        return $organizations;
    }

    /**
     * @param  Collection<int, Organization>  $organizations
     */
    private function attachRecursiveUnits(Collection $organizations): void
    {
        $campuses = $organizations
            ->flatMap(fn (Organization $organization) => $organization->children)
            ->values();

        if ($campuses->isEmpty()) {
            return;
        }

        $units = OrganizationUnit::query()
            ->whereIn('organization_id', $campuses->pluck('id'))
            ->with('heads:id,name,email')
            ->orderBy('name')
            ->get();

        $unitsByCampus = $units->groupBy('organization_id');

        $campuses->each(function (Organization $campus) use ($unitsByCampus): void {
            $campusUnits = new Collection($unitsByCampus->get($campus->id, collect())->all());
            $campus->setRelation('units', $this->buildUnitTree($campusUnits));
        });
    }

    /**
     * @param  Collection<int, OrganizationUnit>  $units
     * @return Collection<int, OrganizationUnit>
     */
    private function buildUnitTree(Collection $units, ?int $parentId = null): Collection
    {
        $children = $units
            ->filter(fn (OrganizationUnit $unit): bool => $unit->parent_id === $parentId)
            ->values();

        $children->each(function (OrganizationUnit $unit) use ($units): void {
            $unit->setRelation('children', $this->buildUnitTree($units, $unit->id));
        });

        return $children;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeOrganization(array $data): Organization
    {
        $this->hierarchy->validateOrganization($data);

        return DB::transaction(function () use ($data): Organization {
            $logoPath = $this->storeLogoFile($data, null);

            $organization = Organization::create([
                'name' => $data['name'],
                'parent_id' => $data['parent_id'] ?? null,
                'type' => $data['type'] ?? 'campus',
                'slug' => $data['slug'] ?: Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'logo_path' => $logoPath,
                'address' => $data['address'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->activity->record(
                self::ORGANIZATIONS_MODULE,
                'organization.created',
                "Created organization {$organization->name}.",
                $organization,
                ['type' => $organization->type],
            );

            return $organization;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateOrganization(Organization $organization, array $data): Organization
    {
        $this->hierarchy->validateOrganization($data, $organization);

        return DB::transaction(function () use ($organization, $data): Organization {
            $logoPath = $this->storeLogoFile($data, $organization->logo_path);

            $organization->update([
                'name' => $data['name'],
                'parent_id' => $data['parent_id'] ?? null,
                'type' => $data['type'] ?? 'campus',
                'slug' => $data['slug'] ?: Str::slug($data['name']),
                'description' => $data['description'] ?? null,
                'logo_path' => $logoPath,
                'address' => $data['address'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $this->activity->record(
                self::ORGANIZATIONS_MODULE,
                'organization.updated',
                "Updated organization {$organization->name}.",
                $organization,
                ['type' => $organization->type],
            );

            return $organization;
        });
    }

    public function deleteOrganization(Organization $organization): void
    {
        DB::transaction(function () use ($organization): void {
            $organizationName = $organization->name;
            $organizationType = $organization->type;

            $this->deleteLogoFile($organization->logo_path);
            $organization->delete();

            $this->activity->record(
                self::ORGANIZATIONS_MODULE,
                'organization.deleted',
                "Deleted organization {$organizationName}.",
                null,
                ['type' => $organizationType],
            );
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeOrganizationUnit(array $data): OrganizationUnit
    {
        $this->hierarchy->validateOrganizationUnit($data);

        return DB::transaction(function () use ($data): OrganizationUnit {
            $logoPath = $this->storeLogoFile($data, null);

            $unit = OrganizationUnit::create([
                'organization_id' => $data['organization_id'],
                'parent_id' => $data['parent_id'] ?? null,
                'type' => $data['type'],
                'name' => $data['name'],
                'logo_path' => $logoPath,
                'address' => $data['address'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $unit->heads()->sync($data['head_user_ids'] ?? []);

            $this->activity->record(
                self::ORGANIZATIONS_MODULE,
                'unit.created',
                "Created {$unit->type} {$unit->name}.",
                $unit,
                [
                    'type' => $unit->type,
                    'organization_id' => $unit->organization_id,
                ],
            );

            return $unit;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateOrganizationUnit(OrganizationUnit $unit, array $data): OrganizationUnit
    {
        $this->hierarchy->validateOrganizationUnit($data, $unit);

        return DB::transaction(function () use ($unit, $data): OrganizationUnit {
            $logoPath = $this->storeLogoFile($data, $unit->logo_path);

            $unit->update([
                'organization_id' => $data['organization_id'],
                'parent_id' => $data['parent_id'] ?? null,
                'type' => $data['type'],
                'name' => $data['name'],
                'logo_path' => $logoPath,
                'address' => $data['address'] ?? null,
                'description' => $data['description'] ?? null,
                'is_active' => (bool) ($data['is_active'] ?? true),
            ]);

            $unit->heads()->sync($data['head_user_ids'] ?? []);

            $this->activity->record(
                self::ORGANIZATIONS_MODULE,
                'unit.updated',
                "Updated {$unit->type} {$unit->name}.",
                $unit,
                [
                    'type' => $unit->type,
                    'organization_id' => $unit->organization_id,
                ],
            );

            return $unit;
        });
    }

    public function deleteOrganizationUnit(OrganizationUnit $unit): void
    {
        DB::transaction(function () use ($unit): void {
            $unitName = $unit->name;
            $unitType = $unit->type;
            $organizationId = $unit->organization_id;

            $this->deleteLogoFile($unit->logo_path);
            $unit->delete();

            $this->activity->record(
                self::ORGANIZATIONS_MODULE,
                'unit.deleted',
                "Deleted {$unitType} {$unitName}.",
                null,
                [
                    'type' => $unitType,
                    'organization_id' => $organizationId,
                ],
            );
        });
    }

    /**
     * Stores a new logo file and deletes the old one if replaced.
     * Returns the stored path or the existing path if no new file was provided.
     *
     * @param  array<string, mixed>  $data
     */
    private function storeLogoFile(array $data, ?string $existingPath): ?string
    {
        if (isset($data['logo']) && $data['logo'] instanceof UploadedFile) {
            // Delete old file when replacing
            $this->deleteLogoFile($existingPath);

            $path = Storage::disk('public')->putFile('logos', $data['logo']);

            if ($path === false) {
                throw new RuntimeException('Unable to store the uploaded logo.');
            }

            return $path;
        }

        // No new file — keep existing path (or use logo_path if provided)
        if (array_key_exists('logo_path', $data)) {
            return $data['logo_path'] ?: null;
        }

        return $existingPath;
    }

    private function deleteLogoFile(?string $path): void
    {
        if ($path && Storage::disk('public')->exists($path)) {
            Storage::disk('public')->delete($path);
        }
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Role>
     */
    public function roles(int $organizationId, array $filters): LengthAwarePaginator
    {
        return Role::query()
            ->where('team_id', $organizationId)
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")))
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storeRole(int $organizationId, array $data): Role
    {
        return DB::transaction(function () use ($organizationId, $data): Role {
            app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);

            $role = new Role([
                'name' => $data['name'],
                'guard_name' => 'web',
                'team_id' => $organizationId,
                'description' => $data['description'] ?? null,
            ]);
            $role->save();

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'role.created',
                "Created role {$role->name}.",
                $role,
                ['organization_id' => $organizationId],
            );

            return $role;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateRole(Role $role, array $data): Role
    {
        return DB::transaction(function () use ($role, $data): Role {
            $role->update([
                'name' => $data['name'],
                'description' => $data['description'] ?? null,
            ]);

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'role.updated',
                "Updated role {$role->name}.",
                $role,
            );

            return $role;
        });
    }

    public function deleteRole(Role $role): void
    {
        DB::transaction(function () use ($role): void {
            $roleName = $role->name;
            $role->delete();

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'role.deleted',
                "Deleted role {$roleName}.",
            );
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Permission>
     */
    public function permissions(array $filters): LengthAwarePaginator
    {
        return Permission::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%")
                    ->orWhere('description', 'like', "%{$search}%")))
            ->when($filters['group'] ?? null, fn (Builder $query, string $group): Builder => $query->where('group', $group))
            ->orderBy('group')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function storePermission(array $data): Permission
    {
        return DB::transaction(function () use ($data): Permission {
            $permission = new Permission([
                'name' => $data['name'],
                'guard_name' => 'web',
                'group' => $data['group'],
                'description' => $data['description'] ?? null,
            ]);
            $permission->save();

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'permission.created',
                "Created permission {$permission->name}.",
                $permission,
                ['group' => $permission->getAttribute('group')],
            );

            return $permission;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updatePermission(Permission $permission, array $data): Permission
    {
        return DB::transaction(function () use ($permission, $data): Permission {
            $permission->update([
                'name' => $data['name'],
                'group' => $data['group'],
                'description' => $data['description'] ?? null,
            ]);

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'permission.updated',
                "Updated permission {$permission->name}.",
                $permission,
                ['group' => $permission->getAttribute('group')],
            );

            return $permission;
        });
    }

    public function deletePermission(Permission $permission): void
    {
        DB::transaction(function () use ($permission): void {
            $permissionName = $permission->name;
            $permission->delete();

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'permission.deleted',
                "Deleted permission {$permissionName}.",
            );
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rolePermissionMatrix(int $organizationId, ?int $roleId = null): array
    {
        $roles = Role::query()
            ->where('team_id', $organizationId)
            ->orderBy('name')
            ->get(['id', 'name', 'description']);

        $role = $roleId ? $roles->firstWhere('id', $roleId) : $roles->first();

        $permissions = Permission::query()
            ->orderBy('group')
            ->orderBy('name')
            ->get(['id', 'name', 'group', 'description'])
            ->groupBy('group')
            ->map(fn ($items) => $items->values())
            ->all();

        $assigned = $role
            ? $role->permissions()->pluck('permissions.id')->all()
            : [];

        return [
            'roles' => $roles,
            'selectedRole' => $role,
            'permissionGroups' => $permissions,
            'assignedPermissionIds' => $assigned,
        ];
    }

    /**
     * @param  array<int>  $permissionIds
     */
    public function syncRolePermissions(Role $role, array $permissionIds): void
    {
        DB::transaction(function () use ($role, $permissionIds): void {
            app(PermissionRegistrar::class)->setPermissionsTeamId((int) $role->getAttribute('team_id'));
            $role->syncPermissions(Permission::query()->whereIn('id', $permissionIds)->pluck('name')->all());

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'role.permissions.updated',
                "Updated permissions for role {$role->name}.",
                $role,
                ['permission_count' => count($permissionIds)],
            );
        });
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, User>
     */
    public function users(array $filters): LengthAwarePaginator
    {
        $organizationId = request()->attributes->get('organization_id');

        return User::query()
            ->with([
                'organizations:id,name,slug',
                'roles' => fn ($query) => $organizationId
                    ? $query->wherePivot('team_id', $organizationId)->select('roles.id', 'roles.name')
                    : $query->select('roles.id', 'roles.name'),
                'permissions' => fn ($query) => $organizationId
                    ? $query->wherePivot('team_id', $organizationId)->select('permissions.id', 'permissions.name')
                    : $query->select('permissions.id', 'permissions.name'),
            ])
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")))
            ->when($filters['organization_id'] ?? null, fn (Builder $query, int|string $organizationId): Builder => $query
                ->whereHas('organizations', fn (Builder $query): Builder => $query->whereKey($organizationId)))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query): Builder => $query->where('is_active', true)->whereNull('locked_at'))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->when(($filters['status'] ?? null) === 'locked', fn (Builder $query): Builder => $query->whereNotNull('locked_at'))
            ->orderBy('name')
            ->paginate(10)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function inviteUser(array $data): User
    {
        return DB::transaction(function () use ($data): User {
            $user = User::create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make(($data['password'] ?? null) ?: Str::password(32)),
                'profile_photo_path' => $this->storeUserPhoto($data['photo'] ?? null),
                'is_active' => true,
            ]);

            $this->syncUserOrganizations($user, $data['organization_ids'] ?? []);

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'user.created',
                "Created user {$user->name}.",
                $user,
                ['email' => $user->email],
            );

            return $user;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateUser(User $user, array $data): User
    {
        return DB::transaction(function () use ($user, $data): User {
            $photoPath = $user->profile_photo_path;

            if (($data['photo'] ?? null) instanceof UploadedFile) {
                $this->deleteUserPhoto($photoPath);
                $photoPath = $this->storeUserPhoto($data['photo']);
            }

            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
                'profile_photo_path' => $photoPath,
                'is_active' => (bool) $data['is_active'],
            ]);

            $this->syncUserOrganizations($user, $data['organization_ids'] ?? []);

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'user.updated',
                "Updated user {$user->name}.",
                $user,
                ['email' => $user->email],
            );

            return $user;
        });
    }

    public function deleteUser(User $user): void
    {
        DB::transaction(function () use ($user): void {
            $photoPath = $user->profile_photo_path;
            $userName = $user->name;
            $userEmail = $user->email;

            $user->organizations()->detach();
            $user->delete();

            $this->deleteUserPhoto($photoPath);

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'user.deleted',
                "Deleted user {$userName}.",
                null,
                ['email' => $userEmail],
            );
        });
    }

    public function lockUser(User $user, string $reason): void
    {
        $user->forceFill([
            'locked_at' => now(),
            'locked_reason' => $reason,
        ])->save();

        $this->activity->record(
            self::AUTHENTICATION_MODULE,
            'user.locked',
            "Locked user {$user->name}.",
            $user,
            ['reason' => $reason],
        );
    }

    public function unlockUser(User $user): void
    {
        $previousReason = $user->locked_reason;

        $user->forceFill([
            'locked_at' => null,
            'locked_reason' => null,
            'is_active' => true,
        ])->save();

        $this->activity->record(
            self::AUTHENTICATION_MODULE,
            'user.unlocked',
            "Unlocked user {$user->name}.",
            $user,
            ['previous_reason' => $previousReason],
        );
    }

    public function resetPassword(User $user): void
    {
        $user->forceFill(['password' => Hash::make(Str::password(32))])->save();

        $this->activity->record(
            self::AUTHENTICATION_MODULE,
            'user.password.reset',
            "Reset password for {$user->name}.",
            $user,
        );
    }

    public function changePassword(User $user, string $password): void
    {
        $user->forceFill(['password' => Hash::make($password)])->save();

        $this->activity->record(
            self::AUTHENTICATION_MODULE,
            'user.password.changed',
            "Changed password for {$user->name}.",
            $user,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function userAccess(User $user, int $organizationId): array
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        return [
            'user' => $user->load('organizations:id,name,slug'),
            'roles' => Role::query()->where('team_id', $organizationId)->orderBy('name')->get(['id', 'name']),
            'permissions' => Permission::query()->orderBy('group')->orderBy('name')->get(['id', 'name', 'group']),
            'assignedRoleIds' => $user->roles()->wherePivot('team_id', $organizationId)->pluck('roles.id')->all(),
            'assignedPermissionIds' => $user->permissions()->wherePivot('team_id', $organizationId)->pluck('permissions.id')->all(),
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name', 'slug']),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function syncUserAccess(User $user, int $organizationId, array $data): void
    {
        DB::transaction(function () use ($user, $organizationId, $data): void {
            app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);
            $user->unsetRelation('roles')->unsetRelation('permissions');

            $roleNames = Role::query()
                ->where('team_id', $organizationId)
                ->whereIn('id', $data['role_ids'] ?? [])
                ->pluck('name')
                ->all();

            $permissionNames = Permission::query()
                ->whereIn('id', $data['permission_ids'] ?? [])
                ->pluck('name')
                ->all();

            $user->syncRoles($roleNames);
            $user->syncPermissions($permissionNames);

            if (array_key_exists('organization_ids', $data)) {
                $user->organizations()->syncWithPivotValues($data['organization_ids'], ['is_default' => false]);

                if (! empty($data['organization_ids'])) {
                    $user->organizations()->updateExistingPivot($organizationId, [
                        'is_default' => in_array($organizationId, $data['organization_ids'], true),
                    ]);
                }
            }

            $this->activity->record(
                self::AUTHENTICATION_MODULE,
                'user.access.updated',
                "Updated access for {$user->name}.",
                $user,
                [
                    'organization_id' => $organizationId,
                    'role_count' => count($roleNames),
                    'permission_count' => count($permissionNames),
                ],
            );
        });
    }

    /**
     * @param  array<int>  $organizationIds
     */
    private function syncUserOrganizations(User $user, array $organizationIds): void
    {
        $user->organizations()->syncWithPivotValues($organizationIds, ['is_default' => false]);

        if (! empty($organizationIds)) {
            $user->organizations()->updateExistingPivot($organizationIds[0], ['is_default' => true]);
        }
    }

    private function storeUserPhoto(mixed $photo): ?string
    {
        if (! $photo instanceof UploadedFile) {
            return null;
        }

        $path = $photo->store('users/photos', 'public');

        if ($path === false) {
            throw new RuntimeException('Unable to store the uploaded user photo.');
        }

        return $path;
    }

    private function deleteUserPhoto(?string $path): void
    {
        if ($path) {
            Storage::disk('public')->delete($path);
        }
    }
}
