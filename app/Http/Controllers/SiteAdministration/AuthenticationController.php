<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Models\Organization;
use App\Services\SiteAdministration\AccessAdministrationService;
use App\Services\SiteAdministration\ModuleActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class AuthenticationController extends Controller
{
    public function __construct(
        private readonly AccessAdministrationService $service,
        private readonly ModuleActivityLogger $activity,
    ) {}

    public function index(Request $request): Response
    {
        Gate::authorize('authentication.view');

        $activeTab = $this->activeTab($request);

        $this->authorizeTab($activeTab);

        return Inertia::render('site-administration/Authentication', [
            'activeTab' => $activeTab,
            'tabs' => $this->tabs(),
            'filters' => $request->only(['search', 'organization_id', 'status', 'group', 'role_id']),
            'activities' => $this->activity->latestForModule('authentication'),
            'payload' => $this->payload($request, $activeTab),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(Request $request, string $activeTab): array
    {
        return match ($activeTab) {
            'users' => [
                'users' => $this->service->users($request->only(['search', 'organization_id', 'status'])),
                'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
            ],
            'roles' => [
                'roles' => $this->service->roles($this->organizationId($request), $request->only(['search'])),
            ],
            'role-permissions' => [
                'matrix' => $this->service->rolePermissionMatrix(
                    $this->organizationId($request),
                    $request->integer('role_id') ?: null,
                ),
            ],
            'permissions' => [
                'permissions' => $this->service->permissions($request->only(['search', 'group'])),
                'groups' => Permission::query()->select('group')->distinct()->orderBy('group')->pluck('group'),
            ],
            default => [],
        };
    }

    private function activeTab(Request $request): string
    {
        $tab = $request->string('tab')->toString();

        return in_array($tab, array_keys($this->tabPermissions()), true)
            ? $tab
            : 'users';
    }

    private function authorizeTab(string $tab): void
    {
        Gate::authorize($this->tabPermissions()[$tab]);
    }

    /**
     * @return array<string, string>
     */
    private function tabPermissions(): array
    {
        return [
            'users' => 'users.view',
            'roles' => 'access.roles.view',
            'role-permissions' => 'access.role-permissions.view',
            'permissions' => 'access.permissions.view',
        ];
    }

    /**
     * @return array<int, array{key: string, label: string, permission: string}>
     */
    private function tabs(): array
    {
        return collect($this->tabPermissions())
            ->map(fn (string $permission, string $key): array => [
                'key' => $key,
                'label' => str($key)->replace('-', ' ')->title()->toString(),
                'permission' => $permission,
            ])
            ->values()
            ->all();
    }

    private function organizationId(Request $request): int
    {
        $organizationId = $request->attributes->get('organization_id');
        abort_unless($organizationId, 403);

        return (int) $organizationId;
    }
}
