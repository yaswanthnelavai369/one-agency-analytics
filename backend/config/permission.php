<?php

return [
    'models' => [
        'permission' => \App\Models\Permission::class,
        'role' => \App\Models\Role::class,
    ],

    'table_names' => [
        'roles' => 'roles',
        'permissions' => 'permissions',
        'model_has_permissions' => 'model_has_permissions',
        'model_has_roles' => 'model_has_roles',
        'role_has_permissions' => 'role_has_permissions',
    ],

    'column_names' => [
        'role_pivot_key' => null,
        'permission_pivot_key' => null,
        'model_morph_key' => 'model_id',
        // Roles/permissions are scoped per-agency ("team"). Global platform roles
        // (Master Admin) have agency_id = null.
        'team_foreign_key' => 'agency_id',
    ],

    // Enables spatie/laravel-permission's multi-tenant "teams" feature, scoped by agency.
    'teams' => true,

    'display_permission_in_exception' => false,
    'display_role_in_exception' => false,
    'enable_wildcard_permission' => false,

    'cache' => [
        'expiration_time' => \DateInterval::createFromDateString('24 hours'),
        'key' => 'search29.permission.cache',
        'store' => 'redis',
    ],
];
