<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAdministration\ImpersonateUserRequest;
use App\Models\User;
use App\Services\SiteAdministration\UserImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UserImpersonationController extends Controller
{
    public function __construct(private readonly UserImpersonationService $service) {}

    public function store(ImpersonateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('users.impersonate');

        /** @var array{reference_number: string, reason: string} $validated */
        $validated = $request->validated();

        $this->service->start($request->user(), $user, $validated, $request);

        return to_route('dashboard')
            ->with('success', 'Impersonation started successfully.');
    }

    public function destroy(Request $request): RedirectResponse
    {
        $this->service->stop($request);

        return to_route('site-administration.authentication.index', ['tab' => 'users'])
            ->with('success', 'Impersonation stopped successfully.');
    }
}
