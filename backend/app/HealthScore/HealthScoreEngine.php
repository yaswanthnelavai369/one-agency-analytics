<?php

namespace App\HealthScore;

use App\HealthScore\Calculators\AdsScoreCalculator;
use App\HealthScore\Calculators\GrowthScoreCalculator;
use App\HealthScore\Calculators\LeadScoreCalculator;
use App\HealthScore\Calculators\RevenueScoreCalculator;
use App\HealthScore\Calculators\SeoScoreCalculator;
use App\HealthScore\Calculators\SocialScoreCalculator;
use App\HealthScore\Calculators\WebsiteScoreCalculator;
use App\HealthScore\Contracts\ScoreCalculatorInterface;
use App\Models\Client;

/**
 * Registry + orchestrator for every scoring category. Adding a category is one
 * class (implementing ScoreCalculatorInterface) + one line in $calculatorClasses
 * — same pluggable pattern as IntegrationManager for connectors.
 *
 * Weights across all registered calculators should sum to 1.0; ScoreMath::weightedAverage
 * re-normalizes automatically when a category returns null (not enough data yet), so an
 * agency with only GA4 connected still gets a meaningful overall score from what it has.
 */
class HealthScoreEngine
{
    /** @var class-string<ScoreCalculatorInterface>[] */
    protected array $calculatorClasses = [
        GrowthScoreCalculator::class,
        SeoScoreCalculator::class,
        AdsScoreCalculator::class,
        SocialScoreCalculator::class,
        WebsiteScoreCalculator::class,
        LeadScoreCalculator::class,
        RevenueScoreCalculator::class,
    ];

    /** @return ScoreCalculatorInterface[] */
    public function calculators(): array
    {
        return array_map(fn ($class) => app($class), $this->calculatorClasses);
    }

    /**
     * @return array{
     *   overall_score: int,
     *   categories: array<string, array{score: ?int, label: string, signals: array, suggestions: string[]}>,
     *   suggestions: string[]
     * }
     */
    public function compute(Client $client, int $lookbackDays = 30): array
    {
        $categories = [];
        $weighted = [];
        $suggestions = [];

        foreach ($this->calculators() as $calculator) {
            $result = $calculator->calculate($client, $lookbackDays);

            $categories[$calculator->key()] = [
                'score' => $result['score'],
                'label' => $calculator->label(),
                'signals' => $result['signals'],
                'suggestions' => $result['suggestions'],
            ];

            $weighted[] = ['score' => $result['score'], 'weight' => $calculator->weight()];
            $suggestions = array_merge($suggestions, $result['suggestions']);
        }

        $overall = ScoreMath::weightedAverage($weighted) ?? 50; // 50 = neutral "no data yet" baseline

        return [
            'overall_score' => $overall,
            'categories' => $categories,
            'suggestions' => array_values(array_unique($suggestions)),
        ];
    }
}
