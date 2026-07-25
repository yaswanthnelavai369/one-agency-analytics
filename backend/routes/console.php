<?php

use App\Console\Commands\ComputeHealthScores;
use App\Console\Commands\DetectAnomalies;
use App\Console\Commands\SyncDueIntegrations;
use Illuminate\Support\Facades\Schedule;

Schedule::command(SyncDueIntegrations::class, ['hourly'])->hourly();
Schedule::command(SyncDueIntegrations::class, ['daily'])->dailyAt('03:00');

// Runs after the 03:00 daily sync so scores reflect that day's freshly-synced metrics.
Schedule::command(ComputeHealthScores::class)->dailyAt('04:00');

// Runs after health scores so anomaly messages could eventually reference the
// day's score movement too; currently independent but ordered for that future use.
Schedule::command(DetectAnomalies::class)->dailyAt('04:30');
