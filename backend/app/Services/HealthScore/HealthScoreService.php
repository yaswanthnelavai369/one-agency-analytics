<?php

namespace App\Services\HealthScore;

use App\HealthScore\HealthScoreEngine;
use App\Models\Client;
use App\Models\HealthScore;
use App\Repositories\Contracts\HealthScoreRepositoryInterface;
use Carbon\Carbon;

class HealthScoreService
{
    public function __construct(
        protected HealthScoreEngine $engine,
        protected HealthScoreRepositoryInterface $repository,
    ) {}

    /** Computes today's score from current analytics_metrics and stores/overwrites today's row (idempotent re-run). */
    public function computeAndStore(Client $client): HealthScore
    {
        $result = $this->engine->compute($client);

        return $this->repository->upsertForDate($client->id, Carbon::today()->toDateString(), [
            'overall_score' => $result['overall_score'],
            'growth_score' => $result['categories']['growth']['score'] ?? null,
            'seo_score' => $result['categories']['seo']['score'] ?? null,
            'ads_score' => $result['categories']['ads']['score'] ?? null,
            'social_score' => $result['categories']['social']['score'] ?? null,
            'website_score' => $result['categories']['website']['score'] ?? null,
            'lead_score' => $result['categories']['lead']['score'] ?? null,
            'revenue_score' => $result['categories']['revenue']['score'] ?? null,
            'breakdown' => [
                'categories' => $result['categories'],
                'suggestions' => $result['suggestions'],
            ],
        ]);
    }

    public function latest(Client $client): ?HealthScore
    {
        return $this->repository->latestForClient($client->id);
    }

    /** @return array{history: \Illuminate\Support\Collection, previous_overall: ?int} */
    public function historyWithComparison(Client $client, int $days = 90): array
    {
        $from = Carbon::now()->subDays($days)->toDateString();
        $to = Carbon::now()->toDateString();

        $history = $this->repository->historyForClient($client->id, $from, $to);

        // "30 days ago" score, for the historical-comparison figure on the Health Score page.
        $comparisonDate = Carbon::now()->subDays(30)->toDateString();
        $previous = $history->firstWhere('date', $comparisonDate) ?? $history->first();

        return [
            'history' => $history,
            'previous_overall' => $previous?->overall_score,
        ];
    }
}
