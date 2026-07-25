<?php

namespace App\Anomaly\Detectors;

use App\Anomaly\AnomalyMath;
use App\Anomaly\Contracts\AnomalyDetectorInterface;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/** Watches paid media cost efficiency: CPC/CPA spikes, and a ROAS collapse while spend continues (campaign failure). */
class AdsAnomalyDetector implements AnomalyDetectorInterface
{
    protected const COST_INCREASE_THRESHOLD = 40.0;
    protected const ROAS_FAILURE_THRESHOLD = 0.5; // ROAS below this while spend continues = campaign likely broken

    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function detect(Client $client): array
    {
        $anomalies = [];
        $today = Carbon::yesterday();
        $from = $today->copy()->subDays(7)->toDateString();
        $to = $today->copy()->subDay()->toDateString();

        foreach (['cpc' => ['CPC', 'high_cpc'], 'cpa' => ['CPA', 'high_cpa']] as $metric => [$label, $type]) {
            if (! $this->metrics->hasData($client->id, $metric)) {
                continue;
            }

            $current = $this->metrics->latestValue($client->id, $metric) ?? 0;
            $baseline = $this->metrics->averageBetween($client->id, $metric, $from, $to) ?? 0;
            $result = AnomalyMath::increaseIsBadCheck($current, $baseline, self::COST_INCREASE_THRESHOLD);

            if ($result['is_anomaly']) {
                $anomalies[] = [
                    'type' => $type,
                    'severity' => 'warning',
                    'metric' => $metric,
                    'current_value' => $current,
                    'baseline_value' => round($baseline, 2),
                    'change_percent' => $result['change_percent'],
                    'message' => "{$label} increased {$result['change_percent']}% vs. the 7-day average.",
                    'possible_causes' => [
                        'Increased auction competition for your keywords/audience',
                        'A lower Quality Score / relevance score on active ads',
                        'Budget or bid strategy change',
                    ],
                    'recommended_fixes' => [
                        'Review recent bid strategy or budget changes',
                        'Check ad relevance/quality scores and refresh underperforming creative',
                    ],
                    'integration_id' => null,
                ];
            }
        }

        if ($this->metrics->hasData($client->id, 'roas') && $this->metrics->hasData($client->id, 'ad_spend')) {
            $currentRoas = $this->metrics->latestValue($client->id, 'roas') ?? 0;
            $currentSpend = $this->metrics->latestValue($client->id, 'ad_spend') ?? 0;

            if ($currentSpend > 0 && $currentRoas < self::ROAS_FAILURE_THRESHOLD) {
                $anomalies[] = [
                    'type' => 'campaign_failure',
                    'severity' => 'critical',
                    'metric' => 'roas',
                    'current_value' => $currentRoas,
                    'baseline_value' => null,
                    'change_percent' => null,
                    'message' => "Ad spend continues (\${$currentSpend}) but ROAS has collapsed to {$currentRoas}x — campaigns may be broken or badly misconfigured.",
                    'possible_causes' => [
                        'A campaign is spending with broken conversion tracking',
                        'A landing page linked from ads is down or broken',
                        'Budget is going to an underperforming audience/keyword set',
                    ],
                    'recommended_fixes' => [
                        'Pause the affected campaign(s) until diagnosed',
                        'Verify the landing page loads correctly and tracking fires',
                    ],
                    'integration_id' => null,
                ];
            }
        }

        return $anomalies;
    }
}
