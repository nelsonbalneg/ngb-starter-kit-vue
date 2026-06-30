<?php

namespace App\Console\Commands;

use App\Models\SystemFeature;
use App\Services\Features\FeatureService;
use Illuminate\Console\Command;

class SyncSystemFeatures extends Command
{
    protected $signature = 'features:sync
                            {--dry-run : Preview which routes would be added without writing to database}';

    protected $description = 'Scan all named Laravel routes and register new ones into system_features table';

    public function __construct(private readonly FeatureService $features)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $isDryRun = $this->option('dry-run');

        if ($isDryRun) {
            $this->info('[DRY RUN] Previewing routes that would be synced...');
            $this->previewRoutes();
            return self::SUCCESS;
        }

        $this->info('Scanning and syncing route features...');

        $result = $this->features->syncRoutes();

        $this->table(
            ['Metric', 'Count'],
            [
                ['New features added', $result['inserted']],
                ['Skipped (already exist or excluded)', $result['skipped']],
            ]
        );

        if ($result['inserted'] > 0) {
            $this->info('✓ Cache cleared. New features are now active.');
        } else {
            $this->info('✓ No new routes found. All routes are already registered.');
        }

        return self::SUCCESS;
    }

    private function previewRoutes(): void
    {
        $routes = collect(\Illuminate\Support\Facades\Route::getRoutes()->getRoutesByName())
            ->keys()
            ->filter(fn ($name) => ! SystemFeature::where('feature_key', $name)->exists())
            ->values();

        if ($routes->isEmpty()) {
            $this->warn('No new routes to sync.');
            return;
        }

        $this->table(
            ['Route Name'],
            $routes->map(fn ($r) => [$r])->toArray()
        );

        $this->info("Total routes that would be added: {$routes->count()}");
    }
}
