<?php

namespace App\HealthScore\Contracts;

use App\Models\Client;

/**
 * Every scoring category (Growth, SEO, Ads, Social, Website, Lead, Revenue)
 * implements this. HealthScoreEngine iterates a registry of these — same
 * pluggable pattern as IntegrationManager — so adding a category (e.g. once
 * PageSpeed/Core Web Vitals data exists) is one new class + one registry line.
 */
interface ScoreCalculatorInterface
{
    /** Key used in health_scores' *_score columns and the frontend, e.g. "seo". */
    public function key(): string;

    /** Human label for the UI, e.g. "SEO Score". */
    public function label(): string;

    /** Weight (0–1) this category contributes to the overall score. Registry weights must sum to 1.0. */
    public function weight(): float;

    /**
     * Computes this category's 0–100 score for the client using the last $lookbackDays
     * of analytics_metrics. Returns null when there isn't enough data yet (that
     * category is then excluded and remaining weights are re-normalized).
     *
     * @return array{score: ?int, signals: array<string, mixed>, suggestions: string[]}
     */
    public function calculate(Client $client, int $lookbackDays = 30): array;
}
