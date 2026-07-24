<?php

namespace App\AI;

use App\Models\Client;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use App\Repositories\Contracts\HealthScoreRepositoryInterface;
use App\Repositories\Contracts\IntegrationRepositoryInterface;
use Carbon\Carbon;

/**
 * Assembles the real data an answer must be grounded in before it reaches the
 * model: this is what keeps "Why did traffic drop?" answerable from actual
 * numbers instead of a plausible-sounding guess.
 */
class AIContextBuilder
{
    protected const CORE_METRICS = ['visitors', 'sessions', 'conversions', 'revenue', 'ad_spend', 'roas', 'leads'];

    public function __construct(
        protected AnalyticsMetricRepositoryInterface $metrics,
        protected HealthScoreRepositoryInterface $healthScores,
        protected IntegrationRepositoryInterface $integrations,
    ) {}

    public function buildSystemPrompt(Client $client): string
    {
        $from = Carbon::now()->subDays(30)->toDateString();
        $to = Carbon::now()->toDateString();

        $metricLines = [];
        foreach (self::CORE_METRICS as $metric) {
            if (! $this->metrics->hasData($client->id, $metric)) {
                continue;
            }
            $sum = $this->metrics->sumBetween($client->id, $metric, $from, $to);
            $metricLines[] = "- {$metric}: {$this->formatNumber($sum)} (last 30 days)";
        }

        $healthScore = $this->healthScores->latestForClient($client->id);
        $healthLine = $healthScore
            ? "Overall AI Health Score: {$healthScore->overall_score}/100 ({$healthScore->band()})."
            : 'No Health Score has been computed yet for this client.';

        $connectedProviders = $this->integrations->forClient($client->id)
            ->where('status', 'connected')
            ->pluck('provider')
            ->implode(', ') ?: 'none yet';

        $metricsBlock = $metricLines ? implode("\n", $metricLines) : 'No metrics data available yet — no integrations have synced data for this client.';

        return <<<PROMPT
        You are the AI marketing analyst inside Search29 Analytics AI, a marketing analytics
        platform for agencies. You are answering questions about ONE specific client's
        marketing performance. Base every claim strictly on the data provided below — if the
        data doesn't support an answer, say so plainly rather than guessing or inventing numbers.

        Client: {$client->name}
        Connected data sources: {$connectedProviders}
        {$healthLine}

        Key metrics (last 30 days, summed):
        {$metricsBlock}

        Guidelines:
        - Be concise and specific. Reference actual numbers from above when relevant.
        - If asked to compare periods, campaigns, or channels you don't have granular data for,
          say what data would be needed rather than fabricating a comparison.
        - When asked for recommendations, tie them back to the specific weak metric or category.
        - Keep responses focused and skimmable for a busy marketer — short paragraphs or bullets.
        PROMPT;
    }

    protected function formatNumber(float $value): string
    {
        return number_format($value, $value == floor($value) ? 0 : 2);
    }
}
