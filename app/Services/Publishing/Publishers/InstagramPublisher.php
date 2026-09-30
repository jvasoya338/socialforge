<?php

namespace App\Services\Publishing\Publishers;

use App\Exceptions\SocialPublishException;
use App\Models\ActivityLog;
use App\Models\PublishingAttempt;
use App\Models\SocialPost;
use App\Services\Publishing\AbstractSocialPublisher;
use Illuminate\Support\Facades\Http;

class InstagramPublisher extends AbstractSocialPublisher
{
    public function platformSlug(): string
    {
        return 'instagram';
    }

    public function validatePostRequirements(SocialPost $post): void
    {
        $post->loadMissing(['media', 'content']);

        $hasMedia = $post->media->isNotEmpty() || ! empty($post->content?->featured_image);
        if (! $hasMedia) {
            throw new SocialPublishException(
                'Instagram requires at least one image or video media item.',
                failureCategory: PublishingAttempt::CATEGORY_MEDIA,
            );
        }
    }

    public function publish(SocialPost $post): array
    {
        $this->validatePostRequirements($post);
        $credential = $this->requireValidCredential($post);
        $igAccountId = $post->socialAccount->platform_account_id ?: 'me';

        $firstMedia = $post->media->first();
        $mediaUrl = $firstMedia?->path ?: $post->content?->featured_image;

        $response = Http::timeout(25)
            ->withToken($credential->access_token)
            ->post("https://graph.facebook.com/v20.0/{$igAccountId}/media_publish", [
                'image_url' => $mediaUrl,
                'caption' => $post->formattedCaption(),
            ]);

        if (! $response->successful()) {
            throw $this->classifyHttpFailure($response, 'Instagram publishing failed');
        }

        $data = $response->json();
        $platformPostId = (string) ($data['id'] ?? 'ig_'.uniqid());

        return [
            'platform_post_id' => $platformPostId,
            'platform_post_url' => "https://www.instagram.com/p/{$platformPostId}/",
            'http_status' => $response->status(),
            'response_data' => is_array($data) ? ActivityLog::sanitizeProperties($data) : [],
        ];
    }
}
