<?php

namespace App\Http\Controllers\SiteAdministration;

use App\Http\Controllers\Controller;
use App\Http\Requests\SiteAdministration\LookupRequest;
use App\Models\Lookup;
use App\Services\SiteAdministration\LookupAdministrationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class LookupController extends Controller
{
    public function __construct(private readonly LookupAdministrationService $service) {}

    public function index(Request $request): Response
    {
        Gate::authorize('lookups.view');

        return Inertia::render('site-administration/Lookups', [
            'lookups' => $this->service->list($request->only(['search', 'group', 'status'])),
            'filters' => $request->only(['search', 'group', 'status']),
            'groups' => Lookup::query()->select('group')->distinct()->orderBy('group')->pluck('group'),
        ]);
    }

    public function store(LookupRequest $request): RedirectResponse
    {
        Gate::authorize('lookups.create');

        $this->service->store($request->validated());

        return to_route('site-administration.lookups.index')
            ->with('success', 'Lookup saved successfully.');
    }

    public function update(LookupRequest $request, Lookup $lookup): RedirectResponse
    {
        Gate::authorize('lookups.update');

        $this->service->update($lookup, $request->validated());

        return to_route('site-administration.lookups.index')
            ->with('success', 'Lookup updated successfully.');
    }

    public function destroy(Lookup $lookup): RedirectResponse
    {
        Gate::authorize('lookups.delete');

        $this->service->delete($lookup);

        return to_route('site-administration.lookups.index')
            ->with('success', 'Lookup deleted successfully.');
    }
}
