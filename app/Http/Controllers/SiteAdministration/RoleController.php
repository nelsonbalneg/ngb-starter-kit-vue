<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAdministration\RoleRequest;
use App\Services\SiteAdministration\AccessAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function __construct(private readonly AccessAdministrationService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('access.roles.view');

        return Inertia::render('site-administration/Roles', [
            'roles' => $this->service->roles($this->organizationId($request), $request->only(['search'])),
            'filters' => $request->only(['search']),
        ]);
    }

    public function store(RoleRequest $request): RedirectResponse
    {
        Gate::authorize('access.roles.create');

        $this->service->storeRole($this->organizationId($request), $request->validated());

        if ($request->boolean('stay_on_authentication')) {
            return to_route('site-administration.authentication.index', ['tab' => 'roles'])
                ->with('success', 'Role created successfully.');
        }

        return to_route('site-administration.roles.index')
            ->with('success', 'Role created successfully.');
    }

    public function update(RoleRequest $request, Role $role): RedirectResponse
    {
        Gate::authorize('access.roles.update');

        abort_unless((int) $role->getAttribute('team_id') === $this->organizationId($request), 404);
        $this->service->updateRole($role, $request->validated());

        return to_route('site-administration.roles.index')
            ->with('success', 'Role updated successfully.');
    }

    public function destroy(Request $request, Role $role): RedirectResponse
    {
        Gate::authorize('access.roles.delete');

        abort_unless((int) $role->getAttribute('team_id') === $this->organizationId($request), 404);
        $this->service->deleteRole($role);

        return to_route('site-administration.roles.index')
            ->with('success', 'Role deleted successfully.');
    }

    private function organizationId(Request $request): int
    {
        $organizationId = $request->attributes->get('organization_id');
        abort_unless($organizationId, 403);

        return (int) $organizationId;
    }
}
