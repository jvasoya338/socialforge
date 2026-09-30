<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PublishingAttempt extends Model
{
    use HasFactory;

    public const STATUS_SUCCESS = 'success';

    public const STATUS_FAILED = 'failed';

    public const STATUS_RETRYING = 'retrying';

    public const CATEGORY_AUTHENTICATION = 'authentication';

    public const CATEGORY_PERMISSION = 'permission';

    public const CATEGORY_RATE_LIMIT = 'rate_limit';

    public const CATEGORY_VALIDATION = 'validation';

    public const CATEGORY_MEDIA = 'media';

    public const CATEGORY_NETWORK = 'network';

    public const CATEGORY_PLATFORM_ERROR = 'platform_error';

    public const CATEGORY_UNKNOWN = 'unknown';

    protected $fillable = [
        'social_post_id',
        'social_account_id',
        'attempt_number',
        'requested_at',
        'responded_at',
        'http_status',
        'platform_post_id',
        'platform_post_url',
        'response_data',
        'failure_category',
        'error_code',
        'error_message',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'attempt_number' => 'integer',
            'requested_at' => 'datetime',
            'responded_at' => 'datetime',
            'http_status' => 'integer',
            'response_data' => 'array',
        ];
    }

    public function socialPost(): BelongsTo
    {
        return $this->belongsTo(SocialPost::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function isRetryableCategory(): bool
    {
        return in_array($this->failure_category, [
            self::CATEGORY_RATE_LIMIT,
            self::CATEGORY_NETWORK,
            self::CATEGORY_PLATFORM_ERROR,
        ], true);
    }
}
