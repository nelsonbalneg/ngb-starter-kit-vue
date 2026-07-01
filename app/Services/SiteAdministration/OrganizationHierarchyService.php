<?php

namespace App\Services\SiteAdministration;

use App\Enums\OrganizationType;
use App\Enums\OrganizationUnitType;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class OrganizationHierarchyService
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function validateOrganization(array $data, ?Organization $organization = null): void
    {
        $type = (string) ($data['type'] ?? '');
        $parentId = $data['parent_id'] ?? null;

        if ($type === OrganizationType::University->value) {
            if ($organization !== null && $organization->units()->exists()) {
                $this->fail('type', 'Move offices before changing this campus into a parent organization.');
            }

            if ($parentId !== null) {
                $this->fail('parent_id', 'A parent organization cannot be nested under another organization.');
            }

            return;
        }

        if ($type !== OrganizationType::Campus->value) {
            $this->fail('type', 'The selected organization type is invalid.');
        }

        if ($organization !== null && $organization->children()->exists()) {
            $this->fail('type', 'Move campuses before changing this parent organization into a campus.');
        }

        if ($parentId === null) {
            $this->fail('parent_id', 'A campus must be assigned to a parent organization.');
        }

        if ($organization !== null && (int) $parentId === $organization->id) {
            $this->fail('parent_id', 'An organization cannot be its own parent.');
        }

        $parent = Organization::query()->find($parentId);

        if (! $parent instanceof Organization) {
            $this->fail('parent_id', 'The selected parent organization does not exist.');
        }

        if ($parent->type !== OrganizationType::University->value || $parent->parent_id !== null) {
            $this->fail('parent_id', 'A campus can only be nested under a parent organization.');
        }

        if ($organization !== null && $this->organizationDescendantIds($organization)->contains($parent->id)) {
            $this->fail('parent_id', 'An organization cannot be moved under one of its descendants.');
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function validateOrganizationUnit(array $data, ?OrganizationUnit $unit = null): void
    {
        $organizationId = (int) ($data['organization_id'] ?? 0);
        $type = (string) ($data['type'] ?? '');
        $parentId = $data['parent_id'] ?? null;
        $rootUnitTypes = [
            OrganizationUnitType::Campus->value,
            OrganizationUnitType::Office->value,
            OrganizationUnitType::College->value,
        ];

        if (! Organization::query()->whereKey($organizationId)->exists()) {
            $this->fail('organization_id', 'The selected campus does not exist.');
        }

        if ($parentId === null) {
            if ($unit !== null && $unit->children()->exists() && $unit->organization_id !== $organizationId) {
                $this->fail('organization_id', 'Move child units before moving this unit to another campus.');
            }

            if (! in_array($type, $rootUnitTypes, true)) {
                $this->fail('parent_id', 'The selected type must be assigned to a parent unit.');
            }

            return;
        }

        if ($unit !== null && (int) $parentId === $unit->id) {
            $this->fail('parent_id', 'A unit cannot be its own parent.');
        }

        $parent = OrganizationUnit::query()->find($parentId);

        if (! $parent instanceof OrganizationUnit) {
            $this->fail('parent_id', 'The selected parent unit does not exist.');
        }

        if ($parent->organization_id !== $organizationId) {
            $this->fail('parent_id', 'The selected parent unit belongs to a different campus.');
        }

        if ($unit !== null && $this->unitDescendantIds($unit)->contains($parent->id)) {
            $this->fail('parent_id', 'A unit cannot be moved under one of its descendants.');
        }
    }

    /**
     * @return Collection<int, int>
     */
    private function organizationDescendantIds(Organization $organization): Collection
    {
        $children = $organization->children()->get(['id']);

        return $children->pluck('id')->merge(
            $children->flatMap(fn (Organization $child) => $this->organizationDescendantIds($child)),
        );
    }

    /**
     * @return Collection<int, int>
     */
    private function unitDescendantIds(OrganizationUnit $unit): Collection
    {
        $children = $unit->children()->get(['id']);

        return $children->pluck('id')->merge(
            $children->flatMap(fn (OrganizationUnit $child) => $this->unitDescendantIds($child)),
        );
    }

    private function fail(string $field, string $message): never
    {
        throw ValidationException::withMessages([
            $field => $message,
        ]);
    }
}
