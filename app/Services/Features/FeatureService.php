<?php

namespace App\Services\Features;

use App\Models\FeatureStatusLog;
use App\Models\SystemFeature;
use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Route;

class FeatureService
{
    // ─── Queries ─────────────────────────────────────────────────────────────

    public function getPaginated(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        return SystemFeature::with(['updatedBy:id,name'])
            ->when(! empty($filters['module']), fn ($q) => $q->where('module_name', $filters['module']))
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['search']), function ($q) use ($filters) {
                $s = $filters['search'];
                $q->where(function ($q2) use ($s) {
                    $q2->where('feature_name', 'like', "%{$s}%")
                        ->orWhere('module_name', 'like', "%{$s}%")
                        ->orWhere('menu_name', 'like', "%{$s}%")
                        ->orWhere('feature_key', 'like', "%{$s}%")
                        ->orWhere('route_name', 'like', "%{$s}%");
                });
            })
            ->orderBy('module_name')
            ->orderBy('sort_order')
            ->orderBy('menu_name')
            ->paginate($perPage)
            ->withQueryString();
    }

    public function getDistinctModules(): Collection
    {
        return SystemFeature::distinct()->orderBy('module_name')->pluck('module_name');
    }

    // ─── Mutations ───────────────────────────────────────────────────────────

    public function updateStatus(
        SystemFeature $feature,
        string $status,
        ?string $maintenanceMessage,
        ?string $reason,
        User $actor
    ): SystemFeature {
        $oldStatus = $feature->status;

        $feature->update([
            'status'              => $status,
            'maintenance_message' => $status === 'maintenance' ? $maintenanceMessage : $feature->maintenance_message,
            'updated_by'          => $actor->id,
        ]);

        FeatureStatusLog::create([
            'system_feature_id' => $feature->id,
            'old_status'        => $oldStatus,
            'new_status'        => $status,
            'reason'            => $reason,
            'changed_by'        => $actor->id,
            'created_at'        => now(),
        ]);

        SystemFeature::clearCache();

        return $feature->fresh(['updatedBy']);
    }

    // ─── History ─────────────────────────────────────────────────────────────

    public function getHistory(SystemFeature $feature): Collection
    {
        return $feature->statusLogs()
            ->with('changedBy:id,name')
            ->limit(50)
            ->get()
            ->map(fn ($log) => [
                'id'          => $log->id,
                'old_status'  => $log->old_status,
                'new_status'  => $log->new_status,
                'reason'      => $log->reason,
                'changed_by'  => $log->changedBy?->name ?? 'System',
                'created_at'  => $log->created_at?->toIso8601String(),
            ]);
    }

    // ─── Route Sync ──────────────────────────────────────────────────────────

    /**
     * Scan all named Laravel routes and upsert new ones into system_features.
     * Existing entries are never overwritten — only new routes are added.
     *
     * Returns counts of [inserted, skipped].
     */
    public function syncRoutes(?User $actor = null): array
    {
        $routes = Route::getRoutes()->getRoutesByName();
        $inserted = 0;
        $skipped  = 0;
        $sortOrder = 0;

        foreach ($routes as $name => $route) {
            // Skip internal/framework/auth routes
            if ($this->shouldSkipRoute($name)) {
                $skipped++;
                continue;
            }

            [$module, $menu] = $this->inferModuleAndMenu($name);

            $exists = SystemFeature::where('feature_key', $name)->exists();

            if (! $exists) {
                SystemFeature::create([
                    'module_name'    => $module,
                    'menu_name'      => $menu,
                    'feature_name'   => $this->humanize($name),
                    'feature_key'    => $name,
                    'route_name'     => $name,
                    'description'    => null,
                    'status'         => 'active',
                    'is_visible_in_menu' => false, // Admin decides what's visible
                    'sort_order'     => $sortOrder++,
                    'created_by'     => $actor?->id,
                    'updated_by'     => $actor?->id,
                ]);
                $inserted++;
            } else {
                $skipped++;
            }
        }

        SystemFeature::clearCache();

        return compact('inserted', 'skipped');
    }

    // ─── Private Helpers ─────────────────────────────────────────────────────

    private function shouldSkipRoute(string $name): bool
    {
        $skipPrefixes = [
            'debugbar.',
            'ignition.',
            'sanctum.',
            'horizon.',
            'telescope.',
            'filament.',
            'livewire.',
            'vapor.',
        ];

        foreach ($skipPrefixes as $prefix) {
            if (str_starts_with($name, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Infer [module, menu] from a dot-notation route name.
     * e.g. 'settings.user-management.roles.index' → ['Settings', 'User Management']
     */
    private function inferModuleAndMenu(string $routeName): array
    {
        $parts = explode('.', $routeName);

        $moduleMap = [
            'site-administration' => 'Site Administration',
            'profile'             => 'Settings',
            'security'            => 'Settings',
            'appearance'          => 'Settings',
            'sso'                 => 'SSO',
            'dashboard'           => 'Dashboard',
        ];

        $module = $moduleMap[$parts[0] ?? ''] ?? ucfirst($parts[0] ?? 'General');
        $menu   = isset($parts[1]) ? $this->humanize($parts[1]) : $module;

        return [$module, $menu];
    }

    private function humanize(string $key): string
    {
        return ucwords(str_replace(['-', '_', '.'], ' ', $key));
    }
}
