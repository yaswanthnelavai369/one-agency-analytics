<?php

namespace App\Console\Commands;

use App\Jobs\SyncIntegrationDataJob;
use App\Repositories\Contracts\IntegrationRepositoryInterface;
use Illuminate\Console\Command;

class SyncDueIntegrations extends Command
{
    protected $signature = 'integrations:sync {frequency=daily : daily|hourly}';
    protected $description = 'Dispatches a sync job for every connected integration matching the given frequency.';

    public function handle(IntegrationRepositoryInterface $integrations): int
    {
        $frequency = $this->argument('frequency');
        $due = $integrations->dueForSync($frequency);

        foreach ($due as $integration) {
            SyncIntegrationDataJob::dispatch($integration->id);
        }

        $this->info("Dispatched {$due->count()} sync job(s) for frequency [{$frequency}].");

        return self::SUCCESS;
    }
}
