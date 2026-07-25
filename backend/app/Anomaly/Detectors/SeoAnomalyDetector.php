<?php

namespace App\Anomaly\Detectors;

use App\Anomaly\AnomalyMath;
use App\Anomaly\Contracts\AnomalyDetectorInterface;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/** Watches organic search health: CTR drop and average ranking position getting worse. */
class SeoAnomalyDetector implements AnomalyDetectorInterface
{
    protected const CTR_DROP_THRESHOLD = 25.0;
    protected const POSITION_WORSENING_THRESHOLD = 20.0; // position getting 20%+ higher (worse)

    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function detect(Client $client): array
    {
        $anomalies = [];
        $today = Carbon::yesterday();
        $from = $today->copy()->subDays(7)->toDateString();
        $to = $today->copy()->subDay()->toDateString();

        if ($this->metrics->hasData($client->id, 'ctr')) {
            $current = $this->metrics->latestValue($client->id, 'ctr') ?? 0;
            $baseline = $this->metrics->averageBetween($client->id, 'ctr', $from, $to) ?? 0;
            $result = AnomalyMath::deviationCheck($current, $baseline, self::CTR_DROP_THRESHOLD, 999);

            if ($result['is_anomaly'] && $result['direction'] === 'drop') {
                $anomalies[] = [
                    'type' => 'ctr_drop',
                    'severity' => 'warning',
                    'metric' => 'ctr',
                    'current_value' => $current,
                    'baseline_value' => round($baseline, 2),
                    'change_percent' => $result['change_percent'],
                    'message' => 'Search click-through rate dropped '.abs($result['change_percent']).'% vs. the 7-day average.',
                    'possible_causes' => [
                        'A competitor outranking you or taking a featured snippet',
                        'Title tags/meta descriptions became less compelling (or a recent change)',
                        'A ranking drop pushing you further down the results page',
                    ],
                    'recommended_fixes' => [
                        'Check Search Console for ranking changes on top queries',
                        'Refresh title tags and meta descriptions on affected pages',
                    ],
                    'integration_id' => null,
                ];
            }
        }

        if ($this->metrics->hasData($client->id, 'avg_position')) {
            $current = $this->metrics->latestValue($client->id, 'avg_position') ?? 0;
            $baseline = $this->metrics->averageBetween($client->id, 'avg_position', $from, $to) ?? 0;
            $result = AnomalyMath::increaseIsBadCheck($current, $baseline, self::POSITION_WORSENING_THRESHOLD);

            if ($result['is_anomaly']) {
                $anomalies[] = [
                    'type' => 'ranking_loss',
                    'severity' => 'warning',
                    'metric' => 'avg_position',
                    'current_value' => $current,
                    'baseline_value' => round($baseline, 2),
                    'change_percent' => $result['change_percent'],
                    'message' => 'Average search ranking position got worse by '.$result['change_percent'].'% vs. the 7-day average.',
                    'possible_causes' => [
                        'A Google algorithm update',
                        'New or stronger competing content',
                        'Technical SEO issue (broken pages, slow load times, lost backlinks)',
                    ],
                    'recommended_fixes' => [
                        'Check Search Console for specific pages/queries that lost position',
                        'Audit recently changed pages for technical SEO regressions',
                    ],
                    'integration_id' => null,
                ];
            }
        }

        return $anomalies;
    }
}
