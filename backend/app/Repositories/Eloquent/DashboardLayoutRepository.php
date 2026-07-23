<?php

namespace App\Repositories\Eloquent;

use App\Models\DashboardLayout;
use App\Repositories\Contracts\DashboardLayoutRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class DashboardLayoutRepository extends BaseRepository implements DashboardLayoutRepositoryInterface
{
    public function __construct(DashboardLayout $model)
    {
        parent::__construct($model);
    }

    public function visibleToUser(int $agencyId, int $userId, ?int $clientId = null): Collection
    {
        return $this->model->newQuery()
            ->where('agency_id', $agencyId)
            ->when($clientId, fn ($q) => $q->where('client_id', $clientId))
            ->where(fn ($q) => $q->where('user_id', $userId)->orWhere('is_shared', true))
            ->with('widgets')
            ->latest()
            ->get();
    }

    public function findVisibleToUser(int $id, int $agencyId, int $userId): ?DashboardLayout
    {
        return $this->model->newQuery()
            ->where('agency_id', $agencyId)
            ->where('id', $id)
            ->where(fn ($q) => $q->where('user_id', $userId)->orWhere('is_shared', true))
            ->with('widgets')
            ->first();
    }

    public function findDefault(int $agencyId, int $userId, ?int $clientId): ?DashboardLayout
    {
        return $this->model->newQuery()
            ->where('agency_id', $agencyId)
            ->where('user_id', $userId)
            ->where('client_id', $clientId)
            ->where('is_default', true)
            ->with('widgets')
            ->first();
    }
}
