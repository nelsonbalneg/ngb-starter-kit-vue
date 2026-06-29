<?php

namespace App\Services\SiteAdministration;

use App\Models\Lookup;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class LookupAdministrationService
{
    /**
     * @param  array<string, mixed>  $filters
     * @return LengthAwarePaginator<int, Lookup>
     */
    public function list(array $filters): LengthAwarePaginator
    {
        return Lookup::query()
            ->when($filters['search'] ?? null, fn (Builder $query, string $search): Builder => $query
                ->where(fn (Builder $query): Builder => $query
                    ->where('name', 'like', "%{$search}%")
                    ->orWhere('code', 'like', "%{$search}%")
                    ->orWhere('group', 'like', "%{$search}%")))
            ->when($filters['group'] ?? null, fn (Builder $query, string $group): Builder => $query->where('group', $group))
            ->when(($filters['status'] ?? null) === 'active', fn (Builder $query): Builder => $query->where('is_active', true))
            ->when(($filters['status'] ?? null) === 'inactive', fn (Builder $query): Builder => $query->where('is_active', false))
            ->orderBy('group')
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function store(array $data): Lookup
    {
        return DB::transaction(fn (): Lookup => Lookup::create($data));
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Lookup $lookup, array $data): Lookup
    {
        return DB::transaction(function () use ($lookup, $data): Lookup {
            $lookup->update($data);

            return $lookup;
        });
    }

    public function delete(Lookup $lookup): void
    {
        DB::transaction(fn () => $lookup->delete());
    }
}
