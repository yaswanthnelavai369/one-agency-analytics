<?php

namespace Database\Seeders;

use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use App\Services\RBAC\RBACService;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        app()->make(PermissionRegistrar::class)->forgetCachedPermissions();

        $rbac = app(RBACService::class);

        // Global (agency_id = null) permission catalogue — the full set any
        // agency's roles can be composed from, plus the Master Admin's own role.
        foreach (RBACService::DEFAULT_PERMISSIONS as $module => $actions) {
            foreach ($actions as $action) {
                Permission::firstOrCreate([
                    'name' => $rbac->permissionName($module, $action),
                    'guard_name' => 'sanctum',
                ], ['module' => $module]);
            }
        }

        // Master Admin is a global, platform-wide role (agency_id null) with every permission.
        app()->make(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $masterAdminRole = Role::firstOrCreate(
            ['name' => 'Master Admin', 'guard_name' => 'sanctum', 'agency_id' => null],
            ['is_system_role' => true]
        );
        $masterAdminRole->syncPermissions($rbac->allPermissionNames());

        // Seed one Master Admin account for initial platform access.
        $admin = User::firstOrCreate(
            ['email' => 'admin@search29.ai'],
            [
                'uuid' => Str::uuid(),
                'user_type' => 'master_admin',
                'name' => 'Platform Admin',
                'password' => Hash::make('ChangeMe!12345'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );
        app()->make(PermissionRegistrar::class)->setPermissionsTeamId(null);
        $admin->syncRoles([$masterAdminRole]);

        // Demo agency owner + agency + client, for local testing against a fresh install.
        $demoOwner = User::firstOrCreate(
            ['email' => 'agency@search29.ai'],
            [
                'uuid' => Str::uuid(),
                'user_type' => 'agency',
                'name' => 'Demo Agency Owner',
                'password' => Hash::make('ChangeMe!12345'),
                'status' => 'active',
                'email_verified_at' => now(),
            ]
        );

        $agencyService = app(\App\Services\Agency\AgencyService::class);
        $plan = \App\Models\Plan::where('slug', 'professional')->first() ?: \App\Models\Plan::first();

        $demoAgency = \App\Models\Agency::where('owner_id', $demoOwner->id)->first();
        if (! $demoAgency) {
            $demoAgency = $agencyService->createForOwner($demoOwner, [
                'name' => 'Search29 Agency',
                'plan_id' => $plan?->id,
            ]);
        } else {
            $demoOwner->forceFill(['agency_id' => $demoAgency->id])->save();
        }

        $clientService = app(\App\Services\Client\ClientService::class);
        $demoClient = \App\Models\Client::where('agency_id', $demoAgency->id)->first();
        if (! $demoClient) {
            $clientService->createForAgency($demoAgency, [
                'name' => 'Acme Corporation',
                'website' => 'https://acme.example.com',
                'industry' => 'Manufacturing',
                'timezone' => 'UTC',
            ]);
        }
    }
}
