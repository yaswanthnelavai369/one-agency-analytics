<?php

namespace App\Repositories\Contracts;

use App\Models\HealthScore;
use Illuminate\Database\Eloquent\Collection;

interface HealthScoreRepositoryInterface
{
    public function latestForClient(int $clientId): ?HealthScore;

    /** @return Collection<int, HealthScore> ordered by date ascending — for trend graphs. */
    public function historyForClient(int $clientId, string $from, string $to): Collection;

    public function upsertForDate(int $clientId, string $date, array $data): HealthScore;
}
