<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class ContentSource extends Model
{
    use HasFactory;

    public const TYPE_API = 'api';

    public const TYPE_WEBHOOK = 'webhook';

    public const TYPE_RSS = 'rss';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_DATABASE = 'database';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_REVOKED = 'revoked';

    protected $fillable = [
        'brand_id',
        'name',
        'type',
        'base_url',
        'authentication_type',
        'encrypted_credentials',
        'api_key_hash',
        'api_key_prefix',
        'api_key_last_used_at',
        'configuration',
        'status',
        'last_synced_at',
    ];

    protected $hidden = [
        'encrypted_credentials',
        'api_key_hash',
    ];

    protected function casts(): array
    {
        return [
            'encrypted_credentials' => 'encrypted:array',
            'configuration' => 'array',
            'api_key_last_used_at' => 'datetime',
            'last_synced_at' => 'datetime',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function contents(): HasMany
    {
        return $this->hasMany(Content::class);
    }

    /**
     * Generate a new API key, store only its SHA-256 hash and safe prefix, and return the raw key once.
     */
    public function rotateApiKey(): string
    {
        $rawKey = 'sf_live_'.Str::random(40);
        $this->update([
            'api_key_hash' => hash('sha256', $rawKey),
            'api_key_prefix' => substr($rawKey, 0, 14).'...',
            'status' => self::STATUS_ACTIVE,
        ]);

        return $rawKey;
    }

    public function revokeApiKey(): void
    {
        $this->update([
            'api_key_hash' => null,
            'api_key_prefix' => null,
            'status' => self::STATUS_REVOKED,
        ]);
    }

    public static function findActiveByRawApiKey(string $rawKey): ?self
    {
        $hash = hash('sha256', $rawKey);

        return self::query()
            ->where('api_key_hash', $hash)
            ->where('status', self::STATUS_ACTIVE)
            ->first();
    }
}
