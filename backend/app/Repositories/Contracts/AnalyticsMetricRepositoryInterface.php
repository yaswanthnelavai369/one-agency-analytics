<?php

namespace App\Repositories\Contracts;

interface AnalyticsMetricRepositoryInterface
{
    /** Insert or update a single day's value for a metric (idempotent re-sync). */
    public function upsert(int $clientId, int $integrationId, string $metric, string $date, float $value, ?array $dimensions = null): void;

    /** @return array<array{date: string, value: float}> ordered by date ascending */
    public function series(int $clientId, string $metric, string $from, string $to): array;

    public function latestValue(int $clientId, string $metric): ?float;
}
