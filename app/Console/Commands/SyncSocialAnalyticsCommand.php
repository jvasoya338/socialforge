<?php

namespace App\Console\Commands;

use App\Jobs\FetchSocialAnalyticsJob;
use Illuminate\Console\Command;

class SyncSocialAnalyticsCommand extends Command
{
    protected $signature = 'social:sync-analytics';

    protected $description = 'Dispatch background analytics collection for recently published social posts';

    public function handle(): int
    {
        FetchSocialAnalyticsJob::dispatch();
        $this->info('Dispatched FetchSocialAnalyticsJob.');

        return self::SUCCESS;
    }
}
