<?php

namespace App\HealthScore\Calculators;

use App\HealthScore\Contracts\ScoreCalculatorInterface;
use App\HealthScore\ScoreMath;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/** Organic visibility: search traffic level, click-through rate, and average ranking position. */
class SeoScoreCalculator implements ScoreCalculatorInterface
{
    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function key(): string { return 'seo'; }
    public function label(): string { return 'SEO Score'; }
    public function weight(): float { return 0.15; }

    public function calculate(Client $client, int $lookbackDays = 30): array
    {
        if (! $this->metrics->hasData($client->id, 'organic_traffic') && ! $this->metrics->hasData($client->id, 'avg_position')) {
            return ['score' => null, 'signals' => [], 'suggestions' => []];
        }

        $from = Carbon::now()->subDays($lookbackDays)->toDateString();
        $to = Carbon::now()->toDateString();

        $ctr = $this->metrics->averageBetween($client->id, 'ctr', $from, $to); // percent, e.g. 3.2
        $avgPosition = $this->metrics->averageBetween($client->id, 'avg_position', $from, $to); // 1 = best

        // CTR: 0% -> 0, 8%+ average CTR -> 100 (a strong result for most industries)
        $ctrScore = $ctr !== null ? ScoreMath::linear($ctr, 0, 8) : null;
        // Position: position 1 -> 100, position 30+ -> 0 (lower is better)
        $positionScore = $avgPosition !== null ? ScoreMath::inverseLinear($avgPosition, 1, 30) : null;

        $score = ScoreMath::weightedAverage(array_filter([
            $ctrScore !== null ? ['score' => $ctrScore, 'weight' => 0.5] : null,
            $positionScore !== null ? ['score' => $positionScore, 'weight' => 0.5] : null,
        ]));

        $suggestions = [];
        if ($positionScore !== null && $positionScore < 50) {
            $suggestions[] = 'Average ranking position is weak — prioritize on-page SEO and backlink building for your top target keywords.';
        }
        if ($ctrScore !== null && $ctrScore < 50) {
            $suggestions[] = 'Search click-through rate is below benchmark — test more compelling title tags and meta descriptions.';
        }

        return [
            'score' => $score,
            'signals' => ['ctr' => $ctr, 'avg_position' => $avgPosition],
            'suggestions' => $suggestions,
        ];
    }
}
