<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAdministration\OrganizationRequest;
use App\Http\Requests\SiteAdministration\OrganizationUnitRequest;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Models\User;
use App\Services\SiteAdministration\AccessAdministrationService;
use App\Services\SiteAdministration\ModuleActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class OrganizationController extends Controller
{
    public function __construct(
        private readonly AccessAdministrationService $service,
        private readonly ModuleActivityLogger $activity,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('access.organizations.view');

        return Inertia::render('site-administration/Organizations', [
            'organizations' => $this->service->organizationHierarchy($request->only(['search', 'status'])),
            'parentOrganizations' => Organization::query()
                ->where('type', 'university')
                ->orderBy('name')
                ->get(['id', 'name']),
            'campusOrganizations' => Organization::query()
                ->where('type', 'campus')
                ->orderBy('name')
                ->get(['id', 'name', 'parent_id']),
            'officeUnits' => OrganizationUnit::query()
                ->where('type', 'office')
                ->orderBy('name')
                ->get(['id', 'name', 'organization_id']),
            'filters' => $request->only(['search', 'status']),
            'activities' => $this->activity->latestForModule('organizations'),
        ]);
    }

    public function searchUsers(Request $request): JsonResponse
    {
        Gate::authorize('access.organizations.view');

        $query = $request->string('q')->toString();

        $users = User::query()
            ->when(
                strlen($query) >= 2,
                fn ($q) => $q->where(function ($q) use ($query): void {
                    $q->where('name', 'like', "%{$query}%")
                        ->orWhere('email', 'like', "%{$query}%");
                }),
                fn ($q) => $q->whereRaw('1 = 0')
            )
            ->orderBy('name')
            ->limit(20)
            ->get(['id', 'name', 'email']);

        return response()->json($users);
    }

    public function store(OrganizationRequest $request): RedirectResponse
    {
        Gate::authorize('access.organizations.create');

        $this->service->storeOrganization($request->validated());

        return to_route('site-administration.organizations.index')
            ->with('success', 'Organization created successfully.');
    }

    public function update(OrganizationRequest $request, Organization $organization): RedirectResponse
    {
        Gate::authorize('access.organizations.update');

        $this->service->updateOrganization($organization, $request->validated());

        return to_route('site-administration.organizations.index')
            ->with('success', 'Organization updated successfully.');
    }

    public function destroy(Organization $organization): RedirectResponse
    {
        Gate::authorize('access.organizations.delete');

        $this->service->deleteOrganization($organization);

        return to_route('site-administration.organizations.index')
            ->with('success', 'Organization deleted successfully.');
    }

    public function storeUnit(OrganizationUnitRequest $request): RedirectResponse
    {
        Gate::authorize('access.organizations.create');

        $this->service->storeOrganizationUnit($request->validated());

        return to_route('site-administration.organizations.index')
            ->with('success', 'Organization unit created successfully.');
    }

    public function updateUnit(OrganizationUnitRequest $request, OrganizationUnit $unit): RedirectResponse
    {
        Gate::authorize('access.organizations.update');

        $this->service->updateOrganizationUnit($unit, $request->validated());

        return to_route('site-administration.organizations.index')
            ->with('success', 'Organization unit updated successfully.');
    }

    public function destroyUnit(OrganizationUnit $unit): RedirectResponse
    {
        Gate::authorize('access.organizations.delete');

        $this->service->deleteOrganizationUnit($unit);

        return to_route('site-administration.organizations.index')
            ->with('success', 'Organization unit deleted successfully.');
    }
}
