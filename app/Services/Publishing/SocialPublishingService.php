<?php

namespace App\Services\Publishing;

use App\Exceptions\SocialPublishException;
use App\Models\ActivityLog;
use App\Models\PublishingAttempt;
use App\Models\SocialAccount;
use App\Models\SocialPost;
use App\Notifications\OperationalAlertNotification;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Cache;

class SocialPublishingService
{
    public function __construct(protected SocialPublisherManager $publisherManager) {}

    public function publishPost(SocialPost $post, bool $ignoreScheduleTime = false): PublishingAttempt
    {
        $post->loadMissing(['brand.brandSetting', 'socialAccount.credential', 'socialPlatform', 'media', 'content']);

        // Idempotency check: if already published, return last successful attempt
        if ($post->status === SocialPost::STATUS_PUBLISHED && ! empty($post->platform_post_id)) {
            $existingAttempt = $post->publishingAttempts()->where('status', PublishingAttempt::STATUS_SUCCESS)->first();
            if ($existingAttempt) {
                return $existingAttempt;
            }
        }

        $lock = Cache::lock("publish_social_post:{$post->id}", 60);
        if (! $lock->get()) {
            throw new SocialPublishException(
                'Post is currently being processed by another worker.',
                failureCategory: PublishingAttempt::CATEGORY_VALIDATION,
            );
        }

        try {
            $requestedAt = now();
            $attemptNumber = ((int) $post->publishingAttempts()->max('attempt_number')) + 1;

            $this->verifyPrePublishSafety($post, $ignoreScheduleTime);

            $post->update(['status' => SocialPost::STATUS_PROCESSING]);

            $publisher = $this->publisherManager->publisher($post->socialPlatform->slug);
            $result = $publisher->publish($post);

            $attempt = PublishingAttempt::create([
                'social_post_id' => $post->id,
                'social_account_id' => $post->social_account_id,
                'attempt_number' => $attemptNumber,
                'requested_at' => $requestedAt,
                'responded_at' => now(),
                'http_status' => $result['http_status'] ?? 200,
                'platform_post_id' => $result['platform_post_id'],
                'platform_post_url' => $result['platform_post_url'],
                'response_data' => $result['response_data'] ?? [],
                'status' => PublishingAttempt::STATUS_SUCCESS,
            ]);

            $post->update([
                'status' => SocialPost::STATUS_PUBLISHED,
                'published_at' => now(),
                'platform_post_id' => $result['platform_post_id'],
                'platform_post_url' => $result['platform_post_url'],
                'failure_category' => null,
                'last_error_message' => null,
            ]);

            ActivityLog::record(
                action: 'post.published',
                description: "Published post #{$post->id} to {$post->socialPlatform->name}",
                subject: $post,
                brandId: $post->brand_id,
                properties: [
                    'platform_post_id' => $result['platform_post_id'],
                    'platform_post_url' => $result['platform_post_url'],
                ],
            );

            return $attempt;
        } catch (ConnectionException $e) {
            return $this->recordFailedAttempt(
                post: $post,
                attemptNumber: $attemptNumber ?? 1,
                requestedAt: $requestedAt ?? now(),
                category: PublishingAttempt::CATEGORY_NETWORK,
                message: 'Network timeout or connection failure: '.$e->getMessage(),
                httpStatus: null,
                errorCode: 'NETWORK_ERROR',
                response: [],
            );
        } catch (SocialPublishException $e) {
            return $this->recordFailedAttempt(
                post: $post,
                attemptNumber: $attemptNumber ?? 1,
                requestedAt: $requestedAt ?? now(),
                category: $e->failureCategory,
                message: $e->getMessage(),
                httpStatus: $e->httpStatus,
                errorCode: $e->errorCode,
                response: $e->sanitizedResponse,
            );
        } catch (\Throwable $e) {
            return $this->recordFailedAttempt(
                post: $post,
                attemptNumber: $attemptNumber ?? 1,
                requestedAt: $requestedAt ?? now(),
                category: PublishingAttempt::CATEGORY_UNKNOWN,
                message: $e->getMessage(),
                httpStatus: null,
                errorCode: 'UNEXPECTED_ERROR',
                response: [],
            );
        } finally {
            $lock->release();
        }
    }

    protected function verifyPrePublishSafety(SocialPost $post, bool $ignoreScheduleTime): void
    {
        if ($post->brand->requiresApproval() && $post->approval_status !== SocialPost::APPROVAL_APPROVED) {
            throw new SocialPublishException(
                'Post requires admin approval before publishing.',
                failureCategory: PublishingAttempt::CATEGORY_VALIDATION,
            );
        }

        if (! $ignoreScheduleTime && $post->scheduled_at && $post->scheduled_at->isFuture()) {
            throw new SocialPublishException(
                'Scheduled publish time has not been reached yet.',
                failureCategory: PublishingAttempt::CATEGORY_VALIDATION,
            );
        }

        if (! $post->socialAccount) {
            throw new SocialPublishException(
                'No social account is linked to this post.',
                failureCategory: PublishingAttempt::CATEGORY_VALIDATION,
            );
        }

        if (! $post->socialAccount->isConnected()) {
            throw new SocialPublishException(
                "Social account '{$post->socialAccount->account_name}' is not connected or has an invalid/expired token.",
                failureCategory: PublishingAttempt::CATEGORY_AUTHENTICATION,
            );
        }
    }

    protected function recordFailedAttempt(
        SocialPost $post,
        int $attemptNumber,
        $requestedAt,
        string $category,
        string $message,
        ?int $httpStatus,
        ?string $errorCode,
        array $response
    ): PublishingAttempt {
        $attempt = PublishingAttempt::create([
            'social_post_id' => $post->id,
            'social_account_id' => $post->social_account_id,
            'attempt_number' => $attemptNumber,
            'requested_at' => $requestedAt,
            'responded_at' => now(),
            'http_status' => $httpStatus,
            'response_data' => $response,
            'failure_category' => $category,
            'error_code' => $errorCode,
            'error_message' => $message,
            'status' => PublishingAttempt::STATUS_FAILED,
        ]);

        $post->update([
            'status' => SocialPost::STATUS_FAILED,
            'failure_category' => $category,
            'last_error_message' => $message,
        ]);

        if ($category === PublishingAttempt::CATEGORY_AUTHENTICATION && $post->socialAccount) {
            $post->socialAccount->update([
                'connection_status' => SocialAccount::CONNECTION_RECONNECT_REQUIRED,
            ]);
        }

        ActivityLog::record(
            action: 'post.publish_failed',
            description: "Failed publishing post #{$post->id} ({$category}): {$message}",
            subject: $post,
            brandId: $post->brand_id,
            properties: ['failure_category' => $category, 'attempt_number' => $attemptNumber],
        );

        if ($attemptNumber >= 2 || $category === PublishingAttempt::CATEGORY_AUTHENTICATION) {
            OperationalAlertNotification::notifyAdmins(
                alertType: 'publishing_failed',
                title: "Publishing Failed: Post #{$post->id} ({$post->brand?->name})",
                message: "Attempt #{$attemptNumber} failed ({$category}): {$message}",
                severity: 'danger',
                actionUrl: route('admin.publishing.failed'),
                deduplicationKey: "publish_failed:{$post->id}:{$category}",
            );
        }

        return $attempt;
    }
}
