<?php

use App\Console\Commands\ComputeHealthScores;
use App\Console\Commands\SyncDueIntegrations;
use Illuminate\Support\Facades\Schedule;

Schedule::command(SyncDueIntegrations::class, ['hourly'])->hourly();
Schedule::command(SyncDueIntegrations::class, ['daily'])->dailyAt('03:00');

// Runs after the 03:00 daily sync so scores reflect that day's freshly-synced metrics.
Schedule::command(ComputeHealthScores::class)->dailyAt('04:00');
