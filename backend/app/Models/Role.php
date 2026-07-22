<?php

namespace App\Models;

use Spatie\Permission\Models\Role as SpatieRole;

class Role extends SpatieRole
{
    protected $fillable = ['name', 'guard_name', 'agency_id', 'is_system_role'];

    protected function casts(): array
    {
        return [
            'is_system_role' => 'boolean',
        ];
    }
}
