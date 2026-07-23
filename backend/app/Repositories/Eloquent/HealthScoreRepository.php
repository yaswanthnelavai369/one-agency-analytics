<?php

namespace App\Repositories\Eloquent;

use App\Models\HealthScore;
use App\Repositories\Contracts\HealthScoreRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class HealthScoreRepository implements HealthScoreRepositoryInterface
{
    public function __construct(protected HealthScore $model) {}

    public function latestForClient(int $clientId): ?HealthScore
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->orderByDesc('date')
            ->first();
    }

    public function historyForClient(int $clientId, string $from, string $to): Collection
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get();
    }

    public function upsertForDate(int $clientId, string $date, array $data): HealthScore
    {
        return $this->model->newQuery()->updateOrCreate(
            ['client_id' => $clientId, 'date' => $date],
            $data
        );
    }
}
