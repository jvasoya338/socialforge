<?php

namespace App\Jobs;

use App\Models\SocialPost;
use App\Services\AnalyticsService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class FetchSocialAnalyticsJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(public ?int $socialPostId = null)
    {
        $this->onQueue('analytics');
    }

    public function handle(AnalyticsService $analyticsService): void
    {
        if ($this->socialPostId) {
            $post = SocialPost::find($this->socialPostId);
            if ($post && $post->status === SocialPost::STATUS_PUBLISHED) {
                $analyticsService->fetchPostAnalytics($post);
            }

            return;
        }

        $analyticsService->syncRecentPublishedPosts();
    }
}
