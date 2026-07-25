<?php

namespace App\Repositories\Eloquent;

use App\Models\Anomaly;
use App\Repositories\Contracts\AnomalyRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class AnomalyRepository implements AnomalyRepositoryInterface
{
    public function __construct(protected Anomaly $model) {}

    public function forClient(int $clientId, ?string $status = null): Collection
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->when($status, fn ($q) => $q->where('status', $status))
            ->orderByDesc('detected_date')
            ->orderByRaw("FIELD(severity, 'critical', 'warning', 'info')")
            ->get();
    }

    public function findForClient(int $clientId, int $anomalyId): ?Anomaly
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->where('id', $anomalyId)
            ->first();
    }

    public function firstOrCreate(array $uniqueKeys, array $data): Anomaly
    {
        return $this->model->newQuery()->firstOrCreate($uniqueKeys, $data);
    }
}
