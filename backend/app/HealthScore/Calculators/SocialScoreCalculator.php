<?php

namespace App\HealthScore\Calculators;

use App\HealthScore\Contracts\ScoreCalculatorInterface;
use App\HealthScore\ScoreMath;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/** Social reach momentum across connected platforms (Instagram, Facebook today; more as connectors ship). */
class SocialScoreCalculator implements ScoreCalculatorInterface
{
    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function key(): string { return 'social'; }
    public function label(): string { return 'Social Score'; }
    public function weight(): float { return 0.10; }

    public function calculate(Client $client, int $lookbackDays = 30): array
    {
        $hasData = $this->metrics->hasData($client->id, 'instagram_reach') || $this->metrics->hasData($client->id, 'facebook_reach');
        if (! $hasData) {
            return ['score' => null, 'signals' => [], 'suggestions' => []];
        }

        $now = Carbon::now();
        $currentFrom = $now->copy()->subDays($lookbackDays)->toDateString();
        $previousFrom = $now->copy()->subDays($lookbackDays * 2)->toDateString();
        $previousTo = $now->copy()->subDays($lookbackDays + 1)->toDateString();
        $to = $now->toDateString();

        $currentReach = $this->metrics->sumBetween($client->id, 'instagram_reach', $currentFrom, $to)
            + $this->metrics->sumBetween($client->id, 'facebook_reach', $currentFrom, $to);
        $previousReach = $this->metrics->sumBetween($client->id, 'instagram_reach', $previousFrom, $previousTo)
            + $this->metrics->sumBetween($client->id, 'facebook_reach', $previousFrom, $previousTo);

        $score = ScoreMath::growthScore($currentReach, $previousReach, swing: 30.0);

        $suggestions = [];
        if ($score < 40) {
            $suggestions[] = 'Social reach has dropped — increase posting cadence or test a short-form video or Reels-style format.';
        }

        return [
            'score' => $score,
            'signals' => ['reach_current' => $currentReach, 'reach_previous' => $previousReach],
            'suggestions' => $suggestions,
        ];
    }
}
