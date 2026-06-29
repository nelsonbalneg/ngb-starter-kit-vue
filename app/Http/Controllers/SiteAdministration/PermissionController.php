<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAdministration\PermissionRequest;
use App\Services\SiteAdministration\AccessAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function __construct(private readonly AccessAdministrationService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('access.permissions.view');

        return Inertia::render('site-administration/Permissions', [
            'permissions' => $this->service->permissions($request->only(['search', 'group'])),
            'filters' => $request->only(['search', 'group']),
            'groups' => Permission::query()->select('group')->distinct()->orderBy('group')->pluck('group'),
        ]);
    }

    public function store(PermissionRequest $request): RedirectResponse
    {
        Gate::authorize('access.permissions.create');

        $this->service->storePermission($request->validated());

        return to_route('site-administration.permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    public function update(PermissionRequest $request, Permission $permission): RedirectResponse
    {
        Gate::authorize('access.permissions.update');

        $this->service->updatePermission($permission, $request->validated());

        return to_route('site-administration.permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    public function destroy(Permission $permission): RedirectResponse
    {
        Gate::authorize('access.permissions.delete');

        $this->service->deletePermission($permission);

        return to_route('site-administration.permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
