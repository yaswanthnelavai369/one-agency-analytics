<?php

namespace App\Repositories\Contracts;

interface AnalyticsMetricRepositoryInterface
{
    /** Insert or update a single day's value for a metric (idempotent re-sync). */
    public function upsert(int $clientId, int $integrationId, string $metric, string $date, float $value, ?array $dimensions = null): void;

    /** @return array<array{date: string, value: float}> ordered by date ascending */
    public function series(int $clientId, string $metric, string $from, string $to): array;

    public function latestValue(int $clientId, string $metric): ?float;

    /** Sum of a metric's values over a date range — used for period-over-period growth comparisons. */
    public function sumBetween(int $clientId, string $metric, string $from, string $to): float;

    /** Average of a metric's values over a date range — used for rate-style metrics (bounce rate, CTR, ROAS, ...). */
    public function averageBetween(int $clientId, string $metric, string $from, string $to): ?float;

    /** Whether any data exists at all for this metric — lets calculators distinguish "0" from "not connected yet". */
    public function hasData(int $clientId, string $metric): bool;
}
