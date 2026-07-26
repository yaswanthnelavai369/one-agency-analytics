<?php

namespace App\Services\RBAC;

use App\Models\Agency;
use App\Models\Role;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

/**
 * Central place for role/permission logic. Roles are "teamed" by agency_id
 * (see config/permission.php), so the same role name (e.g. "Editor") can
 * exist independently per agency with a different permission set, while
 * global platform permissions (agency_id null) back the Master Admin role.
 */
class RBACService
{
    /** Baseline permission set every fresh agency gets seeded with. */
    public const DEFAULT_PERMISSIONS = [
        'dashboards' => ['view', 'create', 'edit', 'delete', 'share'],
        'reports' => ['view', 'create', 'export', 'schedule'],
        'integrations' => ['view', 'connect', 'disconnect'],
        'clients' => ['view', 'create', 'edit', 'delete'],
        'team' => ['view', 'invite', 'edit', 'remove'],
        'billing' => ['view', 'manage'],
        'settings' => ['view', 'edit'],
        'support' => ['view', 'create'],
        'health' => ['view', 'recalculate'],
        'ai_chat' => ['view', 'send'],
        'anomalies' => ['view', 'manage'],
        'goals' => ['view', 'create', 'edit', 'delete'],
    ];

    /** System roles auto-created for every agency, with sane default abilities. */
    public const SYSTEM_ROLE_DEFAULTS = [
        'Agency Owner' => '*',
        'Manager' => ['dashboards.*', 'reports.*', 'integrations.*', 'clients.*', 'team.view', 'support.*', 'health.*', 'ai_chat.*', 'anomalies.*', 'goals.*'],
        'Analyst' => ['dashboards.view', 'reports.view', 'reports.export', 'integrations.view', 'health.view', 'ai_chat.*', 'anomalies.*', 'goals.view', 'goals.create'],
        'Viewer' => ['dashboards.view', 'reports.view', 'health.view', 'ai_chat.view', 'anomalies.view', 'goals.view'],
    ];

    public function permissionName(string $module, string $action): string
    {
        return "{$module}.{$action}";
    }

    public function allPermissionNames(): array
    {
        $names = [];
        foreach (self::DEFAULT_PERMISSIONS as $module => $actions) {
            foreach ($actions as $action) {
                $names[] = $this->permissionName($module, $action);
            }
        }

        return $names;
    }

    /**
     * Provisions the standard role set for a newly created agency.
     * Wrapped in a transaction and scoped to the agency's team id so
     * Spatie writes the roles/pivot rows with the correct agency_id.
     */
    public function provisionDefaultRoles(Agency $agency): void
    {
        DB::transaction(function () use ($agency) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($agency->id);

            foreach (self::SYSTEM_ROLE_DEFAULTS as $roleName => $abilities) {
                $role = Role::firstOrCreate(
                    ['name' => $roleName, 'guard_name' => 'sanctum', 'agency_id' => $agency->id],
                    ['is_system_role' => true]
                );

                if ($abilities === '*') {
                    $role->syncPermissions($this->allPermissionNames());
                } else {
                    $expanded = collect($abilities)->flatMap(function ($ability) {
                        if (str_ends_with($ability, '.*')) {
                            $module = substr($ability, 0, -2);
                            $actions = self::DEFAULT_PERMISSIONS[$module] ?? [];

                            return collect($actions)->map(fn ($a) => $this->permissionName($module, $a));
                        }

                        return [$ability];
                    })->unique()->values()->all();

                    $role->syncPermissions($expanded);
                }
            }
        });
    }

    public function assignRole(User $user, string $roleName, Agency $agency): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($agency->id);
        $user->syncRoles([$roleName]);
    }

    public function grantDirectPermission(User $user, string $permissionName, Agency $agency): void
    {
        app(PermissionRegistrar::class)->setPermissionsTeamId($agency->id);
        $user->givePermissionTo($permissionName);
    }

    public function userCan(User $user, string $module, string $action): bool
    {
        if ($user->isMasterAdmin()) {
            return true;
        }

        app(PermissionRegistrar::class)->setPermissionsTeamId($user->agency_id);

        return $user->can($this->permissionName($module, $action));
    }
}
