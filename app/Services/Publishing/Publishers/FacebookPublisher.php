<?php

namespace App\Services\Publishing\Publishers;

use App\Exceptions\SocialPublishException;
use App\Models\ActivityLog;
use App\Models\PublishingAttempt;
use App\Models\SocialPost;
use App\Services\Publishing\AbstractSocialPublisher;
use Illuminate\Support\Facades\Http;

class FacebookPublisher extends AbstractSocialPublisher
{
    public function platformSlug(): string
    {
        return 'facebook';
    }

    public function validatePostRequirements(SocialPost $post): void
    {
        if (trim($post->body) === '') {
            throw new SocialPublishException('Facebook post body cannot be empty.', PublishingAttempt::CATEGORY_VALIDATION);
        }
    }

    public function publish(SocialPost $post): array
    {
        $this->validatePostRequirements($post);
        $credential = $this->requireValidCredential($post);
        $pageId = $post->socialAccount->platform_account_id ?: 'me';

        $payload = [
            'message' => $post->formattedCaption(),
        ];

        if (! empty($post->link_url)) {
            $payload['link'] = $post->link_url;
        }

        $response = Http::timeout(20)
            ->withToken($credential->access_token)
            ->post("https://graph.facebook.com/v20.0/{$pageId}/feed", $payload);

        if (! $response->successful()) {
            throw $this->classifyHttpFailure($response, 'Facebook publishing failed');
        }

        $data = $response->json();
        $platformPostId = (string) ($data['id'] ?? 'fb_'.uniqid());

        return [
            'platform_post_id' => $platformPostId,
            'platform_post_url' => "https://www.facebook.com/{$platformPostId}",
            'http_status' => $response->status(),
            'response_data' => is_array($data) ? ActivityLog::sanitizeProperties($data) : [],
        ];
    }
}
