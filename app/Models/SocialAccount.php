<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class SocialAccount extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const CONNECTION_NOT_CONNECTED = 'not_connected';

    public const CONNECTION_CONNECTED = 'connected';

    public const CONNECTION_EXPIRING_SOON = 'expiring_soon';

    public const CONNECTION_REFRESH_FAILED = 'refresh_failed';

    public const CONNECTION_EXPIRED = 'expired';

    public const CONNECTION_RECONNECT_REQUIRED = 'reconnect_required';

    public const CONNECTION_DISCONNECTED = 'disconnected';

    protected $fillable = [
        'brand_id',
        'social_platform_id',
        'account_name',
        'username',
        'platform_account_id',
        'avatar',
        'account_type',
        'status',
        'connection_status',
        'connected_at',
        'last_verified_at',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'connected_at' => 'datetime',
            'last_verified_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function socialPlatform(): BelongsTo
    {
        return $this->belongsTo(SocialPlatform::class);
    }

    public function credential(): HasOne
    {
        return $this->hasOne(SocialCredential::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function publishingAttempts(): HasMany
    {
        return $this->hasMany(PublishingAttempt::class);
    }

    public function accountAnalytics(): HasMany
    {
        return $this->hasMany(AccountAnalytic::class);
    }

    public function isConnected(): bool
    {
        return in_array($this->connection_status, [
            self::CONNECTION_CONNECTED,
            self::CONNECTION_EXPIRING_SOON,
        ], true) && $this->credential && $this->credential->hasValidAccessToken();
    }

    public function healthLabel(): string
    {
        return match ($this->connection_status) {
            self::CONNECTION_CONNECTED => 'Healthy',
            self::CONNECTION_EXPIRING_SOON => 'Expiring Soon',
            self::CONNECTION_REFRESH_FAILED => 'Refresh Failed',
            self::CONNECTION_EXPIRED => 'Expired',
            self::CONNECTION_RECONNECT_REQUIRED => 'Reconnect Required',
            self::CONNECTION_DISCONNECTED => 'Disconnected',
            default => 'Not Connected',
        };
    }

    public function healthBadgeClass(): string
    {
        return match ($this->connection_status) {
            self::CONNECTION_CONNECTED => 'badge-success',
            self::CONNECTION_EXPIRING_SOON => 'badge-warning',
            self::CONNECTION_REFRESH_FAILED, self::CONNECTION_EXPIRED, self::CONNECTION_RECONNECT_REQUIRED => 'badge-danger',
            default => 'badge-secondary',
        };
    }
}
