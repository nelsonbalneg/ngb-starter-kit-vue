<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAdministration\AppearanceSettingsRequest;
use App\Http\Requests\SiteAdministration\SiteSettingRequest;
use App\Models\SiteSetting;
use App\Services\SiteAdministration\SiteSettingAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class SiteSettingController extends Controller
{
    public function __construct(private readonly SiteSettingAdministrationService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('settings.view');

        return Inertia::render('site-administration/SiteSettings', [
            'settings' => $this->service->allGrouped(),
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        Gate::authorize('settings.update');

        $validated = $request->validate([
            'site_name' => ['required', 'string', 'max:255'],
            'tagline' => ['nullable', 'string', 'max:255'],
            'logo_light' => ['nullable', 'string', 'max:255'],
            'logo_light_file' => ['nullable', 'file', 'image', 'max:2048', 'mimes:jpeg,jpg,png,svg,webp'],
            'logo_dark' => ['nullable', 'string', 'max:255'],
            'logo_dark_file' => ['nullable', 'file', 'image', 'max:2048', 'mimes:jpeg,jpg,png,svg,webp'],
            'footer_text' => ['nullable', 'string', 'max:1000'],
            'show_footer' => ['boolean'],
            'enable_passkey' => ['boolean'],
            'enable_registration' => ['boolean'],
            'enable_forgot_password' => ['boolean'],
            'favicon' => ['nullable', 'string', 'max:255'],
            'favicon_file' => ['nullable', 'file', 'image', 'max:1024', 'mimes:jpeg,jpg,png,svg,webp,ico'],
        ]);

        $this->service->updateBranding($validated);

        return to_route('site-administration.settings.index')
            ->with('success', 'Branding settings updated successfully.');
    }

    public function updateMaintenance(Request $request): RedirectResponse
    {
        Gate::authorize('settings.update');

        $validated = $request->validate([
            'mode' => ['boolean'],
            'title' => ['required', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
            'allowed_ips' => ['nullable', 'string', 'max:1000'],
            'bypass_users' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->service->updateMaintenance($validated);

        return to_route('site-administration.settings.index')
            ->with('success', 'Maintenance settings updated successfully.');
    }

    public function updateAppearance(AppearanceSettingsRequest $request): RedirectResponse
    {
        Gate::authorize('settings.update');

        $this->service->updateAppearance($request->validated());

        return to_route('site-administration.settings.index', ['tab' => 'appearance'])
            ->with('success', 'Appearance settings updated successfully.');
    }

    public function store(SiteSettingRequest $request): RedirectResponse
    {
        Gate::authorize('settings.create');

        $this->service->store($request->validated());

        return to_route('site-administration.settings.index')
            ->with('success', 'Site setting saved successfully.');
    }

    public function update(SiteSettingRequest $request, SiteSetting $setting): RedirectResponse
    {
        Gate::authorize('settings.update');

        $this->service->update($setting, $request->validated());

        return to_route('site-administration.settings.index')
            ->with('success', 'Site setting updated successfully.');
    }

    public function destroy(SiteSetting $setting): RedirectResponse
    {
        Gate::authorize('settings.delete');

        $this->service->delete($setting);

        return to_route('site-administration.settings.index')
            ->with('success', 'Site setting deleted successfully.');
    }
}
