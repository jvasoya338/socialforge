<?php

namespace App\Services\Publishing\Publishers;

use App\Exceptions\SocialPublishException;
use App\Models\ActivityLog;
use App\Models\ContentMedia;
use App\Models\PublishingAttempt;
use App\Models\SocialPost;
use App\Services\Publishing\AbstractSocialPublisher;
use Illuminate\Support\Facades\Http;

class YouTubePublisher extends AbstractSocialPublisher
{
    public function platformSlug(): string
    {
        return 'youtube';
    }

    public function validatePostRequirements(SocialPost $post): void
    {
        $post->loadMissing('media');
        $hasVideo = $post->media->contains(fn ($m) => $m->type === ContentMedia::TYPE_VIDEO);

        if (! $hasVideo) {
            throw new SocialPublishException(
                'YouTube publishing requires a video media attachment.',
                failureCategory: PublishingAttempt::CATEGORY_MEDIA,
            );
        }
    }

    public function publish(SocialPost $post): array
    {
        $this->validatePostRequirements($post);
        $credential = $this->requireValidCredential($post);

        $response = Http::timeout(30)
            ->withToken($credential->access_token)
            ->post('https://www.googleapis.com/youtube/v3/videos?part=snippet,status', [
                'snippet' => [
                    'title' => $post->title ?: mb_substr($post->body, 0, 95),
                    'description' => $post->formattedCaption(),
                ],
                'status' => [
                    'privacyStatus' => 'public',
                ],
            ]);

        if (! $response->successful()) {
            throw $this->classifyHttpFailure($response, 'YouTube publishing failed');
        }

        $data = $response->json();
        $videoId = (string) ($data['id'] ?? 'yt_'.uniqid());

        return [
            'platform_post_id' => $videoId,
            'platform_post_url' => "https://www.youtube.com/watch?v={$videoId}",
            'http_status' => $response->status(),
            'response_data' => is_array($data) ? ActivityLog::sanitizeProperties($data) : [],
        ];
    }
}
