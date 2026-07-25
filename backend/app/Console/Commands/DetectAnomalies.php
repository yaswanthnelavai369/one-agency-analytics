<?php

namespace App\Console\Commands;

use App\Jobs\DetectAnomaliesJob;
use App\Models\Client;
use Illuminate\Console\Command;

class DetectAnomalies extends Command
{
    protected $signature = 'anomalies:detect';
    protected $description = 'Dispatches anomaly detection for every active client.';

    public function handle(): int
    {
        $clients = Client::where('status', 'active')->get(['id']);

        foreach ($clients as $client) {
            DetectAnomaliesJob::dispatch($client->id);
        }

        $this->info("Dispatched anomaly detection for {$clients->count()} client(s).");

        return self::SUCCESS;
    }
}
