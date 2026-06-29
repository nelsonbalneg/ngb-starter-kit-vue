<?php

namespace App\Services\Auth;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use RuntimeException;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SsoAuthenticationService
{
    public function authorizationUrl(string $state): string
    {
        $query = http_build_query([
            'client_id' => config('sso.client_id'),
            'redirect_uri' => config('sso.redirect_uri'),
            'response_type' => 'code',
            'scope' => config('sso.scopes'),
            'state' => $state,
        ]);

        return config('sso.authorize_url').'?'.$query;
    }

    /**
     * @return array<string, mixed>
     */
    public function token(string $code): array
    {
        $response = $this->http()
            ->asForm()
            ->post((string) config('sso.token_url'), [
                'grant_type' => 'authorization_code',
                'client_id' => config('sso.client_id'),
                'client_secret' => config('sso.client_secret'),
                'redirect_uri' => config('sso.redirect_uri'),
                'code' => $code,
            ]);

        if ($response->failed()) {
            throw new RuntimeException('SSO token exchange failed.');
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $token
     * @return array<string, mixed>
     */
    public function user(array $token): array
    {
        $accessToken = $token['access_token'] ?? null;

        if (! is_string($accessToken) || $accessToken === '') {
            throw new RuntimeException('SSO response did not include an access token.');
        }

        $response = $this->http()
            ->withToken($accessToken)
            ->acceptJson()
            ->get((string) config('sso.user_url'));

        if ($response->failed()) {
            throw new RuntimeException('SSO user profile request failed.');
        }

        return $response->json();
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    public function resolveUser(array $profile): User
    {
        $email = $this->stringValue($profile, ['email', 'mail']);
        $ssoId = $this->stringValue($profile, ['sub', 'id', 'sso_id']);

        if ($email === null || $ssoId === null) {
            throw new RuntimeException('SSO profile must include email and identifier.');
        }

        return DB::transaction(function () use ($profile, $email, $ssoId): User {
            $user = User::query()
                ->where('sso_id', $ssoId)
                ->orWhere('email', $email)
                ->first();

            $existingUuid = $user instanceof User ? $user->uuid : null;

            $attributes = [
                'uuid' => $this->stringValue($profile, ['uuid']) ?? $existingUuid ?? (string) Str::uuid(),
                'sso_id' => $ssoId,
                'name' => $this->stringValue($profile, ['name', 'full_name']) ?? $email,
                'email' => $email,
                'is_active' => true,
                'locked_at' => null,
            ];

            if ($user) {
                $user->forceFill($attributes)->save();
            } else {
                $user = User::query()->create([
                    ...$attributes,
                    'password' => Hash::make(Str::password(48)),
                ]);
            }

            $this->assignDefaultAccess($user);

            return $user;
        });
    }

    private function assignDefaultAccess(User $user): void
    {
        $organization = Organization::query()
            ->where('slug', config('sso.default_organization_slug'))
            ->first()
            ?? Organization::query()->where('type', 'campus')->orderBy('name')->first()
            ?? Organization::query()->orderBy('name')->first();

        if (! $organization) {
            return;
        }

        $user->organizations()->syncWithoutDetaching([
            $organization->id => ['is_default' => true],
        ]);

        app(PermissionRegistrar::class)->setPermissionsTeamId($organization->id);
        $user->unsetRelation('roles')->unsetRelation('permissions');

        $role = Role::query()
            ->where('team_id', $organization->id)
            ->where('name', config('sso.default_role'))
            ->first();

        if ($role) {
            $user->assignRole($role);
        }
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

    private function http(): PendingRequest
    {
        return Http::timeout(15)->acceptJson();
    }
}
