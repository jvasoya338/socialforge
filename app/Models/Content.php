<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Content extends Model
{
    use HasFactory;

    public const TYPE_GAME = 'game';

    public const TYPE_ARTICLE = 'article';

    public const TYPE_PRODUCT = 'product';

    public const TYPE_MANUAL = 'manual';

    public const TYPE_VIDEO = 'video';

    public const TYPE_ANNOUNCEMENT = 'announcement';

    public const TYPE_OTHER = 'other';

    public const STATUS_NEW = 'new';

    public const STATUS_READY = 'ready';

    public const STATUS_GENERATED = 'generated';

    public const STATUS_PUBLISHED = 'published';

    public const STATUS_ARCHIVED = 'archived';

    protected $fillable = [
        'brand_id',
        'content_source_id',
        'external_id',
        'type',
        'title',
        'slug',
        'summary',
        'body',
        'source_url',
        'featured_image',
        'published_at',
        'metadata',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'published_at' => 'datetime',
            'metadata' => 'array',
        ];
    }

    public function brand(): BelongsTo
    {
        return $this->belongsTo(Brand::class);
    }

    public function contentSource(): BelongsTo
    {
        return $this->belongsTo(ContentSource::class);
    }

    public function contentMedia(): HasMany
    {
        return $this->hasMany(ContentMedia::class)->orderBy('sort_order');
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AIGeneration::class);
    }
}
