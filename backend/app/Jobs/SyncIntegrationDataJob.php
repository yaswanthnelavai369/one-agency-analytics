<?php

namespace App\Jobs;

use App\Integrations\IntegrationManager;
use App\Models\Integration;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class SyncIntegrationDataJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;
    public int $backoff = 60;

    public function __construct(protected int $integrationId, protected int $lookbackDays = 30) {}

    public function handle(IntegrationManager $manager): void
    {
        $integration = Integration::with('client')->find($this->integrationId);

        if (! $integration || ! $integration->isConnected()) {
            return;
        }

        $provider = $manager->resolve($integration->provider);
        $provider->syncMetrics($integration, $integration->client, now()->subDays($this->lookbackDays), now());
    }
}
