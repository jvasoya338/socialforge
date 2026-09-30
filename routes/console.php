<?php

use App\Models\SystemSetting;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('scheduler:heartbeat', function () {
    SystemSetting::setValue('scheduler_last_heartbeat', now()->toIso8601String(), 'system', 'string');
    $this->info('Scheduler heartbeat recorded.');
})->purpose('Record scheduler heartbeat timestamp');

Schedule::command('social:dispatch-due-posts')->everyMinute()->withoutOverlapping();
Schedule::command('social:refresh-tokens')->hourly()->withoutOverlapping();
Schedule::command('social:sync-analytics')->everySixHours()->withoutOverlapping();
Schedule::command('social:sync-content-sources')->hourly()->withoutOverlapping();
Schedule::command('scheduler:heartbeat')->everyFiveMinutes();
