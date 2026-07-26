<?php

namespace App\Repositories\Eloquent;

use App\Models\Goal;
use App\Repositories\Contracts\GoalRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class GoalRepository extends BaseRepository implements GoalRepositoryInterface
{
    public function __construct(Goal $model)
    {
        parent::__construct($model);
    }

    public function forClient(int $clientId, ?string $status = null): Collection
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->latest()
            ->get();
    }

    public function findForClient(int $clientId, int $goalId): ?Goal
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->where('id', $goalId)
            ->first();
    }

    public function activeWithUpcomingDeadline(int $withinDays): Collection
    {
        return $this->model->newQuery()
            ->where('status', 'active')
            ->whereNotNull('deadline')
            ->whereBetween('deadline', [now()->toDateString(), now()->addDays($withinDays)->toDateString()])
            ->get();
    }
}
