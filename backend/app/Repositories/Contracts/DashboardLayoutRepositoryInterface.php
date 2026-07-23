<?php

namespace App\Repositories\Contracts;

use App\Models\DashboardLayout;
use Illuminate\Database\Eloquent\Collection;

interface DashboardLayoutRepositoryInterface extends BaseRepositoryInterface
{
    /** Layouts visible to this user: their own, plus any marked shared within their agency. */
    public function visibleToUser(int $agencyId, int $userId, ?int $clientId = null): Collection;

    public function findVisibleToUser(int $id, int $agencyId, int $userId): ?DashboardLayout;

    public function findDefault(int $agencyId, int $userId, ?int $clientId): ?DashboardLayout;
}
