<?php

namespace App\Jobs;

use App\Models\ContentSource;
use App\Services\Content\ContentImportService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;

class ImportBrandContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public int $contentSourceId)
    {
        $this->onQueue('imports');
    }

    public function handle(ContentImportService $importService): void
    {
        $source = ContentSource::with('brand')->find($this->contentSourceId);
        if (! $source || ! $source->brand || $source->status !== ContentSource::STATUS_ACTIVE) {
            return;
        }

        if (empty($source->base_url)) {
            return;
        }

        try {
            $response = Http::timeout(15)->get($source->base_url);
            if (! $response->successful()) {
                return;
            }

            $items = $response->json('items', $response->json('data', []));
            if (! is_array($items)) {
                return;
            }

            foreach ($items as $item) {
                if (is_array($item) && ! empty($item['title'])) {
                    $importService->importContent($source->brand, $item, $source);
                }
            }
        } catch (\Throwable) {
            // Silently ignore external feed downtime during background sync
        }
    }
}
