<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Services\SiteAdministration\ModuleActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ModuleActivityLogController extends Controller
{
    /**
     * @var array<string, string>
     */
    private const MODULE_PERMISSIONS = [
        'authentication' => 'authentication.view',
        'organizations' => 'access.organizations.view',
    ];

    public function __construct(private readonly ModuleActivityLogger $activity) {}

    public function index(Request $request, string $module): JsonResponse
    {
        abort_unless(array_key_exists($module, self::MODULE_PERMISSIONS), 404);

        $validated = $request->validate([
            'cursor' => ['nullable', 'string'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:50'],
        ]);

        Gate::authorize(self::MODULE_PERMISSIONS[$module]);

        return response()->json($this->activity->latestForModule(
            module: $module,
            perPage: (int) ($validated['per_page'] ?? 20),
            cursor: $validated['cursor'] ?? null,
        ));
    }
}
