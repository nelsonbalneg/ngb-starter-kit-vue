<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAdministration\ChangeUserPasswordRequest;
use App\Http\Requests\SiteAdministration\InviteUserRequest;
use App\Http\Requests\SiteAdministration\LockUserRequest;
use App\Http\Requests\SiteAdministration\UpdateUserRequest;
use App\Models\Organization;
use App\Models\User;
use App\Services\SiteAdministration\AccessAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class UserAdministrationController extends Controller
{
    public function __construct(private readonly AccessAdministrationService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('users.view');

        return Inertia::render('site-administration/Users', [
            'users' => $this->service->users($request->only(['search', 'organization_id', 'status'])),
            'filters' => $request->only(['search', 'organization_id', 'status']),
            'organizations' => Organization::query()->orderBy('name')->get(['id', 'name']),
        ]);
    }

    public function store(InviteUserRequest $request): RedirectResponse
    {
        Gate::authorize('users.create');

        $this->service->inviteUser($request->validated());

        return $this->redirectToAuthenticationUsers()
            ->with('success', 'User created successfully.');
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('users.update');

        $this->service->updateUser($user, $request->validated());

        return $this->redirectToAuthenticationUsers()
            ->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        Gate::authorize('users.delete');

        $this->service->deleteUser($user);

        return $this->redirectToAuthenticationUsers()
            ->with('success', 'User deleted successfully.');
    }

    public function changePassword(ChangeUserPasswordRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('users.change-password');

        $validated = $request->validated();

        $this->service->changePassword($user, (string) $validated['password']);

        return $this->redirectToAuthenticationUsers()
            ->with('success', 'User password changed successfully.');
    }

    public function resetPassword(User $user): RedirectResponse
    {
        Gate::authorize('users.reset-password');

        $this->service->resetPassword($user);

        return $this->redirectToAuthenticationUsers()
            ->with('success', 'User password reset successfully.');
    }

    public function lock(LockUserRequest $request, User $user): RedirectResponse
    {
        Gate::authorize('users.lock');

        $validated = $request->validated();

        $this->service->lockUser($user, (string) $validated['locked_reason']);

        return $this->redirectToAuthenticationUsers()
            ->with('success', 'User account locked successfully.');
    }

    public function unlock(User $user): RedirectResponse
    {
        Gate::authorize('users.unlock');

        $this->service->unlockUser($user);

        return $this->redirectToAuthenticationUsers()
            ->with('success', 'User account unlocked successfully.');
    }

    private function redirectToAuthenticationUsers(): RedirectResponse
    {
        return to_route('site-administration.authentication.index', ['tab' => 'users']);
    }
}
