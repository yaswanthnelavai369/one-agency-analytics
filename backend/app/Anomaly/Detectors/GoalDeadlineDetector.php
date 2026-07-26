<?php

namespace App\Anomaly\Detectors;

use App\Anomaly\Contracts\AnomalyDetectorInterface;
use App\Goals\GoalMath;
use App\Models\Client;
use App\Repositories\Contracts\GoalRepositoryInterface;

/**
 * Fulfills the spec's Goal Tracking "Deadline Alerts" by reusing the anomaly
 * pipeline rather than building a second, parallel alerting mechanism — a
 * goal at risk shows up in the same Alerts feed as a traffic drop or an API
 * failure, which is exactly where a marketer already looks for "things that
 * need my attention."
 */
class GoalDeadlineDetector implements AnomalyDetectorInterface
{
    protected const LOOKAHEAD_DAYS = 14;

    public function __construct(protected GoalRepositoryInterface $goals) {}

    public function detect(Client $client): array
    {
        $anomalies = [];

        $atRiskGoals = $this->goals->activeWithUpcomingDeadline(self::LOOKAHEAD_DAYS)
            ->where('client_id', $client->id);

        foreach ($atRiskGoals as $goal) {
            $forecast = GoalMath::forecast($goal->current_value, $goal->target_value, $goal->start_date, $goal->deadline);

            if ($forecast['pace_status'] !== 'behind') {
                continue;
            }

            $anomalies[] = [
                'type' => 'goal_at_risk',
                'severity' => $forecast['days_remaining'] <= 3 ? 'critical' : 'warning',
                'metric' => $goal->metric,
                'current_value' => $goal->current_value,
                'baseline_value' => $forecast['expected_progress'],
                'change_percent' => null,
                'message' => "\"{$goal->name}\" is behind pace with {$forecast['days_remaining']} day(s) left — ".
                    "{$goal->current_value} of {$goal->target_value} ({$forecast['achievement_rate']}%).",
                'possible_causes' => [
                    'The underlying metric has been flat or declining recently',
                    'The target may have been set too aggressively for the timeframe',
                ],
                'recommended_fixes' => [
                    'Review the linked metric\'s recent trend for a specific bottleneck',
                    'Consider extending the deadline or adjusting the target if circumstances changed',
                ],
                'integration_id' => null,
                'goal_id' => $goal->id,
            ];
        }

        return $anomalies;
    }
}
