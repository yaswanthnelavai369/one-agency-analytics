<?php

namespace App\Repositories\Eloquent;

use App\Models\AnalyticsMetric;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;

class AnalyticsMetricRepository implements AnalyticsMetricRepositoryInterface
{
    public function __construct(protected AnalyticsMetric $model) {}

    public function upsert(int $clientId, int $integrationId, string $metric, string $date, float $value, ?array $dimensions = null): void
    {
        $this->model->newQuery()->updateOrCreate(
            [
                'client_id' => $clientId,
                'integration_id' => $integrationId,
                'metric' => $metric,
                'date' => $date,
            ],
            [
                'value' => $value,
                'dimensions' => $dimensions,
            ]
        );
    }

    public function series(int $clientId, string $metric, string $from, string $to): array
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->where('metric', $metric)
            ->whereBetween('date', [$from, $to])
            ->orderBy('date')
            ->get(['date', 'value'])
            ->map(fn ($row) => ['date' => $row->date->toDateString(), 'value' => (float) $row->value])
            ->all();
    }

    public function latestValue(int $clientId, string $metric): ?float
    {
        $row = $this->model->newQuery()
            ->where('client_id', $clientId)
            ->where('metric', $metric)
            ->orderByDesc('date')
            ->first(['value']);

        return $row ? (float) $row->value : null;
    }

    public function sumBetween(int $clientId, string $metric, string $from, string $to): float
    {
        return (float) $this->model->newQuery()
            ->where('client_id', $clientId)
            ->where('metric', $metric)
            ->whereBetween('date', [$from, $to])
            ->sum('value');
    }

    public function averageBetween(int $clientId, string $metric, string $from, string $to): ?float
    {
        $avg = $this->model->newQuery()
            ->where('client_id', $clientId)
            ->where('metric', $metric)
            ->whereBetween('date', [$from, $to])
            ->avg('value');

        return $avg !== null ? (float) $avg : null;
    }

    public function hasData(int $clientId, string $metric): bool
    {
        return $this->model->newQuery()
            ->where('client_id', $clientId)
            ->where('metric', $metric)
            ->exists();
    }
}
