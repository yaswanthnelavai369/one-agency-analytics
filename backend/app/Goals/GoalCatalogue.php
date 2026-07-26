<?php

namespace App\Goals;

/**
 * Suggested goal templates matching the spec's examples (100 Leads, 5000
 * Visitors, ROAS 5, etc.). Each maps to a metric already flowing through
 * analytics_metrics where one exists, with the tracking_mode that makes
 * sense for that metric (cumulative for running totals, snapshot for rates).
 * 'Keyword count' has no numeric metric any connector populates yet, so it's
 * offered as a manual template rather than silently pretending to auto-track it.
 */
class GoalCatalogue
{
    protected static array $templates = [
        ['key' => 'leads', 'label' => '100 Leads', 'metric' => 'leads', 'tracking_mode' => 'cumulative', 'suggested_target' => 100, 'format' => 'number'],
        ['key' => 'visitors', 'label' => '5,000 Visitors', 'metric' => 'visitors', 'tracking_mode' => 'cumulative', 'suggested_target' => 5000, 'format' => 'number'],
        ['key' => 'sales', 'label' => '100 Sales', 'metric' => 'conversions', 'tracking_mode' => 'cumulative', 'suggested_target' => 100, 'format' => 'number'],
        ['key' => 'form_submissions', 'label' => '100 Form Submissions', 'metric' => 'leads', 'tracking_mode' => 'cumulative', 'suggested_target' => 100, 'format' => 'number'],
        ['key' => 'ctr', 'label' => '10% CTR', 'metric' => 'ctr', 'tracking_mode' => 'snapshot', 'suggested_target' => 10, 'format' => 'percent'],
        ['key' => 'roas', 'label' => 'ROAS 5', 'metric' => 'roas', 'tracking_mode' => 'snapshot', 'suggested_target' => 5, 'format' => 'number'],
        ['key' => 'google_reviews', 'label' => '100 Google Reviews', 'metric' => 'google_reviews', 'tracking_mode' => 'cumulative', 'suggested_target' => 100, 'format' => 'number'],
        ['key' => 'calls', 'label' => '500 Calls', 'metric' => 'google_calls', 'tracking_mode' => 'cumulative', 'suggested_target' => 500, 'format' => 'number'],
        ['key' => 'keywords', 'label' => '50 Keywords Ranking', 'metric' => null, 'tracking_mode' => 'manual', 'suggested_target' => 50, 'format' => 'number', 'note' => 'No connector reports a keyword-ranking count yet — track manually until one does.'],
        ['key' => 'custom', 'label' => 'Custom goal', 'metric' => null, 'tracking_mode' => 'manual', 'suggested_target' => null, 'format' => 'number'],
    ];

    public static function all(): array
    {
        return self::$templates;
    }

    public static function find(string $key): ?array
    {
        return collect(self::$templates)->firstWhere('key', $key);
    }
}
