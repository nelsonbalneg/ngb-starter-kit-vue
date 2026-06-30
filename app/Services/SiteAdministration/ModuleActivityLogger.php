<?php

namespace App\Services\SiteAdministration;

use App\Models\ModuleActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Pagination\Cursor;

class ModuleActivityLogger
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function record(
        string $module,
        string $action,
        string $description,
        ?Model $subject = null,
        array $metadata = [],
    ): ModuleActivityLog {
        return ModuleActivityLog::query()->create([
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'causer_id' => auth()->id(),
            'metadata' => $metadata === [] ? null : $metadata,
        ]);
    }

    /**
     * @return array{
     *     data: array<int, array{
     *     id: int,
     *     module: string,
     *     action: string,
     *     description: string,
     *     causer: array{id: int, name: string, email: string}|null,
     *     metadata: array<string, mixed>,
     *     created_at: string|null,
     *     created_at_human: string|null,
     *     created_on: string|null,
     *     created_time: string|null
     *     }>,
     *     next_cursor: string|null,
     *     per_page: int
     * }
     */
    public function latestForModule(string $module, int $perPage = 20, ?string $cursor = null): array
    {
        $activities = ModuleActivityLog::query()
            ->with('causer:id,name,email')
            ->where('module', $module)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->cursorPaginate(
                perPage: min(max($perPage, 1), 50),
                cursor: $cursor ? Cursor::fromEncoded($cursor) : null,
            );

        return [
            'data' => $activities
                ->getCollection()
                ->map(fn (ModuleActivityLog $activity): array => $this->format($activity))
                ->all(),
            'next_cursor' => $activities->nextCursor()?->encode(),
            'per_page' => $activities->perPage(),
        ];
    }

    /**
     * @return array{
     *     id: int,
     *     module: string,
     *     action: string,
     *     description: string,
     *     causer: array{id: int, name: string, email: string}|null,
     *     metadata: array<string, mixed>,
     *     created_at: string|null,
     *     created_at_human: string|null,
     *     created_on: string|null,
     *     created_time: string|null
     * }
     */
    private function format(ModuleActivityLog $activity): array
    {
        return [
            'id' => $activity->id,
            'module' => $activity->module,
            'action' => $activity->action,
            'description' => $activity->description,
            'causer' => $activity->causer
                ? [
                    'id' => $activity->causer->id,
                    'name' => $activity->causer->name,
                    'email' => $activity->causer->email,
                ]
                : null,
            'metadata' => is_array($activity->metadata) ? $activity->metadata : [],
            'created_at' => $activity->created_at?->toISOString(),
            'created_at_human' => $activity->created_at?->diffForHumans(),
            'created_on' => $activity->created_at?->format('M j, Y'),
            'created_time' => $activity->created_at?->format('g:i A'),
        ];
    }
}
