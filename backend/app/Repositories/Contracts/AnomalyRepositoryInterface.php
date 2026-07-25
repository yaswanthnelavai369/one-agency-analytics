<?php

namespace App\Repositories\Contracts;

use App\Models\Anomaly;
use Illuminate\Database\Eloquent\Collection;

interface AnomalyRepositoryInterface
{
    public function forClient(int $clientId, ?string $status = null): Collection;

    public function findForClient(int $clientId, int $anomalyId): ?Anomaly;

    /** Creates if no anomaly of this type/metric/date exists yet for the client (see anomalies_dedup_unique). */
    public function firstOrCreate(array $uniqueKeys, array $data): Anomaly;
}
