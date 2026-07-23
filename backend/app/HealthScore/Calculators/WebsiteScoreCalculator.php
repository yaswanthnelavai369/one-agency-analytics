<?php

namespace App\HealthScore\Calculators;

use App\HealthScore\Contracts\ScoreCalculatorInterface;
use App\HealthScore\ScoreMath;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/**
 * On-site experience: bounce rate and average session duration today.
 * Core Web Vitals / PageSpeed will fold in here once that connector ships —
 * this calculator is written to accept additional signals without breaking
 * the weighting (see the TODO below).
 */
class WebsiteScoreCalculator implements ScoreCalculatorInterface
{
    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function key(): string { return 'website'; }
    public function label(): string { return 'Website Score'; }
    public function weight(): float { return 0.15; }

    public function calculate(Client $client, int $lookbackDays = 30): array
    {
        if (! $this->metrics->hasData($client->id, 'bounce_rate') && ! $this->metrics->hasData($client->id, 'avg_session_duration')) {
            return ['score' => null, 'signals' => [], 'suggestions' => []];
        }

        $from = Carbon::now()->subDays($lookbackDays)->toDateString();
        $to = Carbon::now()->toDateString();

        $bounceRate = $this->metrics->averageBetween($client->id, 'bounce_rate', $from, $to); // percent
        $sessionDuration = $this->metrics->averageBetween($client->id, 'avg_session_duration', $from, $to); // minutes

        // Bounce rate: 20% -> 100 (great), 90%+ -> 0
        $bounceScore = $bounceRate !== null ? ScoreMath::inverseLinear($bounceRate, 20, 90) : null;
        // Session duration: 0 min -> 0, 5+ min -> 100
        $durationScore = $sessionDuration !== null ? ScoreMath::linear($sessionDuration, 0, 5) : null;

        // TODO: once a PageSpeed/Core Web Vitals connector exists, add a third signal here
        // (e.g. ['score' => $cwvScore, 'weight' => 0.34]) and rebalance the two below to 0.33 each.
        $score = ScoreMath::weightedAverage(array_filter([
            $bounceScore !== null ? ['score' => $bounceScore, 'weight' => 0.6] : null,
            $durationScore !== null ? ['score' => $durationScore, 'weight' => 0.4] : null,
        ]));

        $suggestions = [];
        if ($bounceScore !== null && $bounceScore < 40) {
            $suggestions[] = 'Bounce rate is high — check page load speed and whether landing pages match ad/search intent.';
        }

        return [
            'score' => $score,
            'signals' => ['bounce_rate' => $bounceRate, 'avg_session_duration' => $sessionDuration],
            'suggestions' => $suggestions,
        ];
    }
}
