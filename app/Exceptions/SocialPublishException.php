<?php

namespace App\Exceptions;

use App\Models\PublishingAttempt;
use RuntimeException;

class SocialPublishException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $failureCategory = PublishingAttempt::CATEGORY_UNKNOWN,
        public readonly ?int $httpStatus = null,
        public readonly ?string $errorCode = null,
        public readonly array $sanitizedResponse = [],
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $httpStatus ?? 0, $previous);
    }

    public function isRetryable(): bool
    {
        return in_array($this->failureCategory, [
            PublishingAttempt::CATEGORY_RATE_LIMIT,
            PublishingAttempt::CATEGORY_NETWORK,
            PublishingAttempt::CATEGORY_PLATFORM_ERROR,
        ], true);
    }
}
