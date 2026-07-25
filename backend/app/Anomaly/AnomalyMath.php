<?php

namespace App\Anomaly;

/**
 * Simple, transparent baseline-deviation math shared by every detector —
 * same "readable formula, not a black box" philosophy as HealthScore\ScoreMath.
 *
 * Method: compare today's value against the trailing N-day average (excluding
 * today), and flag when the deviation exceeds a threshold. This is a plain
 * percent-deviation check rather than a full statistical model (e.g. seasonal
 * decomposition) — good enough to catch obvious drops/spikes without needing
 * months of history to calibrate, which fits a platform where many clients
 * will have only weeks of connected data.
 */
class AnomalyMath
{
    /**
     * @return array{is_anomaly: bool, change_percent: ?float, direction: ?string}
     *   direction is 'drop' or 'spike' when is_anomaly is true.
     */
    public static function deviationCheck(
        float $current,
        float $baseline,
        float $dropThresholdPercent,
        float $spikeThresholdPercent
    ): array {
        if ($baseline <= 0) {
            return ['is_anomaly' => false, 'change_percent' => null, 'direction' => null];
        }

        $changePercent = (($current - $baseline) / $baseline) * 100;

        if ($changePercent <= -$dropThresholdPercent) {
            return ['is_anomaly' => true, 'change_percent' => round($changePercent, 2), 'direction' => 'drop'];
        }

        if ($changePercent >= $spikeThresholdPercent) {
            return ['is_anomaly' => true, 'change_percent' => round($changePercent, 2), 'direction' => 'spike'];
        }

        return ['is_anomaly' => false, 'change_percent' => round($changePercent, 2), 'direction' => null];
    }

    /** Same idea, but for metrics where an INCREASE is the bad direction (e.g. CPC, CPA, avg_position). */
    public static function increaseIsBadCheck(float $current, float $baseline, float $increaseThresholdPercent): array
    {
        if ($baseline <= 0) {
            return ['is_anomaly' => false, 'change_percent' => null];
        }

        $changePercent = (($current - $baseline) / $baseline) * 100;

        return [
            'is_anomaly' => $changePercent >= $increaseThresholdPercent,
            'change_percent' => round($changePercent, 2),
        ];
    }
}
