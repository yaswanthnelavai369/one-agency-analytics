<?php

use App\Console\Commands\SyncDueIntegrations;
use Illuminate\Support\Facades\Schedule;

Schedule::command(SyncDueIntegrations::class, ['hourly'])->hourly();
Schedule::command(SyncDueIntegrations::class, ['daily'])->dailyAt('03:00');
