<?php

namespace App\HealthScore\Calculators;

use App\HealthScore\Contracts\ScoreCalculatorInterface;
use App\HealthScore\ScoreMath;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/** Traffic + conversion momentum: this period vs the equivalent prior period. */
class GrowthScoreCalculator implements ScoreCalculatorInterface
{
    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function key(): string { return 'growth'; }
    public function label(): string { return 'Growth Score'; }
    public function weight(): float { return 0.15; }

    public function calculate(Client $client, int $lookbackDays = 30): array
    {
        if (! $this->metrics->hasData($client->id, 'visitors')) {
            return ['score' => null, 'signals' => [], 'suggestions' => []];
        }

        $now = Carbon::now();
        $currentFrom = $now->copy()->subDays($lookbackDays);
        $previousFrom = $now->copy()->subDays($lookbackDays * 2);
        $previousTo = $currentFrom->copy()->subDay();

        $currentVisitors = $this->metrics->sumBetween($client->id, 'visitors', $currentFrom->toDateString(), $now->toDateString());
        $previousVisitors = $this->metrics->sumBetween($client->id, 'visitors', $previousFrom->toDateString(), $previousTo->toDateString());

        $currentConversions = $this->metrics->sumBetween($client->id, 'conversions', $currentFrom->toDateString(), $now->toDateString());
        $previousConversions = $this->metrics->sumBetween($client->id, 'conversions', $previousFrom->toDateString(), $previousTo->toDateString());

        $visitorGrowth = ScoreMath::growthScore($currentVisitors, $previousVisitors);
        $conversionGrowth = ScoreMath::growthScore($currentConversions, $previousConversions);

        $score = (int) round(($visitorGrowth * 0.5) + ($conversionGrowth * 0.5));

        $suggestions = [];
        if ($visitorGrowth < 40) {
            $suggestions[] = 'Traffic is trending down vs. the prior period — review recent SEO/ranking changes and paid campaign pacing.';
        }
        if ($conversionGrowth < 40) {
            $suggestions[] = 'Conversion volume is declining — check for broken forms, tracking gaps, or a landing page regression.';
        }

        return [
            'score' => $score,
            'signals' => [
                'visitors_current' => $currentVisitors,
                'visitors_previous' => $previousVisitors,
                'conversions_current' => $currentConversions,
                'conversions_previous' => $previousConversions,
            ],
            'suggestions' => $suggestions,
        ];
    }
}
