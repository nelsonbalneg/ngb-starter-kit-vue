<?php

return [
    'organization' => [
        'slug' => env('STARTER_PARENT_ORGANIZATION_SLUG', 'starter'),
        'name' => env('STARTER_PARENT_ORGANIZATION_NAME', 'Starter Organization'),
        'type' => env('STARTER_PARENT_ORGANIZATION_TYPE', 'organization'),
        'description' => env('STARTER_PARENT_ORGANIZATION_DESCRIPTION', 'Parent organization workspace.'),
    ],

    'default_workspace' => [
        'slug' => env('STARTER_DEFAULT_WORKSPACE_SLUG', 'main'),
        'name' => env('STARTER_DEFAULT_WORKSPACE_NAME', 'Main Workspace'),
        'type' => env('STARTER_DEFAULT_WORKSPACE_TYPE', 'workspace'),
        'description' => env('STARTER_DEFAULT_WORKSPACE_DESCRIPTION', 'Primary starter-kit workspace.'),
    ],

    'sample_workspaces' => [
        [
            'slug' => env('STARTER_SAMPLE_WORKSPACE_SLUG', 'branch'),
            'name' => env('STARTER_SAMPLE_WORKSPACE_NAME', 'Branch Workspace'),
            'type' => env('STARTER_SAMPLE_WORKSPACE_TYPE', 'workspace'),
            'description' => env('STARTER_SAMPLE_WORKSPACE_DESCRIPTION', 'Secondary sample workspace.'),
        ],
    ],

    'users' => [
        [
            'name' => env('STARTER_SUPER_ADMIN_NAME', 'Super Admin'),
            'email' => env('STARTER_SUPER_ADMIN_EMAIL', 'super_admin@example.com'),
            'role' => 'super_admin',
        ],
        [
            'name' => env('STARTER_ADMIN_NAME', 'Site Administrator'),
            'email' => env('STARTER_ADMIN_EMAIL', 'admin@example.com'),
            'role' => 'super_admin',
        ],
        [
            'name' => env('STARTER_USER_NAME', 'Application User'),
            'email' => env('STARTER_USER_EMAIL', 'user@example.com'),
            'role' => 'user',
        ],
        [
            'name' => env('STARTER_EMPLOYEE_NAME', 'Employee User'),
            'email' => env('STARTER_EMPLOYEE_EMAIL', 'employee@example.com'),
            'role' => 'employee',
        ],
    ],

    'default_password' => env('STARTER_DEFAULT_PASSWORD', 'password'),
];
