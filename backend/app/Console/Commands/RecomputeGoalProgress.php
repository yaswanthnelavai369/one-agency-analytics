<?php

namespace App\Console\Commands;

use App\Models\Goal;
use App\Services\Goals\GoalService;
use Illuminate\Console\Command;

class RecomputeGoalProgress extends Command
{
    protected $signature = 'goals:recompute';
    protected $description = 'Recomputes current_value for every active, auto-tracked goal from analytics_metrics.';

    public function handle(GoalService $service): int
    {
        $goals = Goal::where('status', 'active')
            ->where('tracking_mode', '!=', 'manual')
            ->whereNotNull('metric')
            ->get();

        foreach ($goals as $goal) {
            $service->recomputeAutoProgress($goal);
        }

        $this->info("Recomputed progress for {$goals->count()} auto-tracked goal(s).");

        return self::SUCCESS;
    }
}
