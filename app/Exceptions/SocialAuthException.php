<?php

namespace App\Exceptions;

use RuntimeException;

class SocialAuthException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly string $provider = 'unknown',
        public readonly bool $requiresReconnect = false,
        int $code = 0,
        ?\Throwable $previous = null
    ) {
        parent::__construct($message, $code, $previous);
    }
}
