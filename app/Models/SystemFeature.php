<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;

class SystemFeature extends Model
{
    protected $fillable = [
        'module_name',
        'menu_name',
        'feature_name',
        'feature_key',
        'route_name',
        'description',
        'status',
        'maintenance_message',
        'is_visible_in_menu',
        'sort_order',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'is_visible_in_menu' => 'boolean',
        'sort_order'         => 'integer',
    ];

    // ─── Relationships ───────────────────────────────────────────────────────

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function statusLogs(): HasMany
    {
        return $this->hasMany(FeatureStatusLog::class)->latest();
    }

    // ─── Scopes ──────────────────────────────────────────────────────────────

    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', 'active');
    }

    public function scopeMaintenance(Builder $query): Builder
    {
        return $query->where('status', 'maintenance');
    }

    public function scopeDisabled(Builder $query): Builder
    {
        return $query->where('status', 'disabled');
    }

    public function scopeVisibleInMenu(Builder $query): Builder
    {
        return $query->where('is_visible_in_menu', true);
    }

    public function scopeModule(Builder $query, string $module): Builder
    {
        return $query->where('module_name', $module);
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isInMaintenance(): bool
    {
        return $this->status === 'maintenance';
    }

    public function isDisabled(): bool
    {
        return $this->status === 'disabled';
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'active'      => 'emerald',
            'maintenance' => 'amber',
            'disabled'    => 'red',
            default       => 'slate',
        };
    }

    // ─── Cache ───────────────────────────────────────────────────────────────

    public static function cacheKey(): string
    {
        return 'system_features.all';
    }

    /**
     * Returns a flat array keyed by feature_key => ['status', 'maintenance_message', 'feature_name']
     * Used for fast middleware lookups without DB hits.
     */
    public static function allCached(): array
    {
        return Cache::rememberForever(static::cacheKey(), function () {
            return static::select(['feature_key', 'status', 'maintenance_message', 'feature_name', 'route_name'])
                ->get()
                ->keyBy('feature_key')
                ->map(fn ($f) => [
                    'status'              => $f->status,
                    'maintenance_message' => $f->maintenance_message,
                    'feature_name'        => $f->feature_name,
                    'route_name'          => $f->route_name,
                ])
                ->toArray();
        });
    }

    public static function clearCache(): void
    {
        Cache::forget(static::cacheKey());
    }

    /**
     * Returns an array keyed by route_name => status
     * Used to hide/badge sidebar items.
     */
    public static function routeStatusMap(): array
    {
        $features = static::allCached();
        $map = [];
        foreach ($features as $data) {
            if (! empty($data['route_name'])) {
                $map[$data['route_name']] = $data['status'];
            }
        }
        return $map;
    }
}
