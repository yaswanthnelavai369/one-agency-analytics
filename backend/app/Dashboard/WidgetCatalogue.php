<?php

namespace App\Dashboard;

/**
 * Registry of every widget type the dashboard builder can place. Adding a
 * new widget is one array entry here (plus, for a KPI-style widget, a
 * matching 'metric' key that SyncIntegrationDataJob/providers write into
 * analytics_metrics) — no schema change needed, since dashboard_widgets
 * stores widget_type as a plain string.
 *
 * 'kind' tells the frontend which renderer to use: 'kpi' -> KpiCard,
 * 'list' -> a ranked table (top pages, top campaigns, ...), 'chart' -> a
 * time-series chart, 'health_score' -> the AI Health Score gauge.
 */
class WidgetCatalogue
{
    public const DEFAULT_SIZE = ['w' => 4, 'h' => 2];

    protected static array $widgets = [
        // --- Traffic & engagement ---
        'kpi_visitors' => ['label' => 'Visitors', 'kind' => 'kpi', 'metric' => 'visitors', 'category' => 'traffic', 'format' => 'number'],
        'kpi_sessions' => ['label' => 'Sessions', 'kind' => 'kpi', 'metric' => 'sessions', 'category' => 'traffic', 'format' => 'number'],
        'kpi_users' => ['label' => 'Users', 'kind' => 'kpi', 'metric' => 'visitors', 'category' => 'traffic', 'format' => 'number'],
        'kpi_bounce_rate' => ['label' => 'Bounce Rate', 'kind' => 'kpi', 'metric' => 'bounce_rate', 'category' => 'traffic', 'format' => 'percent'],
        'kpi_avg_session_duration' => ['label' => 'Avg. Session Duration', 'kind' => 'kpi', 'metric' => 'avg_session_duration', 'category' => 'traffic', 'format' => 'number'],
        'kpi_organic_traffic' => ['label' => 'Organic Traffic', 'kind' => 'kpi', 'metric' => 'organic_traffic', 'category' => 'traffic', 'format' => 'number'],
        'kpi_paid_traffic' => ['label' => 'Paid Traffic', 'kind' => 'kpi', 'metric' => 'paid_traffic', 'category' => 'traffic', 'format' => 'number'],
        'realtime_visitors' => ['label' => 'Realtime Visitors', 'kind' => 'kpi', 'metric' => 'realtime_visitors', 'category' => 'traffic', 'format' => 'number'],

        // --- Conversions & revenue ---
        'kpi_conversions' => ['label' => 'Conversions', 'kind' => 'kpi', 'metric' => 'conversions', 'category' => 'conversions', 'format' => 'number'],
        'kpi_revenue' => ['label' => 'Revenue', 'kind' => 'kpi', 'metric' => 'revenue', 'category' => 'conversions', 'format' => 'currency'],
        'kpi_leads' => ['label' => 'Leads', 'kind' => 'kpi', 'metric' => 'leads', 'category' => 'conversions', 'format' => 'number'],

        // --- Search & SEO ---
        'kpi_clicks' => ['label' => 'Clicks', 'kind' => 'kpi', 'metric' => 'clicks', 'category' => 'seo', 'format' => 'number'],
        'kpi_impressions' => ['label' => 'Impressions', 'kind' => 'kpi', 'metric' => 'impressions', 'category' => 'seo', 'format' => 'number'],
        'kpi_ctr' => ['label' => 'CTR', 'kind' => 'kpi', 'metric' => 'ctr', 'category' => 'seo', 'format' => 'percent'],
        'kpi_avg_position' => ['label' => 'Average Position', 'kind' => 'kpi', 'metric' => 'avg_position', 'category' => 'seo', 'format' => 'number'],
        'top_keywords' => ['label' => 'Keywords', 'kind' => 'list', 'metric' => 'keywords', 'category' => 'seo'],

        // --- Ads ---
        'kpi_ad_spend' => ['label' => 'Ad Spend', 'kind' => 'kpi', 'metric' => 'ad_spend', 'category' => 'ads', 'format' => 'currency'],
        'kpi_roas' => ['label' => 'ROAS', 'kind' => 'kpi', 'metric' => 'roas', 'category' => 'ads', 'format' => 'number'],
        'kpi_cpa' => ['label' => 'CPA', 'kind' => 'kpi', 'metric' => 'cpa', 'category' => 'ads', 'format' => 'currency'],
        'kpi_cpc' => ['label' => 'CPC', 'kind' => 'kpi', 'metric' => 'cpc', 'category' => 'ads', 'format' => 'currency'],
        'kpi_cpm' => ['label' => 'CPM', 'kind' => 'kpi', 'metric' => 'cpm', 'category' => 'ads', 'format' => 'currency'],
        'top_campaigns' => ['label' => 'Top Campaigns', 'kind' => 'list', 'metric' => 'campaigns', 'category' => 'ads'],
        'top_ads' => ['label' => 'Top Ads', 'kind' => 'list', 'metric' => 'ads', 'category' => 'ads'],

        // --- Local / Google Business ---
        'kpi_google_reviews' => ['label' => 'Google Reviews', 'kind' => 'kpi', 'metric' => 'google_reviews', 'category' => 'local', 'format' => 'number'],
        'kpi_google_calls' => ['label' => 'Google Calls', 'kind' => 'kpi', 'metric' => 'google_calls', 'category' => 'local', 'format' => 'number'],
        'kpi_direction_requests' => ['label' => 'Direction Requests', 'kind' => 'kpi', 'metric' => 'direction_requests', 'category' => 'local', 'format' => 'number'],
        'kpi_maps_views' => ['label' => 'Maps Views', 'kind' => 'kpi', 'metric' => 'maps_views', 'category' => 'local', 'format' => 'number'],

        // --- Social ---
        'kpi_instagram_reach' => ['label' => 'Instagram Reach', 'kind' => 'kpi', 'metric' => 'instagram_reach', 'category' => 'social', 'format' => 'number'],
        'kpi_facebook_reach' => ['label' => 'Facebook Reach', 'kind' => 'kpi', 'metric' => 'facebook_reach', 'category' => 'social', 'format' => 'number'],

        // --- Audience breakdowns ---
        'top_landing_pages' => ['label' => 'Top Landing Pages', 'kind' => 'list', 'metric' => 'landing_pages', 'category' => 'audience'],
        'top_countries' => ['label' => 'Top Countries', 'kind' => 'list', 'metric' => 'countries', 'category' => 'audience'],
        'top_cities' => ['label' => 'Top Cities', 'kind' => 'list', 'metric' => 'cities', 'category' => 'audience'],
        'top_devices' => ['label' => 'Top Devices', 'kind' => 'list', 'metric' => 'devices', 'category' => 'audience'],
        'top_browsers' => ['label' => 'Top Browsers', 'kind' => 'list', 'metric' => 'browsers', 'category' => 'audience'],

        // --- Health / status widgets ---
        'health_score' => ['label' => 'AI Health Score', 'kind' => 'health_score', 'metric' => null, 'category' => 'health', 'size' => ['w' => 4, 'h' => 3]],
        'website_health' => ['label' => 'Website Health', 'kind' => 'health_score', 'metric' => null, 'category' => 'health'],
        'seo_health' => ['label' => 'SEO Health', 'kind' => 'health_score', 'metric' => null, 'category' => 'health'],
        'performance_health' => ['label' => 'Performance Health', 'kind' => 'health_score', 'metric' => null, 'category' => 'health'],

        // --- Activity ---
        'recent_notifications' => ['label' => 'Recent Notifications', 'kind' => 'list', 'metric' => null, 'category' => 'activity'],
        'recent_activity' => ['label' => 'Recent Activity', 'kind' => 'list', 'metric' => null, 'category' => 'activity'],
        'tasks' => ['label' => 'Tasks', 'kind' => 'list', 'metric' => null, 'category' => 'activity'],
        'goals' => ['label' => 'Goals', 'kind' => 'list', 'metric' => null, 'category' => 'activity'],
        'alerts' => ['label' => 'Alerts', 'kind' => 'list', 'metric' => null, 'category' => 'activity'],
    ];

    public static function all(): array
    {
        return self::$widgets;
    }

    public static function get(string $type): ?array
    {
        return self::$widgets[$type] ?? null;
    }

    public static function exists(string $type): bool
    {
        return isset(self::$widgets[$type]);
    }

    public static function defaultSize(string $type): array
    {
        return self::$widgets[$type]['size'] ?? self::DEFAULT_SIZE;
    }

    /** Catalogue payload for the frontend's "add widget" picker, grouped by category. */
    public static function forPicker(): array
    {
        return collect(self::$widgets)
            ->map(fn ($def, $type) => array_merge(['type' => $type], $def))
            ->groupBy('category')
            ->map(fn ($group) => $group->values())
            ->all();
    }

    /** A sensible starting layout for a brand-new client dashboard. */
    public static function defaultWidgetTypes(): array
    {
        return [
            'kpi_visitors', 'kpi_sessions', 'kpi_conversions',
            'kpi_revenue', 'kpi_ad_spend', 'kpi_roas',
            'health_score', 'top_landing_pages', 'recent_activity',
        ];
    }
}
