<?php

namespace App\HealthScore;

/**
 * Small, transparent normalization helpers shared by every ScoreCalculator.
 * Deliberately simple (linear clamps against documented benchmarks) rather
 * than a black-box model — every number on the Health Score page should be
 * traceable back to a formula a human can read here.
 */
class ScoreMath
{
    /** Maps $value linearly from [$min, $max] to [0, 100], clamped at both ends. */
    public static function linear(float $value, float $min, float $max): int
    {
        if ($max === $min) {
            return 50;
        }

        $pct = ($value - $min) / ($max - $min) * 100;

        return (int) round(max(0, min(100, $pct)));
    }

    /** Same as linear(), but for metrics where LOWER is better (e.g. bounce rate, CPA). */
    public static function inverseLinear(float $value, float $min, float $max): int
    {
        return 100 - self::linear($value, $min, $max);
    }

    /** Percent change from $previous to $current, mapped to 0–100 around a +/- $swing% band. */
    public static function growthScore(float $current, float $previous, float $swing = 20.0): int
    {
        if ($previous <= 0) {
            return $current > 0 ? 70 : 50; // can't compute a rate from zero; mildly reward any activity
        }

        $changePercent = (($current - $previous) / $previous) * 100;

        return self::linear($changePercent, -$swing, $swing);
    }

    /** Weighted average of [score => weight] pairs, re-normalizing when some scores are null (missing data). */
    public static function weightedAverage(array $scoresWithWeights): ?int
    {
        $available = array_filter($scoresWithWeights, fn ($pair) => $pair['score'] !== null);

        if (empty($available)) {
            return null;
        }

        $totalWeight = array_sum(array_column($available, 'weight'));
        $sum = array_sum(array_map(fn ($p) => $p['score'] * $p['weight'], $available));

        return (int) round($sum / $totalWeight);
    }
}
