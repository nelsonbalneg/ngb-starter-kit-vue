<?php

namespace Database\Seeders;

use App\Models\Organization;
use Illuminate\Database\Seeder;

class OrganizationSeeder extends Seeder
{
    public function run(): void
    {
        /** @var array{slug: string, name: string, type: string, description: string} $parentDefinition */
        $parentDefinition = config('starter-kit.organization');

        $parent = Organization::query()->updateOrCreate(
            ['slug' => $parentDefinition['slug']],
            [
                'parent_id' => null,
                'type' => $parentDefinition['type'],
                'name' => $parentDefinition['name'],
                'description' => $parentDefinition['description'],
                'is_active' => true,
            ],
        );

        /** @var array<int, array{slug: string, name: string, type: string, description: string}> $workspaces */
        $workspaces = [
            config('starter-kit.default_workspace'),
            ...config('starter-kit.sample_workspaces', []),
        ];

        collect($workspaces)->each(fn (array $workspace): Organization => Organization::query()->updateOrCreate(
            ['slug' => $workspace['slug']],
            [
                'parent_id' => $parent->id,
                'type' => $workspace['type'],
                'name' => $workspace['name'],
                'description' => $workspace['description'],
                'is_active' => true,
            ],
        ));
    }
}
