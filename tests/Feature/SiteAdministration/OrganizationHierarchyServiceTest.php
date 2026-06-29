<?php

use App\Enums\OrganizationType;
use App\Enums\OrganizationUnitType;
use App\Models\Organization;
use App\Models\OrganizationUnit;
use App\Services\SiteAdministration\AccessAdministrationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function hierarchyService(): AccessAdministrationService
{
    return app(AccessAdministrationService::class);
}

function createOrganization(array $attributes = []): Organization
{
    $name = $attributes['name'] ?? fake()->unique()->company();

    return Organization::query()->create(array_merge([
        'name' => $name,
        'slug' => str($name)->slug()->toString(),
        'type' => OrganizationType::University->value,
        'parent_id' => null,
        'is_active' => true,
    ], $attributes));
}

function createUnit(array $attributes = []): OrganizationUnit
{
    return OrganizationUnit::query()->create(array_merge([
        'organization_id' => createOrganization(['type' => OrganizationType::Campus->value])->id,
        'parent_id' => null,
        'type' => OrganizationUnitType::Office->value,
        'name' => fake()->unique()->company(),
        'is_active' => true,
    ], $attributes));
}

test('a campus must be assigned to a parent organization', function () {
    hierarchyService()->storeOrganization([
        'type' => OrganizationType::Campus->value,
        'parent_id' => null,
        'name' => 'Standalone Campus',
        'slug' => 'standalone-campus',
        'is_active' => true,
    ]);
})->throws(ValidationException::class, 'A campus must be assigned to a parent organization.');

test('a campus can only be nested under a parent organization', function () {
    $parent = createOrganization(['name' => 'Parent University', 'slug' => 'parent-university']);
    $campus = createOrganization([
        'name' => 'Existing Campus',
        'slug' => 'existing-campus',
        'type' => OrganizationType::Campus->value,
        'parent_id' => $parent->id,
    ]);

    hierarchyService()->storeOrganization([
        'type' => OrganizationType::Campus->value,
        'parent_id' => $campus->id,
        'name' => 'Invalid Nested Campus',
        'slug' => 'invalid-nested-campus',
        'is_active' => true,
    ]);
})->throws(ValidationException::class, 'A campus can only be nested under a parent organization.');

test('an organization cannot be moved under itself', function () {
    $parent = createOrganization(['name' => 'Parent University', 'slug' => 'parent-university']);

    hierarchyService()->updateOrganization($parent, [
        'type' => OrganizationType::Campus->value,
        'parent_id' => $parent->id,
        'name' => $parent->name,
        'slug' => $parent->slug,
        'is_active' => true,
    ]);
})->throws(ValidationException::class, 'An organization cannot be its own parent.');

test('a parent organization with campuses cannot be changed into a campus', function () {
    $parent = createOrganization(['name' => 'Parent University', 'slug' => 'parent-university']);
    createOrganization([
        'name' => 'Existing Campus',
        'slug' => 'existing-campus',
        'type' => OrganizationType::Campus->value,
        'parent_id' => $parent->id,
    ]);
    $newParent = createOrganization(['name' => 'New Parent', 'slug' => 'new-parent']);

    hierarchyService()->updateOrganization($parent, [
        'type' => OrganizationType::Campus->value,
        'parent_id' => $newParent->id,
        'name' => $parent->name,
        'slug' => $parent->slug,
        'is_active' => true,
    ]);
})->throws(ValidationException::class, 'Move campuses before changing this parent organization into a campus.');

test('an office cannot be nested under another unit', function () {
    $parent = createOrganization(['name' => 'Parent University', 'slug' => 'parent-university']);
    $campus = createOrganization([
        'name' => 'Campus',
        'slug' => 'campus',
        'type' => OrganizationType::Campus->value,
        'parent_id' => $parent->id,
    ]);
    $office = createUnit([
        'organization_id' => $campus->id,
        'name' => 'Office',
        'type' => OrganizationUnitType::Office->value,
    ]);

    hierarchyService()->storeOrganizationUnit([
        'organization_id' => $campus->id,
        'parent_id' => $office->id,
        'type' => OrganizationUnitType::Office->value,
        'name' => 'Invalid Office',
        'is_active' => true,
    ]);
})->throws(ValidationException::class, 'An office cannot be nested under another unit.');

test('a campus with offices cannot be changed into a parent organization', function () {
    $parent = createOrganization(['name' => 'Parent University', 'slug' => 'parent-university']);
    $campus = createOrganization([
        'name' => 'Campus',
        'slug' => 'campus',
        'type' => OrganizationType::Campus->value,
        'parent_id' => $parent->id,
    ]);
    createUnit([
        'organization_id' => $campus->id,
        'name' => 'Office',
        'type' => OrganizationUnitType::Office->value,
    ]);

    hierarchyService()->updateOrganization($campus, [
        'type' => OrganizationType::University->value,
        'parent_id' => null,
        'name' => $campus->name,
        'slug' => $campus->slug,
        'is_active' => true,
    ]);
})->throws(ValidationException::class, 'Move offices before changing this campus into a parent organization.');

test('a department must belong to an office in the same campus', function () {
    $parent = createOrganization(['name' => 'Parent University', 'slug' => 'parent-university']);
    $firstCampus = createOrganization([
        'name' => 'First Campus',
        'slug' => 'first-campus',
        'type' => OrganizationType::Campus->value,
        'parent_id' => $parent->id,
    ]);
    $secondCampus = createOrganization([
        'name' => 'Second Campus',
        'slug' => 'second-campus',
        'type' => OrganizationType::Campus->value,
        'parent_id' => $parent->id,
    ]);
    $office = createUnit([
        'organization_id' => $firstCampus->id,
        'name' => 'First Campus Office',
        'type' => OrganizationUnitType::Office->value,
    ]);

    hierarchyService()->storeOrganizationUnit([
        'organization_id' => $secondCampus->id,
        'parent_id' => $office->id,
        'type' => OrganizationUnitType::Department->value,
        'name' => 'Invalid Department',
        'is_active' => true,
    ]);
})->throws(ValidationException::class, 'The selected office belongs to a different campus.');

test('valid hierarchy records can be created through the service', function () {
    $parent = hierarchyService()->storeOrganization([
        'type' => OrganizationType::University->value,
        'parent_id' => null,
        'name' => 'University',
        'slug' => 'university',
        'is_active' => true,
    ]);

    $campus = hierarchyService()->storeOrganization([
        'type' => OrganizationType::Campus->value,
        'parent_id' => $parent->id,
        'name' => 'Main Campus',
        'slug' => 'main-campus',
        'is_active' => true,
    ]);

    $office = hierarchyService()->storeOrganizationUnit([
        'organization_id' => $campus->id,
        'parent_id' => null,
        'type' => OrganizationUnitType::Office->value,
        'name' => 'Office of the President',
        'is_active' => true,
    ]);

    $department = hierarchyService()->storeOrganizationUnit([
        'organization_id' => $campus->id,
        'parent_id' => $office->id,
        'type' => OrganizationUnitType::Department->value,
        'name' => 'Executive Department',
        'is_active' => true,
    ]);

    expect($campus->parent_id)->toBe($parent->id)
        ->and($office->parent_id)->toBeNull()
        ->and($department->parent_id)->toBe($office->id);
});
