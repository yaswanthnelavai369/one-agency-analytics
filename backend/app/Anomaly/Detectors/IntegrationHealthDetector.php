<?php

namespace App\Anomaly\Detectors;

use App\Anomaly\Contracts\AnomalyDetectorInterface;
use App\Models\Client;
use App\Repositories\Contracts\IntegrationRepositoryInterface;
use Carbon\Carbon;

/**
 * Unlike the other detectors, this one doesn't look at analytics_metrics —
 * it looks at the Integration records themselves, covering the spec's
 * "API Failure" and "Missing Tracking Codes" anomaly types.
 */
class IntegrationHealthDetector implements AnomalyDetectorInterface
{
    protected const STALE_SYNC_HOURS = 48;

    public function __construct(protected IntegrationRepositoryInterface $integrations) {}

    public function detect(Client $client): array
    {
        $anomalies = [];

        foreach ($this->integrations->forClient($client->id) as $integration) {
            if ($integration->status === 'error') {
                $anomalies[] = [
                    'type' => 'api_failure',
                    'severity' => 'critical',
                    'metric' => null,
                    'current_value' => null,
                    'baseline_value' => null,
                    'change_percent' => null,
                    'message' => "{$integration->display_name} ({$integration->provider}) sync is failing: ".
                        ($integration->last_error ?: 'unknown error'),
                    'possible_causes' => [
                        'The OAuth token was revoked or expired without a valid refresh token',
                        "The connected account lost access to the {$integration->provider} resource",
                        'A change on the provider\'s side (API deprecation, permission change)',
                    ],
                    'recommended_fixes' => [
                        'Reconnect the integration from the Integrations page',
                        'Confirm the connected account still has access on the provider\'s side',
                    ],
                    'integration_id' => $integration->id,
                ];

                continue;
            }

            if ($integration->status !== 'connected') {
                continue;
            }

            $staleSince = $integration->last_synced_at
                ? $integration->last_synced_at
                : $integration->connected_at;

            if ($staleSince && Carbon::parse($staleSince)->lt(now()->subHours(self::STALE_SYNC_HOURS))) {
                $anomalies[] = [
                    'type' => 'missing_tracking_codes',
                    'severity' => 'warning',
                    'metric' => null,
                    'current_value' => null,
                    'baseline_value' => null,
                    'change_percent' => null,
                    'message' => "{$integration->display_name} ({$integration->provider}) hasn't synced new data in over ".self::STALE_SYNC_HOURS.' hours.',
                    'possible_causes' => [
                        'Tracking code/pixel was removed from the site',
                        'The scheduled sync job is not running',
                        'The connected property/account has no recent activity',
                    ],
                    'recommended_fixes' => [
                        'Verify the tracking code is still present on the site',
                        'Try a manual "Sync now" from the Integrations page',
                    ],
                    'integration_id' => $integration->id,
                ];
            }
        }

        return $anomalies;
    }
}
