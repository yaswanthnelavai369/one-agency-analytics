<?php

namespace App\Anomaly\Detectors;

use App\Anomaly\AnomalyMath;
use App\Anomaly\Contracts\AnomalyDetectorInterface;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/** Watches visitors/sessions for sudden drops or spikes vs. the trailing 7-day average. */
class TrafficAnomalyDetector implements AnomalyDetectorInterface
{
    protected const DROP_THRESHOLD = 30.0;  // % below baseline
    protected const SPIKE_THRESHOLD = 75.0; // % above baseline

    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function detect(Client $client): array
    {
        $anomalies = [];

        foreach (['visitors' => 'Traffic', 'sessions' => 'Sessions'] as $metric => $label) {
            $anomaly = $this->checkMetric($client, $metric, $label);
            if ($anomaly) {
                $anomalies[] = $anomaly;
            }
        }

        return $anomalies;
    }

    protected function checkMetric(Client $client, string $metric, string $label): ?array
    {
        if (! $this->metrics->hasData($client->id, $metric)) {
            return null;
        }

        $today = Carbon::yesterday(); // yesterday = last fully-synced day, typically
        $current = $this->metrics->latestValue($client->id, $metric) ?? 0;
        $baseline = $this->metrics->averageBetween(
            $client->id, $metric,
            $today->copy()->subDays(7)->toDateString(),
            $today->copy()->subDay()->toDateString()
        ) ?? 0;

        $result = AnomalyMath::deviationCheck($current, $baseline, self::DROP_THRESHOLD, self::SPIKE_THRESHOLD);

        if (! $result['is_anomaly']) {
            return null;
        }

        $isDrop = $result['direction'] === 'drop';

        return [
            'type' => $isDrop ? 'traffic_drop' : 'traffic_spike',
            'severity' => $isDrop ? 'critical' : 'info',
            'metric' => $metric,
            'current_value' => $current,
            'baseline_value' => round($baseline, 2),
            'change_percent' => $result['change_percent'],
            'message' => $isDrop
                ? "{$label} dropped {$this->abs($result['change_percent'])}% vs. the 7-day average."
                : "{$label} spiked {$result['change_percent']}% above the 7-day average.",
            'possible_causes' => $isDrop ? [
                'A recent site change broke tracking (check the tag/pixel is still firing)',
                'A paid campaign paused or ran out of budget',
                'A ranking drop for key organic search terms',
                'Seasonality (holiday, weekend, industry slow period)',
            ] : [
                'A campaign launch, PR mention, or viral post',
                'Bot/spam traffic (check for unusual sources or bounce rate)',
            ],
            'recommended_fixes' => $isDrop ? [
                'Verify the tracking code is still installed and firing',
                'Check recent campaign status and budget pacing',
                'Compare against Search Console for a ranking change',
            ] : [
                'Identify the traffic source and consider doubling down if it converts well',
                'Check bounce rate and conversion rate to rule out bot traffic',
            ],
            'integration_id' => null,
        ];
    }

    protected function abs(float $value): float
    {
        return abs($value);
    }
}
