<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SocialPlatform extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    protected $fillable = [
        'name',
        'slug',
        'icon',
        'status',
        'supports_text',
        'supports_image',
        'supports_video',
        'supports_reels',
        'supports_stories',
        'supports_analytics',
        'supports_scheduling',
        'max_text_length',
        'configuration',
    ];

    protected function casts(): array
    {
        return [
            'supports_text' => 'boolean',
            'supports_image' => 'boolean',
            'supports_video' => 'boolean',
            'supports_reels' => 'boolean',
            'supports_stories' => 'boolean',
            'supports_analytics' => 'boolean',
            'supports_scheduling' => 'boolean',
            'max_text_length' => 'integer',
            'configuration' => 'array',
        ];
    }

    public function socialAccounts(): HasMany
    {
        return $this->hasMany(SocialAccount::class);
    }

    public function socialPosts(): HasMany
    {
        return $this->hasMany(SocialPost::class);
    }

    public function brandVoiceExamples(): HasMany
    {
        return $this->hasMany(BrandVoiceExample::class);
    }

    public function aiGenerations(): HasMany
    {
        return $this->hasMany(AIGeneration::class);
    }

    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }
}
