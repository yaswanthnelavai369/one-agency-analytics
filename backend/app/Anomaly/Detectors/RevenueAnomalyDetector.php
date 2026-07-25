<?php

namespace App\Anomaly\Detectors;

use App\Anomaly\AnomalyMath;
use App\Anomaly\Contracts\AnomalyDetectorInterface;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

class RevenueAnomalyDetector implements AnomalyDetectorInterface
{
    protected const DROP_THRESHOLD = 30.0;

    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function detect(Client $client): array
    {
        if (! $this->metrics->hasData($client->id, 'revenue')) {
            return [];
        }

        $today = Carbon::yesterday();
        $current = $this->metrics->latestValue($client->id, 'revenue') ?? 0;
        $baseline = $this->metrics->averageBetween(
            $client->id, 'revenue',
            $today->copy()->subDays(7)->toDateString(),
            $today->copy()->subDay()->toDateString()
        ) ?? 0;

        $result = AnomalyMath::deviationCheck($current, $baseline, self::DROP_THRESHOLD, 999);

        if (! $result['is_anomaly'] || $result['direction'] !== 'drop') {
            return [];
        }

        return [[
            'type' => 'revenue_loss',
            'severity' => 'critical',
            'metric' => 'revenue',
            'current_value' => $current,
            'baseline_value' => round($baseline, 2),
            'change_percent' => $result['change_percent'],
            'message' => 'Revenue dropped '.abs($result['change_percent']).'% vs. the 7-day average.',
            'possible_causes' => [
                'A checkout or payment processing issue',
                'Fewer conversions (check the Conversions anomaly)',
                'A drop in average order value (discount, product mix shift)',
            ],
            'recommended_fixes' => [
                'Test the checkout/payment flow immediately',
                'Check payment processor status page for outages',
                'Compare against the Conversions anomaly to isolate volume vs. value',
            ],
            'integration_id' => null,
        ]];
    }
}
