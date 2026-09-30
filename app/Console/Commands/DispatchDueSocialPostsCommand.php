<?php

namespace App\Console\Commands;

use App\Jobs\PublishSocialPostJob;
use App\Models\SocialPost;
use Illuminate\Console\Command;

class DispatchDueSocialPostsCommand extends Command
{
    protected $signature = 'social:dispatch-due-posts';

    protected $description = 'Dispatch queued publishing jobs for scheduled social posts that are due';

    public function handle(): int
    {
        $duePosts = SocialPost::query()
            ->where('status', SocialPost::STATUS_SCHEDULED)
            ->whereNotNull('scheduled_at')
            ->where('scheduled_at', '<=', now())
            ->get();

        $count = 0;
        foreach ($duePosts as $post) {
            PublishSocialPostJob::dispatch($post->id);
            $count++;
        }

        $this->info("Dispatched {$count} due social post(s).");

        return self::SUCCESS;
    }
}
