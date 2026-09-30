<?php

namespace App\Services\Publishing\Publishers;

use App\Exceptions\SocialPublishException;
use App\Models\ActivityLog;
use App\Models\PublishingAttempt;
use App\Models\SocialPost;
use App\Services\Publishing\AbstractSocialPublisher;
use Illuminate\Support\Facades\Http;

class LinkedInPublisher extends AbstractSocialPublisher
{
    public function platformSlug(): string
    {
        return 'linkedin';
    }

    public function validatePostRequirements(SocialPost $post): void
    {
        if (trim($post->body) === '') {
            throw new SocialPublishException('LinkedIn post commentary cannot be empty.', PublishingAttempt::CATEGORY_VALIDATION);
        }
    }

    public function publish(SocialPost $post): array
    {
        $this->validatePostRequirements($post);
        $credential = $this->requireValidCredential($post);
        $authorUrn = $post->socialAccount->platform_account_id ?: 'urn:li:person:me';
        if (! str_starts_with($authorUrn, 'urn:li:')) {
            $authorUrn = "urn:li:person:{$authorUrn}";
        }

        $response = Http::timeout(20)
            ->withToken($credential->access_token)
            ->post('https://api.linkedin.com/v2/ugcPosts', [
                'author' => $authorUrn,
                'lifecycleState' => 'PUBLISHED',
                'specificContent' => [
                    'com.linkedin.ugc.ShareContent' => [
                        'shareCommentary' => [
                            'text' => $post->formattedCaption(),
                        ],
                        'shareMediaCategory' => 'NONE',
                    ],
                ],
                'visibility' => [
                    'com.linkedin.ugc.MemberNetworkVisibility' => 'PUBLIC',
                ],
            ]);

        if (! $response->successful()) {
            throw $this->classifyHttpFailure($response, 'LinkedIn publishing failed');
        }

        $data = $response->json();
        $postId = (string) ($data['id'] ?? 'urn:li:share:'.uniqid());

        return [
            'platform_post_id' => $postId,
            'platform_post_url' => "https://www.linkedin.com/feed/update/{$postId}/",
            'http_status' => $response->status(),
            'response_data' => is_array($data) ? ActivityLog::sanitizeProperties($data) : [],
        ];
    }
}
