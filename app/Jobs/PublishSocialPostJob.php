<?php

namespace App\Jobs;

use App\Models\PublishingAttempt;
use App\Models\SocialPost;
use App\Services\Publishing\SocialPublishingService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class PublishSocialPostJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public array $backoff = [30, 120, 300];

    public function __construct(
        public int $socialPostId,
        public bool $ignoreScheduleTime = false,
    ) {
        $this->onQueue('publishing');
    }

    public function handle(SocialPublishingService $publishingService): void
    {
        $post = SocialPost::find($this->socialPostId);

        if (! $post || $post->status === SocialPost::STATUS_CANCELLED) {
            return;
        }

        if ($post->status === SocialPost::STATUS_PUBLISHED && ! empty($post->platform_post_id)) {
            return;
        }

        $attempt = $publishingService->publishPost($post, $this->ignoreScheduleTime);

        if (
            $attempt->status === PublishingAttempt::STATUS_FAILED
            && $attempt->isRetryableCategory()
            && $this->attempts() < $this->tries
        ) {
            $delay = $this->backoff[$this->attempts() - 1] ?? 60;
            $this->release($delay);
        }
    }
}
