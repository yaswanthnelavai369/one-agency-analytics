<?php

namespace App\Console\Commands;

use App\Jobs\ComputeHealthScoreJob;
use App\Models\Client;
use Illuminate\Console\Command;

class ComputeHealthScores extends Command
{
    protected $signature = 'health-scores:compute';
    protected $description = 'Dispatches a Health Score computation job for every active client.';

    public function handle(): int
    {
        $clients = Client::where('status', 'active')->get(['id']);

        foreach ($clients as $client) {
            ComputeHealthScoreJob::dispatch($client->id);
        }

        $this->info("Dispatched Health Score computation for {$clients->count()} client(s).");

        return self::SUCCESS;
    }
}
