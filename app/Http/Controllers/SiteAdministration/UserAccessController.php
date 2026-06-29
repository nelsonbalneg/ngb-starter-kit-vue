<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAdministration\UserAccessRequest;
use App\Models\User;
use App\Services\SiteAdministration\AccessAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserAccessController extends Controller
{
    public function __construct(private readonly AccessAdministrationService $service) {}

    public function edit(Request $request, User $user): Response
    {
        Gate::authorize('access.user-access.view');

        return Inertia::render('site-administration/UserAccess', [
            'access' => $this->service->userAccess($user, $this->organizationId($request)),
        ]);
    }

    public function update(UserAccessRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('access.user-access.update');

        $this->service->syncUserAccess($user, $this->organizationId($request), $request->validated());

        return to_route('site-administration.user-access.edit', $user)
            ->with('success', 'User access updated successfully.');
    }

    private function organizationId(Request $request): int
    {
        $organizationId = $request->attributes->get('organization_id');
        abort_unless($organizationId, 403);

        return (int) $organizationId;
    }
}
