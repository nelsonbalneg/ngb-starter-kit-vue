<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAdministration\RolePermissionRequest;
use App\Services\SiteAdministration\AccessAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RolePermissionController extends Controller
{
    public function __construct(private readonly AccessAdministrationService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('access.role-permissions.view');

        return Inertia::render('site-administration/RolePermissions', [
            'matrix' => $this->service->rolePermissionMatrix(
                $this->organizationId($request),
                $request->integer('role_id') ?: null,
            ),
            'filters' => $request->only(['role_id', 'search']),
        ]);
    }

    public function update(RolePermissionRequest $request, Role $role): RedirectResponse
    {
        Gate::authorize('access.role-permissions.update');

        abort_unless((int) $role->getAttribute('team_id') === $this->organizationId($request), 404);
        $this->service->syncRolePermissions($role, $request->validated('permission_ids', []));

        return to_route('site-administration.role-permissions.index', ['role_id' => $role->id])
            ->with('success', 'Role permissions updated successfully.');
    }

    private function organizationId(Request $request): int
    {
        $organizationId = $request->attributes->get('organization_id');
        abort_unless($organizationId, 403);

        return (int) $organizationId;
    }
}
