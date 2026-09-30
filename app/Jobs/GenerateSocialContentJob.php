<?php

namespace App\Jobs;

use App\Models\AIProvider;
use App\Models\Content;
use App\Services\AI\AIContentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class GenerateSocialContentJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    /**
     * @param  array<int, int>  $platformIds
     */
    public function __construct(
        public int $contentId,
        public array $platformIds,
        public ?int $aiProviderId = null,
    ) {
        $this->onQueue('ai');
    }

    public function handle(AIContentService $aiContentService): void
    {
        $content = Content::with('brand')->find($this->contentId);
        if (! $content || ! $content->brand) {
            return;
        }

        $provider = $this->aiProviderId ? AIProvider::find($this->aiProviderId) : null;

        $aiContentService->generatePostsForContent(
            brand: $content->brand,
            content: $content,
            platformIds: $this->platformIds,
            providerModel: $provider,
        );
    }
}
