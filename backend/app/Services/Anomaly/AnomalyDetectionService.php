<?php

namespace App\Services\Anomaly;

use App\Anomaly\AnomalyEngine;
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
     * Dedup key is (client_id, type, metric, detected_date) — see the
     * anomalies_dedup_unique index — so re-running detection on the same day
     * (e.g. a manual trigger after the nightly job already ran) never creates
     * duplicate alerts.
     *
     * @return Anomaly[] newly created anomalies (not ones that already existed today)
     */
    public function run(Client $client): array
    {
        $today = Carbon::today()->toDateString();
        $found = $this->engine->detect($client);
        $created = [];

        foreach ($found as $anomaly) {
            $existing = Anomaly::where('client_id', $client->id)
                ->where('type', $anomaly['type'])
                ->where('metric', $anomaly['metric'])
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
                    'detected_date' => $today,
                ],
                [
                    'uuid' => Str::uuid(),
                    'agency_id' => $client->agency_id,
                    'integration_id' => $anomaly['integration_id'],
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

            // TODO: once the Notifications module ships, dispatch email/WhatsApp/push
            // here for 'critical' severity anomalies (spec: "Send notifications. Email
            // users. WhatsApp users. Push Notifications."). Left as a clear extension
            // point rather than building a partial notification pipeline now.
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
