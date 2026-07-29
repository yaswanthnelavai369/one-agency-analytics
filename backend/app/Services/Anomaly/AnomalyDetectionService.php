<?php

namespace App\Services\Anomaly;

use App\Anomaly\AnomalyEngine;
use App\Jobs\SendAnomalyNotificationJob;
use App\Models\Anomaly;
use App\Models\Client;
use App\Models\User;
use App\Repositories\Contracts\AnomalyRepositoryInterface;
use Carbon\Carbon;
use Illuminate\Support\Str;

class AnomalyDetectionService
{
    public function __construct(
        protected AnomalyEngine $engine,
        protected AnomalyRepositoryInterface $repository,
    ) {}

    /**
     * Runs every detector for the client and persists any new findings.
     * Dedup key is (client_id, type, metric, goal_id, detected_date) — see the
     * anomalies_dedup_unique index — so re-running detection on the same day
     * (e.g. a manual trigger after the nightly job already ran) never creates
     * duplicate alerts. goal_id is part of the key so multiple goals going
     * at-risk on the same day don't collide (they'd otherwise share
     * type='goal_at_risk' + metric=null).
     *
     * @return Anomaly[] newly created anomalies (not ones that already existed today)
     */
    public function run(Client $client): array
    {
        $today = Carbon::today()->toDateString();
        $found = $this->engine->detect($client);
        $created = [];

        foreach ($found as $anomaly) {
            $goalId = $anomaly['goal_id'] ?? null;

            $existing = Anomaly::where('client_id', $client->id)
                ->where('type', $anomaly['type'])
                ->where('metric', $anomaly['metric'])
                ->where('goal_id', $goalId)
                ->where('detected_date', $today)
                ->exists();

            if ($existing) {
                continue;
            }

            $record = $this->repository->firstOrCreate(
                [
                    'client_id' => $client->id,
                    'type' => $anomaly['type'],
                    'metric' => $anomaly['metric'],
                    'goal_id' => $goalId,
                    'detected_date' => $today,
                ],
                [
                    'uuid' => Str::uuid(),
                    'agency_id' => $client->agency_id,
                    'integration_id' => $anomaly['integration_id'] ?? null,
                    'severity' => $anomaly['severity'],
                    'current_value' => $anomaly['current_value'],
                    'baseline_value' => $anomaly['baseline_value'],
                    'change_percent' => $anomaly['change_percent'],
                    'message' => $anomaly['message'],
                    'possible_causes' => $anomaly['possible_causes'],
                    'recommended_fixes' => $anomaly['recommended_fixes'],
                    'status' => 'open',
                ]
            );

            $created[] = $record;

            // Critical severity only — warning/info anomalies still show up in the
            // Alerts feed, but don't interrupt anyone. Keeps this from becoming the
            // kind of notification system people learn to ignore.
            if ($record->severity === 'critical') {
                SendAnomalyNotificationJob::dispatch($record->id);
            }
        }

        return $created;
    }

    public function list(Client $client, ?string $status = null)
    {
        return $this->repository->forClient($client->id, $status);
    }

    public function acknowledge(Anomaly $anomaly, User $user): Anomaly
    {
        $anomaly->forceFill([
            'status' => 'acknowledged',
            'acknowledged_by' => $user->id,
            'acknowledged_at' => now(),
        ])->save();

        return $anomaly;
    }

    public function resolve(Anomaly $anomaly): Anomaly
    {
        $anomaly->forceFill([
            'status' => 'resolved',
            'resolved_at' => now(),
        ])->save();

        return $anomaly;
    }
}
