<?php

namespace App\Services\Publishing;

use App\Exceptions\SocialPublishException;
use App\Models\ActivityLog;
use App\Models\PublishingAttempt;
use App\Models\SocialCredential;
use App\Models\SocialPost;
use App\Services\Publishing\Contracts\SocialPublisherInterface;
use Illuminate\Http\Client\Response;

abstract class AbstractSocialPublisher implements SocialPublisherInterface
{
    protected function requireValidCredential(SocialPost $post): SocialCredential
    {
        $post->loadMissing(['socialAccount.credential']);
        $account = $post->socialAccount;

        if (! $account) {
            throw new SocialPublishException(
                'No social account is assigned to this post.',
                failureCategory: PublishingAttempt::CATEGORY_VALIDATION,
            );
        }

        $credential = $account->credential;
        if (! $credential || empty($credential->access_token)) {
            throw new SocialPublishException(
                "Social account '{$account->account_name}' is not connected.",
                failureCategory: PublishingAttempt::CATEGORY_AUTHENTICATION,
            );
        }

        if ($credential->isExpired()) {
            throw new SocialPublishException(
                "Access token for '{$account->account_name}' has expired.",
                failureCategory: PublishingAttempt::CATEGORY_AUTHENTICATION,
            );
        }

        return $credential;
    }

    protected function classifyHttpFailure(Response $response, string $defaultMessage = 'Platform API error'): SocialPublishException
    {
        $status = $response->status();
        $json = $response->json();
        $sanitized = is_array($json) ? ActivityLog::sanitizeProperties($json) : ['raw' => substr($response->body(), 0, 500)];

        $errorMessage = (string) (
            data_get($json, 'error.message')
            ?? data_get($json, 'detail')
            ?? data_get($json, 'message')
            ?? "{$defaultMessage} (HTTP {$status})"
        );

        $errorCode = (string) (
            data_get($json, 'error.code')
            ?? data_get($json, 'code')
            ?? $status
        );

        $category = match (true) {
            $status === 401 => PublishingAttempt::CATEGORY_AUTHENTICATION,
            $status === 403 => PublishingAttempt::CATEGORY_PERMISSION,
            $status === 429 => PublishingAttempt::CATEGORY_RATE_LIMIT,
            $status === 400 || $status === 422 => PublishingAttempt::CATEGORY_VALIDATION,
            $status >= 500 => PublishingAttempt::CATEGORY_PLATFORM_ERROR,
            default => PublishingAttempt::CATEGORY_UNKNOWN,
        };

        return new SocialPublishException(
            message: $errorMessage,
            failureCategory: $category,
            httpStatus: $status,
            errorCode: $errorCode,
            sanitizedResponse: $sanitized,
        );
    }
}
