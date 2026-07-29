<?php

namespace App\Jobs;

use App\Models\Anomaly;
use App\Services\Notifications\NotificationService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SendAnomalyNotificationJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(protected int $anomalyId) {}

    public function handle(NotificationService $service): void
    {
        $anomaly = Anomaly::find($this->anomalyId);

        if (! $anomaly) {
            return;
        }

        $service->notifyForAnomaly($anomaly);
    }
}
