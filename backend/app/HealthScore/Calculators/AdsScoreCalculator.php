<?php

namespace App\HealthScore\Calculators;

use App\HealthScore\Contracts\ScoreCalculatorInterface;
use App\HealthScore\ScoreMath;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/** Paid media efficiency: return on ad spend and cost per acquisition. */
class AdsScoreCalculator implements ScoreCalculatorInterface
{
    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function key(): string { return 'ads'; }
    public function label(): string { return 'Ads Score'; }
    public function weight(): float { return 0.15; }

    public function calculate(Client $client, int $lookbackDays = 30): array
    {
        if (! $this->metrics->hasData($client->id, 'roas') && ! $this->metrics->hasData($client->id, 'ad_spend')) {
            return ['score' => null, 'signals' => [], 'suggestions' => []];
        }

        $from = Carbon::now()->subDays($lookbackDays)->toDateString();
        $to = Carbon::now()->toDateString();

        $roas = $this->metrics->averageBetween($client->id, 'roas', $from, $to);
        $cpa = $this->metrics->averageBetween($client->id, 'cpa', $from, $to);

        // ROAS: 1x (break-even) -> ~0, 6x+ -> 100
        $roasScore = $roas !== null ? ScoreMath::linear($roas, 1, 6) : null;
        // CPA: cheaper is better. $0 -> 100, $200+ -> 0 (a generic ceiling; agencies can tune per-client benchmarks later)
        $cpaScore = $cpa !== null ? ScoreMath::inverseLinear($cpa, 0, 200) : null;

        $score = ScoreMath::weightedAverage(array_filter([
            $roasScore !== null ? ['score' => $roasScore, 'weight' => 0.6] : null,
            $cpaScore !== null ? ['score' => $cpaScore, 'weight' => 0.4] : null,
        ]));

        $suggestions = [];
        if ($roasScore !== null && $roasScore < 40) {
            $suggestions[] = 'Return on ad spend is below a healthy threshold — pause underperforming campaigns and reallocate budget to top performers.';
        }
        if ($cpaScore !== null && $cpaScore < 40) {
            $suggestions[] = 'Cost per acquisition is high — tighten audience targeting or improve landing page conversion rate.';
        }

        return [
            'score' => $score,
            'signals' => ['roas' => $roas, 'cpa' => $cpa],
            'suggestions' => $suggestions,
        ];
    }
}
