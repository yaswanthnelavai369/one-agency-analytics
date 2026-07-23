<?php

namespace App\HealthScore\Calculators;

use App\HealthScore\Contracts\ScoreCalculatorInterface;
use App\HealthScore\ScoreMath;
use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use Carbon\Carbon;

/** Lead generation momentum: form submissions/leads this period vs. the prior period. */
class LeadScoreCalculator implements ScoreCalculatorInterface
{
    public function __construct(protected AnalyticsMetricRepositoryInterface $metrics) {}

    public function key(): string { return 'lead'; }
    public function label(): string { return 'Lead Score'; }
    public function weight(): float { return 0.15; }

    public function calculate(Client $client, int $lookbackDays = 30): array
    {
        if (! $this->metrics->hasData($client->id, 'leads')) {
            return ['score' => null, 'signals' => [], 'suggestions' => []];
        }

        $now = Carbon::now();
        $currentFrom = $now->copy()->subDays($lookbackDays)->toDateString();
        $previousFrom = $now->copy()->subDays($lookbackDays * 2)->toDateString();
        $previousTo = $now->copy()->subDays($lookbackDays + 1)->toDateString();
        $to = $now->toDateString();

        $currentLeads = $this->metrics->sumBetween($client->id, 'leads', $currentFrom, $to);
        $previousLeads = $this->metrics->sumBetween($client->id, 'leads', $previousFrom, $previousTo);

        $score = ScoreMath::growthScore($currentLeads, $previousLeads, swing: 25.0);

        $suggestions = [];
        if ($score < 40) {
            $suggestions[] = 'Lead volume is down — audit your lead forms for friction and confirm tracking is still firing correctly.';
        }

        return [
            'score' => $score,
            'signals' => ['leads_current' => $currentLeads, 'leads_previous' => $previousLeads],
            'suggestions' => $suggestions,
        ];
    }
}
