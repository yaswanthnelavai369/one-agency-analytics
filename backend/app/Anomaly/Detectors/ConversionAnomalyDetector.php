<?php

namespace App\Anomaly\Detectors;

use App\Anomaly\AnomalyMath;
use App\Anomaly\Contracts\AnomalyDetectorInterface;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/** Watches conversions/leads for drops — usually the highest-signal anomaly for a marketer. */
class ConversionAnomalyDetector implements AnomalyDetectorInterface
{
    protected const DROP_THRESHOLD = 35.0;

    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function detect(Client $client): array
    {
        $anomalies = [];

        foreach (['conversions' => 'Conversions', 'leads' => 'Leads'] as $metric => $label) {
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

        $today = Carbon::yesterday();
        $current = $this->metrics->latestValue($client->id, $metric) ?? 0;
        $baseline = $this->metrics->averageBetween(
            $client->id, $metric,
            $today->copy()->subDays(7)->toDateString(),
            $today->copy()->subDay()->toDateString()
        ) ?? 0;

        $result = AnomalyMath::deviationCheck($current, $baseline, self::DROP_THRESHOLD, 999);

        if (! $result['is_anomaly'] || $result['direction'] !== 'drop') {
            return null;
        }

        return [
            'type' => 'conversion_drop',
            'severity' => 'critical',
            'metric' => $metric,
            'current_value' => $current,
            'baseline_value' => round($baseline, 2),
            'change_percent' => $result['change_percent'],
            'message' => "{$label} dropped ".abs($result['change_percent'])."% vs. the 7-day average.",
            'possible_causes' => [
                'A broken form, checkout flow, or booking widget',
                'Conversion tracking/pixel stopped firing',
                'A traffic drop feeding through to fewer conversions',
                'A pricing, offer, or landing page change hurt conversion rate',
            ],
            'recommended_fixes' => [
                'Manually test the conversion flow end-to-end right now',
                'Check tracking/pixel status in the relevant integration',
                'Cross-check against the Traffic anomaly — is this a downstream effect?',
            ],
            'integration_id' => null,
        ];
    }
}
