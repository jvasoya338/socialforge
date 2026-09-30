<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPost extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';

    public const STATUS_PENDING_APPROVAL = 'pending_approval';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_PROCESSING = 'processing';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public const APPROVAL_PENDING = 'pending';

    public const APPROVAL_APPROVED = 'approved';

    public const APPROVAL_REJECTED = 'rejected';

    public const PUBLISH_MODE_MANUAL = 'manual';

    public const PUBLISH_MODE_SCHEDULED = 'scheduled';

    public const PUBLISH_MODE_IMMEDIATE = 'immediate';

    public const PUBLISH_MODE_AUTO = 'auto';

    protected $fillable = [
        'brand_id',
        'content_id',
        'social_account_id',
        'social_platform_id',
        'ai_generation_id',
        'title',
        'body',
        'hashtags',
        'cta',
        'link_url',
        'status',
        'approval_status',
        'publish_mode',
        'scheduled_at',
        'published_at',
        'platform_post_id',
        'platform_post_url',
        'failure_category',
        'last_error_message',
        'idempotency_key',
        'created_by',
        'approved_by',
        'approved_at',
        'rejected_by',
        'rejected_at',
        'rejection_reason',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'hashtags' => 'array',
            'scheduled_at' => 'datetime',
            'published_at' => 'datetime',
            'approved_at' => 'datetime',
            'rejected_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function content(): BelongsTo
    {
        return $this->belongsTo(Content::class);
    }

    public function socialAccount(): BelongsTo
    {
        return $this->belongsTo(SocialAccount::class);
    }

    public function socialPlatform(): BelongsTo
    {
        return $this->belongsTo(SocialPlatform::class);
    }

    public function aiGeneration(): BelongsTo
    {
        return $this->belongsTo(AIGeneration::class, 'ai_generation_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejecter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function media(): HasMany
    {
        return $this->hasMany(SocialPostMedia::class)->orderBy('sort_order');
    }

    public function publishingAttempts(): HasMany
    {
        return $this->hasMany(PublishingAttempt::class)->orderByDesc('attempt_number');
    }

    public function analytics(): HasMany
    {
        return $this->hasMany(PostAnalytic::class);
    }

    public function formattedCaption(): string
    {
        $parts = [trim($this->body)];

        if (! empty($this->cta)) {
            $parts[] = trim($this->cta);
        }

        if (! empty($this->link_url)) {
            $parts[] = trim($this->link_url);
        }

        if (! empty($this->hashtags) && is_array($this->hashtags)) {
            $tags = array_map(fn ($t) => str_starts_with(trim($t), '#') ? trim($t) : '#'.trim($t), $this->hashtags);
            $parts[] = implode(' ', $tags);
        }

        return implode("\n\n", array_filter($parts));
    }

    public function scheduledInBrandTimezone(): ?string
    {
        if (! $this->scheduled_at) {
            return null;
        }

        $tz = $this->brand?->timezone ?? 'UTC';

        return $this->scheduled_at->copy()->setTimezone($tz)->format('Y-m-d H:i T');
    }

    public function statusBadgeClass(): string
    {
        return match ($this->status) {
            self::STATUS_PUBLISHED => 'badge-success',
            self::STATUS_SCHEDULED, self::STATUS_APPROVED => 'badge-primary',
            self::STATUS_PENDING_APPROVAL => 'badge-warning',
            self::STATUS_PROCESSING => 'badge-info',
            self::STATUS_FAILED => 'badge-danger',
            self::STATUS_CANCELLED => 'badge-dark',
            default => 'badge-secondary',
        };
    }
}
