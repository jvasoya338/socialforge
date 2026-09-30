<?php

namespace App\Services\Publishing\Publishers;

use App\Exceptions\SocialPublishException;
use App\Models\ActivityLog;
use App\Models\PublishingAttempt;
use App\Models\SocialPost;
use App\Services\Publishing\AbstractSocialPublisher;
use Illuminate\Support\Facades\Http;

class PinterestPublisher extends AbstractSocialPublisher
{
    public function platformSlug(): string
    {
        return 'pinterest';
    }

    public function validatePostRequirements(SocialPost $post): void
    {
        $post->loadMissing(['media', 'content']);
        $hasMedia = $post->media->isNotEmpty() || ! empty($post->content?->featured_image);

        if (! $hasMedia) {
            throw new SocialPublishException(
                'Pinterest pins require an image or video media item.',
                failureCategory: PublishingAttempt::CATEGORY_MEDIA,
            );
        }
    }

    public function publish(SocialPost $post): array
    {
        $this->validatePostRequirements($post);
        $credential = $this->requireValidCredential($post);

        $firstMedia = $post->media->first();
        $mediaUrl = $firstMedia?->path ?: $post->content?->featured_image;
        $boardId = $post->socialAccount->metadata['board_id'] ?? $post->socialAccount->platform_account_id ?? 'default_board';

        $response = Http::timeout(20)
            ->withToken($credential->access_token)
            ->post('https://api.pinterest.com/v5/pins', [
                'board_id' => $boardId,
                'title' => $post->title ?: mb_substr($post->body, 0, 90),
                'description' => $post->formattedCaption(),
                'link' => $post->link_url,
                'media_source' => [
                    'source_type' => 'image_url',
                    'url' => $mediaUrl,
                ],
            ]);

        if (! $response->successful()) {
            throw $this->classifyHttpFailure($response, 'Pinterest publishing failed');
        }

        $data = $response->json();
        $pinId = (string) ($data['id'] ?? 'pin_'.uniqid());

        return [
            'platform_post_id' => $pinId,
            'platform_post_url' => "https://www.pinterest.com/pin/{$pinId}/",
            'http_status' => $response->status(),
            'response_data' => is_array($data) ? ActivityLog::sanitizeProperties($data) : [],
        ];
    }
}
