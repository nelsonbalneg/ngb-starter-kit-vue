<?php

namespace App\Http\Middleware;

use App\Models\SystemFeature;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class CheckFeatureAvailability
{
    /**
     * Routes that are always allowed regardless of feature status.
     * Include all auth, settings, and critical system routes.
     */
    private array $alwaysAllowed = [
        'home',
        'dashboard',
        'sso.redirect',
        'sso.callback',
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'security.edit',
        'user-password.update',
        'appearance.edit',
        'up',
        'api.sso-features',
        'api.sso-user-access',
    ];

    private array $alwaysAllowedPrefixes = [
        'site-administration.',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        $routeName = $request->route()?->getName();

        // No named route or always-allowed → pass through
        if (! $routeName || $this->isAlwaysAllowed($routeName)) {
            return $next($request);
        }

        // Load feature map from cache (no DB hit after first load)
        $features = SystemFeature::allCached();

        // Check by route_name match
        $feature = $this->findByRouteName($features, $routeName);

        if ($feature === null) {
            // Not registered in features table — allow access
            return $next($request);
        }

        return match ($feature['status']) {
            'disabled'    => $this->handleDisabled($request),
            'maintenance' => $this->handleMaintenance($request, $feature),
            default       => $next($request),
        };
    }

    private function isAlwaysAllowed(string $routeName): bool
    {
        if (in_array($routeName, $this->alwaysAllowed, true)) {
            return true;
        }

        foreach ($this->alwaysAllowedPrefixes as $prefix) {
            if (str_starts_with($routeName, $prefix)) {
                return true;
            }
        }

        return false;
    }

    private function findByRouteName(array $features, string $routeName): ?array
    {
        foreach ($features as $data) {
            if (($data['route_name'] ?? null) === $routeName) {
                return $data;
            }
        }
        return null;
    }

    private function handleDisabled(Request $request): Response
    {
        if ($request->expectsJson() || $request->header('X-Inertia')) {
            return response()->json(['message' => 'This feature is currently disabled.'], 403);
        }

        abort(403, 'This feature is currently disabled.');
    }

    private function handleMaintenance(Request $request, array $feature): Response
    {
        $message = $feature['maintenance_message']
            ?? 'This feature is currently under maintenance. Please try again later.';

        if ($request->expectsJson()) {
            return response()->json([
                'message'      => 'Feature under maintenance.',
                'details'      => $message,
                'feature_name' => $feature['feature_name'],
            ], 503);
        }

        return Inertia::render('FeatureMaintenance', [
            'featureName'        => $feature['feature_name'],
            'maintenanceMessage' => $message,
        ])->toResponse($request)->setStatusCode(503);
    }
}
