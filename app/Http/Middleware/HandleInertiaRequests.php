<?php

namespace App\Http\Middleware;

use App\Services\SiteAdministration\SiteSettingAdministrationService;
use App\Services\SiteAdministration\UserImpersonationService;
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
            'appearance' => cache()->remember('site_appearance_settings', 3600, function () {
                return app(SiteSettingAdministrationService::class)->allGrouped()['appearance'];
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
            'impersonation' => $this->impersonation($request),
            'flash' => [
                'toast' => $request->session()->get('toast')
                    ?? ($request->session()->has('success')
                        ? ['type' => 'success', 'message' => $request->session()->get('success')]
                        : null),
            ],
            'sidebarOpen' => $this->sidebarOpen($request),
        ];
    }

    /**
     * @return array{is_impersonating: bool, impersonated_user_name: string|null, reference_number: string|null}
     */
    private function impersonation(Request $request): array
    {
        $isImpersonating = app(UserImpersonationService::class)->isImpersonating($request);

        return [
            'is_impersonating' => $isImpersonating,
            'impersonated_user_name' => $isImpersonating ? $request->user()?->name : null,
            'reference_number' => $isImpersonating
                ? $request->session()->get(UserImpersonationService::SESSION_REFERENCE)
                : null,
        ];
    }

    private function sidebarOpen(Request $request): bool
    {
        if ($request->hasCookie('sidebar_state')) {
            return $request->cookie('sidebar_state') === 'true';
        }

        $appearance = cache()->remember('site_appearance_settings', 3600, function () {
            return app(SiteSettingAdministrationService::class)->allGrouped()['appearance'];
        });

        return ($appearance['sidebar_default'] ?? 'expanded') !== 'collapsed';
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
            'users.reset-password',
            'users.lock',
            'users.unlock',
            'users.impersonate',
            'users.impersonate.privileged',
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
