<?php

namespace App\Http\Middleware;

use App\Services\SiteAdministration\SiteSettingAdministrationService;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'name' => cache()->remember('site_name', 3600, function () {
                return app(SiteSettingAdministrationService::class)->getValue('branding', 'site_name', config('app.name', 'Enterprise Starter Kit'));
            }),
            'branding' => cache()->remember('site_branding_settings', 3600, function () {
                return app(SiteSettingAdministrationService::class)->allGrouped()['branding'];
            }),
            'auth' => [
                'user' => $request->user(),
                'currentOrganization' => $request->user()
                    ?->organizations()
                    ->select('organizations.id', 'organizations.name', 'organizations.slug')
                    ->whereKey($request->attributes->get('organization_id'))
                    ->first(),
                'permissions' => $request->user()
                    ? $this->sharedPermissions($request)
                    : [],
            ],
            'flash' => [
                'toast' => $request->session()->get('toast')
                    ?? ($request->session()->has('success')
                        ? ['type' => 'success', 'message' => $request->session()->get('success')]
                        : null),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
        ];
    }

    /**
     * @return array<string, bool>
     */
    private function sharedPermissions(Request $request): array
    {
        $permissions = [
            'site-administration.view',
            'authentication.view',
            'access.view',
            'access.organizations.view',
            'access.organizations.create',
            'access.organizations.update',
            'access.organizations.delete',
            'access.roles.view',
            'access.roles.create',
            'access.permissions.view',
            'access.role-permissions.view',
            'access.user-access.view',
            'users.view',
            'users.create',
            'users.update',
            'users.delete',
            'users.change-password',
            'lookups.view',
            'settings.view',
            'settings.create',
            'settings.update',
            'settings.delete',
        ];

        return collect($permissions)
            ->mapWithKeys(fn (string $permission): array => [
                $permission => $request->user()?->can($permission) ?? false,
            ])
            ->all();
    }
}
