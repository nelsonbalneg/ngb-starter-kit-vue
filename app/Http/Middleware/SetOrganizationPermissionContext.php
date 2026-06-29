<?php

namespace App\Http\Middleware;

use App\Models\Organization;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

class SetOrganizationPermissionContext
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            app(PermissionRegistrar::class)->setPermissionsTeamId(null);

            return $next($request);
        }

        $organizationId = $this->resolveOrganizationId($request);

        app(PermissionRegistrar::class)->setPermissionsTeamId($organizationId);

        if ($organizationId !== null) {
            $request->attributes->set('organization_id', $organizationId);
        }

        return $next($request);
    }

    private function resolveOrganizationId(Request $request): ?int
    {
        $sessionOrganizationId = $request->session()->get('current_organization_id');

        if ($sessionOrganizationId && $request->user()?->organizations()->whereKey($sessionOrganizationId)->exists()) {
            return (int) $sessionOrganizationId;
        }

        $organization = $request->user()
            ?->organizations()
            ->where('organizations.is_active', true)
            ->orderByDesc('organization_user.is_default')
            ->orderBy('organizations.name')
            ->first();

        if (! $organization instanceof Organization) {
            return null;
        }

        $request->session()->put('current_organization_id', $organization->id);

        return $organization->id;
    }
}
