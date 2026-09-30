<?php

namespace App\Console\Commands;

use App\Jobs\ImportBrandContentJob;
use App\Models\ContentSource;
use Illuminate\Console\Command;

class SyncContentSourcesCommand extends Command
{
    protected $signature = 'social:sync-content-sources';

    protected $description = 'Dispatch content import jobs for active RSS and API feed sources';

    public function handle(): int
    {
        $sources = ContentSource::query()
            ->where('status', ContentSource::STATUS_ACTIVE)
            ->whereIn('type', [ContentSource::TYPE_RSS, ContentSource::TYPE_API])
            ->whereNotNull('base_url')
            ->get();

        foreach ($sources as $source) {
            ImportBrandContentJob::dispatch($source->id);
        }

        $this->info("Dispatched import jobs for {$sources->count()} active content source(s).");

        return self::SUCCESS;
    }
}
