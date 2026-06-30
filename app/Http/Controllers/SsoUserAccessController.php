<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SsoUserAccessController extends Controller
{
    public function handle(Request $request): JsonResponse
    {
        $signatureResponse = $this->validateSignature($request);

        if ($signatureResponse instanceof JsonResponse) {
            return $signatureResponse;
        }

        $email = $request->string('email')->trim()->toString();

        if ($email === '') {
            return response()->json(['message' => 'Email parameter is required.'], 400);
        }

        $organization = $this->resolveOrganization($request, null);
        $user = User::query()->where('email', $email)->first();

        if (! $user instanceof User) {
            $details = $request->input('user_details');

            if ($request->isMethod('post') && is_array($details)) {
                $user = $this->createUser($email, $details);
                $organization = $this->resolveOrganization($request, $user);
                $this->attachOrganization($user, $organization);
            } else {
                return response()->json([
                    ...$this->accessCatalog($organization),
                    'roles' => [],
                    'permissions' => [],
                    'message' => 'User not found in client application database. Saving will auto-create this user.',
                ]);
            }
        } else {
            $organization = $this->resolveOrganization($request, $user);
            $this->attachOrganization($user, $organization);
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization?->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        if ($request->isMethod('post')) {
            if (! $organization instanceof Organization) {
                return response()->json([
                    'message' => 'No active organization is available for organization-scoped access syncing.',
                ], 422);
            }

            $validated = $request->validate([
                'roles' => ['array'],
                'roles.*' => ['string'],
                'permissions' => ['array'],
                'permissions.*' => ['string'],
            ]);

            DB::transaction(function () use ($user, $organization, $validated): void {
                app(PermissionRegistrar::class)->setPermissionsTeamId($organization?->id);
                $user->syncRoles($this->validRoleNames($validated['roles'] ?? [], $organization));
                $user->syncPermissions($this->validPermissionNames($validated['permissions'] ?? []));
            });

            $user->unsetRelation('roles')->unsetRelation('permissions');

            return response()->json([
                'message' => 'User access tags updated successfully and user created/synced in client application.',
                'roles' => $user->roles()->pluck('roles.name')->all(),
                'permissions' => $user->getDirectPermissions()->pluck('name')->all(),
                'organization_id' => $organization?->id,
            ]);
        }

        return response()->json([
            ...$this->accessCatalog($organization),
            'roles' => $user->roles()->pluck('roles.name')->all(),
            'permissions' => $user->getDirectPermissions()->pluck('name')->all(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $details
     */
    private function createUser(string $email, array $details): User
    {
        return DB::transaction(function () use ($email, $details): User {
            $user = User::query()->create([
                'name' => $this->stringValue($details, ['name', 'full_name']) ?? 'SSO User',
                'email' => $email,
                'password' => Hash::make(Str::password(48)),
                'sso_id' => $this->stringValue($details, ['id', 'sub', 'sso_id']),
                'uuid' => $this->stringValue($details, ['uuid']) ?? (string) Str::uuid(),
                'is_active' => true,
            ]);

            $user->forceFill(['email_verified_at' => now()])->save();

            return $user;
        });
    }

    private function resolveOrganization(Request $request, ?User $user): ?Organization
    {
        $requestedId = $request->integer('organization_id');

        if ($requestedId > 0) {
            $organization = Organization::query()->whereKey($requestedId)->where('is_active', true)->first();

            if ($organization instanceof Organization) {
                return $organization;
            }
        }

        $organization = $user
            ?->organizations()
            ->where('organizations.is_active', true)
            ->orderByDesc('organization_user.is_default')
            ->orderBy('organizations.name')
            ->first();

        if ($organization instanceof Organization) {
            return $organization;
        }

        return Organization::query()
            ->where('slug', config('sso.default_organization_slug'))
            ->where('is_active', true)
            ->first()
            ?? Organization::query()->where('is_active', true)->orderBy('name')->first();
    }

    private function attachOrganization(User $user, ?Organization $organization): void
    {
        if (! $organization instanceof Organization) {
            return;
        }

        $user->organizations()->syncWithoutDetaching([
            $organization->id => ['is_default' => true],
        ]);
    }

    /**
     * @return array{all_roles: list<string>, all_permissions: list<string>, role_permissions: array<string, list<string>>, organization_id: int|null}
     */
    private function accessCatalog(?Organization $organization): array
    {
        $roles = Role::query()
            ->when($organization instanceof Organization, fn ($query) => $query->where('team_id', $organization->id))
            ->with('permissions')
            ->orderBy('name')
            ->get();

        $permissions = Permission::query()->orderBy('name')->pluck('name')->all();

        return [
            'all_roles' => $roles->pluck('name')->all(),
            'all_permissions' => $permissions,
            'role_permissions' => $roles
                ->mapWithKeys(fn (Role $role): array => [
                    $role->name => $role->permissions->pluck('name')->all(),
                ])
                ->all(),
            'organization_id' => $organization?->id,
        ];
    }

    /**
     * @param  array<int, string>  $roles
     * @return list<string>
     */
    private function validRoleNames(array $roles, ?Organization $organization): array
    {
        return Role::query()
            ->when($organization instanceof Organization, fn ($query) => $query->where('team_id', $organization->id))
            ->whereIn('name', $roles)
            ->pluck('name')
            ->all();
    }

    /**
     * @param  array<int, string>  $permissions
     * @return list<string>
     */
    private function validPermissionNames(array $permissions): array
    {
        return Permission::query()
            ->whereIn('name', $permissions)
            ->pluck('name')
            ->all();
    }

    private function validateSignature(Request $request): ?JsonResponse
    {
        $clientId = $request->header('X-SSO-Client-ID');
        $timestamp = $request->header('X-SSO-Timestamp');
        $signature = $request->header('X-SSO-Signature');

        if (! $clientId || ! $timestamp || ! $signature) {
            return response()->json(['message' => 'Missing signature headers.'], 401);
        }

        if (abs(time() - (int) $timestamp) > 300) {
            return response()->json(['message' => 'Request expired.'], 401);
        }

        $configuredClientId = config('services.sso.client_id');
        $configuredClientSecret = config('services.sso.client_secret');

        if (! $configuredClientId || ! $configuredClientSecret) {
            return response()->json(['message' => 'SSO client is not configured on this remote server.'], 500);
        }

        if (strtolower((string) $clientId) !== strtolower((string) $configuredClientId)) {
            return response()->json(['message' => 'Invalid Client ID.'], 401);
        }

        $expectedSignature = hash_hmac('sha256', $timestamp.'.'.$clientId, (string) $configuredClientSecret);

        if (! hash_equals($expectedSignature, (string) $signature)) {
            return response()->json(['message' => 'Invalid signature verification.'], 401);
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  list<string>  $keys
     */
    private function stringValue(array $data, array $keys): ?string
    {
        foreach ($keys as $key) {
            $value = $data[$key] ?? null;

            if (is_string($value) && $value !== '') {
                return $value;
            }

            if (is_int($value)) {
                return (string) $value;
            }
        }

        return null;
    }
}
