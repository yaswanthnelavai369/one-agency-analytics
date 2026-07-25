<?php

namespace App\Anomaly\Contracts;

use App\Models\Client;

/**
 * Every anomaly detector (traffic, conversions, revenue, SEO, ads, integration
 * health) implements this. AnomalyEngine iterates a registry of these — same
 * pluggable pattern as IntegrationManager / HealthScoreEngine / AIProviderManager.
 * Adding a new detector (e.g. once PageSpeed data exists, a "pagespeed_drop"
 * detector) is one new class + one registry line.
 */
interface AnomalyDetectorInterface
{
    /**
     * Runs this detector's checks for the client and returns any anomalies found.
     *
     * @return array<array{
     *   type: string, severity: string, metric: ?string,
     *   current_value: ?float, baseline_value: ?float, change_percent: ?float,
     *   message: string, possible_causes: string[], recommended_fixes: string[],
     *   integration_id: ?int
     * }>
     */
    public function detect(Client $client): array;
}
