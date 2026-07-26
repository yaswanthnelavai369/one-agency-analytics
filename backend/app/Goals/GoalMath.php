<?php

namespace App\Goals;

use Carbon\Carbon;

/**
 * Same "documented formula, not a black box" philosophy as HealthScore\ScoreMath
 * and Anomaly\AnomalyMath. Forecasting here is deliberately simple linear
 * projection — good enough to flag "you're behind pace" without needing a
 * real forecasting model.
 */
class GoalMath
{
    /**
     * @return array{
     *   expected_progress: float, remaining: float, achievement_rate: float,
     *   pace_status: string, projected_completion_date: ?string, days_remaining: ?int
     * }
     */
    public static function forecast(
        float $current,
        float $target,
        \DateTimeInterface $startDate,
        ?\DateTimeInterface $deadline,
    ): array {
        $achievementRate = $target > 0 ? round(($current / $target) * 100, 1) : 0.0;
        $remaining = max(0, $target - $current);

        if (! $deadline) {
            return [
                'expected_progress' => 0.0,
                'remaining' => $remaining,
                'achievement_rate' => $achievementRate,
                'pace_status' => $current >= $target ? 'achieved' : 'no_deadline',
                'projected_completion_date' => null,
                'days_remaining' => null,
            ];
        }

        $start = Carbon::instance($startDate)->startOfDay();
        $end = Carbon::instance($deadline)->startOfDay();
        $today = Carbon::today();

        $totalDays = max(1, $start->diffInDays($end));
        $elapsedDays = max(0, min($totalDays, $start->diffInDays($today)));
        $elapsedFraction = $elapsedDays / $totalDays;

        $expectedProgress = round($target * $elapsedFraction, 2);
        $daysRemaining = $today->lte($end) ? (int) $today->diffInDays($end) : 0;

        $paceStatus = match (true) {
            $current >= $target => 'achieved',
            $today->gt($end) => 'missed',
            $current >= $expectedProgress => 'on_track',
            default => 'behind',
        };

        // Linear projection: at the current daily rate, when would target be hit?
        $dailyRate = $elapsedDays > 0 ? $current / $elapsedDays : 0;
        $projectedCompletionDate = null;
        if ($dailyRate > 0 && $current < $target) {
            $daysToTarget = (int) ceil(($target - $current) / $dailyRate);
            $projectedCompletionDate = $today->copy()->addDays($daysToTarget)->toDateString();
        }

        return [
            'expected_progress' => $expectedProgress,
            'remaining' => $remaining,
            'achievement_rate' => $achievementRate,
            'pace_status' => $paceStatus,
            'projected_completion_date' => $projectedCompletionDate,
            'days_remaining' => $daysRemaining,
        ];
    }
}
