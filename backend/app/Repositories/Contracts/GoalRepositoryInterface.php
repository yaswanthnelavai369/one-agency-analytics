<?php

namespace App\Repositories\Contracts;

use App\Models\Goal;
use Illuminate\Database\Eloquent\Collection;

interface GoalRepositoryInterface extends BaseRepositoryInterface
{
    public function forClient(int $clientId, ?string $status = null): Collection;

    public function findForClient(int $clientId, int $goalId): ?Goal;

    /** Active goals with a deadline within $withinDays — used by GoalDeadlineDetector. */
    public function activeWithUpcomingDeadline(int $withinDays): Collection;
}
