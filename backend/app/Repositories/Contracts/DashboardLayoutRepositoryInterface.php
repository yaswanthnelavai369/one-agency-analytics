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

    /**
     * Layouts an agency team member has explicitly shared for a given client,
     * regardless of who owns them — this is what the client-portal dashboard
     * reads, since client users never own a layout themselves.
     */
    public function sharedForClient(int $clientId): Collection;
}
