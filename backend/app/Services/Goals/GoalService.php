<?php

namespace App\Services\Goals;

use App\Goals\GoalMath;
use App\Models\Client;
use App\Models\Goal;
use App\Models\GoalProgress;
use App\Models\User;
use App\Repositories\Contracts\AnalyticsMetricRepositoryInterface;
use App\Repositories\Contracts\GoalRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;

class GoalService
{
    public function __construct(
        protected GoalRepositoryInterface $goals,
        protected AnalyticsMetricRepositoryInterface $metrics,
    ) {}

    public function create(Client $client, ?User $creator, array $data): Goal
    {
        $goal = $this->goals->create([
            'uuid' => Str::uuid(),
            'agency_id' => $client->agency_id,
            'client_id' => $client->id,
            'created_by' => $creator?->id,
            'name' => $data['name'],
            'metric' => $data['metric'] ?? null,
            'tracking_mode' => $data['tracking_mode'] ?? 'manual',
            'target_value' => $data['target_value'],
            'current_value' => 0,
            'format' => $data['format'] ?? 'number',
            'start_date' => $data['start_date'] ?? Carbon::today()->toDateString(),
            'deadline' => $data['deadline'] ?? null,
            'status' => 'active',
        ]);

        if ($goal->isAutoTracked()) {
            $this->recomputeAutoProgress($goal);
        }

        return $goal;
    }

    public function update(Goal $goal, array $data): Goal
    {
        $this->goals->update($goal, array_intersect_key($data, array_flip(['name', 'target_value', 'deadline', 'format'])));

        return $goal->fresh();
    }

    public function archive(Goal $goal): Goal
    {
        $this->goals->update($goal, ['status' => 'archived']);

        return $goal->fresh();
    }

    public function delete(Goal $goal): void
    {
        $this->goals->delete($goal);
    }

    /**
     * For metric-linked goals: recomputes current_value from analytics_metrics
     * and writes today's snapshot. Idempotent — safe to call multiple times a
     * day (e.g. once from the nightly job, once from a manual "refresh").
     */
    public function recomputeAutoProgress(Goal $goal): Goal
    {
        if (! $goal->isAutoTracked()) {
            return $goal;
        }

        $value = $goal->tracking_mode === 'cumulative'
            ? $this->metrics->sumBetween($goal->client_id, $goal->metric, $goal->start_date->toDateString(), Carbon::today()->toDateString())
            : ($this->metrics->latestValue($goal->client_id, $goal->metric) ?? 0);

        $this->applyProgress($goal, $value, source: 'auto');

        return $goal->fresh();
    }

    /** For manual goals: the creator (or the client) sets or increments progress by hand. */
    public function addManualProgress(Goal $goal, float $value, string $mode, ?User $user = null): Goal
    {
        $newValue = $mode === 'increment' ? $goal->current_value + $value : $value;
        $this->applyProgress($goal, $newValue, source: 'manual', loggedBy: $user);

        return $goal->fresh();
    }

    public function forecast(Goal $goal): array
    {
        return GoalMath::forecast(
            $goal->current_value,
            $goal->target_value,
            $goal->start_date,
            $goal->deadline,
        );
    }

    public function listForClient(Client $client, ?string $status = null)
    {
        return $this->goals->forClient($client->id, $status);
    }

    protected function applyProgress(Goal $goal, float $value, string $source, ?User $loggedBy = null): void
    {
        $status = $goal->status;
        $completedAt = $goal->completed_at;

        if ($value >= $goal->target_value && $status === 'active') {
            $status = 'completed';
            $completedAt = now();
        } elseif ($goal->deadline && Carbon::today()->gt($goal->deadline) && $value < $goal->target_value && $status === 'active') {
            $status = 'missed';
        }

        $this->goals->update($goal, [
            'current_value' => $value,
            'status' => $status,
            'completed_at' => $completedAt,
        ]);

        GoalProgress::updateOrCreate(
            ['goal_id' => $goal->id, 'date' => Carbon::today()->toDateString()],
            ['value' => $value, 'source' => $source, 'logged_by' => $loggedBy?->id]
        );
    }
}
