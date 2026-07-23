<?php

namespace App\HealthScore\Calculators;

use App\HealthScore\Contracts\ScoreCalculatorInterface;
use App\HealthScore\ScoreMath;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/** Revenue momentum: this period vs. the prior period. */
class RevenueScoreCalculator implements ScoreCalculatorInterface
{
    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function key(): string { return 'revenue'; }
    public function label(): string { return 'Revenue Score'; }
    public function weight(): float { return 0.15; }

    public function calculate(Client $client, int $lookbackDays = 30): array
    {
        if (! $this->metrics->hasData($client->id, 'revenue')) {
            return ['score' => null, 'signals' => [], 'suggestions' => []];
        }

        $now = Carbon::now();
        $currentFrom = $now->copy()->subDays($lookbackDays)->toDateString();
        $previousFrom = $now->copy()->subDays($lookbackDays * 2)->toDateString();
        $previousTo = $now->copy()->subDays($lookbackDays + 1)->toDateString();
        $to = $now->toDateString();

        $currentRevenue = $this->metrics->sumBetween($client->id, 'revenue', $currentFrom, $to);
        $previousRevenue = $this->metrics->sumBetween($client->id, 'revenue', $previousFrom, $previousTo);

        $score = ScoreMath::growthScore($currentRevenue, $previousRevenue, swing: 20.0);

        $suggestions = [];
        if ($score < 40) {
            $suggestions[] = 'Revenue is trending down — cross-check against the Ads and Growth scores to see if it traces back to traffic or conversion rate.';
        }

        return [
            'score' => $score,
            'signals' => ['revenue_current' => $currentRevenue, 'revenue_previous' => $previousRevenue],
            'suggestions' => $suggestions,
        ];
    }
}
