<?php

namespace App\Logging;

use App\Models\ActivityLog;
use Illuminate\Log\Logger;
use Monolog\LogRecord;

class SanitizeLogsTap
{
    public function __invoke(Logger $logger): void
    {
        foreach ($logger->getHandlers() as $handler) {
            if (method_exists($handler, 'pushProcessor')) {
                $handler->pushProcessor(function (LogRecord $record): LogRecord {
                    $message = self::sanitizeString($record->message);
                    $context = ActivityLog::sanitizeProperties($record->context);
                    $extra = ActivityLog::sanitizeProperties($record->extra);

                    return $record->with(
                        message: $message,
                        context: $context,
                        extra: $extra,
                    );
                });
            }
        }
    }

    public static function sanitizeString(string $input): string
    {
        $patterns = [
            '/Bearer\s+[A-Za-z0-9\-\._~\+\/]+=*/i' => 'Bearer [REDACTED]',
            '/sk-[A-Za-z0-9\-_]{16,}/' => 'sk-[REDACTED]',
            '/sf_live_[A-Za-z0-9]{16,}/' => 'sf_live_[REDACTED]',
            '/(access_token|refresh_token|client_secret|api_key|password)=([^&\s]+)/i' => '$1=[REDACTED]',
        ];

        return (string) preg_replace(array_keys($patterns), array_values($patterns), $input);
    }
}
