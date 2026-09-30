<?php

namespace App\Services\Publishing\Publishers;

use App\Exceptions\SocialPublishException;
use App\Models\ActivityLog;
use App\Models\PublishingAttempt;
use App\Models\SocialPost;
use App\Services\Publishing\AbstractSocialPublisher;
use Illuminate\Support\Facades\Http;

class XPublisher extends AbstractSocialPublisher
{
    public function platformSlug(): string
    {
        return 'x';
    }

    public function validatePostRequirements(SocialPost $post): void
    {
        $caption = $post->formattedCaption();
        if (trim($caption) === '') {
            throw new SocialPublishException('X post text cannot be empty.', PublishingAttempt::CATEGORY_VALIDATION);
        }

        if (mb_strlen($caption) > 280) {
            throw new SocialPublishException('X post exceeds the 280-character limit.', PublishingAttempt::CATEGORY_VALIDATION);
        }
    }

    public function publish(SocialPost $post): array
    {
        $this->validatePostRequirements($post);
        $credential = $this->requireValidCredential($post);

        $response = Http::timeout(20)
            ->withToken($credential->access_token)
            ->post('https://api.x.com/2/tweets', [
                'text' => $post->formattedCaption(),
            ]);

        if (! $response->successful()) {
            throw $this->classifyHttpFailure($response, 'X publishing failed');
        }

        $data = $response->json();
        $tweetId = (string) (data_get($data, 'data.id') ?? 'x_'.uniqid());
        $username = $post->socialAccount->username ?: 'i/web';

        return [
            'platform_post_id' => $tweetId,
            'platform_post_url' => "https://x.com/{$username}/status/{$tweetId}",
            'http_status' => $response->status(),
            'response_data' => is_array($data) ? ActivityLog::sanitizeProperties($data) : [],
        ];
    }
}
