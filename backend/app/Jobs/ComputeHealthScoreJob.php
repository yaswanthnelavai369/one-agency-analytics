<?php

namespace App\Jobs;

use App\Models\Client;
use App\Services\HealthScore\HealthScoreService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ComputeHealthScoreJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 2;

    public function __construct(protected int $clientId) {}

    public function handle(HealthScoreService $service): void
    {
        $client = Client::find($this->clientId);

        if (! $client) {
            return;
        }

        $service->computeAndStore($client);
    }
}
